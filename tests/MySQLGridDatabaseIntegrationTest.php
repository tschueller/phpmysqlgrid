<?php

declare(strict_types=1);

namespace MySQLGridTests;

require_once __DIR__ . "/DatabaseTestCase.php";
require_once __DIR__ . "/MySQLGridSqliteAdapter.php";

final class MySQLGridDatabaseIntegrationTest extends DatabaseTestCase {
    private MySQLGridSqliteAdapter $grid;

    protected function setUp(): void {
        parent::setUp();

        $this->grid = new MySQLGridSqliteAdapter($this->sqlite);
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

    public function testConnectAndDisconnect(): void {
        $this->assertSame(2, $this->grid->tableRowCount());

        $this->grid->disconnect();

        $this->expectException(\RuntimeException::class);
        $this->grid->tableRowCount();
    }

    public function testUseAllColumnsReadsSchema(): void {
        $this->grid->useAllColumns();

        $fields = array_map(static fn(array $column): string => (string)$column["field"], $this->grid->columns);

        $this->assertContains("id", $fields);
        $this->assertContains("email", $fields);
        $this->assertContains("display_name", $fields);
    }

    public function testAddDataInsertsNewRow(): void {
        $this->grid->addData(array(
            "carol@example.test",
            "Carol",
            1,
            "Created by addData"
        ));

        $this->assertSame(3, $this->grid->tableRowCount());

        $created = $this->fetchUserByEmail("carol@example.test");
        $this->assertNotNull($created);
        $this->assertSame("Carol", $created["display_name"]);
    }

    public function testEditDataUpdatesExistingRow(): void {
        $this->grid->editData(1, array(
            "alice@example.test",
            "Alice Updated",
            0,
            "Updated by editData"
        ));

        $updated = $this->fetchUserByEmail("alice@example.test");
        $this->assertNotNull($updated);
        $this->assertSame("Alice Updated", $updated["display_name"]);
        $this->assertSame(0, (int)$updated["is_active"]);
    }

    public function testDeleteDataRemovesRow(): void {
        $this->grid->deleteData(2);

        $this->assertSame(1, $this->grid->tableRowCount());

        $deleted = $this->fetchUserByEmail("bob@example.test");
        $this->assertNull($deleted);
    }

    public function testFetchRowsSupportsSorting(): void {
        $this->grid->sort = 1; // display_name
        $this->grid->dir = 1;  // DESC

        $rows = $this->grid->fetchRows();

        $this->assertCount(2, $rows);
        $this->assertSame("Bob", $rows[0]["display_name"]);
        $this->assertSame("Alice", $rows[1]["display_name"]);
    }
}
