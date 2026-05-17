<?php
// +----------------------------------------------------------------------+
// | phpMySQLGrid                                                         |
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

namespace PhpMySQLGrid;

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

/**
 * Renders a configurable database grid with built-in CRUD, filtering, sorting, and pagination.
 *
 * The class is designed as a reusable widget for PHP applications that need quick table-based
 * data management. It can connect using internal connection settings or an injected PDO instance,
 * supports multiple field types (text, lookup, selection, password, multiline text, file, boolean),
 * and provides lifecycle hooks for custom add/edit/delete behavior.
 *
 * Output generation is handled by execute(), which orchestrates request processing, data loading,
 * and HTML rendering (header, captions, rows, edit/add controls, and navigation footer).
 *
 * Public configuration is intentionally exposed via dynamic properties for backwards compatibility.
 *
 * @property string $hostname Database host used for internal connection creation. Default: "localhost".
 * @property int $port Database port used for internal connection creation. Default: 3128.
 * @property string $username Database user used for internal connection creation.
 * @property string $password Database password used for internal connection creation.
 * @property string $database Database name used for internal connection creation.
 * @property string $table Database table name rendered by the grid.
 *
 * @property string|array<int, string> $primary Primary key column name or list for composite keys.
 * @property string $style CSS class prefix used for generated markup. Default: "phpmysqlgrid".
 * @property string|string[] $cssClass Additional custom CSS class(es) appended to the table. Accepts a string or array of strings.
 * @property array<int, array<string, mixed>> $columns Grid column configuration.
 * @property array<int, array<string, mixed>> $actions Extra row action button definitions.
 * @property int $limit Rows shown per page. Default: 10.
 * @property string $name Grid instance name used for generated query/session keys and for id attributes. Must be unique. Default: "phpmysqlgrid".
 *
 * @property bool $can_add Enables add-row mode and controls. Default: true.
 * @property bool $can_delete Enables delete controls. Default: true.
 * @property bool $can_edit Enables edit controls. Default: true.
 * @property bool $can_sort Enables sortable captions. Default: true.
 * @property bool $can_navigate Enables footer pagination. Default: true.
 * @property bool $can_filter Enables per-column filter inputs. Default: true.
 *
 * @property string $filter Optional raw SQL filter fragment for advanced filtering. Default: "".
 * @property int $filter_size Default filter input size. Default: 8.
 * @property int $default_sort_column Zero-based default sort column. Default: 0.
 * @property int $default_sort_direction Default sort direction (0 asc, 1 desc). Default: 0.
 * @property array<string, mixed> $add_values Additional values always inserted during add.
 * @property array<int, array<string, mixed>> $lookups Lookup definitions used by columns.
 * @property string $charset Charset used by HTML entity escaping. Default: "UTF-8".
 *
 * @property bool $allow_url_import Whether to allow URL-based file imports. Default: false (disabled for security).
 * @property int|null $max_file_size Maximum file upload size in bytes. Default: null (no limit).
 * @property array<int, string> $allowed_file_extensions List of allowed file extensions (e.g. ['pdf', 'doc']). Empty array allows all. Default: [].
 * @property array<int, string> $allowed_file_mime_types List of allowed MIME types detected via finfo (e.g. ['image/jpeg', 'image/png']). Empty array allows all. Default: [].
 * @property array<int, string> $allowed_url_domains Allowlist of trusted hostnames for URL imports (e.g. ['cdn.example.com']). Empty array allows all hosts (when allow_url_import is true). Default: [].
 * @property bool $csrf_protection_enabled Enables CSRF validation for state-changing confirm actions. Default: false.
 * @property string $csrf_token_field Base field name used for CSRF hidden input (grid name prefix is added automatically). Default: "csrf_token".
 *
 * @property callable|false $delete_before Hook called before delete. Default: false.
 * @property callable|false $delete_after Hook called after delete. Default: false.
 * @property callable|false $add_before Hook called before add. Default: false.
 * @property callable|false $add_after Hook called after add. Default: false.
 * @property callable|false $edit_before Hook called before edit. Default: false.
 * @property callable|false $edit_after Hook called after edit. Default: false.
 *
 * @property string $txtPrevious Label for previous-page navigation. Default: "Previous Page".
 * @property string $txtNext Label for next-page navigation. Default: "Next Page".
 * @property string $txtDelete Label for delete action. Default: "Delete Entry".
 * @property string $txtAdd Label for add action. Default: "Add Entry".
 * @property string $txtEdit Label for edit action. Default: "Edit Entry".
 * @property string $txtConfirm Label for confirm action. Default: "Confirm Changes".
 * @property string $txtCancel Label for cancel action. Default: "Cancel Changes".
 * @property string $txtYes Label for boolean yes values. Default: "Yes".
 * @property string $txtNo Label for boolean no values. Default: "No".
 * @property string $txtFileTrue Label for present file values. Default: "File present".
 * @property string $txtFileFalse Label for missing file values. Default: "No file present".
 * @property string $txtFile Label for file upload control. Default: "File".
 * @property string $txtURL Label for URL file source control. Default: "URL".
 * @property string $txtCsrfError Error message shown when CSRF validation fails. Default: "Security check failed. Please try again.".
 * @property string $txtPaginationLabel ARIA label for pagination navigation. Default: "Pagination".
 * @property string $txtSortAsc ARIA/title label for ascending sort control. Default: "Sort ascending".
 * @property string $txtSortDesc ARIA/title label for descending sort control. Default: "Sort descending".
 *
 * @property string $svgIconEdit Inline SVG markup for edit action icon.
 * @property string $svgIconDelete Inline SVG markup for delete action icon.
 * @property string $svgIconConfirm Inline SVG markup for confirm action icon.
 * @property string $svgIconCancel Inline SVG markup for cancel action icon.
 * @property string $svgIconAdd Inline SVG markup for add action icon.
 * @property string $svgSortAscActive Inline SVG markup for active ascending sort icon.
 * @property string $svgSortAscInactive Inline SVG markup for inactive ascending sort icon.
 * @property string $svgSortDescActive Inline SVG markup for active descending sort icon.
 * @property string $svgSortDescInactive Inline SVG markup for inactive descending sort icon.
 * @property string $svgNavPrev Inline SVG markup for previous-page icon.
 * @property string $svgNavNext Inline SVG markup for next-page icon.
 * @property string $svgBoolTrue Inline SVG markup for true boolean cell icon.
 * @property string $svgBoolFalse Inline SVG markup for false boolean cell icon.
 */
#[\AllowDynamicProperties]
class MySQLGrid {

    private bool $db_is_injected = false;
    private \PDO | null $db = null;
    private \PDO | null $db_connection = null;
    private string $db_driver = "pdo_mysql";
    /** @var array<int, string> */
    private array $frontendErrors = array();
    /** @internal @ignore */
    public int $mode = PHPMYSQLGRID_VIEWMODE;

    /**
     * Creates a grid instance with default configuration values.
     */
    public function __construct() {
        $this->hostname = "localhost";
        $this->port = 3306;
        $this->username = "root";
        $this->password = "";
        $this->database = "";
        $this->table = "";
        $this->primary = "";
        $this->style = "phpmysqlgrid";
        $this->cssClass = "";
        $this->columns = array();
        $this->actions = array();
        $this->limit = 10;
        $this->name = "phpmysqlgrid";
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

        // File upload security configuration
        $this->allow_url_import = false;  // Disabled by default to prevent SSRF attacks
        $this->max_file_size = null;      // No limit by default (can be set by user)
        $this->allowed_file_extensions = array();  // Empty array = allow all extensions
        $this->allowed_file_mime_types = array();  // Empty array = no MIME type restriction
        $this->allowed_url_domains = array();     // Empty array = any host allowed (when allow_url_import = true)
        $this->csrf_protection_enabled = false;
        $this->csrf_token_field = "csrf_token";

        $this->internationalize();
        $this->initSvgIcons();
    }

    private function isPostRequest(): bool {
        return isset($_SERVER["REQUEST_METHOD"]) && strtoupper((string)$_SERVER["REQUEST_METHOD"]) === "POST";
    }

    private function isCsrfProtectionEnabled(): bool {
        return isset($this->csrf_protection_enabled) && (bool)$this->csrf_protection_enabled;
    }

    private function getGridSessionKey(): string {
        return "phpMySQLGrid_" . $this->name;
    }

    private function getCsrfTokenFieldName(): string {
        $fieldName = isset($this->csrf_token_field) ? trim((string)$this->csrf_token_field) : "csrf_token";
        if ($fieldName === "") {
            $fieldName = "csrf_token";
        }

        $prefix = $this->name . "_";
        if (str_starts_with($fieldName, $prefix)) {
            return $fieldName;
        }

        return $prefix . $fieldName;
    }

    private function ensureCsrfToken(): void {
        if (!$this->isCsrfProtectionEnabled()) {
            return;
        }

        $sessionKey = $this->getGridSessionKey();
        if (!isset($_SESSION[$sessionKey]) || !is_array($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = array();
        }

        if (!isset($_SESSION[$sessionKey]["csrf_token"]) || !is_string($_SESSION[$sessionKey]["csrf_token"]) || $_SESSION[$sessionKey]["csrf_token"] === "") {
            try {
                $_SESSION[$sessionKey]["csrf_token"] = bin2hex(random_bytes(32));
            } catch (\Exception) {
                $this->frontendErrors[] = "Unable to initialize security token.";
            }
        }
    }

    private function addCsrfFailureError(): void {
        $error = isset($this->txtCsrfError) ? (string)$this->txtCsrfError : "Security check failed. Please try again.";
        if (!in_array($error, $this->frontendErrors, true)) {
            $this->frontendErrors[] = $error;
        }
    }

    private function validateCsrfToken(): bool {
        if (!$this->isCsrfProtectionEnabled()) {
            return true;
        }

        $this->ensureCsrfToken();
        $sessionToken = $_SESSION[$this->getGridSessionKey()]["csrf_token"] ?? "";
        if (!is_string($sessionToken) || $sessionToken === "") {
            $this->addCsrfFailureError();
            return false;
        }

        $tokenField = $this->getCsrfTokenFieldName();
        $submittedToken = $_POST[$tokenField] ?? null;
        if (!is_string($submittedToken) || $submittedToken === "") {
            $this->addCsrfFailureError();
            return false;
        }

        if (!hash_equals($sessionToken, $submittedToken)) {
            $this->addCsrfFailureError();
            return false;
        }

        return true;
    }

    private function renderCsrfTokenInput(): string {
        if (!$this->isCsrfProtectionEnabled()) {
            return "";
        }

        $this->ensureCsrfToken();
        $sessionToken = $_SESSION[$this->getGridSessionKey()]["csrf_token"] ?? "";
        if (!is_string($sessionToken) || $sessionToken === "") {
            return "";
        }

        return '<input type="hidden" name="'
            . $this->convertToHtmlEntities($this->getCsrfTokenFieldName())
            . '" value="'
            . $this->convertToHtmlEntities($sessionToken)
            . '">';
    }

    /**
     * Injects an existing database connection.
     *
     * Supported drivers are pdo, pdo_mysql, and pdo_sqlite.
      *
      * @param mixed $connection Existing database connection instance.
      * @param string $driver Connection driver identifier (pdo, pdo_mysql, pdo_sqlite).
     */
    public function setDatabaseConnection(mixed $connection, string $driver = "pdo_mysql"): void {
        $allowedDrivers = array("pdo", "pdo_mysql", "pdo_sqlite");
        if (!in_array($driver, $allowedDrivers, true)) {
            trigger_error("Unsupported database driver: " . $driver, E_USER_ERROR);
        }

        $this->db_connection = $connection;
        $this->db = $connection;
        $this->db_driver = $driver;
        $this->db_is_injected = true;
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
     * Validates one SQL identifier (for example table/column name) against a strict allowlist.
     *
     * This protects dynamic SQL parts that cannot be parameterized via prepared statements
     * (for example table names, column lists, ORDER BY fields, JOIN identifiers).
     *
     * Allowed forms:
     * - Simple identifiers: letters/underscore first, then letters/digits/underscore.
     * - Optional dotted identifiers when $allowQualified is true (for example schema.table).
     * - Optional backtick-wrapped identifiers for backwards compatibility.
     *
     * Allowed examples: 'users', 'display_name', 'myschema.mytable' (with $allowQualified=true), '`column`'.
     * Forbidden examples: 'users; DROP TABLE users; --', "col'injection", '0invalid', ''.
     *
     * @throws \InvalidArgumentException When the identifier is empty or contains unsafe characters/tokens.
     */
    private function assertSafeSqlIdentifier(string $identifier, string $context, bool $allowQualified = false): void {
        $trimmed = trim($identifier);
        if ($trimmed === "") {
            throw new \InvalidArgumentException("Unsafe SQL identifier detected in " . $context);
        }

        $parts = $allowQualified ? explode(".", $trimmed) : array($trimmed);
        foreach ($parts as $part) {
            $segment = trim((string)$part);
            if ($segment === "") {
                throw new \InvalidArgumentException("Unsafe SQL identifier detected in " . $context);
            }

            // Allow backtick-quoted identifiers for backward compatibility.
            if (str_starts_with($segment, "`") || str_ends_with($segment, "`")) {
                if (!(str_starts_with($segment, "`") && str_ends_with($segment, "`") && strlen($segment) > 2)) {
                    throw new \InvalidArgumentException("Unsafe SQL identifier detected in " . $context);
                }

                $unquoted = substr($segment, 1, -1);
                if ($unquoted === "" || str_contains($unquoted, "`") || str_contains($unquoted, "\0")) {
                    throw new \InvalidArgumentException("Unsafe SQL identifier detected in " . $context);
                }
                continue;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $segment)) {
                throw new \InvalidArgumentException("Unsafe SQL identifier detected in " . $context);
            }
        }
    }

    /**
     * Runs identifier validation for all dynamic SQL identifier sources used by the grid.
     *
     * Checked sources:
     * - table + primary key definitions
     * - column field names (including LOOKUP metadata)
     * - global lookup definitions
     * - add_values keys used as INSERT column names
     *
     * This method is intentionally called before SQL query assembly in read/write code paths,
     * so invalid identifiers fail closed before any dynamic SQL is built.
     *
     * Example: a grid with $table='orders', $primary='id', columns [['field'=>'customer_name']] passes.
     * Example: a grid with $table='orders; DROP TABLE orders; --' throws \InvalidArgumentException.
     *
     * @throws \InvalidArgumentException When any configured identifier is unsafe.
     */
    private function validateSqlIdentifiers(): void {
        $this->assertSafeSqlIdentifier((string)$this->table, "table", true);

        $primaryColumns = is_array($this->primary) ? $this->primary : array($this->primary);
        foreach ($primaryColumns as $index => $primaryColumn) {
            $this->assertSafeSqlIdentifier((string)$primaryColumn, "primary[" . $index . "]");
        }

        foreach ($this->columns as $index => $column) {
            if (!isset($column["field"])) {
                throw new \InvalidArgumentException("Unsafe SQL identifier detected in columns[" . $index . "].field");
            }
            $this->assertSafeSqlIdentifier((string)$column["field"], "columns[" . $index . "].field");

            $columnType = isset($column["type"]) ? (int)$column["type"] : PHPMYSQLGRID_TEXT;
            if ($columnType === PHPMYSQLGRID_LOOKUP) {
                if (!isset($column["lookup_table"], $column["lookup_primary"], $column["lookup_field"])) {
                    throw new \InvalidArgumentException("Unsafe SQL identifier detected in columns[" . $index . "].lookup");
                }

                $this->assertSafeSqlIdentifier((string)$column["lookup_table"], "columns[" . $index . "].lookup_table", true);
                $this->assertSafeSqlIdentifier((string)$column["lookup_primary"], "columns[" . $index . "].lookup_primary");
                $this->assertSafeSqlIdentifier((string)$column["lookup_field"], "columns[" . $index . "].lookup_field");
            }
        }

        foreach ($this->lookups as $index => $lookup) {
            if (!isset($lookup["lookup_table"], $lookup["lookup_primary"])) {
                throw new \InvalidArgumentException("Unsafe SQL identifier detected in lookups[" . $index . "]");
            }

            $this->assertSafeSqlIdentifier((string)$lookup["lookup_table"], "lookups[" . $index . "].lookup_table", true);
            $this->assertSafeSqlIdentifier((string)$lookup["lookup_primary"], "lookups[" . $index . "].lookup_primary");
        }

        foreach ($this->add_values as $key => $_value) {
            $this->assertSafeSqlIdentifier((string)$key, "add_values[" . (string)$key . "]");
        }
    }

    /**
     * Validates an uploaded file against configured security rules.
     *
    * @param array<string, mixed>|false $fileData File data from $_FILES array (keys: "name", "type", "size", "tmp_name", "error").
     * @return bool True if file passes validation, false otherwise.
     * @internal
     */
    private function validateUploadedFile(mixed $fileData): bool {
        if (!is_array($fileData)) {
            return false;
        }

        // Check for upload errors
        if (isset($fileData["error"]) && $fileData["error"] !== UPLOAD_ERR_OK) {
            $this->reportValidationFailure("File upload error: " . $this->getUploadErrorMessage($fileData["error"]));
            return false;
        }

        // Check file size if max_file_size is set
        if ($this->max_file_size !== null && isset($fileData["size"])) {
            if ((int)$fileData["size"] > $this->max_file_size) {
                $this->reportValidationFailure(
                    "File size (" . $fileData["size"] . " bytes) exceeds maximum allowed size (" . $this->max_file_size . " bytes)"
                );
                return false;
            }
        }

        // Check file extension if allowed_file_extensions is set
        if (!empty($this->allowed_file_extensions) && isset($fileData["name"])) {
            $filename = (string)$fileData["name"];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowed_file_extensions, true)) {
                $this->reportValidationFailure(
                    "File extension '." . $ext . "' is not allowed. Allowed extensions: " . implode(", ", $this->allowed_file_extensions)
                );
                return false;
            }
        }

        // Verify tmp_name is a valid uploaded file
        if (!isset($fileData["tmp_name"]) || !is_uploaded_file((string)$fileData["tmp_name"])) {
            $this->reportValidationFailure("Invalid uploaded file");
            return false;
        }

        // Check MIME type if allowed_file_mime_types is set (uses finfo on the actual file, not client-supplied type)
        if (!empty($this->allowed_file_mime_types)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file((string)$fileData["tmp_name"]);
            if ($mime === false || !in_array($mime, $this->allowed_file_mime_types, true)) {
                $this->reportValidationFailure(
                    "MIME type '" . ($mime ?: "unknown") . "' is not allowed. Allowed types: " . implode(", ", $this->allowed_file_mime_types)
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Validates a URL for safe file import (only if allow_url_import is enabled).
     *
     * @param string $url URL to validate and open.
     * @return bool True if URL passes validation, false otherwise.
     * @internal
     */
    private function validateFileUrl(string $url): bool {
        // URL imports are disabled by default for security (SSRF prevention)
        if (!$this->allow_url_import) {
            $this->reportValidationFailure("URL-based file imports are disabled for security. Set allow_url_import=true to enable.");
            return false;
        }

        // Parse and validate URL
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed["scheme"]) || !isset($parsed["host"])) {
            $this->reportValidationFailure("Invalid URL format");
            return false;
        }

        // Only allow http and https schemes
        $scheme = strtolower((string)$parsed["scheme"]);
        if (!in_array($scheme, array("http", "https"), true)) {
            $this->reportValidationFailure("URL scheme '" . $scheme . "' is not allowed. Only http and https are supported.");
            return false;
        }

        // Prevent localhost/private IP ranges to mitigate SSRF attacks
        $host = (string)$parsed["host"];
        $ip = gethostbyname($host);
        if ($this->isPrivateIpAddress($ip)) {
            $this->reportValidationFailure("URL points to a private IP address. Private IP ranges are not allowed for security.");
            return false;
        }

        // Check domain allowlist if configured
        if (!empty($this->allowed_url_domains) && !in_array($host, $this->allowed_url_domains, true)) {
            $this->reportValidationFailure(
                "Host '" . $host . "' is not in the allowed domains list."
            );
            return false;
        }

        return true;
    }

    /**
     * Checks if an IP address is in a private range.
     *
     * @param string $ip IP address to check.
     * @return bool True if IP is private/reserved, false otherwise.
     * @internal
     */
    private function isPrivateIpAddress(string $ip): bool {
        // Check if it's a valid IP first
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;  // Treat invalid as private for safety
        }

        // Private IPv4 ranges
        $privateRanges = array(
            "10.0.0.0|10.255.255.255",
            "127.0.0.0|127.255.255.255",
            "172.16.0.0|172.31.255.255",
            "192.168.0.0|192.168.255.255",
        );

        // Check if IP is in any private range
        foreach ($privateRanges as $range) {
            list($start, $end) = explode("|", $range);
            if (ip2long($ip) >= ip2long($start) && ip2long($ip) <= ip2long($end)) {
                return true;
            }
        }

        // Also check for localhost and special addresses
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }

    /**
     * Gets a user-friendly message for a PHP file upload error code.
     *
     * @param int $errorCode PHP upload error constant.
     * @return string Error message.
     * @internal
     */
    private function getUploadErrorMessage(int $errorCode): string {
        // TODO: make messages customizable via properties for internationalization
        $messages = array(
            UPLOAD_ERR_OK => "No error",
            UPLOAD_ERR_INI_SIZE => "File exceeds upload_max_filesize directive",
            UPLOAD_ERR_FORM_SIZE => "File exceeds MAX_FILE_SIZE form directive",
            UPLOAD_ERR_PARTIAL => "File was only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file was uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Cannot write file to disk",
            UPLOAD_ERR_EXTENSION => "PHP extension stopped the file upload",
        );

        return $messages[$errorCode] ?? "Unknown upload error";
    }

    private function getPrimaryColumnForSingleColumnContext(): string {
        if (is_array($this->primary)) {
            return isset($this->primary[0]) ? (string)$this->primary[0] : "";
        }

        return (string)$this->primary;
    }

    /**
     * @return array<int, mixed>|false
     */
    private function fetchResultRow(): array|false {
        if ($this->result instanceof \PDOStatement) {
            $row = $this->result->fetch(\PDO::FETCH_NUM);
            return is_array($row) ? $row : false;
        }

        return false;
    }

    private function prepareDataWithPdo(): void {
        if (!($this->db instanceof \PDO)) {
            trigger_error("PDO connection expected for injected PDO driver", E_USER_ERROR);
        }

        $this->validateSqlIdentifiers();

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
                $this->getPrimaryColumnForSingleColumnContext()
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
            $this->page = (int)ceil((int)$this->rows / $this->limit);
            $_SESSION[$this->getGridSessionKey()]["page"] = $this->page;
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
        $statement = $this->executePdoStatement($query);
        $rows = $statement->fetchAll(\PDO::FETCH_NUM);
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
        $this->txtCsrfError = "Security check failed. Please try again.";
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

    /**
     * @internal
     * @ignore
     */
    public function connect(): void {
        if ($this->db_is_injected) {
            $this->db = $this->db_connection;
            return;
        }

        if ($this->hostname === "" || $this->port <= 0 || $this->database === "" || $this->username === "") {
            trigger_error("Invalid database connection parameters", E_USER_ERROR);
        }
        $dsn = sprintf(
            "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
            $this->hostname,
            (int)$this->port,
            $this->database
        );
        $this->db = new \PDO($dsn, $this->username, $this->password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
    }

    /**
     * @internal
     * @ignore
     */
    public function disconnect(): void {
        if ($this->db_is_injected) {
            return;
        }

        $this->db = null;
    }

    /**
     * Auto-populates grid columns from the configured table metadata.
     */
    public function useAllColumns(): void {
        $this->assertSafeSqlIdentifier((string)$this->table, "table", true);
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
    }

    /**
     * @internal
     * @ignore
     */
    public function countPrimaries(): int {
        if (is_array($this->primary))
            return count($this->primary);
        else
            return 1;
    }

    /**
     * @internal
     * @ignore
     */
    public function prepareData(): void {
        $this->prepareDataWithPdo();
    }

    /**
     * @internal
     * @ignore
     */
    public function unprepareData(): void {
        if ($this->result instanceof \PDOStatement) {
            $this->result->closeCursor();
        }
    }

    /**
     * @internal
     * @ignore
     */
    public function prepareQueryVars(): void {
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

    private function processSession(): void {
        $sessionKey = $this->getGridSessionKey();

        if (!isset($this->page)) {
            if (isset($_SESSION[$sessionKey]["page"]))
                $this->page = $_SESSION[$sessionKey]["page"];
            else {
                $this->page = 1;
                $_SESSION[$sessionKey]["page"] = 1;
            }
        } else $_SESSION[$sessionKey]['page'] = $this->page;
        for ($i = 0; $i < count($this->columns); $i++) {
            if (isset($_SESSION[$sessionKey]['filter'][$i])) {
                $this->columns[$i]['active_filter'] =
                    $_SESSION[$sessionKey]['filter'][$i];
            } else if (isset($this->columns[$i]['filter'])) {
                $this->columns[$i]['active_filter'] =
                    $this->columns[$i]['filter'];
            } else {
                $this->columns[$i]['active_filter'] = '';
            }
        }
        if (isset($_SESSION[$sessionKey]["sort"]))
            $this->sort = min(count($this->columns) - 1,
                $_SESSION[$sessionKey]["sort"]);
        else {
            $this->sort = $this->default_sort_column;
            $_SESSION[$sessionKey]["sort"] = $this->default_sort_column;
        }
        if (isset($_SESSION[$sessionKey]["dir"]))
            $this->dir = $_SESSION[$sessionKey]["dir"];
        else {
            $this->dir = $this->default_sort_direction;
            $_SESSION[$sessionKey]["dir"] = $this->default_sort_direction;
        }

        $this->ensureCsrfToken();
    }

    private function addDataWithPdo(mixed $data): void {
        $this->validateSqlIdentifiers();
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
        $this->validateSqlIdentifiers();
        $query = sprintf(
            "DELETE FROM %s where %s=:id",
            $this->table,
            $this->getPrimaryColumnForSingleColumnContext()
        );

        $this->executePdoStatement($query, array(":id" => $id));
    }

    private function editDataWithPdo(mixed $id, mixed $data): void {
        $this->validateSqlIdentifiers();
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
            $this->getPrimaryColumnForSingleColumnContext()
        );

        $this->executePdoStatement($query, $params);
    }

    /**
     * @internal
     * @ignore
     */
    public function addData(mixed $data): void {
        if (!$this->can_add) return;

        $hook = $this->add_before;
        if (is_callable($hook))
            if (!$hook($this, $data)) return;

        $this->addDataWithPdo($data);
    }

    /**
     * @internal
     * @ignore
     */
    public function deleteData(mixed $id): void {
        if (!$this->can_delete) return;

        $hook = $this->delete_before;
        if (is_callable($hook))
            if (!$hook($this, $id)) return;

        $this->deleteDataWithPdo($id);

        $hook = $this->delete_after;
        if (is_callable($hook)) $hook($this, $id);
    }

    /**
     * @internal
     * @ignore
     */
    public function editData(mixed $id, mixed $data): void {
        if (!$this->can_edit) return;

        $hook = $this->edit_before;
        if (is_callable($hook))
            if (!$hook($this, $id, $data)) return;

        $this->editDataWithPdo($id, $data);

        $hook = $this->edit_after;
        if (is_callable($hook))
            if (!$hook($this, $id, $data)) return;
    }

    private function processRequests(): void {
        $sessionKey = $this->getGridSessionKey();

        // Process SetPage command
        if (isset($_REQUEST[$this->cmdSetPage])) {
            $this->page = intval($_REQUEST[$this->cmdSetPage]);
            $_SESSION[$sessionKey]["page"] = $this->page;
        }

        // Process SetSort command
        if (isset($_REQUEST[$this->cmdSetSort])) {
            $this->sort = intval($_REQUEST[$this->cmdSetSort]);
            $_SESSION[$sessionKey]["sort"] = $this->sort;
        }

        // Process SetFilter command
        if (isset($_REQUEST[$this->cmdSetFilter])) {
            foreach ($_REQUEST[$this->cmdSetFilter] as $key => $value) {
                $this->columns[$key]['active_filter'] = stripslashes($value);
                $_SESSION[$sessionKey]["filter"][$key] = $this->columns[$key]['active_filter'];
            }
        }

        // Process SetDir command
        if (isset($_REQUEST[$this->cmdSetDir])) {
            $this->dir = intval($_REQUEST[$this->cmdSetDir]);
            $_SESSION[$sessionKey]["dir"] = $this->dir;
        }

        // Process data vars
        $data = array();
        if (isset($_POST[$this->cmdSetData])) {
            reset($_FILES);

            for ($i = 0; $i < count($this->columns); $i++) {
                switch ($this->columns[$i]["type"]) {
                    case PHPMYSQLGRID_FILE:
                        if (isset($_POST[$this->cmdClearFile][$i])) {
                            $data[$i] = false;
                        } else if (isset($_POST[$this->cmdSetURL][$i]) && $_POST[$this->cmdSetURL][$i]) {
                            // URL import - validate if enabled
                            $url = (string)$_POST[$this->cmdSetURL][$i];
                            if ($this->validateFileUrl($url)) {
                                $data[$i] = $url;
                            } else {
                                // Validation failed - skip this file
                                $data[$i] = false;
                            }
                        } else {
                            // Regular file upload - validate
                            $fileData = current($_FILES);
                            if ($fileData && isset($fileData["error"])) {
                                // Skip validation if no file was uploaded (optional file upload)
                                if ($fileData["error"] === UPLOAD_ERR_NO_FILE) {
                                    $data[$i] = false;
                                } else if ($this->validateUploadedFile($fileData)) {
                                    $data[$i] = $fileData;
                                } else {
                                    // Validation failed - skip this file
                                    $data[$i] = false;
                                }
                            } else {
                                // No file data available - treat as no upload
                                $data[$i] = false;
                            }
                        }
                        next($_FILES);
                        break;
                    default:
                        $data[$i] = $_POST[$this->cmdSetData][$i];
                }
            }
        }

        // Process Add command
        if (($this->can_add) && (isset($_REQUEST[$this->cmdAdd]))) {
            $this->mode = PHPMYSQLGRID_ADDMODE;
        }

        // Process ConfirmAdd command
        if (($this->can_add) && $this->isPostRequest() && (isset($_POST[$this->cmdConfirmAdd])) && $this->validateCsrfToken()) {
            $this->addData($data);
        }

        // Process Delete command
        if (($this->can_delete) && (isset($_REQUEST[$this->cmdDelete]))) {
            $this->mode = PHPMYSQLGRID_DELETEMODE;
        }

        // Process ConfirmDelete command
        if (($this->can_delete) && $this->isPostRequest() &&
            (isset($_POST[$this->cmdConfirmDelete])) && isset($_POST[$this->varDeleteID]) && $this->validateCsrfToken()) {
            $this->deleteData($_POST[$this->varDeleteID]);
        }

        // Process Edit command
        if (($this->can_edit) && (isset($_REQUEST[$this->cmdEdit]))) {
            $this->mode = PHPMYSQLGRID_EDITMODE;
        }

        // Process ConfirmEdit command
        if (($this->can_edit) && $this->isPostRequest() &&
            (isset($_POST[$this->cmdConfirmEdit])) && isset($_POST[$this->varEditID]) && $this->validateCsrfToken()) {
            $this->editData($_POST[$this->varEditID], $data);
        }
    }

    /**
     * Returns the HTML-escaped value of PHP_SELF, safe for use in href and action attributes.
     */
    private function selfUrl(): string {
        return htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES, $this->charset);
    }

    /**
     * Extracts preserved GET parameters (non-grid-specific) merged with grid parameters.
     *
     * @param array<string, string|int|float> $gridParams Grid parameters to merge.
     * @return array<string, string> Merged parameters.
     * @internal
     */
    protected function getPreservedParams(array $gridParams = []): array {
        $params = [];
        if (is_array($_GET)) {
            foreach ($_GET as $key => $value) {
                if (is_string($key) && strpos($key, $this->name . "_") === 0) {
                    continue;
                }
                if (is_scalar($value)) {
                    $params[(string)$key] = (string)$value;
                }
            }
        }
        foreach ($gridParams as $key => $value) {
            $params[(string)$key] = (string)$value;
        }
        return $params;
    }

    /**
     * Builds a URL with preserved query parameters (except MySQLGrid command parameters).
     *
     * Preserves existing GET parameters (e.g., theme, asset_mode) and adds new MySQLGrid parameters.
     * All grid-specific parameters are filtered out before adding new ones.
     *
     * @param array<string, string|int|float> $gridParams Grid-specific parameters to add.
     * @return string Full query string with preserved parameters and grid parameters.
     */
    private function buildUrl(array $gridParams): string {
        $params = $this->getPreservedParams($gridParams);
        return http_build_query($params, "", "&amp;");
    }

    /**
     * Creates a safe DOM id from user-configurable values (for example $this->name).
     */
    private function buildSafeDomId(string $value): string {
        $sanitized = preg_replace('/[^A-Za-z0-9_\-:.]/', '_', $value);
        if (!is_string($sanitized) || $sanitized === "") {
            return "mysqlgrid";
        }
        return $sanitized;
    }

    private function sanitizeActionUrl(string $url, string $type): string {
        $fallback = ($type === "href") ? "#" : "";

        $trimmed = trim($url);
        if ($trimmed === "") {
            return $fallback;
        }

        $scheme = parse_url($trimmed, PHP_URL_SCHEME);
        if (!is_string($scheme) || $scheme === "") {
            return $trimmed;
        }

        $allowedSchemes = array("http", "https");
        if (!in_array(strtolower($scheme), $allowedSchemes, true)) {
            return $fallback;
        }

        return $trimmed;
    }

    /**
     * @internal
     * @ignore
     */
    public function drawHeader(): void {
        // Check if a file upload is present in this grid. This is
        // important to switch to multipart/form-data encoding.
        $upload = false;
        for ($i = 0; $i < count($this->columns); $i++)
            if ($this->columns[$i]["type"] == PHPMYSQLGRID_FILE) {
                $upload = true;
                break;
            }
        // Support cssClass as string or array
        $cssClass = $this->cssClass;
        if (is_array($cssClass)) {
            $cssClass = implode(" ", $cssClass);
        }
        $tableClass = trim($this->style . ' ' . $cssClass);

        // Build form action with preserved GET parameters
        $formAction = $this->selfUrl();
        $preservedParams = $this->getPreservedParams();
        if (!empty($preservedParams)) {
            $formAction .= "?" . http_build_query($preservedParams, "", "&amp;");
        }

        $formId = $this->buildSafeDomId($this->name . "_form");

        echo
            '<form action="', $formAction, '" method="post" id="' , $this->convertToHtmlEntities($formId),'"',
            $upload ? ' enctype="multipart/form-data"' : '',
            '>',
            '<input type="image" style="width: 0; height: 0; border: none; visibility: hidden; position: absolute; left: -999px" />';

        if (!empty($this->frontendErrors)) {
            echo '<div class="', $this->style, '-error-summary" role="alert" aria-live="polite">',
                '<ul class="', $this->style, '-error-list">';
            foreach ($this->frontendErrors as $message) {
                echo '<li class="', $this->style, '-error-item">', $this->convertToHtmlEntities($message), '</li>';
            }
            echo '</ul></div>';
        }

        echo '<table class="', $tableClass ,'" border="0" cellspacing="1">';
    }

    /**
     * @internal
     * @ignore
     */
    public function drawFooter(): void {
        $bottomId = $this->buildSafeDomId($this->name . "_bottom");
        echo
            '</table>',
            '</form><a href="#" id="', $this->convertToHtmlEntities($bottomId), '"></a>';
    }

    /**
     * @internal
     * @ignore
     */
    public function drawCaptions(): void {
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
                    '<a href="', $this->selfUrl(), '?',
                    $this->buildUrl(array($this->cmdSetSort => $i, $this->cmdSetDir => 0)), '"',
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
                    '&nbsp;<a href="', $this->selfUrl(), '?',
                    $this->buildUrl(array($this->cmdSetSort => $i, $this->cmdSetDir => 1)), '"',
                    ' aria-label="', $this->convertToHtmlEntities($this->txtSortDesc),
                    '" title="', $this->convertToHtmlEntities($this->txtSortDesc), '">',
                        $this->renderIcon((($this->sort == $i) && $this->dir) ? $this->svgSortDescActive : $this->svgSortDescInactive, "sort-desc"),
                    '</a>';
            echo '</th>';
        }
        echo "</tr></thead>";
    }

    private function drawData(): void {
        $formId = $this->buildSafeDomId($this->name . "_form");
        echo '<tbody>';
        $this->row = 0;
        while (($data = $this->fetchResultRow()) !== false) {
            $rowClass = $this->style . '-cell--' . (($this->row % 2) ? 'odd' : 'even');
            if (($this->mode == PHPMYSQLGRID_DELETEMODE)
                && ($_REQUEST[$this->varDeleteID] == $data[0])) {
                $headstyle = $this->style . '-action ' . $rowClass . ' ' . $this->style . '-action--delete';
                $datastyle = $this->style . '-cell ' . $rowClass . ' ' . $this->style . '-cell--delete';
            } else {
                $headstyle = $this->style . '-action ' . $rowClass;
                $datastyle = $this->style . '-cell ' . $rowClass;
            }
            if (($this->mode == PHPMYSQLGRID_EDITMODE)
                && ($_REQUEST[$this->varEditID] == $data[0])) {
                $this->drawEditControls($data);
                continue;
            }
            echo
                '<tr data-id="', $this->convertToHtmlEntities($data[0]), '">',
                '<td class="', $headstyle, '" nowrap="nowrap" align="right">';
            if (($this->mode == PHPMYSQLGRID_DELETEMODE)
                && ($_REQUEST[$this->varDeleteID] == $data[0])) {
                echo
                    '<input type="hidden" name="', $this->cmdConfirmDelete, '" value="1">',
                    '<input type="hidden" name="', $this->varDeleteID, '" value="', $this->convertToHtmlEntities($data[0]), '">',
                    $this->renderCsrfTokenInput(),
                    '<a href="#" onclick="document.getElementById(\'', $formId, '\').submit(); return false;"',
                    ' aria-label="', $this->convertToHtmlEntities($this->txtConfirm),
                    '" title="', $this->convertToHtmlEntities($this->txtConfirm), '">',
                        $this->renderIcon($this->svgIconConfirm, "confirm"),
                    '</a>',
                    '<a href="', $this->selfUrl(), '?',
                    $this->buildUrl(array($this->cmdCancel => 1)), '"',
                    ' aria-label="', $this->convertToHtmlEntities($this->txtCancel),
                    '" title="', $this->convertToHtmlEntities($this->txtCancel), '">',
                        $this->renderIcon($this->svgIconCancel, "delete"),
                    '</a>';
            } else {
                if ($this->can_edit) {
                    echo
                        '<a href="', $this->selfUrl(), '?',
                        $this->buildUrl(array($this->cmdEdit => 1, $this->varEditID => $data[0])), '"',
                        '" aria-label="', $this->convertToHtmlEntities($this->txtEdit),
                        '" title="', $this->convertToHtmlEntities($this->txtEdit), '">',
                            $this->renderIcon($this->svgIconEdit, "edit"),
                        '</a>';
                }
                if ($this->can_delete) {
                    echo
                        '<a href="', $this->selfUrl(), '?',
                        $this->buildUrl(array($this->cmdDelete => 1, $this->varDeleteID => $data[0])), '"',
                        '" aria-label="', $this->convertToHtmlEntities($this->txtDelete),
                        '" title="', $this->convertToHtmlEntities($this->txtDelete), '">',
                            $this->renderIcon($this->svgIconDelete, "delete"),
                        '</a>';
                }
                foreach ($this->actions as $action) {
                    switch ($action["type"]) {
                        case PHPMYSQLGRID_IMAGEBUTTON:
                            $actionUrl = $this->sanitizeActionUrl((string)str_replace("<ID>", (string)$data[0], (string)$action["url"]), "href");
                            $actionImage = $this->sanitizeActionUrl((string)$action["image"], "src");
                            echo
                                '<a href="',
                                $this->convertToHtmlEntities($actionUrl),
                                '">',
                                '<img hspace="1" src="', $this->convertToHtmlEntities($actionImage), '" alt="',
                                $this->convertToHtmlEntities($action["caption"]), '" title="',
                                $this->convertToHtmlEntities($action["caption"]),
                                '" border="0" align="middle"';
                            if (isset($action["width"]))
                                echo ' width="', (int)$action["width"], '"';
                            if (isset($action["height"]))
                                echo ' height="', (int)$action["height"], '"';
                            echo '/></a>';
                            break;
                        default:
                            $actionUrl = $this->sanitizeActionUrl((string)str_replace("<ID>", (string)$data[0], (string)$action["url"]), "href");
                            echo
                                '&nbsp;<a href="',
                                $this->convertToHtmlEntities($actionUrl),
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
                $text = $data[$i + $this->countPrimaries()];
                $cellTypeClass = "";
                $type = $this->columns[$i]["type"] ?? PHPMYSQLGRID_TEXT;
                switch ($type) {
                    case PHPMYSQLGRID_TEXT:
                        $cellTypeClass = $this->style . "-cell--text";
                        break;
                    case PHPMYSQLGRID_BOOLEAN:
                        $cellTypeClass = $this->style . "-cell--boolean";
                        break;
                    case PHPMYSQLGRID_LOOKUP:
                        $cellTypeClass = $this->style . "-cell--lookup";
                        break;
                    case PHPMYSQLGRID_PASSWORD:
                        $cellTypeClass = $this->style . "-cell--password";
                        $text = PHPMYSQLGRID_PWDUMMY;
                        break;
                    case PHPMYSQLGRID_SELECTION:
                        $cellTypeClass = $this->style . "-cell--select";
                        break;
                    case PHPMYSQLGRID_MULTILINETEXT:
                        $cellTypeClass = $this->style . "-cell--multilinetext";
                        break;
                    case PHPMYSQLGRID_FILE:
                        $cellTypeClass = $this->style . "-cell--file";
                        if (isset($this->columns[$i]['convert_output']))
                            $text = $data[$i + $this->countPrimaries()];
                        else
                            $text = $data[$i + $this->countPrimaries()] ?
                                $this->txtFileTrue : $this->txtFileFalse;
                        break;
                }

                // Handle output converter
                if (isset($this->columns[$i]["convert_output"]))
                    $text = $this->columns[$i]["convert_output"]($this, $text, $i + $this->countPrimaries(), $data, false);
                else {
                    switch ($type) {
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

                echo '<td class="', $datastyle , ' ' , $cellTypeClass, '"';
                if (isset($this->columns[$i]["align"]))
                    echo ' align="', $this->convertToHtmlEntities($this->columns[$i]["align"]), '"';
                echo '>';

                // Trust converted output, otherwise htmlentity it.
                if (isset($this->columns[$i]["convert_output"]) || $type === PHPMYSQLGRID_BOOLEAN)
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
     * @internal
     * @ignore
     */
    public function drawEditControls(array|false $data = false): void {
        $formId = $this->buildSafeDomId($this->name . "_form");
        $rowClass = $this->style . '-cell--' . (($this->row % 2) ? 'odd' : 'even');
        switch ($this->mode) {
            case PHPMYSQLGRID_EDITMODE:
                $headstyle = $this->style . '-action ' . $rowClass . ' ' . $this->style . '-action--edit';
                $datastyle = $this->style . '-cell ' . $rowClass . ' ' . $this->style . '-cell--edit';
                break;
            case PHPMYSQLGRID_ADDMODE:
                $headstyle = $this->style . '-action ' . $rowClass . ' ' . $this->style . '-action--add';
                $datastyle = $this->style . '-cell ' . $rowClass . ' ' . $this->style . '-cell--add';
                break;
            default:
                $headstyle = $this->style . '-action ' . $rowClass;
                $datastyle = $this->style . '-cell ' . $rowClass;
        }
        echo
            '<tr>',
            '<td align="right" class="', $headstyle, '" nowrap="nowrap">';

        if ($this->mode == PHPMYSQLGRID_EDITMODE) {
            $editId = isset($_REQUEST[$this->varEditID]) && is_scalar($_REQUEST[$this->varEditID])
                ? (string)$_REQUEST[$this->varEditID]
                : "";
            echo
                '<input type="hidden" name="', $this->varEditID, '" value="',
                $this->convertToHtmlEntities($editId), '">',
                '<input type="hidden" name="' . $this->cmdConfirmEdit. '" value="true" />'.
                $this->renderCsrfTokenInput().
                '<a href="#" onclick="document.getElementById(\'' . $formId . '\').submit(); return false;" aria-label="' . $this->convertToHtmlEntities($this->txtConfirm) . '" title="' . $this->convertToHtmlEntities($this->txtConfirm) . '">'.
                    $this->renderIcon($this->svgIconConfirm, "confirm").
                '</a>',
                '<a href="', $this->selfUrl(), "?",
                $this->buildUrl(array($this->cmdCancel => 1)), '"',
                ' aria-label="', $this->convertToHtmlEntities($this->txtCancel),
                '" title="', $this->convertToHtmlEntities($this->txtCancel), '">',
                    $this->renderIcon($this->svgIconCancel, "delete"),
                '</a>',
                '</td>';
        } else {
            echo
                '<input type="hidden" name="' . $this->cmdConfirmAdd. '" value="true" />'.
                $this->renderCsrfTokenInput().
                '<a href="#" onclick="document.getElementById(\'' . $formId . '\').submit(); return false;" aria-label="' . $this->convertToHtmlEntities($this->txtConfirm) . '" title="' . $this->convertToHtmlEntities($this->txtConfirm) . '">'.
                    $this->renderIcon($this->svgIconConfirm, "confirm").
                '</a>',
                '<a href="', $this->selfUrl(), "?",
                $this->buildUrl(array($this->cmdCancel => 1)), '"',
                ' aria-label="', $this->convertToHtmlEntities($this->txtCancel),
                '" title="', $this->convertToHtmlEntities($this->txtCancel), '">',
                    $this->renderIcon($this->svgIconCancel, "delete"),
                '</a>';
        }
        for ($i = 0; $i < count($this->columns); $i++) {
            echo '<td class="', $datastyle, '"';
            if (isset($this->columns[$i]["align"]))
                echo ' align="', $this->convertToHtmlEntities($this->columns[$i]["align"]), '"';
            echo '>';
            switch($this->columns[$i]["type"]) {
                case PHPMYSQLGRID_LOOKUP:
                    echo
                        '<select class="',
                        $this->style, '-lookup"',
                        ' name="', $this->cmdSetData, '[', $i, ']"';
                    if (isset($this->columns[$i]["width"]))
                        echo
                            ' style="width:', (int)$this->columns[$i]["width"],
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
                            ' style="width:', (int)$this->columns[$i]["width"],
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
                        echo ' size="', (int)$this->columns[$i]["size"], '"';
                    if (isset($this->columns[$i]["maxlength"]))
                        echo
                            ' maxlength="', (int)$this->columns[$i]["maxlength"],
                            '"';
                    if (isset($this->columns[$i]["width"]))
                        echo
                            ' style="width:', (int)$this->columns[$i]["width"],
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
                        echo ' cols="', (int)$this->columns[$i]["size"], '"';
                    if (isset($this->columns[$i]["lines"]))
                        echo ' rows="', (int)$this->columns[$i]["lines"], '"';
                    $style = "";
                    if (isset($this->columns[$i]["width"]))
                        $style .= sprintf("width:%dpx;",
                            (int)$this->columns[$i]["width"]);
                    if (isset($this->columns[$i]["height"]))
                        $style .= sprintf("height:%dpx;",
                            (int)$this->columns[$i]["height"]);
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
                    $rawFileValue = ($this->mode === PHPMYSQLGRID_EDITMODE && $data) ? $data[$i + $this->countPrimaries()] : '';
                    if ($this->mode == PHPMYSQLGRID_EDITMODE) {
                        $value = $rawFileValue;
                        if (isset($this->columns[$i]["convert_output"]))
                            $value = $this->columns[$i]["convert_output"]($this, $value, $i + $this->countPrimaries(), $data, true);
                        else
                            $value = $value ? $this->txtFileTrue : $this->txtFileFalse;
                        if ($data) echo $value;
                        echo '<br><br>';
                    }
                    // Also hide URL input when URL imports are disabled globally (allow_url_import = false),
                    // to avoid showing a field whose input would be silently discarded server-side.
                    $showUrlInput = (!isset($this->columns[$i]["show_url_input"]) || $this->columns[$i]["show_url_input"])
                        && $this->allow_url_import;
                    $showFileInput = !isset($this->columns[$i]["show_file_input"]) || $this->columns[$i]["show_file_input"];
                    if ($showUrlInput) {
                        echo
                            $this->txtURL, '&nbsp;<input type="text" class="', $this->style, '-file" ',
                            'name="', $this->cmdSetURL, '[', $i, ']"';
                        if (isset($this->columns[$i]["size"]))
                            echo ' size="', (int)$this->columns[$i]["size"], '"';
                        $style = "";
                        if (isset($this->columns[$i]["width"]))
                            $style .= sprintf("width:%dpx;", (int)$this->columns[$i]["width"]);
                        if ($style)
                            echo ' style="', $style, '"';
                        echo '><br>';
                    }
                    if ($showFileInput) {
                        echo
                            $this->txtFile, '&nbsp;<input type="file" class="', $this->style,
                            '" name="', $this->cmdSetFile, $i, '"';
                        if (isset($this->columns[$i]["size"]))
                            echo ' size="', (int)$this->columns[$i]["size"], '"';
                        if (isset($this->columns[$i]["maxlength"]))
                            echo ' maxlength="', (int)$this->columns[$i]["maxlength"], '"';
                        if (isset($this->columns[$i]["accept"]))
                            echo ' accept="', $this->convertToHtmlEntities($this->columns[$i]["accept"]), '"';
                        $style = "";
                        if (isset($this->columns[$i]["width"]))
                            $style .= sprintf("width:%dpx;", (int)$this->columns[$i]["width"]);
                        if ($style)
                            echo ' style="', $style, '"';
                        echo '>';
                    }
                    if ($this->mode == PHPMYSQLGRID_EDITMODE && $rawFileValue) {
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
                        echo ' size="', (int)$this->columns[$i]["size"], '"';
                    if (isset($this->columns[$i]["maxlength"]))
                        echo
                            ' maxlength="', (int)$this->columns[$i]["maxlength"],
                            '"';
                    if (isset($this->columns[$i]["width"]))
                        echo
                            ' style="width:', (int)$this->columns[$i]["width"],
                            'px;"';
                    if (isset($this->columns[$i]["placeholder"]))
                        echo
                            ' placeholder="', $this->convertToHtmlEntities($this->columns[$i]["placeholder"]),
                            '"';
                    echo '>';
            }
            echo '</td>';
        }
        echo
            '</tr>';
    }

    /**
     * @internal
     * @ignore
     */
    public function drawNavigation(): void {
        $bottomId = $this->buildSafeDomId($this->name . "_bottom");
        echo
            '<tfoot><tr>',
            '<td align="right" class="', $this->style, '-action">';
        // Draw Add Button if wanted
        if ($this->can_add) {
            echo
                '<a href="', $this->selfUrl(), '?', $this->buildUrl(array($this->cmdAdd => 1)), '#', $this->convertToHtmlEntities($bottomId), '" class="add-button"',
                ' aria-label="', $this->convertToHtmlEntities($this->txtAdd),
                '" title="', $this->convertToHtmlEntities($this->txtAdd), '">',
                    $this->renderIcon($this->svgIconAdd, "add"),
                '</a>';
        } else echo '&nbsp;';
        echo '</td>';

        echo '<td class="', $this->style, '-navigation" colspan="', count($this->columns), '">';
        if ($this->can_navigate) {
            $pages = ceil((int)$this->rows / $this->limit);
            echo '<nav class="', $this->style, '-pagination" aria-label="', $this->convertToHtmlEntities($this->txtPaginationLabel), '">';

            // Prev button
            if ($this->page > 1) {
                echo '<a class="', $this->style, '-pagination-prev" href="',
                    $this->selfUrl(), '?', $this->buildUrl(array($this->cmdSetPage => $this->page - 1)),
                    '" aria-label="', $this->convertToHtmlEntities($this->txtPrevious), '">',
                    $this->renderIcon($this->svgNavPrev, "previous"), '</a>';
            } else {
                echo '<span class="', $this->style, '-pagination-prev is-disabled" aria-disabled="true" aria-label="',
                    $this->convertToHtmlEntities($this->txtPrevious), '">',
                    $this->renderIcon($this->svgNavPrev, "previous"), '</span>';
            }

            echo '<ol class="', $this->style, '-pagination-list">';

            // First page shortcut when far from the window
            if ($this->page > 3) {
                echo '<li class="', $this->style, '-pagination-item">',
                    '<a class="', $this->style, '-pagination-link" href="',
                    $this->selfUrl(), '?', $this->buildUrl(array($this->cmdSetPage => 1)), '">1</a>',
                    '</li>';
            }
            if ($this->page > 4) {
                echo '<li class="', $this->style, '-pagination-ellipsis" aria-hidden="true">&#x2026;</li>';
            }

            // Page window ±2 around current page
            for ($i = max(1, $this->page - 2); $i <= min($pages, $this->page + 2); $i++) {
                if ($i == $this->page) {
                    echo '<li class="', $this->style, '-pagination-item is-active">',
                        '<span class="', $this->style, '-pagination-current" aria-current="page">', $i, '</span>',
                        '</li>';
                } else {
                    echo '<li class="', $this->style, '-pagination-item">',
                        '<a class="', $this->style, '-pagination-link" href="',
                        $this->selfUrl(), '?', $this->buildUrl(array($this->cmdSetPage => $i)), '">', $i, '</a>',
                        '</li>';
                }
            }

            // Last page shortcut when far from the window
            if ($this->page < $pages - 3) {
                echo '<li class="', $this->style, '-pagination-ellipsis" aria-hidden="true">&#x2026;</li>';
            }
            if ($this->page < $pages - 2) {
                echo '<li class="', $this->style, '-pagination-item">',
                    '<a class="', $this->style, '-pagination-link" href="',
                    $this->selfUrl(), '?', $this->buildUrl(array($this->cmdSetPage => $pages)), '">', $pages, '</a>',
                    '</li>';
            }

            echo '</ol>';

            // Next button
            if ($this->page < $pages) {
                echo '<a class="', $this->style, '-pagination-next" href="',
                    $this->selfUrl(), '?', $this->buildUrl(array($this->cmdSetPage => $this->page + 1)),
                    '" aria-label="', $this->convertToHtmlEntities($this->txtNext), '">',
                    $this->renderIcon($this->svgNavNext, "next"), '</a>';
            } else {
                echo '<span class="', $this->style, '-pagination-next is-disabled" aria-disabled="true" aria-label="',
                    $this->convertToHtmlEntities($this->txtNext), '">',
                    $this->renderIcon($this->svgNavNext, "next"), '</span>';
            }

            echo '</nav>';
        } else echo '&nbsp;';
        echo
            '</td>',
            '</tr></tfoot>';
    }

    /**
     * @internal
     * @ignore
     */
    public function validateColumns(): void {
        for ($i = 0; $i < count($this->columns); $i++) {
            if (!isset($this->columns[$i]['type']))
                $this->columns[$i]['type'] = PHPMYSQLGRID_TEXT;
            if (!isset($this->columns[$i]['can_sort']))
                $this->columns[$i]['can_sort'] = true;
            if (!isset($this->columns[$i]['can_filter']))
                $this->columns[$i]['can_filter'] = true;
        }
    }

    /**
     * @internal
     * @ignore
     */
    public function validateActions(): void {
        for ($i = 0; $i < count($this->actions); $i++) {
            if (!isset($this->actions[$i]["type"]))
                $this->actions[$i]["type"] = PHPMYSQLGRID_TEXTBUTTON;
        }
    }

    /**
     * Executes one full grid request lifecycle and renders the grid HTML.
     */
    public function execute(): void {
        $this->frontendErrors = array();

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

    /**
     * Escapes a value for safe HTML output based on the configured charset.
     *
     * @internal
     * @ignore
     */
    public function convertToHtmlEntities(mixed $data): string {
        return htmlentities($data ?? "", ENT_QUOTES, $this->charset);
    }

    private function addFrontendError(string $message): void {
        if ($message === "") {
            return;
        }
        if (!in_array($message, $this->frontendErrors, true)) {
            $this->frontendErrors[] = $message;
        }
    }

    private function reportValidationFailure(string $message): void {
        $this->addFrontendError($message);
        error_log("MySQLGrid validation: " . $message);
    }
}

?>
