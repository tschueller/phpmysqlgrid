<?php

declare(strict_types=1);

namespace MySQLGridTests;

require_once __DIR__ . "/SqliteTestDatabase.php";

use PDO;
use PHPUnit\Framework\TestCase;

abstract class DatabaseTestCase extends TestCase {
    protected PDO $sqlite;

    protected function setUp(): void {
        parent::setUp();
        $this->sqlite = SqliteTestDatabase::createConnection();
    }

    protected function assertTableRowCount(string $table, int $expected): void {
        $statement = $this->sqlite->query("SELECT COUNT(*) FROM " . $table);
        $count = $statement !== false ? (int)$statement->fetchColumn() : -1;

        $this->assertSame($expected, $count);
    }

    protected function fetchUserByEmail(string $email): ?array {
        $statement = $this->sqlite->prepare(
            "SELECT id, email, display_name, is_active, bio FROM users WHERE email = :email"
        );
        $statement->execute(array("email" => $email));
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }
}
