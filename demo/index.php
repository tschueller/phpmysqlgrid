<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../MySQLGrid.php";
require_once __DIR__ . "/DemoSqliteDatabase.php";

session_start();

$databaseFilePath = __DIR__ . "/demo.sqlite";
$resetDatabase = isset($_GET["reset"]) && $_GET["reset"] === "1";

$pdo = \DemoSqliteDatabase::createConnection($databaseFilePath, $resetDatabase);

$grid = new \MySQLGrid();
$grid->setDatabaseConnection($pdo, "pdo_sqlite");
$grid->table = "users";
$grid->primary = "id";
$grid->name = "demo_users_grid";
//$grid->style = "demo_users_grid";

$grid->can_add = true;
$grid->can_edit = true;
$grid->can_delete = true;
$grid->can_filter = true;
$grid->can_sort = true;
$grid->can_navigate = true;

$limit = isset($_GET["limit"]) ? (int) $_GET["limit"] : 5;
$limit = max(1, min(10, $limit));
$grid->limit = $limit;

$grid->columns = array(
    array(
        "field" => "email",
        "caption" => "Email",
        "type" => PHPMYSQLGRID_TEXT,
        "can_sort" => true,
        "can_filter" => true,
        "width" => 180
    ),
    array(
        "field" => "display_name",
        "caption" => "Display Name",
        "type" => PHPMYSQLGRID_TEXT,
        "can_sort" => true,
        "can_filter" => true,
        "width" => 180
    ),
    array(
        "field" => "role",
        "caption" => "Role",
        "type" => PHPMYSQLGRID_SELECTION,
        "selection" => array(
            "admin" => "Administrator",
            "editor" => "Editor",
            "user" => "User",
            "guest" => "Guest"
        ),
        "default" => "user",
        "can_sort" => true,
        "can_filter" => false,
        "width" => 140
    ),
    array(
        "field" => "is_active",
        "caption" => "Active",
        "type" => PHPMYSQLGRID_BOOLEAN,
        "default" => 1,
        "can_sort" => true,
        "can_filter" => false
    ),
    array(
        "field" => "department_name",
        "caption" => "Department",
        "type" => PHPMYSQLGRID_LOOKUP,
        "lookup_primary" => "name",
        "lookup_field" => "name",
        "lookup_table" => "departments",
        "can_sort" => true,
        "can_filter" => false,
        "width" => 145
    ),
    array(
        "field" => "bio",
        "caption" => "Bio",
        "type" => PHPMYSQLGRID_MULTILINETEXT,
        "size" => 38,
        "lines" => 3,
        "width" => 320,
        "height" => 80,
        "can_sort" => false,
        "can_filter" => false
    ),
    array(
        "field" => "password",
        "caption" => "Password",
        "type" => PHPMYSQLGRID_PASSWORD,
        "size" => 20,
        "maxlength" => 128,
        "can_sort" => false,
        "can_filter" => false,
        "width" => 170
    )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Demo - User Grid</title>
    <link rel="stylesheet" href="../gridstyle.css">
    <link rel="stylesheet" href="/demo/demo.css">
</head>
<body>
<div class="demo-shell">
    <div class="demo-header">
        <h1>First Demo</h1>
        <div class="subtitle">Simple User Grid</div>
        <p>
            This page uses a persistent SQLite demo database and renders a user grid for manual testing.
            You can test add, edit, delete, filter, and sorting behavior with predefined sample rows.
        </p>
        <div class="demo-note">
            Data is kept between page loads. Use reset when you want the original seed data again.
        </div>
        <div class="demo-actions">
            <a href="index.php?reset=1" class="reset">🔄 Reset demo data</a>
            <a href="index2.php">→ Second Demo (Products)</a>
        </div>
        <div class="demo-steps">
            <span>Rows per page:</span>
            <?php
            $baseParams = array_filter($_GET, static fn($k) => $k !== "limit" && $k !== "reset", ARRAY_FILTER_USE_KEY);
            for ($s = 1; $s <= 10; $s++):
                $params = $baseParams;
                $params["limit"] = $s;
                $url = "index.php?" . http_build_query($params);
            ?>
            <a href="<?= htmlspecialchars($url) ?>" <?= $s === $limit ? "class=\"is-active\"" : "" ?>><?= $s ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <div class="grid-section">
        <?php $grid->execute(); ?>
    </div>

    <div class="navigation">
        <a href="index2.php">Second Demo (Products) →</a>
    </div>
</div>
</body>
</html>
