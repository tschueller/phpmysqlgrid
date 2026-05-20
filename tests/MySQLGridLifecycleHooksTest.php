<?php

declare(strict_types=1);

namespace MySQLGridTests;

require_once __DIR__ . "/DatabaseTestCase.php";

use PhpMySQLGrid\MySQLGrid;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests for add_before/add_after, edit_before/edit_after, and delete_before/delete_after
 * lifecycle hooks.
 */
final class MySQLGridLifecycleHooksTest extends DatabaseTestCase {
    private MySQLGrid $grid;

    protected function setUp(): void {
        parent::setUp();

        $_REQUEST = array();
        $_POST = array();
        $_GET = array();
        $_FILES = array();
        $_SESSION = array();
        $_SERVER["PHP_SELF"] = "/index.php";
        $_SERVER["REQUEST_METHOD"] = "GET";

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
        $_POST = array();
        $_GET = array();
        $_FILES = array();
        $_SESSION = array();

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // add_before and add_after lifecycle hooks
    // -------------------------------------------------------------------------

    #[TestDox("add_before is called before INSERT and add_after is called after INSERT")]
    public function testAddBeforeAndAddAfterHooksAreCalledOnRecordAdd(): void {
        $log = array();

        $this->grid->can_add = true;
        $this->grid->add_before = static function (MySQLGrid $grid, array $data) use (&$log): bool {
            $log[] = "add_before";
            return true;
        };
        $this->grid->add_after = static function (MySQLGrid $grid) use (&$log): void {
            $log[] = "add_after";
        };

        $this->grid->addData(array(
            "hook-add@example.test",
            "Hook Add User",
            1,
            "Added via lifecycle hook test"
        ));

        // Both hooks must have fired
        $this->assertContains("add_before", $log, "add_before hook was not called");
        $this->assertContains("add_after", $log, "add_after hook was not called");

        // add_before must have fired before add_after
        $this->assertSame(0, array_search("add_before", $log, true), "add_before should be first in log");
        $this->assertSame(1, array_search("add_after", $log, true), "add_after should be second in log");

        // The record must actually have been inserted
        $this->assertTableRowCount("users", 3);
        $row = $this->fetchUserByEmail("hook-add@example.test");
        $this->assertNotNull($row);
    }

    #[TestDox("add_before returning false prevents the INSERT and add_after is not called")]
    public function testAddBeforeReturningFalseAbortesInsertAndSkipsAddAfter(): void {
        $log = array();

        $this->grid->can_add = true;
        $this->grid->add_before = static function (MySQLGrid $grid, array $data) use (&$log): bool {
            $log[] = "add_before";
            return false; // abort
        };
        $this->grid->add_after = static function (MySQLGrid $grid) use (&$log): void {
            $log[] = "add_after";
        };

        $this->grid->addData(array(
            "aborted-add@example.test",
            "Aborted Add",
            1,
            "Should not be inserted"
        ));

        $this->assertContains("add_before", $log);
        $this->assertNotContains("add_after", $log, "add_after must not fire when add_before returns false");
        $this->assertTableRowCount("users", 2);
    }

    // -------------------------------------------------------------------------
    // edit_before and edit_after lifecycle hooks
    // -------------------------------------------------------------------------

    #[TestDox("edit_before is called before UPDATE and edit_after is called after UPDATE")]
    public function testEditBeforeAndEditAfterHooksAreCalledOnRecordEdit(): void {
        $log = array();

        $this->grid->can_edit = true;
        $this->grid->edit_before = static function (MySQLGrid $grid, int|string $id, array $data) use (&$log): bool {
            $log[] = "edit_before";
            return true;
        };
        $this->grid->edit_after = static function (MySQLGrid $grid, int|string $id, array $data) use (&$log): void {
            $log[] = "edit_after";
        };

        $this->grid->editData(1, array(
            "alice@example.test",
            "Alice Hook Edit",
            0,
            "Updated via lifecycle hook test"
        ));

        $this->assertContains("edit_before", $log, "edit_before hook was not called");
        $this->assertContains("edit_after", $log, "edit_after hook was not called");

        // edit_before must have fired before edit_after
        $this->assertSame(0, array_search("edit_before", $log, true), "edit_before should be first in log");
        $this->assertSame(1, array_search("edit_after", $log, true), "edit_after should be second in log");

        // The record must actually have been updated
        $row = $this->fetchUserByEmail("alice@example.test");
        $this->assertNotNull($row);
        $this->assertSame("Alice Hook Edit", $row["display_name"]);
    }

    #[TestDox("edit_before returning false prevents the UPDATE and edit_after is not called")]
    public function testEditBeforeReturningFalseAbortsUpdateAndSkipsEditAfter(): void {
        $log = array();

        $this->grid->can_edit = true;
        $this->grid->edit_before = static function (MySQLGrid $grid, int|string $id, array $data) use (&$log): bool {
            $log[] = "edit_before";
            return false; // abort
        };
        $this->grid->edit_after = static function (MySQLGrid $grid, int|string $id, array $data) use (&$log): void {
            $log[] = "edit_after";
        };

        $this->grid->editData(1, array(
            "alice@example.test",
            "Should Not Change",
            1,
            "Aborted edit"
        ));

        $this->assertContains("edit_before", $log);
        $this->assertNotContains("edit_after", $log, "edit_after must not fire when edit_before returns false");

        // Row must remain unchanged
        $row = $this->fetchUserByEmail("alice@example.test");
        $this->assertNotNull($row);
        $this->assertSame("Alice", $row["display_name"]);
    }

    // -------------------------------------------------------------------------
    // delete_before and delete_after lifecycle hooks
    // -------------------------------------------------------------------------

    #[TestDox("delete_before is called before DELETE and delete_after is called after DELETE")]
    public function testDeleteBeforeAndDeleteAfterHooksAreCalledOnRecordDelete(): void {
        $log = array();
        $capturedId = null;

        $this->grid->can_delete = true;
        $this->grid->delete_before = static function (MySQLGrid $grid, int|string $id) use (&$log): bool {
            $log[] = "delete_before";
            return true;
        };
        $this->grid->delete_after = static function (MySQLGrid $grid, int|string $id) use (&$log, &$capturedId): void {
            $log[] = "delete_after";
            $capturedId = $id;
        };

        $this->grid->deleteData(2);

        $this->assertContains("delete_before", $log, "delete_before hook was not called");
        $this->assertContains("delete_after", $log, "delete_after hook was not called");

        // delete_before must have fired before delete_after
        $this->assertSame(0, array_search("delete_before", $log, true), "delete_before should be first in log");
        $this->assertSame(1, array_search("delete_after", $log, true), "delete_after should be second in log");

        // delete_after must receive the deleted record's primary key
        $this->assertSame(2, (int)$capturedId, "delete_after should receive the deleted record's primary key");

        // The record must actually have been deleted
        $this->assertTableRowCount("users", 1);
        $this->assertNull($this->fetchUserByEmail("bob@example.test"));
    }

    #[TestDox("delete_before returning false prevents the DELETE and delete_after is not called")]
    public function testDeleteBeforeReturningFalseAbortsDeleteAndSkipsDeleteAfter(): void {
        $log = array();

        $this->grid->can_delete = true;
        $this->grid->delete_before = static function (MySQLGrid $grid, int|string $id) use (&$log): bool {
            $log[] = "delete_before";
            return false; // abort
        };
        $this->grid->delete_after = static function (MySQLGrid $grid, int|string $id) use (&$log): void {
            $log[] = "delete_after";
        };

        $this->grid->deleteData(2);

        $this->assertContains("delete_before", $log);
        $this->assertNotContains("delete_after", $log, "delete_after must not fire when delete_before returns false");
        $this->assertTableRowCount("users", 2);
    }
}
