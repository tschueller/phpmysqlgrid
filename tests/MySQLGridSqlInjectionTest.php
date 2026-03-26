<?php

declare(strict_types=1);

namespace MySQLGridTests;

require_once __DIR__ . "/DatabaseTestCase.php";
require_once __DIR__ . "/MySQLGridSqliteAdapter.php";

/**
 * SQL injection behavior tests via MySQLGridSqliteAdapter.
 *
 * These tests document the CORRECT expected behavior: SQL injection payloads
 * must be stored as literal strings and must not affect table integrity.
 *
 * IMPORTANT: These tests pass because MySQLGridSqliteAdapter uses PDO prepared
 * statements and is therefore inherently safe. They do NOT exercise the real
 * MySQLGrid::addData / editData / deleteData methods, which currently use
 * addslashes() + string interpolation and are vulnerable to SQL injection.
 *
 * After the injectable-connection refactoring tracked in TODO.md under
 * "Refactoring", these same tests must pass against the real MySQLGrid code
 * paths to confirm the vulnerability is fixed.
 */
final class MySQLGridSqlInjectionTest extends DatabaseTestCase {
    private MySQLGridSqliteAdapter $grid;

    protected function setUp(): void {
        parent::setUp();

        $this->grid = new MySQLGridSqliteAdapter($this->sqlite);
        $this->grid->table = "users";
        $this->grid->primary = "id";
        $this->grid->columns = array(
            array("field" => "email",        "type" => PHPMYSQLGRID_TEXT),
            array("field" => "display_name", "type" => PHPMYSQLGRID_TEXT),
            array("field" => "is_active",    "type" => PHPMYSQLGRID_BOOLEAN),
            array("field" => "bio",          "type" => PHPMYSQLGRID_MULTILINETEXT),
        );
        $this->grid->connect();
    }

    public function testInsertWithClassicDropTablePayloadStoresLiteralValue(): void {
        $payload = "'; DROP TABLE users; --";

        $this->grid->addData(array(
            "injection@example.test",
            $payload,
            1,
            "SQL injection test"
        ));

        // Table must still exist and contain the original 2 seed rows plus the new one
        $this->assertTableRowCount("users", 3);

        $row = $this->fetchUserByEmail("injection@example.test");
        $this->assertNotNull($row);
        $this->assertSame($payload, $row["display_name"]);
    }

    public function testInsertWithOrAlwaysTruePayloadStoresLiteralValue(): void {
        $payload = "1' OR '1'='1";

        $this->grid->addData(array(
            $payload,
            "InjectionUser",
            1,
            "OR injection test"
        ));

        $this->assertTableRowCount("users", 3);

        $row = $this->fetchUserByEmail($payload);
        $this->assertNotNull($row);
        $this->assertSame("InjectionUser", $row["display_name"]);
    }

    public function testInsertWithDeleteAllPayloadInBioStoresLiteralValue(): void {
        $payload = "normal'; DELETE FROM users; SELECT '";

        $this->grid->addData(array(
            "bio-injection@example.test",
            "BioInjectionUser",
            1,
            $payload
        ));

        $this->assertTableRowCount("users", 3);

        $row = $this->fetchUserByEmail("bio-injection@example.test");
        $this->assertNotNull($row);
        $this->assertSame($payload, $row["bio"]);
    }

    public function testEditWithDropTablePayloadInFieldStoresLiteralValue(): void {
        $payload = "'; DROP TABLE users; --";

        $this->grid->editData(1, array(
            "alice@example.test",
            $payload,
            1,
            "Edit injection test"
        ));

        $this->assertTableRowCount("users", 2);

        $row = $this->fetchUserByEmail("alice@example.test");
        $this->assertNotNull($row);
        $this->assertSame($payload, $row["display_name"]);
    }

    public function testDeleteWithInjectedIdOnlyRemovesRowWithLiteralId(): void {
        // In the real MySQLGrid::deleteData the $id is used directly in a
        // sprintf string ("DELETE FROM %s where %s='%s'") — potentially injectable.
        // A vulnerable implementation of "1 OR 1=1" would delete ALL rows.
        // The correct behavior: no row has id = '1 OR 1=1' literally, so count stays at 2.
        $this->grid->deleteData("1 OR 1=1");

        $this->assertTableRowCount("users", 2);
    }

    public function testXssPayloadStoredAsLiteralNotExecuted(): void {
        $xssPayload = "<script>alert('xss')</script>";

        $this->grid->addData(array(
            "xss@example.test",
            $xssPayload,
            1,
            "XSS storage test"
        ));

        $this->assertTableRowCount("users", 3);

        $row = $this->fetchUserByEmail("xss@example.test");
        $this->assertNotNull($row);
        // Value stored verbatim — HTML encoding must happen at render time, not storage time
        $this->assertSame($xssPayload, $row["display_name"]);
    }
}
