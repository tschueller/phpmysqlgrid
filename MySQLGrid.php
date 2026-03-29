<?php
// +----------------------------------------------------------------------+
// | phpMySQLGrid version 0.6                                             |
// |                                                                      |
// | A flexible mysql data grid for PHP.                                  |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 Klaus Reimer, 2026 Thorsten Schüller             |
// +----------------------------------------------------------------------+
// | Released under the MIT License.                                      |
// | See LICENSE file in the project root for full license text.          |
// +----------------------------------------------------------------------+
// | Authors:                                                             |
// | - Klaus Reimer <k@ailis.de>                                          |
// | - Thorsten Schüller <thorsten@schueller.me>                          |
// +----------------------------------------------------------------------+

define("PHPMYSQLGRID_TEXT", 0);
define("PHPMYSQLGRID_BOOLEAN", 1);
define("PHPMYSQLGRID_LOOKUP", 2);
define("PHPMYSQLGRID_PASSWORD", 3);
define("PHPMYSQLGRID_SELECTION", 4);
define("PHPMYSQLGRID_MULTILINETEXT", 5);
define("PHPMYSQLGRID_FILE", 6);

define("PHPMYSQLGRID_VIEWMODE", 0);
define("PHPMYSQLGRID_ADDMODE", 1);
define("PHPMYSQLGRID_EDITMODE", 2);
define("PHPMYSQLGRID_DELETEMODE", 3);

define("PHPMYSQLGRID_TEXTBUTTON", 0);
define("PHPMYSQLGRID_IMAGEBUTTON", 1);

define("PHPMYSQLGRID_PWDUMMY", "********");

#[AllowDynamicProperties]
class MySQLGrid {
    function __construct() {
        $this->hostname = "localhost";
        $this->port = 3128;
        $this->username = "root";
        $this->password = "";
        $this->database = "mysql";
        $this->table = "user";
        $this->primary = array("host", "user");
        $this->style = "phpmysqlgrid";
        $this->cssClass = "";
        $this->columns = array();
        $this->actions = array();
        $this->limit = 10;
        $this->name = "phpmysqlgrid";
        $this->mode = PHPMYSQLGRID_VIEWMODE;
        $this->imagedir = "images";
        $this->can_add = true;
        $this->can_delete = true;
        $this->can_edit = true;
        $this->can_sort = true;
        $this->can_navigate = true;
        $this->can_filter = true;
        $this->filter = "";
        $this->filter_size = 8;
        $this->default_sort_column = 0;
        $this->default_sort_direction = 0;
        $this->add_values = array();
        $this->delete_before = false;
        $this->delete_after = false;
        $this->add_before = false;
        $this->add_after = false;
        $this->edit_before = false;
        $this->edit_after = false;
        $this->lookups = array();
        $this->charset = "UTF-8";
        $this->db_utf8 = false;
        $this->db = null;
        $this->db_connection = null;
        $this->db_driver = "mysqli";
        $this->db_is_injected = false;
        $this->internationalize();
        $this->initSvgIcons();
    }

    function MySQLGrid(): void {
        self::__construct();
    }

    public function setDatabaseConnection(mixed $connection, string $driver = "mysqli"): void {
        $allowedDrivers = array("mysqli", "pdo", "pdo_mysql", "pdo_sqlite");
        if (!in_array($driver, $allowedDrivers, true)) {
            trigger_error("Unsupported database driver: " . $driver, E_USER_ERROR);
        }

        $this->db_connection = $connection;
        $this->db = $connection;
        $this->db_driver = $driver;
        $this->db_is_injected = true;
    }

    private function usingPdoConnection(): bool {
        return $this->db_is_injected
            && str_starts_with((string)$this->db_driver, "pdo")
            && ($this->db instanceof \PDO);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function executePdoStatement(string $sql, array $params = array()): \PDOStatement {
        if (!($this->db instanceof \PDO)) {
            trigger_error("PDO connection expected for injected PDO driver", E_USER_ERROR);
        }

        $statement = $this->db->prepare($sql);
        if ($statement === false) {
            trigger_error("Failed to prepare PDO statement", E_USER_ERROR);
        }

        if (!$statement->execute($params)) {
            $errorInfo = $statement->errorInfo();
            $message = is_array($errorInfo) && isset($errorInfo[2]) ? (string)$errorInfo[2] : "PDO execute failed";
            trigger_error($message, E_USER_ERROR);
        }

        return $statement;
    }

    /**
     * @param array<int, mixed> $params
     */
    private function executeMysqliPrepared(string $sql, array $params = array()): \mysqli_stmt {
        if (!($this->db instanceof \mysqli)) {
            trigger_error("mysqli connection expected", E_USER_ERROR);
        }

        $statement = mysqli_prepare($this->db, $sql);
        if ($statement === false) {
            trigger_error(mysqli_error($this->db), E_USER_ERROR);
        }

        if ($params !== array()) {
            $types = str_repeat("s", count($params));
            $bindParams = array_merge(array($types), $params);
            $references = array();
            foreach ($bindParams as $index => $value) {
                $references[$index] = &$bindParams[$index];
            }

            if (!call_user_func_array(array($statement, "bind_param"), $references)) {
                trigger_error(mysqli_stmt_error($statement), E_USER_ERROR);
            }
        }

        if (!mysqli_stmt_execute($statement)) {
            trigger_error(mysqli_stmt_error($statement), E_USER_ERROR);
        }

        return $statement;
    }

    private function escapeStringForLike(string $value): string {
        if ($this->usingPdoConnection() && ($this->db instanceof \PDO)) {
            $quoted = $this->db->quote($value);

            if (strlen($quoted) >= 2 && $quoted[0] === "'" && $quoted[strlen($quoted) - 1] === "'") {
                return substr($quoted, 1, -1);
            }

            return $quoted;
        }

        return mysqli_escape_string($this->db, $value);
    }

    private function assertSafeRawSqlFragment(string $fragment, string $context): void {
        if ($fragment === "") {
            return;
        }

        $dangerousTokens = array(";", "--", "/*", "*/", "\0");
        foreach ($dangerousTokens as $token) {
            if (str_contains($fragment, $token)) {
                trigger_error("Unsafe SQL fragment detected in " . $context, E_USER_ERROR);
            }
        }
    }

    /**
     * @return array<int, mixed>|false
     */
    private function fetchResultRow(): array|false {
        if ($this->result instanceof \PDOStatement) {
            $row = $this->result->fetch(\PDO::FETCH_NUM);
            return is_array($row) ? $row : false;
        }

        $row = mysqli_fetch_row($this->result);
        return is_array($row) ? $row : false;
    }

    private function prepareDataWithPdo(): void {
        if (!($this->db instanceof \PDO)) {
            trigger_error("PDO connection expected for injected PDO driver", E_USER_ERROR);
        }

        if (is_array($this->primary)) {
            $fields = array();
            foreach($this->primary as $primary)
                $fields[] = $this->table . "." . $primary;
        } else {
            $fields = array($this->table . "." . $this->primary);
        }

        $joins = array();
        $counter = 0;
        for ($i = 0; $i < count($this->lookups); $i++) {
            $joins[] = sprintf("INNER JOIN %s AS l%d ON %s.%s=%s.%s",
                $this->lookups[$i]["lookup_table"],
                $counter, "l$counter",
                $this->lookups[$i]["lookup_primary"],
                $this->table,
                $this->primary
            );
            $counter++;
        }

        $counter = 0;
        $filterClauses = array();
        $filterParams = array();
        if ((string)$this->filter !== "") {
            $this->assertSafeRawSqlFragment((string)$this->filter, "filter");
            $filterClauses[] = "(" . (string)$this->filter . ")";
        }
        for ($i = 0; $i < count($this->columns); $i++) {
            switch($this->columns[$i]["type"]) {
                case PHPMYSQLGRID_LOOKUP:
                    $fields[] = "t$counter" . "."
                        . $this->columns[$i]["lookup_field"];
                    $joins[] = sprintf("INNER JOIN %s AS t%d ON %s.%s=%s.%s",
                        $this->columns[$i]["lookup_table"],
                        $counter, "t$counter",
                        $this->columns[$i]["lookup_primary"],
                        $this->table,
                        $this->columns[$i]["field"]
                    );
                    if ($this->columns[$i]['active_filter']) {
                        $placeholder = ":flt_" . $i;
                        $filterClauses[] = sprintf("t$counter.%s LIKE %s",
                            $this->columns[$i]['lookup_field'],
                            $placeholder
                        );
                        $filterParams[$placeholder] = "%" . (string)$this->columns[$i]['active_filter'] . "%";
                    }
                    $counter++;
                    break;
                case PHPMYSQLGRID_FILE:
                    if (isset($this->columns[$i]["convert_output"]))
                        $fields[] = $this->table . '.' . $this->columns[$i]["field"];
                    else
                        $fields[] = 'LENGTH(' . $this->table . '.' .
                             $this->columns[$i]["field"] . ')';
                    break;
                default:
                    $fields[] = $this->table . '.' . $this->columns[$i]["field"];
                    if ($this->columns[$i]['active_filter']) {
                        $placeholder = ":flt_" . $i;
                        $filterClauses[] = sprintf("%s.%s LIKE %s",
                            $this->table,
                            $this->columns[$i]['field'],
                            $placeholder
                        );
                        $filterParams[$placeholder] = "%" . (string)$this->columns[$i]['active_filter'] . "%";
                    }
            }
        }

        $whereClause = $filterClauses ? "WHERE " . join(" AND ", $filterClauses) : "";

        $query = sprintf(
            "SELECT COUNT(*) FROM %s %s %s",
            $this->table, join(" ", $joins),
            $whereClause
        );
        $result = $this->executePdoStatement($query, $filterParams);
        $count = $result->fetchColumn();
        $this->rows = $count !== false ? $count : 0;

        if ($this->rows <= (($this->page - 1) * $this->limit)) {
            $this->page = (int)ceil($this->rows / $this->limit);
            $_SESSION["phpMySQLGrid_" . $this->name]["page"] = $this->page;
        }

        $offset = ($this->page > 0 ? (($this->page - 1) * $this->limit) : 0);
        $query = sprintf(
            "SELECT %s FROM %s %s %s ORDER BY %s %s LIMIT %d OFFSET %d",
            join(",", $fields), $this->table, join(" ", $joins),
            $whereClause,
            $fields[$this->sort + $this->countPrimaries()],
            $this->dir ? "DESC" : "ASC",
            $this->limit,
            $offset
        );
        $this->result = $this->executePdoStatement($query, $filterParams);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function queryNumericRows(string $query): array {
        if ($this->usingPdoConnection()) {
            $statement = $this->executePdoStatement($query);
            $rows = $statement->fetchAll(\PDO::FETCH_NUM);
            return $rows;
        }

        if (!($result = mysqli_query($this->db, $query))) {
            trigger_error(mysqli_error($this->db), E_USER_ERROR);
        }
        if ($result === true) {
            trigger_error("Unexpected mysqli result type", E_USER_ERROR);
        }

        $rows = array();
        while ($row = mysqli_fetch_row($result)) {
            if (is_array($row)) {
                $rows[] = $row;
            }
        }
        mysqli_free_result($result);

        return $rows;
    }

    private function internationalize(): void {
        $this->txtPrevious = "Previous Page";
        $this->txtNext = "Next Page";
        $this->txtDelete = "Delete Entry";
        $this->txtAdd = "Add Entry";
        $this->txtEdit = "Edit Entry";
        $this->txtConfirm = "Confirm Changes";
        $this->txtCancel = "Cancel Changes";
        $this->txtYes = "Yes";
        $this->txtNo = "No";
        $this->txtFileTrue = "File present";
        $this->txtFileFalse = "No file present";
        $this->txtFile = "File";
        $this->txtURL = "URL";
        $this->txtPaginationLabel = "Pagination";

        // Accessible labels for sort controls
        // TODO we need column-specific labels like "Sort Email ascending" instead of just "Sort ascending"
        $this->txtSortAsc = "Sort ascending";
        $this->txtSortDesc = "Sort descending";
    }

    private function initSvgIcons(): void {
        // Bootstrap Icons — Copyright (c) 2019-2024 The Bootstrap Authors — MIT License
        // https://github.com/twbs/icons
        // Override any of these public properties to use your own SVGs.
        $this->svgIconEdit = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708zm.646 6.061L9.793 2.5 3.293 9H3.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.207zm-7.468 7.468A.5.5 0 0 1 6 13.5V13h-.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.175-.032l-.179.178a.5.5 0 0 0-.11.168l-2 5a.5.5 0 0 0 .65.65l5-2a.5.5 0 0 0 .168-.11z"/></svg>';
        $this->svgIconDelete = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M4 8a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7A.5.5 0 0 1 4 8"/></svg>';
        $this->svgIconConfirm = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/></svg>';
        $this->svgIconCancel = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>';
        $this->svgIconAdd = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/></svg>';
        $this->svgSortAscActive = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z"/></svg>';
        $this->svgSortAscInactive = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M3.204 11h9.592L8 5.519zm-.753-.659 4.796-5.48a1 1 0 0 1 1.506 0l4.796 5.48c.566.647.106 1.659-.753 1.659H3.204a1 1 0 0 1-.753-1.659"/></svg>';
        $this->svgSortDescActive = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>';
        $this->svgSortDescInactive = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M3.204 5h9.592L8 10.481zm-.753.659 4.796 5.48a1 1 0 0 0 1.506 0l4.796-5.48c.566-.647.106-1.659-.753-1.659H3.204a1 1 0 0 0-.753 1.659"/></svg>';
        $this->svgNavPrev = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/></svg>';
        $this->svgNavNext = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/></svg>';
        $this->svgBoolTrue = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>';
        $this->svgBoolFalse = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" width="1em" height="1em"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 1 0-.708-.708L8 7.293z"/></svg>';
    }

    private function renderIcon(string $content, string $modifier = ""): string {
        $class = $this->style . "-icon";
        if ($modifier !== "") {
            $class .= " " . $this->style . "-icon--" . $modifier;
        }

        return '<span class="' . $class . '" aria-hidden="true">' . $content . '</span>';
    }

    function connect(): void {
        if ($this->db_is_injected) {
            $this->db = $this->db_connection;
            return;
        }

        $this->db = mysqli_connect($this->hostname, $this->username, $this->password);
        if (!$this->db) die();
        if (!mysqli_select_db($this->db, $this->database))
            trigger_error(mysqli_error($this->db), E_USER_ERROR);

        // Switch to utf-8
        if ($this->db_utf8)
            if(!mysqli_query($this->db, "set names 'utf8'"))
                trigger_error(mysqli_error($this->db), E_USER_ERROR);
    }

    function disconnect(): void {
        if ($this->db_is_injected) {
            return;
        }

        mysqli_close($this->db);
    }

    function useAllColumns(): void {
        if ($this->usingPdoConnection()) {
            $this->columns = array();

            if ($this->db_driver === "pdo_sqlite") {
                $statement = $this->executePdoStatement("PRAGMA table_info(" . $this->table . ")");
            } else {
                $statement = $this->executePdoStatement("SHOW COLUMNS FROM " . $this->table);
            }

            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if (!is_array($rows)) {
                return;
            }

            foreach ($rows as $row) {
                if (isset($row["name"])) {
                    $this->columns[] = array("field" => (string)$row["name"]);
                } else if (isset($row["Field"])) {
                    $this->columns[] = array("field" => (string)$row["Field"]);
                }
            }

            return;
        }

        $this->columns = array();
        if (!($fields = mysqli_query($this->db, "SHOW COLUMNS FROM $this->database.$this->table")))
            trigger_error(mysqli_error($this->db), E_USER_ERROR);
        if ($fields === true)
            trigger_error("Unexpected mysqli result type", E_USER_ERROR);
        if (mysqli_num_rows($fields) > 0) {
            while ($row = mysqli_fetch_assoc($fields)) {
                $this->columns[] = array(
                    "field" => $row['Field']
                );
            }
        }
    }

    function countPrimaries(): int {
        if (is_array($this->primary))
            return count($this->primary);
        else
            return 1;
    }

    function prepareData(): void {
        if ($this->usingPdoConnection()) {
            $this->prepareDataWithPdo();
            return;
        }

        // Create query parameters
        if (is_array($this->primary)) {
            $fields = array();
            foreach($this->primary as $primary)
                $fields[] = $this->table . "." . $primary;
        } else {
            $fields = array($this->table . "." . $this->primary);
        }
        $joins = array();
        $counter = 0;
        for ($i = 0; $i < count($this->lookups); $i++) {
            $joins[] = sprintf("INNER JOIN %s AS l%d ON %s.%s=%s.%s",
                $this->lookups[$i]["lookup_table"],
                $counter, "l$counter",
                $this->lookups[$i]["lookup_primary"],
                $this->table,
                $this->primary
            );
            $counter++;
        }
        $counter = 0;
        $filter = (string)$this->filter;
        $this->assertSafeRawSqlFragment($filter, "filter");
        for ($i = 0; $i < count($this->columns); $i++) {
            switch($this->columns[$i]["type"]) {
                case PHPMYSQLGRID_LOOKUP:
                    $fields[] = "t$counter" . "."
                        . $this->columns[$i]["lookup_field"];
                    $joins[] = sprintf("INNER JOIN %s AS t%d ON %s.%s=%s.%s",
                        $this->columns[$i]["lookup_table"],
                        $counter, "t$counter",
                        $this->columns[$i]["lookup_primary"],
                        $this->table,
                        $this->columns[$i]["field"]
                    );
                    if ($this->columns[$i]['active_filter']) {
                        if ($filter) $filter .= ' AND ';
                        $filter .= sprintf("t$counter.%s LIKE '%%%s%%'",
                            $this->columns[$i]['lookup_field'],
                            $this->escapeStringForLike((string)$this->columns[$i]['active_filter'])
                        );
                    }
                    $counter++;
                    break;
                case PHPMYSQLGRID_FILE:
                    if (isset($this->columns[$i]["convert_output"]))
                        $fields[] = $this->table . '.' . $this->columns[$i]["field"];
                    else
                        $fields[] = 'LENGTH(' . $this->table . '.' .
                             $this->columns[$i]["field"] . ')';
                    break;
                default:
                    $fields[] = $this->table . '.' . $this->columns[$i]["field"];
                    if ($this->columns[$i]['active_filter']) {
                        if ($filter) $filter .= ' AND ';
                        $filter .= sprintf("%s.%s LIKE '%%%s%%'",
                            $this->table,
                            $this->columns[$i]['field'],
                            $this->escapeStringForLike((string)$this->columns[$i]['active_filter'])
                        );
                    }
            }
        }

        // Retrieve maximum number of rows
        $query = sprintf(
            "SELECT COUNT(*) FROM %s %s %s",
            $this->table, join(" ", $joins),
            $filter ? "WHERE " . $filter : ""
        );
        $result = mysqli_query($this->db, $query);
        if (!$result)
            trigger_error(mysqli_error($this->db), E_USER_ERROR);
        if ($result === true)
            trigger_error("Unexpected mysqli result type", E_USER_ERROR);
        $data = mysqli_fetch_row($result);
        if (!is_array($data) || !array_key_exists(0, $data)) {
            mysqli_free_result($result);
            trigger_error("Failed to fetch row count", E_USER_ERROR);
        }

        mysqli_free_result($result);
        $this->rows = $data[0];

        // Check if result is visible. If not jump to page 1
        if ($this->rows <= (($this->page - 1) * $this->limit)) {
            $this->page = ceil($this->rows / $this->limit);
            $_SESSION["phpMySQLGrid_" . $this->name]["page"] = $this->page;
        }

        // Open the query
        $query = sprintf(
            "SELECT %s FROM %s %s %s ORDER BY %s %s LIMIT %d,%d",
            join(",", $fields), $this->table, join(" ", $joins),
            $filter ? "WHERE " . $filter : "",
            $fields[$this->sort + $this->countPrimaries()],
            $this->dir ? "DESC" : "ASC",
            ($this->page > 0 ? (($this->page - 1) * $this->limit) : 0), $this->limit
        );
        $this->result = mysqli_query( $this->db, $query);
        if (!$this->result)
            trigger_error(mysqli_error($this->db), E_USER_ERROR);
    }

    function unprepareData(): void {
        if ($this->result instanceof \PDOStatement) {
            $this->result->closeCursor();
            return;
        }

        mysqli_free_result($this->result);
    }

    /** @internal */
    function prepareQueryVars(): void {
        $this->cmdSetPage = $this->name . "_setpage";
        $this->cmdSetSort = $this->name . "_setsort";
        $this->cmdSetDir = $this->name . "_setdir";
        $this->cmdSetFilter = $this->name ."_setfilter";
        $this->cmdSetData = $this->name . "_setdata";
        $this->cmdSetFile = $this->name . "_setfile";
        $this->cmdSetURL = $this->name . "_seturl";
        $this->cmdClearFile = $this->name . "_clearfile";
        $this->cmdAdd = $this->name . "_add";
        $this->cmdConfirmAdd = $this->name . "_confirmadd";
        $this->cmdDelete = $this->name . "_delete";
        $this->cmdConfirmDelete = $this->name . "_confirmdelete";
        $this->cmdCancel = $this->name . "_cancel";
        $this->cmdEdit = $this->name . "_edit";
        $this->cmdConfirmEdit = $this->name . "_confirmedit";
        $this->varDeleteID = $this->name . "_deleteid";
        $this->varEditID = $this->name . "_editid";
    }

    function processSession(): void {
        if (!isset($this->page)) {
            if (isset($_SESSION["phpMySQLGrid_" . $this->name]["page"]))
                $this->page = $_SESSION["phpMySQLGrid_" . $this->name]["page"];
            else {
                $this->page = 1;
                $_SESSION["phpMySQLGrid_" . $this->name]["page"] = 1;
            }
        } else $_SESSION['phpMySQLGrid_' . $this->name]['page'] = $this->page;
        for ($i = 0; $i < count($this->columns); $i++) {
            if (isset($_SESSION['phpMySQLGrid_' . $this->name]['filter'][$i])) {
                $this->columns[$i]['active_filter'] =
                    $_SESSION['phpMySQLGrid_' . $this->name]['filter'][$i];
            } else if (isset($this->columns[$i]['filter'])) {
                $this->columns[$i]['active_filter'] =
                    $this->columns[$i]['filter'];
            } else {
                $this->columns[$i]['active_filter'] = '';
            }
        }
        if (isset($_SESSION["phpMySQLGrid_" . $this->name]["sort"]))
            $this->sort = min(count($this->columns) - 1,
                $_SESSION["phpMySQLGrid_" . $this->name]["sort"]);
        else {
            $this->sort = $this->default_sort_column;
            $_SESSION["phpMySQLGrid_" . $this->name]["sort"] = $this->default_sort_column;
        }
        if (isset($_SESSION["phpMySQLGrid_" . $this->name]["dir"]))
            $this->dir = $_SESSION["phpMySQLGrid_" . $this->name]["dir"];
        else {
            $this->dir = $this->default_sort_direction;
            $_SESSION["phpMySQLGrid_" . $this->name]["dir"] = $this->default_sort_direction;
        }
    }

    private function addDataWithPdo(mixed $data): void {
        $columns = array();
        $params = array();

        for ($i = 0; $i < count($this->columns); $i++) {
            $columnName = (string)$this->columns[$i]["field"];
            $value = null;
            $include = false;

            if ($this->columns[$i]["type"] == PHPMYSQLGRID_FILE) {
                if (isset($this->columns[$i]["convert_input"])) {
                    $value = $this->columns[$i]["convert_input"]($this,
                        $data[$i], $i + $this->countPrimaries(),
                        array_merge((array)false, (array)$data));
                    $include = true;
                } else {
                    if (!$data[$i]) continue;

                    if (is_array($data[$i])) {
                        if (!$data[$i]['size']) continue;
                        $handle = fopen($data[$i]['tmp_name'], 'rb');
                        if ($handle === false) continue;
                        $value = fread($handle, $data[$i]['size']);
                        if ($value === false) $value = '';
                        fclose($handle);
                        $include = true;
                    } else {
                        @$handle = fopen($data[$i], 'rb');
                        if (!$handle) continue;
                        $content = '';
                        while ($buffer = fread($handle, 8192))
                            $content .= $buffer;
                        fclose($handle);
                        $value = $content;
                        $include = true;
                    }
                }
            } else if ((($this->columns[$i]["type"] != PHPMYSQLGRID_PASSWORD)
                || ($data[$i] != PHPMYSQLGRID_PWDUMMY))
                && ($this->columns[$i]["type"] != PHPMYSQLGRID_FILE)) {
                $value = $data[$i];
                if (isset($this->columns[$i]["convert_input"])) {
                    $value = $this->columns[$i]["convert_input"]($this,
                        $value, $i + $this->countPrimaries(),
                        array_merge((array)false, (array)$data));
                }
                $include = true;
            }

            if ($include) {
                $columns[] = $columnName;
                $params[":col_" . $i] = $value;
            }
        }

        foreach ($this->add_values as $key => $value) {
            $columns[] = (string)$key;
            $params[":add_" . (string)$key] = $value;
        }

        if (!$columns) {
            return;
        }

        $placeholders = array();
        for ($i = 0; $i < count($this->columns); $i++) {
            if (array_key_exists(":col_" . $i, $params)) {
                $placeholders[] = ":col_" . $i;
            }
        }
        foreach ($this->add_values as $key => $_value) {
            $placeholders[] = ":add_" . (string)$key;
        }

        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            join(",", $columns),
            join(",", $placeholders)
        );

        $this->executePdoStatement($query, $params);

        $hook = $this->add_after;
        if (is_callable($hook) && ($this->db instanceof \PDO)) {
            $id = (int)$this->db->lastInsertId();
            if (!$hook($this, $id, $data)) return;
        }
    }

    private function deleteDataWithPdo(mixed $id): void {
        $query = sprintf(
            "DELETE FROM %s where %s=:id",
            $this->table,
            $this->primary
        );

        $this->executePdoStatement($query, array(":id" => $id));
    }

    private function editDataWithPdo(mixed $id, mixed $data): void {
        $updates = array();
        $params = array();

        for ($i = 0; $i < count($this->columns); $i++) {
            $columnName = (string)$this->columns[$i]["field"];

            if ($this->columns[$i]["type"] == PHPMYSQLGRID_FILE) {
                if (isset($this->columns[$i]["convert_input"])) {
                    $value = $this->columns[$i]["convert_input"]($this,
                        $data[$i], $i + $this->countPrimaries(),
                        array_merge((array)$id, (array)$data));
                    if ($value !== false) {
                        $updates[] = sprintf("%s=:%s", $columnName, "file_" . $i);
                        $params[":file_" . $i] = $value;
                    }
                } else {
                    if (!$data[$i]) {
                        $updates[] = sprintf("%s=:%s", $columnName, "file_" . $i);
                        $params[":file_" . $i] = "";
                    } else if (is_array($data[$i])) {
                        if (!$data[$i]['size']) continue;
                        $handle = fopen($data[$i]['tmp_name'], 'rb');
                        if ($handle === false) continue;
                        $value = fread($handle, $data[$i]['size']);
                        if ($value === false) $value = '';
                        fclose($handle);
                        $updates[] = sprintf("%s=:%s", $columnName, "file_" . $i);
                        $params[":file_" . $i] = $value;
                    } else {
                        @$handle = fopen($data[$i], 'rb');
                        if (!$handle) continue;
                        $value = '';
                        while ($buffer = fread($handle, 8192))
                            $value .= $buffer;
                        fclose($handle);
                        $updates[] = sprintf("%s=:%s", $columnName, "file_" . $i);
                        $params[":file_" . $i] = $value;
                    }
                }
                continue;
            }

            if (($this->columns[$i]["type"] != PHPMYSQLGRID_PASSWORD)
                || ($data[$i] != PHPMYSQLGRID_PWDUMMY)) {
                $value = $data[$i];
                if (isset($this->columns[$i]["convert_input"])) {
                    $value = $this->columns[$i]["convert_input"]($this,
                        $value, $i + $this->countPrimaries(),
                        array_merge((array)$id, (array)$data));
                }

                $updates[] = sprintf("%s=:%s", $columnName, "col_" . $i);
                $params[":col_" . $i] = $value;
            }
        }

        if (!$updates) {
            return;
        }

        $params[":id"] = $id;
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s=:id",
            $this->table,
            join(",", $updates),
            $this->primary
        );

        $this->executePdoStatement($query, $params);
    }

    function addData(mixed $data): void {
        if ($this->usingPdoConnection()) {
            if (!$this->can_add) return;

            $hook = $this->add_before;
            if (is_callable($hook))
                if (!$hook($this, $data)) return;

            $this->addDataWithPdo($data);
            return;
        }

        if (!$this->can_add) return;
        $columns = array();
        $placeholders = array();
        $params = array();

        // Call add_before hook if set
        $hook = $this->add_before;
        if (is_callable($hook))
            if (!$hook($this, $data)) return;

        for ($i = 0; $i < count($this->columns); $i++) {
            if ($this->columns[$i]["type"] == PHPMYSQLGRID_FILE) {
                // Call input converter if there is one
                if (isset($this->columns[$i]["convert_input"])) {
                    $data[$i] = $this->columns[$i]["convert_input"]($this,
                        $data[$i], $i + $this->countPrimaries(),
                        array_merge((array)false, (array)$data));
                    $columns[] = $this->columns[$i]["field"];
                    $placeholders[] = "?";
                    $params[] = $data[$i];
                } else {
                    // Ignore if data is empty
                    if (!$data[$i]) continue;

                    // If data is an array, process file upload
                    if (is_array($data[$i])) {
                        // Ignore empty or non-uploaded files
                        if (!$data[$i]['size']) continue;
                        $handle = fopen($data[$i]['tmp_name'], 'rb');
                        if ($handle === false) continue;
                        $content = fread($handle, $data[$i]['size']);
                        if ($content === false) $content = '';
                        fclose($handle);
                        $columns[] = $this->columns[$i]["field"];
                        $placeholders[] = "?";
                        $params[] = $content;
                    }
                    // If it's not an array fetch the URL
                    else {
                        // Open URL and suppress messages
                        @$handle = fopen($data[$i]  , 'rb');
                        // Ignore invalid URLs;
                        if (!$handle) continue;
                        $content = '';
                        while ($buffer = fread($handle, 8192))
                            $content .= $buffer;
                        fclose($handle);
                        $columns[] = $this->columns[$i]["field"];
                        $placeholders[] = "?";
                        $params[] = $content;
                    }
                }
                continue;
            }

            if ((($this->columns[$i]["type"] != PHPMYSQLGRID_PASSWORD)
                || ($data[$i] != PHPMYSQLGRID_PWDUMMY))
                && ($this->columns[$i]["type"] != PHPMYSQLGRID_FILE)) {
                if (isset($this->columns[$i]["convert_input"]))
                    $data[$i] = $this->columns[$i]["convert_input"]($this,
                        $data[$i], $i + $this->countPrimaries(),
                        array_merge((array)false, (array)$data));
                $columns[] = $this->columns[$i]["field"];
                $placeholders[] = "?";
                $params[] = (string)$data[$i];
            }
        }
        foreach ($this->add_values as $key => $value) {
            $columns[] = $key;
            $placeholders[] = "?";
            $params[] = $value;
        }

        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table, join(",", $columns), join(",", $placeholders)
        );
        $statement = $this->executeMysqliPrepared($query, $params);
        mysqli_stmt_close($statement);

        // Call add_after hook if set
        $hook = $this->add_after;
        if (is_callable($hook)) {
            $id = mysqli_insert_id($this->db);
            if (!$hook($this, $id, $data)) return;
        }
    }

    function deleteData(mixed $id): void {
        if ($this->usingPdoConnection()) {
            if (!$this->can_delete) return;

            $hook = $this->delete_before;
            if (is_callable($hook))
                if (!$hook($this, $id)) return;

            $this->deleteDataWithPdo($id);

            $hook = $this->delete_after;
            if (is_callable($hook)) $hook($this, $id);
            return;
        }

        if (!$this->can_delete) return;

        // Call delete hook if set
        $hook = $this->delete_before;
        if (is_callable($hook))
            if (!$hook($this, $id)) return;

        $query = sprintf(
            "DELETE FROM %s where %s=?",
            $this->table, $this->primary
        );
        $statement = $this->executeMysqliPrepared($query, array($id));
        mysqli_stmt_close($statement);

        // Call delete hook if set
        $hook = $this->delete_after;
        if (is_callable($hook)) $hook($this, $id);
    }

    function editData(mixed $id, mixed $data): void {
        if ($this->usingPdoConnection()) {
            if (!$this->can_edit) return;

            $hook = $this->edit_before;
            if (is_callable($hook))
                if (!$hook($this, $id, $data)) return;

            $this->editDataWithPdo($id, $data);

            $hook = $this->edit_after;
            if (is_callable($hook))
                if (!$hook($this, $id, $data)) return;
            return;
        }

        if (!$this->can_edit) return;

        // Call edit_before hook if set
        $hook = $this->edit_before;
        if (is_callable($hook))
            if (!$hook($this, $id, $data)) return;

        $updates = array();
        $params = array();
        for ($i = 0; $i < count($this->columns); $i++) {
            if ($this->columns[$i]["type"] == PHPMYSQLGRID_FILE) {
                // Call input converter if there is one
                if (isset($this->columns[$i]["convert_input"])) {
                    $data[$i] = $this->columns[$i]["convert_input"]($this,
                        $data[$i], $i + $this->countPrimaries(),
                        array_merge((array)$id, (array)$data));
                    if ($data[$i] !== false) {
                        $updates[] = sprintf("%s=?", $this->columns[$i]["field"]);
                        $params[] = $data[$i];
                    }
                } else {

                    // Delete file blob if empty
                    if (!$data[$i]) {
                        $updates[] = sprintf("%s=?", $this->columns[$i]["field"]);
                        $params[] = "";
                    }

                    // If data is an array, process file upload
                    else if (is_array($data[$i])) {
                        // Ignore empty or non-uploaded files
                        if (!$data[$i]['size']) continue;
                        $handle = fopen($data[$i]['tmp_name'], 'rb');
                        if ($handle === false) continue;
                        $content = fread($handle, $data[$i]['size']);
                        if ($content === false) $content = '';
                        fclose($handle);
                        $updates[] = sprintf("%s=?", $this->columns[$i]["field"]);
                        $params[] = $content;
                    }
                    // If it's not an array fetch the URL
                    else {
                        // Open URL and suppress messages
                        @$handle = fopen($data[$i]  , 'rb');
                        // Ignore invalid URLs;
                        if (!$handle) continue;
                        $content = '';
                        while ($buffer = fread($handle, 8192))
                            $content .= $buffer;
                        fclose($handle);
                        $updates[] = sprintf("%s=?", $this->columns[$i]["field"]);
                        $params[] = $content;
                    }
                }
                continue;
            }

            if (($this->columns[$i]["type"] != PHPMYSQLGRID_PASSWORD)
                || ($data[$i] != PHPMYSQLGRID_PWDUMMY)) {
                if (isset($this->columns[$i]["convert_input"]))
                    $data[$i] = $this->columns[$i]["convert_input"]($this,
                        $data[$i], $i + $this->countPrimaries(),
                        array_merge((array)$id, (array)$data));
                $updates[] = sprintf("%s=?", $this->columns[$i]["field"]);
                $params[] = $data[$i];
            }
        }
        $params[] = $id;
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s=?",
            $this->table, join(",", $updates), $this->primary
        );
        $statement = $this->executeMysqliPrepared($query, $params);
        mysqli_stmt_close($statement);

        // Call edit_after hook if set
        $hook = $this->edit_after;
        if (is_callable($hook))
            if (!$hook($this, $id, $data)) return;

    }

    function processRequests(): void {
        // Process SetPage command
        if (isset($_REQUEST[$this->cmdSetPage])) {
            $this->page = intval($_REQUEST[$this->cmdSetPage]);
            $_SESSION["phpMySQLGrid_" . $this->name]["page"] = $this->page;
        }

        // Process SetSort command
        if (isset($_REQUEST[$this->cmdSetSort])) {
            $this->sort = intval($_REQUEST[$this->cmdSetSort]);
            $_SESSION["phpMySQLGrid_" . $this->name]["sort"] = $this->sort;
        }

        // Process SetFilter command
        if (isset($_REQUEST[$this->cmdSetFilter])) {
            foreach ($_REQUEST[$this->cmdSetFilter] as $key => $value) {
                $this->columns[$key]['active_filter'] = stripslashes($value);
                $_SESSION["phpMySQLGrid_" . $this->name]["filter"][$key] = $this->columns[$key]['active_filter'];
            }
        }

        // Process SetDir command
        if (isset($_REQUEST[$this->cmdSetDir])) {
            $this->dir = intval($_REQUEST[$this->cmdSetDir]);
            $_SESSION["phpMySQLGrid_" . $this->name]["dir"] = $this->dir;
        }

        // Process data vars
        $data = array();
        if (isset($_REQUEST[$this->cmdSetData])) {
            reset($_FILES);

            for ($i = 0; $i < count($this->columns); $i++) {
                switch ($this->columns[$i]["type"]) {
                    case PHPMYSQLGRID_FILE:
                        if (isset($_REQUEST[$this->cmdClearFile][$i]))
                            $data[$i] = false;
                        else if ($_REQUEST[$this->cmdSetURL][$i])
                            $data[$i] = $_REQUEST[$this->cmdSetURL][$i];
                        else
                            $data[$i] = current($_FILES);
                        next($_FILES);
                        break;
                    default:
                        $data[$i] = $_REQUEST[$this->cmdSetData][$i];
                }
            }
        }

        // Process Add command
        if (($this->can_add) && (isset($_REQUEST[$this->cmdAdd]))) {
            $this->mode = PHPMYSQLGRID_ADDMODE;
        }

        // Process ConfirmAdd command
        if (($this->can_add) && (isset($_REQUEST[$this->cmdConfirmAdd]))) {
            $this->addData($data);
        }

        // Process Delete command
        if (($this->can_delete) && (isset($_REQUEST[$this->cmdDelete]))) {
            $this->mode = PHPMYSQLGRID_DELETEMODE;
        }

        // Process ConfirmDelete command
        if (($this->can_delete) &&
            (isset($_REQUEST[$this->cmdConfirmDelete]))) {
            $this->deleteData($_REQUEST[$this->varDeleteID]);
        }

        // Process Edit command
        if (($this->can_edit) && (isset($_REQUEST[$this->cmdEdit]))) {
            $this->mode = PHPMYSQLGRID_EDITMODE;
        }

        // Process ConfirmEdit command
        if (($this->can_edit) &&
            (isset($_REQUEST[$this->cmdConfirmEdit]))) {
            $this->editData($_REQUEST[$this->varEditID], $data);
        }
    }

    /** @internal */
    function drawHeader(): void {
        // Check if a file upload is present in this grid. This is
        // important to switch to multipart/form-data encoding.
        $upload = false;
        for ($i = 0; $i < count($this->columns); $i++)
            if ($this->columns[$i]["type"] == PHPMYSQLGRID_FILE) {
                $upload = true;
                break;
            }
        echo
            '<form action="', $_SERVER["PHP_SELF"], '" method="post" id="' , $this->name,'_form"',
            $upload ? ' enctype="multipart/form-data"' : '',
            '>',
            '<input type="image" style="width: 0; height: 0; border: none; visibility: hidden; position: absolute; left: -999px" />',
            '<table class="', $this->style, ' ' , $this->cssClass ,'" border="0" cellspacing="1">';
    }

    /** @internal */
    function drawFooter(): void {
        echo
            '</table>',
            '</form><a href="#" id="',$this->name,'_bottom"></a>';
    }

    /** @internal */
    function drawCaptions(): void {
        echo
            '<thead><tr>',
            '<th class="', $this->style, '-header">&nbsp;</th>';
        for ($i = 0; $i < count($this->columns); $i++) {
            if (isset($this->columns[$i]["caption"]))
                $caption = $this->columns[$i]["caption"];
            else
                $caption = $this->columns[$i]["field"];
            echo '<th class="', $this->style, '-header" nowrap="nowrap">';
            if ($this->can_sort && $this->columns[$i]['can_sort']
                    && ($this->columns[$i]["type"] != PHPMYSQLGRID_PASSWORD))
                echo
                    '<a href="', $_SERVER["PHP_SELF"], '?',
                    $this->cmdSetSort, '=', $i, '&amp;',
                    $this->cmdSetDir, '=0"',
                    ' aria-label="', $this->convertToHtmlEntities($this->txtSortAsc),
                    '" title="', $this->convertToHtmlEntities($this->txtSortAsc), '">',
                        $this->renderIcon((($this->sort == $i) && !$this->dir) ? $this->svgSortAscActive : $this->svgSortAscInactive, "sort-asc"),
                    '</a>&nbsp;';
            echo $this->convertToHtmlEntities($caption);
            if ($this->can_filter && $this->columns[$i]['can_filter']
                    && ($this->columns[$i]["type"] != PHPMYSQLGRID_PASSWORD)) {
                echo
                    '&nbsp;<input type="text" name="',
                    $this->cmdSetFilter,
                    '[', $i, ']" value="',
                    $this->convertToHtmlEntities($this->columns[$i]['active_filter']),
                    '" size="',
                    isset($this->columns[$i]['filter_size']) ?
                        intval($this->columns[$i]['filter_size']) : intval($this->filter_size),
                    '">'
                ;
            }
            if ($this->can_sort && $this->columns[$i]['can_sort']
                    && ($this->columns[$i]["type"] != PHPMYSQLGRID_PASSWORD))
                echo
                    '&nbsp;<a href="', $_SERVER["PHP_SELF"], '?',
                    $this->cmdSetSort, '=', $i, '&amp;',
                    $this->cmdSetDir, '=1"',
                    ' aria-label="', $this->convertToHtmlEntities($this->txtSortDesc),
                    '" title="', $this->convertToHtmlEntities($this->txtSortDesc), '">',
                        $this->renderIcon((($this->sort == $i) && $this->dir) ? $this->svgSortDescActive : $this->svgSortDescInactive, "sort-desc"),
                    '</a>';
            echo '</th>';
        }
        echo "</tr></thead>";
    }

    function drawData(): void {
        echo '<tbody>';
        $this->row = 0;
        while (($data = $this->fetchResultRow()) !== false) {
            $rowClass = $this->style . '-cell--' . (($this->row % 2) ? 'odd' : 'even');
            if (($this->mode == PHPMYSQLGRID_DELETEMODE)
                && ($_REQUEST[$this->varDeleteID] == $data[0])) {
                $headstyle = $this->style . '-action ' . $this->style . '-action--delete';
                $datastyle = $this->style . '-cell ' . $rowClass . ' ' . $this->style . '-cell--delete';
            } else {
                $headstyle = $this->style . '-action';
                $datastyle = $this->style . '-cell ' . $rowClass;
            }
            if (($this->mode == PHPMYSQLGRID_EDITMODE)
                && ($_REQUEST[$this->varEditID] == $data[0])) {
                $this->drawEditControls($data);
                continue;
            }
            echo
                '<tr data-id="',$data[0],'">',
                '<td class="', $headstyle, '" nowrap="nowrap" align="right">';
            if (($this->mode == PHPMYSQLGRID_DELETEMODE)
                && ($_REQUEST[$this->varDeleteID] == $data[0])) {
                echo
                    '<a href="', $_SERVER["PHP_SELF"], '?',
                    $this->cmdConfirmDelete, '=1&amp;',
                    $this->varDeleteID, '=', $data[0],
                    '" aria-label="', $this->convertToHtmlEntities($this->txtConfirm),
                    '" title="', $this->convertToHtmlEntities($this->txtConfirm), '">',
                        $this->renderIcon($this->svgIconConfirm, "confirm"),
                    '</a>',
                    '<a href="', $_SERVER["PHP_SELF"], '?',
                    $this->cmdCancel, '=1"',
                    ' aria-label="', $this->convertToHtmlEntities($this->txtCancel),
                    '" title="', $this->convertToHtmlEntities($this->txtCancel), '">',
                        $this->renderIcon($this->svgIconCancel, "delete"),
                    '</a>';
            } else {
                if ($this->can_edit) {
                    echo
                        '<a href="', $_SERVER["PHP_SELF"], '?',
                        $this->cmdEdit, '=1&amp;', $this->varEditID,
                        '=', $data[0],
                        '" aria-label="', $this->convertToHtmlEntities($this->txtEdit),
                        '" title="', $this->convertToHtmlEntities($this->txtEdit), '">',
                            $this->renderIcon($this->svgIconEdit, "edit"),
                        '</a>';
                }
                if ($this->can_delete) {
                    echo
                        '<a href="', $_SERVER["PHP_SELF"], '?',
                        $this->cmdDelete, '=1&amp;', $this->varDeleteID,
                        '=', $data[0],
                        '" aria-label="', $this->convertToHtmlEntities($this->txtDelete),
                        '" title="', $this->convertToHtmlEntities($this->txtDelete), '">',
                            $this->renderIcon($this->svgIconDelete, "delete"),
                        '</a>';
                }
                foreach ($this->actions as $action) {
                    switch ($action["type"]) {
                        case PHPMYSQLGRID_IMAGEBUTTON:
                            echo
                                '<a href="',
                                str_replace("<ID>", $data[0], $action["url"]),
                                '">',
                                '<img hspace="1" src="', $action["image"], '" alt="',
                                $this->convertToHtmlEntities($action["caption"]), '" title="',
                                $this->convertToHtmlEntities($action["caption"]),
                                '" border="0" align="middle"';
                            if (isset($action["width"]))
                                echo ' width="', $action["width"], '"';
                            if (isset($action["height"]))
                                echo ' height="', $action["height"], '"';
                            echo '/></a>';
                            break;
                        default:
                            echo
                                '&nbsp;<a href="',
                                str_replace("<ID>", $data[0], $action["url"]),
                                '">',
                                $this->convertToHtmlEntities($action["caption"]),
                                '</a>';

                    }
                }
                if (!$this->can_delete && !$this->can_edit
                    && !$this->actions) echo '&nbsp;';
            }
            echo
                '</td>';

            for ($i = 0; $i < count($this->columns); $i++) {
                switch ($this->columns[$i]["type"]) {
                    case PHPMYSQLGRID_PASSWORD:
                        $text = PHPMYSQLGRID_PWDUMMY;
                        break;
                    case PHPMYSQLGRID_FILE:
                        if (isset($this->columns[$i]['convert_output']))
                            $text = $data[$i + $this->countPrimaries()];
                        else
                            $text = $data[$i + $this->countPrimaries()] ?
                                $this->txtFileTrue : $this->txtFileFalse;
                        break;
                    default:
                        $text = $data[$i + $this->countPrimaries()];
                }

                // Handle output converter
                if (isset($this->columns[$i]["convert_output"]))
                    $text = $this->columns[$i]["convert_output"]($this, $text, $i + $this->countPrimaries(), $data, false);
                else {
                    switch ($this->columns[$i]["type"]) {
                        case PHPMYSQLGRID_BOOLEAN:
                            $text = $text
                                ? $this->renderIcon($this->svgBoolTrue, "bool-true")
                                : $this->renderIcon($this->svgBoolFalse, "bool-false");
                            break;
                        case PHPMYSQLGRID_SELECTION:
                            if (isset($this->columns[$i]["selection"][$text]))
                                $text = $this->convertToHtmlEntities($this->columns[$i]["selection"][$text]);
                            else
                                $text = "N/A";
                            break;
                    }
                }

                echo '<td class="', $datastyle, '"';
                if (isset($this->columns[$i]["align"]))
                    echo ' align="', $this->columns[$i]["align"], '"';
                echo '>';

                // Trust converted output, otherwise htmlentity it.
                if (isset($this->columns[$i]["convert_output"]) || $this->columns[$i]["type"] === PHPMYSQLGRID_BOOLEAN)
                    echo $text;
                else {
                    if (isset($this->columns[$i]["size"])
                        && (strlen($text ?? "") > $this->columns[$i]["size"])) {
                        echo
                            '<span title="', $this->convertToHtmlEntities($text), '">',
                            $this->convertToHtmlEntities(
                                substr($text, 0, $this->columns[$i]["size"])
                            ),
                            '...</span>';

                    } else echo $this->convertToHtmlEntities($text);
                }
                echo '</td>';
            }
            echo '</tr>';
            $this->row ++;
        }
        echo '</tbody>';
    }

    /**
     * @param array<int, mixed>|false $data
     */
    function drawEditControls(array|false $data = false): void {
        $rowClass = $this->style . '-cell--' . (($this->row % 2) ? 'odd' : 'even');
        switch ($this->mode) {
            case PHPMYSQLGRID_EDITMODE:
                $headstyle = $this->style . '-action ' . $this->style . '-action--edit';
                $datastyle = $this->style . '-cell ' . $rowClass . ' ' . $this->style . '-cell--edit';
                break;
            case PHPMYSQLGRID_ADDMODE:
                $headstyle = $this->style . '-action ' . $this->style . '-action--add';
                $datastyle = $this->style . '-cell ' . $rowClass . ' ' . $this->style . '-cell--add';
                break;
            default:
                $headstyle = $this->style . '-action';
                $datastyle = $this->style . '-cell ' . $rowClass;
        }
        echo
            '<tr>',
            '<td align="right" class="', $headstyle, '" nowrap="nowrap">';

        if ($this->mode == PHPMYSQLGRID_EDITMODE) {
            echo
                '<input type="hidden" name="', $this->varEditID, '" value="',
                $_REQUEST[$this->varEditID], '">',
                '<input type="hidden" name="' . $this->cmdConfirmEdit. '" value="true" />'.
                '<a href="#" onclick="document.getElementById(\''.$this->name.'_form\').submit(); return false;" aria-label="' . $this->convertToHtmlEntities($this->txtConfirm) . '" title="' . $this->convertToHtmlEntities($this->txtConfirm) . '">'.
                    $this->renderIcon($this->svgIconConfirm, "confirm").
                '</a>',
                '<a href="', $_SERVER["PHP_SELF"], "?",
                $this->cmdCancel, '=1"',
                ' aria-label="', $this->convertToHtmlEntities($this->txtCancel),
                '" title="', $this->convertToHtmlEntities($this->txtCancel), '">',
                    $this->renderIcon($this->svgIconCancel, "delete"),
                '</a>',
                '</td>';
        } else {
            echo
                '<input type="hidden" name="' . $this->cmdConfirmAdd. '" value="true" />'.
                '<a href="#" onclick="document.getElementById(\''.$this->name.'_form\').submit(); return false;" aria-label="' . $this->convertToHtmlEntities($this->txtConfirm) . '" title="' . $this->convertToHtmlEntities($this->txtConfirm) . '">'.
                    $this->renderIcon($this->svgIconConfirm, "confirm").
                '</a>',
                '<a href="', $_SERVER["PHP_SELF"], "?",
                $this->cmdCancel, '=1"',
                ' aria-label="', $this->convertToHtmlEntities($this->txtCancel),
                '" title="', $this->convertToHtmlEntities($this->txtCancel), '">',
                    $this->renderIcon($this->svgIconCancel, "delete"),
                '</a>';
        }
        for ($i = 0; $i < count($this->columns); $i++) {
            echo '<td class="', $datastyle, '-input"';
            if (isset($this->columns[$i]["align"]))
                echo ' align="', $this->columns[$i]["align"], '"';
            echo '>';
            switch($this->columns[$i]["type"]) {
                case PHPMYSQLGRID_LOOKUP:
                    echo
                        '<select class="',
                        $this->style, '-lookup"',
                        ' name="', $this->cmdSetData, '[', $i, ']"';
                    if (isset($this->columns[$i]["width"]))
                        echo
                            ' style="width:', $this->columns[$i]["width"],
                            'px;"';
                    echo '>';
                    $lookupFilter = isset($this->columns[$i]["lookup_filter"]) ? (string)$this->columns[$i]["lookup_filter"] : "";
                    $this->assertSafeRawSqlFragment($lookupFilter, "lookup_filter");

                    $lookupRows = $this->queryNumericRows(sprintf(
                        "SELECT %s, %s FROM %s%s",
                        $this->columns[$i]["lookup_primary"],
                        $this->columns[$i]["lookup_field"],
                        $this->columns[$i]["lookup_table"],
                        $lookupFilter !== "" ? " WHERE " . $lookupFilter : ""
                    ));
                    foreach ($lookupRows as $lookup_data) {
                        echo
                            '<option class="', $this->style, '" value="',
                            $this->convertToHtmlEntities($lookup_data[0]),
                            '"';
                        if ($data && ($lookup_data[1] == $data[$i
                                + $this->countPrimaries()]))
                            echo ' selected="selected"';
                        echo
                            '>',
                            $this->convertToHtmlEntities($lookup_data[1]),
                            '</option>';
                    }
                    echo '</select>';
                    break;
                case PHPMYSQLGRID_SELECTION:
                    echo
                        '<select class="',
                        $this->style, '-select"',
                        ' name="', $this->cmdSetData, '[', $i, ']"';
                    if (isset($this->columns[$i]["width"]))
                        echo
                            ' style="width:', $this->columns[$i]["width"],
                            'px;"';
                    echo '>';
                    foreach($this->columns[$i]["selection"] as $key => $value) {
                        echo
                            '<option class="', $this->style, '" value="',
                            $this->convertToHtmlEntities($key),
                            '"';
                        if ($data && ($key == $data[$i + $this->countPrimaries()]))
                            echo ' selected="selected"';
                        else if (!$data && isset($this->columns[$i]["default"]) && $key == $this->columns[$i]["default"])
                            echo ' selected="selected"';
                        echo
                            '>',
                            $this->convertToHtmlEntities($value),
                            '</option>';
                    }
                    echo '</select>';
                    break;
                case PHPMYSQLGRID_PASSWORD:
                    echo
                        '<input class="', $this->style, '-password" type="password" value="';
                    if ($data) echo PHPMYSQLGRID_PWDUMMY;
                    echo '" name="', $this->cmdSetData, '[', $i, ']"';
                    if (isset($this->columns[$i]["size"]))
                        echo ' size="', $this->columns[$i]["size"], '"';
                    if (isset($this->columns[$i]["maxlength"]))
                        echo
                            ' maxlength="', $this->columns[$i]["maxlength"],
                            '"';
                    if (isset($this->columns[$i]["width"]))
                        echo
                            ' style="width:', $this->columns[$i]["width"],
                            'px;"';
                    echo '>';
                    break;
                case PHPMYSQLGRID_BOOLEAN:
                    echo
                        '<input type="hidden" name="',
                        $this->cmdSetData, '[', $i, ']" value="0">',
                        '<input class="', $this->style, '-checkbox"',
                        ' type="checkbox" value="1"';
                    if ($data && $data[$i + $this->countPrimaries()])
                        echo ' checked="checked"';
                    else if (!$data && isset($this->columns[$i]["default"]) && $this->columns[$i]["default"] == 1)
                        echo ' checked="checked"';
                    echo ' name="', $this->cmdSetData, '[', $i, ']">';
                    break;
                case PHPMYSQLGRID_MULTILINETEXT:
                    echo
                        '<textarea class="', $this->style, '-textarea" name="', $this->cmdSetData, '[', $i, ']"';
                    if (isset($this->columns[$i]["size"]))
                        echo ' cols="', $this->columns[$i]["size"], '"';
                    if (isset($this->columns[$i]["lines"]))
                        echo ' rows="', $this->columns[$i]["lines"], '"';
                    $style = "";
                    if (isset($this->columns[$i]["width"]))
                        $style .= sprintf("width:%dpx;",
                            $this->columns[$i]["width"]);
                    if (isset($this->columns[$i]["height"]))
                        $style .= sprintf("height:%dpx;",
                            $this->columns[$i]["height"]);
                    if ($style)
                        echo ' style="', $style, '"';
                    echo '>';
                    if ($data)
                        echo $this->convertToHtmlEntities($data[$i + $this->countPrimaries()]);
                    else if (isset($this->columns[$i]["default"]))
                        echo $this->convertToHtmlEntities($this->columns[$i]["default"]);
                    echo '</textarea>';
                    break;
                case PHPMYSQLGRID_FILE:
                    if ($this->mode == PHPMYSQLGRID_EDITMODE) {
                        $value = $data ? $data[$i + $this->countPrimaries()] : '';
                        if (isset($this->columns[$i]["convert_output"]))
                            $value = $this->columns[$i]["convert_output"]($this, $value, $i + $this->countPrimaries(), $data, true);
                        else
                            $value = $value ? $this->txtFileTrue : $this->txtFileFalse;
                        if ($data) echo $value;
                        echo '<br><br>';
                    }
                    echo
                        $this->txtURL, '&nbsp;<input type="text" class="', $this->style,'-file" ',
                        'name="', $this->cmdSetURL, '[', $i, ']"';
                    if (isset($this->columns[$i]["size"]))
                        echo ' size="', $this->columns[$i]["size"], '"';
                    $style = "";
                    if (isset($this->columns[$i]["width"]))
                        $style .= sprintf("width:%dpx;",
                            $this->columns[$i]["width"]);
                    if ($style)
                        echo ' style="', $style, '"';
                    echo
                        '><br>';
                    echo
                        $this->txtFile, '&nbsp;<input type="file" class="', $this->style,
                        '" name="', $this->cmdSetFile, $i, '"';
                    if (isset($this->columns[$i]["size"]))
                        echo ' size="', $this->columns[$i]["size"], '"';
                    if (isset($this->columns[$i]["maxlength"]))
                        echo ' maxlength="', $this->columns[$i]["maxlength"], '"';
                    if (isset($this->columns[$i]["accept"]))
                        echo ' accept="', $this->columns[$i]["accept"], '"';
                    $style = "";
                    if (isset($this->columns[$i]["width"]))
                        $style .= sprintf("width:%dpx;",
                            $this->columns[$i]["width"]);
                    if ($style)
                        echo ' style="', $style, '"';
                    echo
                        '>';

                    if ($this->mode == PHPMYSQLGRID_EDITMODE) {
                        echo
                            '<br>',
                            $this->txtDelete, '&nbsp;<input type="checkbox"',
                            ' name="', $this->cmdClearFile, '[', $i, ']"',
                            '">';
                    }
                    break;
                default:
                    $value = $data ? $data[$i + $this->countPrimaries()] : '';
                    if (isset($this->columns[$i]["convert_output"]))
                        $value = $this->columns[$i]["convert_output"]($this, $value, $i + $this->countPrimaries(), $data, true);
                    echo
                        '<input class="', $this->style, '-text"',
                        ' type="text" value="';
                    if ($data)
                        echo $this->convertToHtmlEntities($value);
                    else if (isset($this->columns[$i]["default"]))
                        echo $this->convertToHtmlEntities($this->columns[$i]["default"]);
                    echo '" name="', $this->cmdSetData, '[', $i, ']"';
                    if (isset($this->columns[$i]["size"]))
                        echo ' size="', $this->columns[$i]["size"], '"';
                    if (isset($this->columns[$i]["maxlength"]))
                        echo
                            ' maxlength="', $this->columns[$i]["maxlength"],
                            '"';
                    if (isset($this->columns[$i]["width"]))
                        echo
                            ' style="width:', $this->columns[$i]["width"],
                            'px;"';
                    if (isset($this->columns[$i]["placeholder"]))
                        echo
                            ' placeholder="', $this->columns[$i]["placeholder"],
                            '"';
                    echo '>';
            }
            echo '</td>';
        }
        echo
            '</tr>';
    }

    /** @internal */
    function drawNavigation(): void {
        echo
            '<tfoot><tr>',
            '<td align="right" class="', $this->style, '-action">';
        // Draw Add Button if wanted
        if ($this->can_add) {
            echo
                '<a href="', $_SERVER["PHP_SELF"], '?', $this->cmdAdd, '=1#',$this->name,'_bottom" class="add-button"',
                ' aria-label="', $this->convertToHtmlEntities($this->txtAdd),
                '" title="', $this->convertToHtmlEntities($this->txtAdd), '">',
                    $this->renderIcon($this->svgIconAdd, "add"),
                '</a>';
        } else echo '&nbsp;';
        echo '</td>';

        echo '<td class="', $this->style, '-navigation" colspan="', count($this->columns), '">';
        if ($this->can_navigate) {
            $pages = ceil($this->rows / $this->limit);
            echo '<nav class="phpmysqlgrid-pagination" aria-label="', $this->convertToHtmlEntities($this->txtPaginationLabel), '">';

            // Prev button
            if ($this->page > 1) {
                echo '<a class="phpmysqlgrid-pagination-prev" href="',
                    $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=', $this->page - 1,
                    '" aria-label="', $this->convertToHtmlEntities($this->txtPrevious), '">',
                    $this->renderIcon($this->svgNavPrev, "previous"), '</a>';
            } else {
                echo '<span class="phpmysqlgrid-pagination-prev is-disabled" aria-disabled="true" aria-label="',
                    $this->convertToHtmlEntities($this->txtPrevious), '">',
                    $this->renderIcon($this->svgNavPrev, "previous"), '</span>';
            }

            echo '<ol class="phpmysqlgrid-pagination-list">';

            // First page shortcut when far from the window
            if ($this->page > 3) {
                echo '<li class="phpmysqlgrid-pagination-item">',
                    '<a class="phpmysqlgrid-pagination-link" href="',
                    $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=1">1</a>',
                    '</li>';
            }
            if ($this->page > 4) {
                echo '<li class="phpmysqlgrid-pagination-ellipsis" aria-hidden="true">&#x2026;</li>';
            }

            // Page window ±2 around current page
            for ($i = max(1, $this->page - 2); $i <= min($pages, $this->page + 2); $i++) {
                if ($i == $this->page) {
                    echo '<li class="phpmysqlgrid-pagination-item is-active">',
                        '<span class="phpmysqlgrid-pagination-current" aria-current="page">', $i, '</span>',
                        '</li>';
                } else {
                    echo '<li class="phpmysqlgrid-pagination-item">',
                        '<a class="phpmysqlgrid-pagination-link" href="',
                        $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=', $i, '">', $i, '</a>',
                        '</li>';
                }
            }

            // Last page shortcut when far from the window
            if ($this->page < $pages - 3) {
                echo '<li class="phpmysqlgrid-pagination-ellipsis" aria-hidden="true">&#x2026;</li>';
            }
            if ($this->page < $pages - 2) {
                echo '<li class="phpmysqlgrid-pagination-item">',
                    '<a class="phpmysqlgrid-pagination-link" href="',
                    $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=', $pages, '">', $pages, '</a>',
                    '</li>';
            }

            echo '</ol>';

            // Next button
            if ($this->page < $pages) {
                echo '<a class="phpmysqlgrid-pagination-next" href="',
                    $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=', $this->page + 1,
                    '" aria-label="', $this->convertToHtmlEntities($this->txtNext), '">',
                    $this->renderIcon($this->svgNavNext, "next"), '</a>';
            } else {
                echo '<span class="phpmysqlgrid-pagination-next is-disabled" aria-disabled="true" aria-label="',
                    $this->convertToHtmlEntities($this->txtNext), '">',
                    $this->renderIcon($this->svgNavNext, "next"), '</span>';
            }

            echo '</nav>';
        } else echo '&nbsp;';
        echo
            '</td>',
            '</tr></tfoot>';
    }

    /** @internal */
    function validateColumns(): void {
        for ($i = 0; $i < count($this->columns); $i++) {
            if (!isset($this->columns[$i]['type']))
                $this->columns[$i]['type'] = PHPMYSQLGRID_TEXT;
            if (!isset($this->columns[$i]['can_sort']))
                $this->columns[$i]['can_sort'] = true;
            if (!isset($this->columns[$i]['can_filter']))
                $this->columns[$i]['can_filter'] = true;
        }
    }

    /** @internal */
    function validateActions(): void {
        for ($i = 0; $i < count($this->actions); $i++) {
            if (!isset($this->actions[$i]["type"]))
                $this->actions[$i]["type"] = PHPMYSQLGRID_TEXTBUTTON;
        }
    }

    function execute(): void {
        // Prepare some variables
        $this->prepareQueryVars();

        // Process session vars
        $this->processSession();

        // Connect to database
        $this->connect();

        // If no columns are specified use all columns of the table
        if (!$this->columns) $this->useAllColumns();

        // Validate columns
        $this->validateColumns();

        // Validate actions
        $this->validateActions();

        // Process requests
        $this->processRequests();

        // Prepare data
        $this->prepareData();

        // Draw header
        $this->drawHeader();

        // Draw captions
        $this->drawCaptions();

        // Draw data
        $this->drawData();

        // Draw Add-line if in Add mode
        if ($this->mode == PHPMYSQLGRID_ADDMODE) $this->drawEditControls();

        // Draw navigation
        $this->drawNavigation();

        // Draw footer
        $this->drawFooter();

        // Disconnect from database
        $this->disconnect();
    }

    function convertToHtmlEntities(mixed $data): string {
        return htmlentities($data ?? "", ENT_COMPAT, $this->charset);
    }
}

?>
