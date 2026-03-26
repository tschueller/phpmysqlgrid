<?php

declare(strict_types=1);

final class DemoSqliteDatabase {
    public static function createConnection(string $databaseFilePath, bool $reset = false): PDO {
        $directory = dirname($databaseFilePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if ($reset && is_file($databaseFilePath)) {
            unlink($databaseFilePath);
        }

        $isNewDatabase = !is_file($databaseFilePath);

        $pdo = new PDO("sqlite:" . $databaseFilePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("PRAGMA foreign_keys = ON");

        if ($isNewDatabase) {
            self::createSchema($pdo);
            self::seedData($pdo);
        }

        return $pdo;
    }

    private static function createSchema(PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE departments (
                name TEXT PRIMARY KEY
            )"
        );

        $pdo->exec(
            "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                display_name TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                role TEXT NOT NULL DEFAULT 'user',
                department_name TEXT NOT NULL,
                bio TEXT DEFAULT '',
                password TEXT NOT NULL,
                FOREIGN KEY (department_name) REFERENCES departments(name)
            )"
        );
    }

    private static function seedData(PDO $pdo): void {
        $departmentStatement = $pdo->prepare("INSERT INTO departments (name) VALUES (:name)");
        foreach (array("Engineering", "Marketing", "Support", "Finance") as $name) {
            $departmentStatement->execute(array("name" => $name));
        }

        $userStatement = $pdo->prepare(
            "INSERT INTO users (email, display_name, is_active, role, department_name, bio, password)
             VALUES (:email, :display_name, :is_active, :role, :department_name, :bio, :password)"
        );

        $rows = array(
            array(
                "email" => "alice+qa@example.test",
                "display_name" => "Jürgen Weißmann",
                "is_active" => 1,
                "role" => "admin",
                "department_name" => "Engineering",
                "bio" => "Team lead für Plattform-Services.\nEnjoys clean APIs, Test-Automation & Café-Reviews.",
                "password" => "alice-demo"
            ),
            array(
                "email" => "bob@example.test",
                "display_name" => "Jérôme Noël",
                "is_active" => 1,
                "role" => "editor",
                "department_name" => "Marketing",
                "bio" => "Creates campaign content, reviews copy, and tracks KPI's.",
                "password" => "bob-demo"
            ),
            array(
                "email" => "carla@example.test",
                "display_name" => "Carla Süß",
                "is_active" => 0,
                "role" => "user",
                "department_name" => "Support",
                "bio" => "Part-time support specialist.\nAvailable Tue/Thu, handles " .
                    "escalations #1 and customer notes like: \"läuft gut\".",
                "password" => "carla-demo"
            ),
            array(
                "email" => "dan@example.test",
                "display_name" => "Dan O'Neil",
                "is_active" => 1,
                "role" => "guest",
                "department_name" => "Finance",
                "bio" => "External consultant with read-only responsibilities (€ / £ reports).",
                "password" => "dan-demo"
            )
        );

        foreach ($rows as $row) {
            $userStatement->execute($row);
        }
    }
}
