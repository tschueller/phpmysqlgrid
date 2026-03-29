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
        "can_filter" => true,
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
    <title>MySQLGrid SQLite Demo</title>
    <link rel="stylesheet" href="../gridstyle.css">
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            margin: 24px;
            background: #f4f6f8;
            color: #1b2733;
        }

        .demo-shell {
            max-width: 1280px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #d7dee6;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(13, 38, 59, 0.08);
        }

        .demo-header {
            margin-bottom: 16px;
        }

        .demo-header h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .demo-header p {
            margin: 0;
            line-height: 1.45;
        }

        .demo-note {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 6px;
            background: #edf4ff;
            border: 1px solid #c6daf7;
            font-size: 14px;
        }

        .demo-actions {
            margin-top: 12px;
        }

        .demo-actions a {
            display: inline-block;
            text-decoration: none;
            color: #0f4d8a;
            background: #e6f0fb;
            border: 1px solid #b7d1ef;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 14px;
        }

        .demo-actions a:hover {
            background: #d8e9fb;
        }

        .demo-steps {
            margin-top: 10px;
            font-size: 14px;
        }

        .demo-steps a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            text-decoration: none;
            color: #0f4d8a;
            background: #e6f0fb;
            border: 1px solid #b7d1ef;
            border-radius: 4px;
            font-size: 13px;
        }

        .demo-steps a:hover {
            background: #d8e9fb;
        }

        .demo-steps a.is-active {
            background: #0f4d8a;
            color: #ffffff;
            border-color: #0f4d8a;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="demo-shell">
    <div class="demo-header">
        <h1>MySQLGrid Manual Demo (SQLite Persistent)</h1>
        <p>
            This page uses a persistent SQLite demo database and renders a user grid for manual testing.
            You can test add, edit, delete, filter, and sorting behavior with predefined sample rows.
        </p>
        <div class="demo-note">
            Data is kept between page loads. Use reset when you want the original seed data again.
        </div>
        <div class="demo-actions">
            <a href="index.php?reset=1">Reset demo data</a>
        </div>
        <div class="demo-steps">
            Rows per page:
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

    <?php $grid->execute(); ?>
</div>
</body>
</html>
