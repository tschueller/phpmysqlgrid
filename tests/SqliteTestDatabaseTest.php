<?php

declare(strict_types=1);

namespace MySQLGridTests;

require_once __DIR__ . "/DatabaseTestCase.php";

final class SqliteTestDatabaseTest extends DatabaseTestCase {
    public function testSeedDataIsLoadedForEachTest(): void {
        $this->assertTableRowCount("users", 2);

        $alice = $this->fetchUserByEmail("alice@example.test");

        $this->assertNotNull($alice);
        $this->assertSame("Alice", $alice["display_name"]);
    }

    public function testCanInsertAdditionalRowsInTestTransaction(): void {
        $statement = $this->sqlite->prepare(
            "INSERT INTO users (email, display_name, is_active, bio)
             VALUES (:email, :display_name, :is_active, :bio)"
        );

        $statement->execute(array(
            "email" => "charlie@example.test",
            "display_name" => "Charlie",
            "is_active" => 0,
            "bio" => "Created inside a test"
        ));

        $this->assertTableRowCount("users", 3);
    }
}
