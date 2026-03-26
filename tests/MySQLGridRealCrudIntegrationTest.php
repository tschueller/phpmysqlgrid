<?php

declare(strict_types=1);

namespace MySQLGridTests;

require_once __DIR__ . "/DatabaseTestCase.php";

use MySQLGrid;

final class MySQLGridRealCrudIntegrationTest extends DatabaseTestCase {
    private MySQLGrid $grid;

    protected function setUp(): void {
        parent::setUp();

        $_REQUEST = array();
        $_FILES = array();
        $_SESSION = array();
        $_SERVER["PHP_SELF"] = "/index.php";

        $this->grid = new MySQLGrid();
        $this->grid->setDatabaseConnection($this->sqlite, "pdo_sqlite");
        $this->grid->table = "users";
        $this->grid->primary = "id";
        $this->grid->columns = array(
            array("field" => "email", "type" => PHPMYSQLGRID_TEXT),
            array("field" => "display_name", "type" => PHPMYSQLGRID_TEXT),
            array("field" => "is_active", "type" => PHPMYSQLGRID_BOOLEAN),
            array("field" => "bio", "type" => PHPMYSQLGRID_MULTILINETEXT),
        );
        $this->grid->connect();
    }

    protected function tearDown(): void {
        $_REQUEST = array();
        $_FILES = array();
        $_SESSION = array();

        parent::tearDown();
    }

    private function buildExecuteGrid(): MySQLGrid {
        $grid = new MySQLGrid();
        $grid->setDatabaseConnection($this->sqlite, "pdo_sqlite");
        $grid->table = "users";
        $grid->primary = "id";
        $grid->name = "exec_grid";
        $grid->columns = array(
            array("field" => "email", "type" => PHPMYSQLGRID_TEXT),
            array("field" => "display_name", "type" => PHPMYSQLGRID_TEXT),
            array("field" => "is_active", "type" => PHPMYSQLGRID_BOOLEAN),
            array("field" => "bio", "type" => PHPMYSQLGRID_MULTILINETEXT),
        );

        return $grid;
    }

    public function testConnectAndDisconnectWithInjectedPdoRemainUsable(): void {
        $this->assertTableRowCount("users", 2);

        $this->grid->disconnect();

        // Disconnect on injected connections is intentionally a no-op.
        $this->assertTableRowCount("users", 2);

        $this->grid->connect();
        $this->assertTableRowCount("users", 2);
    }

    public function testAddDataUsesRealMysqlGridMethodWithInjectedPdo(): void {
        $this->grid->addData(array(
            "real-add@example.test",
            "Real Add",
            1,
            "Inserted by real MySQLGrid::addData"
        ));

        $this->assertTableRowCount("users", 3);
        $row = $this->fetchUserByEmail("real-add@example.test");
        $this->assertNotNull($row);
        $this->assertSame("Real Add", $row["display_name"]);
    }

    public function testEditDataUsesRealMysqlGridMethodWithInjectedPdo(): void {
        $this->grid->editData(1, array(
            "alice@example.test",
            "Alice Real Edit",
            0,
            "Updated by real MySQLGrid::editData"
        ));

        $this->assertTableRowCount("users", 2);
        $row = $this->fetchUserByEmail("alice@example.test");
        $this->assertNotNull($row);
        $this->assertSame("Alice Real Edit", $row["display_name"]);
        $this->assertSame(0, (int)$row["is_active"]);
    }

    public function testDeleteDataUsesRealMysqlGridMethodWithInjectedPdo(): void {
        $this->grid->deleteData(2);

        $this->assertTableRowCount("users", 1);
        $row = $this->fetchUserByEmail("bob@example.test");
        $this->assertNull($row);
    }

    public function testDeleteDataDoesNotDeleteAllRowsOnInjectedPayload(): void {
        $this->grid->deleteData("1 OR 1=1");

        $this->assertTableRowCount("users", 2);
    }

    public function testUseAllColumnsUsesRealMysqlGridMethodWithInjectedPdo(): void {
        $this->grid->columns = array();

        $this->grid->useAllColumns();

        $fields = array_map(static fn(array $column): string => (string)$column["field"], $this->grid->columns);

        $this->assertContains("id", $fields);
        $this->assertContains("email", $fields);
        $this->assertContains("display_name", $fields);
        $this->assertContains("is_active", $fields);
        $this->assertContains("bio", $fields);
    }

    public function testPrepareDataUsesRealMysqlGridMethodWithInjectedPdo(): void {
        $_SESSION = array();
        $this->grid->sort = 0;
        $this->grid->dir = 0;
        $this->grid->page = 1;
        $this->grid->limit = 10;
        $this->grid->name = "real_prepare_test";

        for ($i = 0; $i < count($this->grid->columns); $i++) {
            $this->grid->columns[$i]["active_filter"] = "";
        }

        $this->grid->prepareData();

        $this->assertSame(2, (int)$this->grid->rows);
        $this->assertInstanceOf(\PDOStatement::class, $this->grid->result);

        $firstRow = $this->grid->result->fetch(\PDO::FETCH_NUM);
        $this->assertIsArray($firstRow);
        if (!is_array($firstRow)) {
            $this->fail("Expected a row from prepared PDO result");
        }

        // selected columns: primary + configured data columns
        $this->assertSame(5, count($firstRow));

        $this->grid->unprepareData();
    }

    public function testPrepareDataTreatsFilterInjectionPayloadAsLiteral(): void {
        $_SESSION = array();
        $this->grid->sort = 0;
        $this->grid->dir = 0;
        $this->grid->page = 1;
        $this->grid->limit = 10;
        $this->grid->name = "real_prepare_injection_test";

        for ($i = 0; $i < count($this->grid->columns); $i++) {
            $this->grid->columns[$i]["active_filter"] = "";
        }
        $this->grid->columns[0]["active_filter"] = "x' OR 1=1 --";

        $this->grid->prepareData();

        // A vulnerable query would return all rows; safe behavior treats payload as literal text.
        $this->assertSame(0, (int)$this->grid->rows);
        $this->assertTableRowCount("users", 2);

        $this->grid->unprepareData();
    }

    public function testUnprepareDataClosesCursorForPdoResult(): void {
        $_SESSION = array();
        $this->grid->sort = 0;
        $this->grid->dir = 0;
        $this->grid->page = 1;
        $this->grid->limit = 10;
        $this->grid->name = "real_unprepare_test";

        for ($i = 0; $i < count($this->grid->columns); $i++) {
            $this->grid->columns[$i]["active_filter"] = "";
        }

        $this->grid->prepareData();
        $this->assertInstanceOf(\PDOStatement::class, $this->grid->result);

        $this->grid->unprepareData();

        // A second query should still work after cursor close (sanity check)
        $this->assertTableRowCount("users", 2);
    }

    public function testDrawEditControlsLookupUsesInjectedPdoQueryPath(): void {
        $this->sqlite->exec("ALTER TABLE users ADD COLUMN role_id INTEGER NOT NULL DEFAULT 1");
        $this->sqlite->exec("CREATE TABLE roles (id INTEGER PRIMARY KEY, title TEXT NOT NULL)");
        $this->sqlite->exec("INSERT INTO roles (id, title) VALUES (1, 'Admin'), (2, 'Editor')");

        $lookupGrid = new MySQLGrid();
        $lookupGrid->setDatabaseConnection($this->sqlite, "pdo_sqlite");
        $lookupGrid->table = "users";
        $lookupGrid->primary = "id";
        $lookupGrid->name = "lookup_grid";
        $lookupGrid->columns = array(
            array(
                "field" => "role_id",
                "type" => PHPMYSQLGRID_LOOKUP,
                "lookup_primary" => "id",
                "lookup_field" => "title",
                "lookup_table" => "roles"
            )
        );
        $lookupGrid->connect();
        $lookupGrid->prepareQueryVars();
        $lookupGrid->mode = PHPMYSQLGRID_ADDMODE;
        $lookupGrid->row = 0;

        ob_start();
        $lookupGrid->drawEditControls(false);
        $html = ob_get_clean();

        $this->assertIsString($html);
        if (!is_string($html)) {
            $this->fail("Expected drawEditControls output string");
        }

        $this->assertStringContainsString('name="lookup_grid_setdata[0]"', $html);
        $this->assertStringContainsString('>Admin</option>', $html);
        $this->assertStringContainsString('>Editor</option>', $html);
    }

    public function testExecuteRendersRowsWithInjectedPdo(): void {
        $grid = $this->buildExecuteGrid();

        ob_start();
        $grid->execute();
        $html = ob_get_clean();

        $this->assertIsString($html);
        if (!is_string($html)) {
            $this->fail("Expected execute output string");
        }

        $this->assertStringContainsString('<tbody>', $html);
        $this->assertStringContainsString('alice@example.test', $html);
        $this->assertStringContainsString('bob@example.test', $html);
        $this->assertStringContainsString('data-id="1"', $html);
    }

    public function testExecuteProcessesConfirmAddRequestWithInjectedPdo(): void {
        $grid = $this->buildExecuteGrid();

        $_REQUEST["exec_grid_confirmadd"] = "1";
        $_REQUEST["exec_grid_setdata"] = array(
            "new-exec@example.test",
            "Exec Added",
            "1",
            "Created through execute()"
        );

        ob_start();
        $grid->execute();
        ob_end_clean();

        $this->assertTableRowCount("users", 3);
        $row = $this->fetchUserByEmail("new-exec@example.test");
        $this->assertNotNull($row);
        $this->assertSame("Exec Added", $row["display_name"]);
    }

    public function testExecuteProcessesConfirmEditRequestWithInjectedPdo(): void {
        $grid = $this->buildExecuteGrid();

        $_REQUEST["exec_grid_confirmedit"] = "1";
        $_REQUEST["exec_grid_editid"] = "1";
        $_REQUEST["exec_grid_setdata"] = array(
            "alice@example.test",
            "Exec Edited",
            "0",
            "Edited through execute()"
        );

        ob_start();
        $grid->execute();
        ob_end_clean();

        $row = $this->fetchUserByEmail("alice@example.test");
        $this->assertNotNull($row);
        $this->assertSame("Exec Edited", $row["display_name"]);
        $this->assertSame(0, (int)$row["is_active"]);
    }

    public function testExecuteProcessesConfirmDeleteRequestWithInjectedPdo(): void {
        $grid = $this->buildExecuteGrid();

        $_REQUEST["exec_grid_confirmdelete"] = "1";
        $_REQUEST["exec_grid_deleteid"] = "2";

        ob_start();
        $grid->execute();
        ob_end_clean();

        $this->assertTableRowCount("users", 1);
        $deleted = $this->fetchUserByEmail("bob@example.test");
        $this->assertNull($deleted);
    }
}
