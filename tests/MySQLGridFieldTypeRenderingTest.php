<?php

declare(strict_types=1);

namespace MySQLGridTests;

require_once __DIR__ . "/DatabaseTestCase.php";
require_once __DIR__ . "/../src/MySQLGrid.php";

use PhpMySQLGrid\MySQLGrid;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests for field type rendering:
 * Boolean, Lookup, Password, Selection, and Multiline text field types.
 */
final class MySQLGridFieldTypeRenderingTest extends DatabaseTestCase {

    protected function setUp(): void {
        parent::setUp();

        $_REQUEST = array();
        $_POST    = array();
        $_GET     = array();
        $_FILES   = array();
        $_SESSION = array();
        $_SERVER["PHP_SELF"]       = "/index.php";
        $_SERVER["REQUEST_METHOD"] = "GET";

        // Seed a lookup table for the lookup tests
        $this->sqlite->exec(
            "CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL
            )"
        );
        $this->sqlite->exec("INSERT INTO roles (name) VALUES ('Admin')");
        $this->sqlite->exec("INSERT INTO roles (name) VALUES ('Editor')");

        // Add role_id column to users for lookup test
        $this->sqlite->exec("ALTER TABLE users ADD COLUMN role_id INTEGER DEFAULT 1");
        $this->sqlite->exec("UPDATE users SET role_id = 1");
    }

    protected function tearDown(): void {
        $_REQUEST = array();
        $_POST    = array();
        $_GET     = array();
        $_FILES   = array();
        $_SESSION = array();

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function captureGridOutput(MySQLGrid $grid): string {
        ob_start();
        $grid->execute();
        return (string)ob_get_clean();
    }

    private function buildViewGrid(array $columns): MySQLGrid {
        $grid = new MySQLGrid();
        $grid->setDatabaseConnection($this->sqlite, "pdo_sqlite");
        $grid->table   = "users";
        $grid->primary = "id";
        $grid->name    = "test_grid";
        $grid->columns = $columns;
        return $grid;
    }

    /**
     * Directly invoke drawEditControls() for a specific row to capture edit-mode HTML.
     * This bypasses execute() and avoids session/request processing issues in CLI.
     */
    private function captureEditControlsOutput(array $columns, int $rowId): string {
        $grid = new MySQLGrid();
        $grid->setDatabaseConnection($this->sqlite, "pdo_sqlite");
        $grid->table   = "users";
        $grid->primary = "id";
        $grid->name    = "test_grid";
        $grid->columns = $columns;
        $grid->connect();
        $grid->prepareQueryVars();
        $grid->row = 0;

        $grid->mode = PHPMYSQLGRID_EDITMODE;
        $_REQUEST[$grid->name . "_editid"] = (string)$rowId;

        // Fetch the row data (id + column values) as a numeric array
        $fields = array_map(fn($c) => $c["field"], $columns);
        $sql = "SELECT id, " . implode(", ", $fields) . " FROM users WHERE id = :id";
        $stmt = $this->sqlite->prepare($sql);
        $stmt->execute(array("id" => $rowId));
        $row = $stmt->fetch(\PDO::FETCH_NUM);

        ob_start();
        $grid->drawEditControls($row !== false ? $row : false);
        return (string)ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Boolean field type renders SVG icons for true/false values
    // -------------------------------------------------------------------------

    #[TestDox("Boolean column renders bool-true icon for rows with value 1")]
    public function testBooleanColumnRendersTrueIconForValue1(): void {
        $grid = $this->buildViewGrid(array(
            array("field" => "is_active", "type" => PHPMYSQLGRID_BOOLEAN),
        ));

        $html = $this->captureGridOutput($grid);

        $this->assertStringContainsString('bool-true', $html,
            "Boolean column must render the bool-true icon for rows with value 1");
    }

    #[TestDox("Boolean column renders bool-false icon for rows with value 0")]
    public function testBooleanColumnRendersFalseIconForValue0(): void {
        $this->sqlite->exec(
            "INSERT INTO users (email, display_name, is_active, bio)
             VALUES ('inactive@example.test', 'Inactive User', 0, '')"
        );

        $grid = $this->buildViewGrid(array(
            array("field" => "is_active", "type" => PHPMYSQLGRID_BOOLEAN),
        ));

        $html = $this->captureGridOutput($grid);

        $this->assertStringContainsString('bool-false', $html,
            "Boolean column must render the bool-false icon for rows with value 0");
    }

    #[TestDox("Boolean column renders both true and false icons when both values exist")]
    public function testBooleanColumnRendersBothIconsWhenBothValuesExist(): void {
        $this->sqlite->exec(
            "INSERT INTO users (email, display_name, is_active, bio)
             VALUES ('inactive2@example.test', 'Inactive2', 0, '')"
        );

        $grid = $this->buildViewGrid(array(
            array("field" => "is_active", "type" => PHPMYSQLGRID_BOOLEAN),
        ));

        $html = $this->captureGridOutput($grid);

        $this->assertStringContainsString('bool-true', $html);
        $this->assertStringContainsString('bool-false', $html);
    }

    // -------------------------------------------------------------------------
    // Lookup field type resolves foreign key to display value
    // -------------------------------------------------------------------------

    #[TestDox("Lookup column displays resolved label instead of raw foreign key")]
    public function testLookupColumnDisplaysResolvedLabel(): void {
        $grid = $this->buildViewGrid(array(
            array(
                "field"          => "role_id",
                "type"           => PHPMYSQLGRID_LOOKUP,
                "lookup_table"   => "roles",
                "lookup_primary" => "id",
                "lookup_field"   => "name",
            ),
        ));

        $html = $this->captureGridOutput($grid);

        $this->assertStringContainsString('Admin', $html,
            "Lookup column must display the resolved label from the lookup table");
    }

    #[TestDox("Lookup column edit mode renders a <select> with lookup options")]
    public function testLookupColumnEditModeRendersSelectWithOptions(): void {
        $columns = array(
            array(
                "field"          => "role_id",
                "type"           => PHPMYSQLGRID_LOOKUP,
                "lookup_table"   => "roles",
                "lookup_primary" => "id",
                "lookup_field"   => "name",
            ),
        );

        $html = $this->captureEditControlsOutput($columns, 1);

        $this->assertStringContainsString('<select', $html,
            "Lookup column in edit mode must render a <select> element");
        $this->assertStringContainsString('Admin', $html,
            "Lookup <select> must contain the lookup option labels");
        $this->assertStringContainsString('Editor', $html,
            "Lookup <select> must contain all lookup option labels");
    }

    // -------------------------------------------------------------------------
    // Password field type masks value in view mode and edit mode
    // -------------------------------------------------------------------------

    #[TestDox("Password column displays PHPMYSQLGRID_PWDUMMY mask in view mode")]
    public function testPasswordColumnDisplaysMaskInViewMode(): void {
        $grid = $this->buildViewGrid(array(
            array("field" => "bio", "type" => PHPMYSQLGRID_PASSWORD),
        ));

        $html = $this->captureGridOutput($grid);

        $this->assertStringContainsString(PHPMYSQLGRID_PWDUMMY, $html,
            "Password column must display the dummy mask string in view mode");
        $this->assertStringNotContainsString('Seed user Alice', $html,
            "Password column must not expose the actual value in view mode");
    }

    #[TestDox("Password column renders <input type=\"password\"> in edit mode")]
    public function testPasswordColumnRendersPasswordInputInEditMode(): void {
        $columns = array(
            array("field" => "bio", "type" => PHPMYSQLGRID_PASSWORD),
        );

        $html = $this->captureEditControlsOutput($columns, 1);

        $this->assertStringContainsString('type="password"', $html,
            "Password column in edit mode must render an <input type=\"password\"> element");
    }

    // -------------------------------------------------------------------------
    // Selection field type renders dropdown in edit mode
    // -------------------------------------------------------------------------

    #[TestDox("Selection column renders <select> with configured options in edit mode")]
    public function testSelectionColumnRendersDropdownInEditMode(): void {
        $columns = array(
            array(
                "field"     => "display_name",
                "type"      => PHPMYSQLGRID_SELECTION,
                "selection" => array("alice" => "Alice", "bob" => "Bob", "carol" => "Carol"),
            ),
        );

        $html = $this->captureEditControlsOutput($columns, 1);

        $this->assertStringContainsString('<select', $html,
            "Selection column in edit mode must render a <select> element");
        $this->assertStringContainsString('Alice', $html,
            "Selection <select> must contain all configured option labels");
        $this->assertStringContainsString('Bob', $html);
        $this->assertStringContainsString('Carol', $html);
    }

    #[TestDox("Selection column displays resolved label in view mode")]
    public function testSelectionColumnDisplaysResolvedLabelInViewMode(): void {
        $grid = $this->buildViewGrid(array(
            array(
                "field"     => "display_name",
                "type"      => PHPMYSQLGRID_SELECTION,
                "selection" => array("Alice" => "Alice Label", "Bob" => "Bob Label"),
            ),
        ));

        $html = $this->captureGridOutput($grid);

        $this->assertStringContainsString('Alice Label', $html,
            "Selection column in view mode must display the resolved label, not the raw key");
    }

    // -------------------------------------------------------------------------
    // Multiline text field renders textarea in edit mode
    // -------------------------------------------------------------------------

    #[TestDox("Multiline text column displays stored text in view mode")]
    public function testMultilineTextColumnDisplaysStoredTextInViewMode(): void {
        $grid = $this->buildViewGrid(array(
            array("field" => "bio", "type" => PHPMYSQLGRID_MULTILINETEXT),
        ));

        $html = $this->captureGridOutput($grid);

        $this->assertStringContainsString('Seed user Alice', $html,
            "Multiline text column must display the stored text content in view mode");
    }

    #[TestDox("Multiline text column renders <textarea> with current value in edit mode")]
    public function testMultilineTextColumnRendersTextareaInEditMode(): void {
        $columns = array(
            array("field" => "bio", "type" => PHPMYSQLGRID_MULTILINETEXT),
        );

        $html = $this->captureEditControlsOutput($columns, 1);

        $this->assertStringContainsString('<textarea', $html,
            "Multiline text column in edit mode must render a <textarea> element");
        $this->assertStringContainsString('Seed user Alice', $html,
            "Textarea must be pre-populated with the current row value");
    }

    #[TestDox("Multiline text column saves updated text and displays it in view mode")]
    public function testMultilineTextColumnSavesUpdatedText(): void {
        $grid = new MySQLGrid();
        $grid->setDatabaseConnection($this->sqlite, "pdo_sqlite");
        $grid->table   = "users";
        $grid->primary = "id";
        $grid->name    = "test_grid";
        $grid->columns = array(
            array("field" => "bio", "type" => PHPMYSQLGRID_MULTILINETEXT),
        );

        $grid->editData(1, array("Updated multiline bio text"));

        $row = $this->fetchUserByEmail("alice@example.test");
        $this->assertNotNull($row);
        $this->assertSame("Updated multiline bio text", $row["bio"],
            "Updated multiline text must be persisted to the database");
    }
}
