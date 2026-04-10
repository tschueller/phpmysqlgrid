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
                email TEXT NOT NULL DEFAULT '',
                display_name TEXT NOT NULL DEFAULT '',
                is_active INTEGER NOT NULL DEFAULT 1,
                role TEXT NOT NULL DEFAULT 'user',
                department_name TEXT,
                bio TEXT DEFAULT '',
                password TEXT NOT NULL DEFAULT '',
                FOREIGN KEY (department_name) REFERENCES departments(name)
            )"
        );

        $pdo->exec(
            "CREATE TABLE categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE
            )"
        );

        $pdo->exec(
            "CREATE TABLE products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL DEFAULT '',
                category_id INTEGER NOT NULL,
                price REAL NOT NULL DEFAULT 0.00,
                quantity_in_stock INTEGER NOT NULL DEFAULT 0,
                description TEXT DEFAULT '',
                is_featured INTEGER NOT NULL DEFAULT 0,
                status TEXT NOT NULL DEFAULT 'active',
                released_date TEXT DEFAULT NULL,
                thumbnail BLOB DEFAULT NULL,
                FOREIGN KEY (category_id) REFERENCES categories(id)
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
            ),
            array(
                "email" => "eva@example.test",
                "display_name" => "Eva Müller",
                "is_active" => 1,
                "role" => "editor",
                "department_name" => "Engineering",
                "bio" => "Backend developer focusing on data pipelines.",
                "password" => "eva-demo"
            ),
            array(
                "email" => "frank@example.test",
                "display_name" => "Frank Becker",
                "is_active" => 0,
                "role" => "user",
                "department_name" => "Finance",
                "bio" => "Budget analyst, currently on leave.",
                "password" => "frank-demo"
            ),
            array(
                "email" => "grace@example.test",
                "display_name" => "Grace Owusu",
                "is_active" => 1,
                "role" => "user",
                "department_name" => "Support",
                "bio" => "Customer success specialist, fluent in French and English.",
                "password" => "grace-demo"
            ),
            array(
                "email" => "hiro@example.test",
                "display_name" => "Hiro Tanaka",
                "is_active" => 1,
                "role" => "editor",
                "department_name" => "Marketing",
                "bio" => "Content strategist and UX copywriter.",
                "password" => "hiro-demo"
            ),
            array(
                "email" => "iris@example.test",
                "display_name" => "Iris Kowalski",
                "is_active" => 1,
                "role" => "admin",
                "department_name" => "Engineering",
                "bio" => "Senior engineer, owns the deployment pipeline.",
                "password" => "iris-demo"
            ),
            array(
                "email" => "jakob@example.test",
                "display_name" => "Jakob Strauss",
                "is_active" => 0,
                "role" => "guest",
                "department_name" => "Finance",
                "bio" => "Temporary contractor, access expires end of quarter.",
                "password" => "jakob-demo"
            )
        );

        foreach ($rows as $row) {
            $userStatement->execute($row);
        }

        // Seed categories
        $categoryStatement = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
        $categories = array("Electronics", "Software", "Books", "Office Supplies", "Services");
        foreach ($categories as $category) {
            $categoryStatement->execute(array("name" => $category));
        }

        // Seed products
        $productStatement = $pdo->prepare(
            "INSERT INTO products (name, category_id, price, quantity_in_stock, description, is_featured, status, released_date)
             VALUES (:name, :category_id, :price, :quantity_in_stock, :description, :is_featured, :status, :released_date)"
        );

        $products = array(
            array(
                "name" => "Premium Wireless Headphones",
                "category_id" => 1,
                "price" => 129.99,
                "quantity_in_stock" => 45,
                "description" => "High-quality wireless headphones with noise cancellation.\nBattery life: 30 hours\nWireless protocol v5.0",
                "is_featured" => 1,
                "status" => "active",
                "released_date" => "2024-01-15"
            ),
            array(
                "name" => "Multi-Port Adapter",
                "category_id" => 1,
                "price" => 49.99,
                "quantity_in_stock" => 120,
                "description" => "7-in-1 multi-port adapter with video output, high-speed data ports, and SD card reader\nSupports up to 100W power delivery",
                "is_featured" => 1,
                "status" => "active",
                "released_date" => "2023-08-22"
            ),
            array(
                "name" => "Cloud Sync Professional",
                "category_id" => 2,
                "price" => 299.00,
                "quantity_in_stock" => 0,
                "description" => "Enterprise-grade file synchronization software\nUnlimited file versions, 24/7 support, API access",
                "is_featured" => 0,
                "status" => "discontinued",
                "released_date" => "2022-06-10"
            ),
            array(
                "name" => "Advanced Networking eBook",
                "category_id" => 3,
                "price" => 39.99,
                "quantity_in_stock" => 1000,
                "description" => "Comprehensive guide to modern networking protocols\n750+ pages, includes labs and examples",
                "is_featured" => 0,
                "status" => "active",
                "released_date" => "2023-03-05"
            ),
            array(
                "name" => "Ergonomic Mechanical Keyboard",
                "category_id" => 1,
                "price" => 189.99,
                "quantity_in_stock" => 32,
                "description" => "Premium mechanical keyboard with multi-color backlighting\nCustomizable switches, hot-swappable",
                "is_featured" => 1,
                "status" => "active",
                "released_date" => "2024-02-28"
            ),
            array(
                "name" => "4K Monitor 32 Inch",
                "category_id" => 1,
                "price" => 599.99,
                "quantity_in_stock" => 8,
                "description" => "Ultra-HD 4K IPS display\nWide color gamut, support for single-cable input and daisy-chaining",
                "is_featured" => 0,
                "status" => "active",
                "released_date" => "2023-11-12"
            ),
            array(
                "name" => "Database Design Fundamentals",
                "category_id" => 3,
                "price" => 45.00,
                "quantity_in_stock" => 500,
                "description" => "Learn database design, normalization, and best practices.\nIncludes SQL and NoSQL examples",
                "is_featured" => 0,
                "status" => "active",
                "released_date" => "2024-03-01"
            ),
            array(
                "name" => "Desk Organizer Set",
                "category_id" => 4,
                "price" => 24.99,
                "quantity_in_stock" => 250,
                "description" => "3-piece desk organizer with drawers and file holder\nMade from sustainable bamboo",
                "is_featured" => 0,
                "status" => "active",
                "released_date" => "2024-01-09"
            ),
            array(
                "name" => "Website Redesign Service",
                "category_id" => 5,
                "price" => 2500.00,
                "quantity_in_stock" => 1,
                "description" => "Complete website redesign including UX/UI consulting\nProject-based engagement, 4-6 weeks delivery",
                "is_featured" => 0,
                "status" => "active",
                "released_date" => "2023-12-15"
            ),
            array(
                "name" => "Portable SSD 2TB",
                "category_id" => 1,
                "price" => 249.99,
                "quantity_in_stock" => 18,
                "description" => "Ultra-fast external solid-state drive with high-speed data interface\nRead speeds: 1050 MB/s, write speeds: 1000 MB/s",
                "is_featured" => 1,
                "status" => "active",
                "released_date" => "2024-02-14"
            )
        );

        foreach ($products as $product) {
            $productStatement->execute($product);
        }
    }
}
