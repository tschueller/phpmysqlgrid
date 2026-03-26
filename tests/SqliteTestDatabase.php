<?php

declare(strict_types=1);

namespace MySQLGridTests;

use PDO;

final class SqliteTestDatabase {
    public static function createConnection(): PDO {
        $pdo = new PDO("sqlite::memory:");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON");

        self::createSchema($pdo);
        self::seedData($pdo);

        return $pdo;
    }

    private static function createSchema(PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                display_name TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                bio TEXT DEFAULT ''
            )"
        );
    }

    private static function seedData(PDO $pdo): void {
        $statement = $pdo->prepare(
            "INSERT INTO users (email, display_name, is_active, bio)
             VALUES (:email, :display_name, :is_active, :bio)"
        );

        $rows = array(
            array(
                "email" => "alice@example.test",
                "display_name" => "Alice",
                "is_active" => 1,
                "bio" => "Seed user Alice"
            ),
            array(
                "email" => "bob@example.test",
                "display_name" => "Bob",
                "is_active" => 1,
                "bio" => "Seed user Bob"
            )
        );

        foreach ($rows as $row) {
            $statement->execute($row);
        }
    }
}
