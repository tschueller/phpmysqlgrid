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
        $this->use_icon_font = false;
        $this->internationalize();
    }

    function MySQLGrid(): void {
        self::__construct();
    }

    private function internationalize(): void {
        $this->txtPrevious = "Previous";
        $this->txtNext = "Next";
        $this->txtDelete = "Delete";
        $this->txtAdd = "Add";
        $this->txtEdit = "Edit";
        $this->txtConfirm = "Confirm";
        $this->txtCancel = "Cancel";
        $this->txtYes = "Yes";
        $this->txtNo = "No";
        $this->txtFileTrue = "File present";
        $this->txtFileFalse = "No file present";
        $this->txtFile = "File";
        $this->txtURL = "URL";
    }

    function connect(): void {
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
        mysqli_close($this->db);
    }

    function useAllColumns(): void {
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
                            mysqli_escape_string($this->db, $this->columns[$i]['active_filter'])
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
                            mysqli_escape_string($this->db, $this->columns[$i]['active_filter'])
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

    function addData(mixed $data): void {
        if (!$this->can_add) return;
        $columns = array();
        $values = array();

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
                    $values[] = "'" . addslashes($data[$i]) . "'";
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
                        $values[] = "'" . addslashes($content) . "'";
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
                        $values[] = "'" . addslashes($content) . "'";
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
                $values[] = "'" . addslashes((string)$data[$i]) . "'";
            }
        }
        foreach ($this->add_values as $key => $value) {
            $columns[] = $key;
            $values[] = "'" . $value . "'";
        }

        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table, join(",", $columns), join(",", $values)
        );
        if (!mysqli_query( $this->db, $query))
            trigger_error(mysqli_error($this->db), E_USER_ERROR);

        // Call add_after hook if set
        $hook = $this->add_after;
        if (is_callable($hook)) {
            $id = mysqli_insert_id($this->db);
            if (!$hook($this, $id, $data)) return;
        }
    }

    function deleteData(mixed $id): void {
        if (!$this->can_delete) return;

        // Call delete hook if set
        $hook = $this->delete_before;
        if (is_callable($hook))
            if (!$hook($this, $id)) return;

        $query = sprintf(
            "DELETE FROM %s where %s='%s'",
            $this->table, $this->primary, $id
        );
        if (!mysqli_query( $this->db, $query))
            trigger_error(mysqli_error($this->db), E_USER_ERROR);

        // Call delete hook if set
        $hook = $this->delete_after;
        if (is_callable($hook)) $hook($this, $id);
    }

    function editData(mixed $id, mixed $data): void {
        if (!$this->can_edit) return;

        // Call edit_before hook if set
        $hook = $this->edit_before;
        if (is_callable($hook))
            if (!$hook($this, $id, $data)) return;

        $updates = array();
        for ($i = 0; $i < count($this->columns); $i++) {
            if ($this->columns[$i]["type"] == PHPMYSQLGRID_FILE) {
                // Call input converter if there is one
                if (isset($this->columns[$i]["convert_input"])) {
                    $data[$i] = $this->columns[$i]["convert_input"]($this,
                        $data[$i], $i + $this->countPrimaries(),
                        array_merge((array)$id, (array)$data));
                    if ($data[$i] !== false) {
                        $updates[] = sprintf("%s='%s'",
                            $this->columns[$i]["field"],
                            addslashes($data[$i])
                        );
                    }
                } else {

                    // Delete file blob if empty
                    if (!$data[$i]) {
                        $updates[] = sprintf("%s=''",
                            $this->columns[$i]["field"]
                        );
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
                        $updates[] = sprintf("%s='%s'",
                            $this->columns[$i]["field"],
                            addslashes($content)
                        );
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
                        $updates[] = sprintf("%s='%s'",
                            $this->columns[$i]["field"],
                            addslashes($content)
                        );
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
                $updates[] = sprintf("%s='%s'",
                    $this->columns[$i]["field"],
                    addslashes($data[$i])
                );
            }
        }
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s='%s'",
            $this->table, join(",", $updates), $this->primary, $id
        );
        if (!mysqli_query( $this->db, $query))
            trigger_error(mysqli_error($this->db), E_USER_ERROR);

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
            '<th class="', $this->style, '">&nbsp;</th>';
        for ($i = 0; $i < count($this->columns); $i++) {
            if (isset($this->columns[$i]["caption"]))
                $caption = $this->columns[$i]["caption"];
            else
                $caption = $this->columns[$i]["field"];
            echo '<th class="', $this->style, '" nowrap="nowrap">';
            if ($this->can_sort && $this->columns[$i]['can_sort']
                    && ($this->columns[$i]["type"] != PHPMYSQLGRID_PASSWORD))
                echo
                    '<a href="', $_SERVER["PHP_SELF"], '?',
                    $this->cmdSetSort, '=', $i, '&amp;',
                    $this->cmdSetDir, '=0">',
                        ($this->use_icon_font ?
                        '<i class="fa fa-sort-asc '.(($this->sort == $i) && !$this->dir ? "active": "in-active").'" style="font-size:large;position: relative; top: -2px;"></i>'
                        :
                        '<img src="'. $this->imagedir. '/down'.
                        (($this->sort == $i) && !$this->dir ? "active" : "").
                        '.png" width="13" height="13" alt="v" title="" border="0" />'),
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
                    $this->cmdSetDir, '=1">',
                        ($this->use_icon_font ?
                        '<i class="fa fa-sort-desc '.(($this->sort == $i) && $this->dir ? "active" : "in-active").'" style="font-size:large;position: relative; top: 5px;"></i>'
                        :
                        '<img src="'. $this->imagedir. '/up'.
                        (($this->sort == $i) && $this->dir ? "active" : "").
                        '.png" width="13" height="13" alt="^" title="" border="0" />'),
                    '</a>';
            echo '</th>';
        }
        echo "</tr></thead>";
    }

    function drawData(): void {
        echo '<tbody>';
        $this->row = 0;
        while (($data = mysqli_fetch_row($this->result))) {
            if (($this->mode == PHPMYSQLGRID_DELETEMODE)
                && ($_REQUEST[$this->varDeleteID] == $data[0])) {
                $headstyle = $this->style . 'actiondelete';
                $datastyle = $this->style . 'datadelete' . ($this->row % 2);
            } else {
                $headstyle = $this->style . 'action';
                $datastyle = $this->style . 'data' . ($this->row % 2);
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
                    $this->varDeleteID, '=', $data[0], '">',
                        ($this->use_icon_font ?
                        '<i class="fa fa-check" title="'.$this->convertToHtmlEntities($this->txtConfirm).'" style="padding-right: 0.75em;"></i>'
                        :
                        '<img hspace="1" src="'. $this->imagedir. '/confirm.png" alt="'.
                        $this->convertToHtmlEntities($this->txtConfirm). '" title="'.
                        $this->convertToHtmlEntities($this->txtConfirm).
                        '" width="13" height="13" border="0" />'),
                    '</a>',
                    '<a href="', $_SERVER["PHP_SELF"], '?',
                    $this->cmdCancel, '=1">',
                        ($this->use_icon_font ?
                        '<i class="fa fa-times" title="'.$this->convertToHtmlEntities($this->txtCancel).'" style="  red;"></i>'
                        :
                        '<img hspace="1" src="'. $this->imagedir. '/cancel.png" alt="'.
                        $this->convertToHtmlEntities($this->txtCancel). '" title="'.
                        $this->convertToHtmlEntities($this->txtCancel).
                        '" width="13" height="13" border="0" />'),
                    '</a>';
            } else {
                if ($this->can_edit) {
                    echo
                        '<a href="', $_SERVER["PHP_SELF"], '?',
                        $this->cmdEdit, '=1&amp;', $this->varEditID,
                        '=', $data[0], '">',
                            ($this->use_icon_font ?
                            '<i class="fa fa-pencil" title="'.$this->convertToHtmlEntities($this->txtEdit).'" style="padding-right: 0.75em"></i>'
                            :
                            '<img hspace="1" src="'. $this->imagedir. '/edit.png" alt="'.
                            $this->convertToHtmlEntities($this->txtEdit). '" title="'.
                            $this->convertToHtmlEntities($this->txtEdit).
                            '" border="0" width="13" height="13" align="middle" />'),
                        '</a>';
                }
                if ($this->can_delete) {
                    echo
                        '<a href="', $_SERVER["PHP_SELF"], '?',
                        $this->cmdDelete, '=1&amp;', $this->varDeleteID,
                        '=', $data[0], '">',
                            ($this->use_icon_font ?
                            '<i class="fa fa-minus" title="'.$this->convertToHtmlEntities($this->txtDelete).'"></i>'
                            :
                            '<img hspace="1" src="'. $this->imagedir. '/delete.png" alt="'.
                            $this->convertToHtmlEntities($this->txtDelete). '" title="'.
                            $this->convertToHtmlEntities($this->txtDelete).
                            '" border="0" width="13" height="13" align="middle" />'),
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
                            $text = $text ? $this->txtYes : $this->txtNo;
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
                if (isset($this->columns[$i]["convert_output"]))
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
        switch ($this->mode) {
            case PHPMYSQLGRID_EDITMODE:
                $headstyle = $this->style . 'actionedit';
                $datastyle = $this->style . 'dataedit' . ($this->row % 2);
                break;
            case PHPMYSQLGRID_ADDMODE:
                $headstyle = $this->style . 'actionadd';
                $datastyle = $this->style . 'dataadd' . ($this->row % 2);
                break;
            default:
                $headstyle = $this->style . 'action';
                $datastyle = $this->style . 'data' . ($this->row % 2);
        }
        echo
            '<tr>',
            '<td align="right" class="', $headstyle, '" nowrap="nowrap">';

        if ($this->mode == PHPMYSQLGRID_EDITMODE) {
            echo
                '<input type="hidden" name="', $this->varEditID, '" value="',
                $_REQUEST[$this->varEditID], '">',
                ($this->use_icon_font ?
                '<input type="hidden" name="' . $this->cmdConfirmEdit. '" value="true" />'.
                '<a href="#" onclick="document.getElementById(\''.$this->name.'_form\').submit(); return false;">'.
                  '<i class="fa fa-check" style="padding-right: 0.75em;" title="' .
                  $this->convertToHtmlEntities($this->txtConfirm) . '"></i>'.
                '</a>'
                :
                '<input type="image" src="'.$this->imagedir.'/confirm.png" alt="'.
                    $this->convertToHtmlEntities($this->txtConfirm). '" title="' .
                    $this->convertToHtmlEntities($this->txtConfirm).
                    '" style="width:13px;height:13px;margin:0px 1px" name="'.
                    $this->cmdConfirmEdit. '[]">'),
                '<a href="', $_SERVER["PHP_SELF"], "?",
                $this->cmdCancel, '=1">',
                    ($this->use_icon_font ?
                    '<i class="fa fa-times" title="'.$this->convertToHtmlEntities($this->txtCancel).'"></i>'
                    :
                    '<img hspace="1" src="'. $this->imagedir. '/cancel.png" alt="'.
                    $this->convertToHtmlEntities($this->txtCancel). '" title="'.
                    $this->convertToHtmlEntities($this->txtCancel).
                    '" width="13" height="13" border="0" />'),
                '</a>',
                '</td>';
        } else {
            echo
                ($this->use_icon_font ?
                '<input type="hidden" name="' . $this->cmdConfirmAdd. '" value="true" />'.
                '<a href="#" onclick="document.getElementById(\''.$this->name.'_form\').submit(); return false;">'.
                  '<i class="fa fa-check" style="padding-right: 0.75em;" title="' .
                  $this->convertToHtmlEntities($this->txtConfirm) . '"></i>'.
                '</a>'
                :
                '<input type="image" src="'.  $this->imagedir.
                    '/confirm.png" alt="'.
                    $this->convertToHtmlEntities($this->txtConfirm). '" title="'.
                    $this->convertToHtmlEntities($this->txtConfirm).
                    '" style="width:13px;height:13px;margin:0px 1px" name="'.
                    $this->cmdConfirmAdd. '[]">'),
                '<a href="', $_SERVER["PHP_SELF"], "?",
                $this->cmdCancel, '=1">',
                    ($this->use_icon_font ?
                    '<i class="fa fa-times" title="'.$this->convertToHtmlEntities($this->txtCancel).'"></i>'
                    :
                    '<img hspace="1" src="'. $this->imagedir. '/cancel.png" alt="'.
                    $this->convertToHtmlEntities($this->txtCancel). '" title="'.
                    $this->convertToHtmlEntities($this->txtCancel).
                    '" width="13" height="13" border="0" />'),
                '</a>';
        }
        for ($i = 0; $i < count($this->columns); $i++) {
            echo '<td class="', $datastyle, '"';
            if (isset($this->columns[$i]["align"]))
                echo ' align="', $this->columns[$i]["align"], '"';
            echo '>';
            switch($this->columns[$i]["type"]) {
                case PHPMYSQLGRID_LOOKUP:
                    echo
                        '<select class="',
                        $this->style,
                        '" name="', $this->cmdSetData, '[', $i, ']"';
                    if (isset($this->columns[$i]["width"]))
                        echo
                            ' style="width:', $this->columns[$i]["width"],
                            'px;"';
                    echo '>';
                    if (!($lookup = mysqli_query( $this->db, sprintf(
                        "SELECT %s, %s FROM %s%s",
                        $this->columns[$i]["lookup_primary"],
                        $this->columns[$i]["lookup_field"],
                        $this->columns[$i]["lookup_table"],
                        isset($this->columns[$i]["lookup_filter"]) ? " WHERE "
                            . $this->columns[$i]["lookup_filter"] : ""
                    )))) trigger_error(mysqli_error($this->db), E_USER_ERROR);
                    if ($lookup === true)
                        trigger_error("Unexpected mysqli result type", E_USER_ERROR);
                    while ($lookup_data = mysqli_fetch_row($lookup)) {
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
                    mysqli_free_result($lookup);
                    echo '</select>';
                    break;
                case PHPMYSQLGRID_SELECTION:
                    echo
                        '<select class="',
                        $this->style,
                        '" name="', $this->cmdSetData, '[', $i, ']"';
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
                        '<input class="', $this->style,
                        '" type="password" value="';
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
                        '<input class="', $this->style,
                        '" type="checkbox" value="1"';
                    if ($data && $data[$i + $this->countPrimaries()])
                        echo 'checked="checked"';
                    else if (!$data && isset($this->columns[$i]["default"]) && $this->columns[$i]["default"] == 1)
                        echo 'checked="checked"';
                    echo '" name="', $this->cmdSetData, '[', $i, ']">';
                    break;
                case PHPMYSQLGRID_MULTILINETEXT:
                    echo
                        '<textarea class="', $this->style,
                        '" name="', $this->cmdSetData, '[', $i, ']"';
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
                        $this->txtURL, '&nbsp;<input type="text" class="', $this->style,
                        '" name="', $this->cmdSetURL, '[', $i, ']"';
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
                        '<input class="', $this->style,
                        '" type="text" value="';
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
            '<td align="right" class="', $this->style, 'action">';
        // Draw Add Button if wanted
        if ($this->can_add) {
            echo
                '<a href="', $_SERVER["PHP_SELF"], '?', $this->cmdAdd, '=1#',$this->name,'_bottom" class="add-button">',
                    ($this->use_icon_font ?
                    '<i class="fa fa-plus" title="'.$this->convertToHtmlEntities($this->txtAdd).'"></i>'
                    :
                    '<img src="'. $this->imagedir. '/add.png" alt="'.
                    $this->convertToHtmlEntities($this->txtAdd). '" title="'.
                    $this->convertToHtmlEntities($this->txtAdd).
                    '" width="13" height="13" border="0" />'),
                '</a>';
        } else echo '&nbsp;';
        echo
            '</td>';

        echo
            '<td class="', $this->style, 'navigation" style="padding:0px" colspan="', count($this->columns), '">';
        if ($this->can_navigate) {
            $pages = ceil($this->rows / $this->limit);
            echo
                '<table border="0" cellspacing="0" cellpadding="0" class="page-navigation">',
                '<tr>',
                '<td align="right" class="', $this->style, 'navigation">';
            if ($this->page > 3) echo
                '<a href="', $_SERVER["PHP_SELF"], '?', $this->cmdSetPage,
                    '=1">1</a>&nbsp;';
            if ($this->page > 4)
                echo '...&nbsp;';
            for ($i = max(1, $this->page - 2); $i <= min($pages, $this->page + 2); $i++) {
                if ($i == $this->page)
                    echo $i, '&nbsp;';
                else
                    echo '<a href="', $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=' ,
                        $i, '">', $i, '</a> ';
            }
            if ($this->page < $pages - 3)
                echo '...&nbsp;';
            if ($this->page < $pages - 2)
                echo '<a href="', $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=',
                    $pages, '">', $pages, '</a> ';
            echo
                '</td><td class="', $this->style, 'navigation next-prev-navigation">';
                if ($this->page > 1) echo
                    '<a href="', $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=',
                    $this->page - 1, '">',
                    $this->convertToHtmlEntities($this->txtPrevious), '</a>&nbsp;';
                else
                    echo $this->convertToHtmlEntities($this->txtPrevious), '&nbsp;';
                if ($this->page < $pages) echo
                    '<a href="', $_SERVER["PHP_SELF"], '?', $this->cmdSetPage, '=',
                    $this->page + 1, '">',
                    $this->convertToHtmlEntities($this->txtNext), '</a>&nbsp;';
                else
                    echo $this->convertToHtmlEntities($this->txtNext), '&nbsp;';
                echo
                    '</td>',
                '</tr>',
                '</table>';
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
                $this->columns[$i]["type"] = PHPMYSQLGRID_TEXTBUTTON;
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
