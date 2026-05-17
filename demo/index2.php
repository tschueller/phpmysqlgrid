<?php

declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/DemoSqliteDatabase.php";
require_once __DIR__ . "/DemoAsset.php";

use PhpMySQLGrid\MySQLGrid;

session_start();

if (!isset($_GET["theme"])) {
    $_GET["theme"] = "dark";
}

switch ($_GET["theme"]) {
    case "default":
        $selectedTheme = "default";
        break;
    case "light":
        $selectedTheme = "light";
        break;
    case "dark":
    default:
        $selectedTheme = "dark";
        break;
}


$databaseFilePath = __DIR__ . "/demo.sqlite";
$resetDatabase = isset($_GET["reset"]) && $_GET["reset"] === "1";

$pdo = \DemoSqliteDatabase::createConnection($databaseFilePath, $resetDatabase);

$grid = new MySQLGrid();
$grid->setDatabaseConnection($pdo, "pdo_sqlite");
$grid->table = "products";
$grid->primary = "id";
$grid->name = "demo_products_grid";
$grid->cssClass = "theme-" . $selectedTheme;

// Secure defaults in demo.
$grid->allow_url_import = false;
$grid->max_file_size = 2 * 1024 * 1024; // 2 MB
$grid->allowed_file_extensions = array("jpg", "jpeg", "png", "gif", "webp");
$grid->allowed_file_mime_types = array("image/jpeg", "image/png", "image/gif", "image/webp");
$grid->csrf_protection_enabled = true;

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
        "field" => "name",
        "caption" => "Product Name",
        "type" => PHPMYSQLGRID_TEXT,
        "can_sort" => true,
        "can_filter" => true,
        "width" => 280,
        "maxlength" => 200
    ),
    array(
        "field" => "category_id",
        "caption" => "Category",
        "type" => PHPMYSQLGRID_LOOKUP,
        "lookup_primary" => "id",
        "lookup_field" => "name",
        "lookup_table" => "categories",
        "can_sort" => true,
        "can_filter" => true,
        "width" => 190
    ),
    array(
        "field" => "status",
        "caption" => "Status",
        "type" => PHPMYSQLGRID_SELECTION,
        "selection" => array(
            "active" => "Active",
            "inactive" => "Inactive",
            "discontinued" => "Discontinued",
            "coming_soon" => "Coming Soon"
        ),
        "default" => "active",
        "can_sort" => true,
        "can_filter" => true,
        "width" => 130
    ),
    array(
        "field" => "released_date",
        "caption" => "Release Date",
        "type" => PHPMYSQLGRID_TEXT,
        "can_sort" => true,
        "can_filter" => false,
        "width" => 140,
        "convert_input" => function (MySQLGrid $grid, mixed $value, int $colIndex): string {
            if (empty($value)) {
                return "";
            }
            $dateValue = trim((string)$value);

            // Save as YYYY-MM-DD regardless of whether the user entered DD.MM.YYYY or YYYY-MM-DD.
            $parsedDate = date_create_from_format("d.m.Y", $dateValue)
                ?: date_create_from_format("Y-m-d", $dateValue);

            if ($parsedDate === false) {
                return $dateValue;
            }

            return date_format($parsedDate, "Y-m-d");
        },
        "convert_output" => function (MySQLGrid $grid, mixed $value, int $colIndex, mixed $row, bool $isEditMode): string {
            if (!$value) {
                return $isEditMode ? "" : '<span class="demo-muted">-</span>';
            }

            // Support both canonical and legacy formats in existing demo data.
            $date = date_create_from_format("Y-m-d", (string)$value)
                ?: date_create_from_format("d.m.Y", (string)$value);

            if ($date) {
                return date_format($date, "d.m.Y");
            }

            return $isEditMode ? trim((string)$value) : '<span class="demo-muted">Invalid</span>';
        }
    ),
    array(
        "field" => "thumbnail",
        "caption" => "Thumbnail",
        "type" => PHPMYSQLGRID_FILE,
        "accept" => "image/*",
        "size" => 30,
        "show_url_input" => false,  // URL import disabled; use file upload only
        "can_sort" => false,
        "can_filter" => false,
        "width" => 180,
        "convert_output" => function (MySQLGrid $grid, mixed $value, int $colIndex, mixed $row, bool $isEditMode): string {
            if (!$isEditMode) {
                if (!$value) {
                    return '<span class="demo-muted-small">No image</span>';
                }
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer((string)$value) ?: "image/jpeg";
                $base64 = base64_encode((string)$value);
                return '<img src="data:' . htmlspecialchars($mimeType, ENT_QUOTES, "UTF-8") . ';base64,' . $base64 . '" class="demo-thumbnail" alt="thumbnail">';
            }
            // edit mode: show existing thumbnail preview if present
            if (!$value) {
                return '<span class="demo-muted-small">No image</span>';
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer((string)$value) ?: "image/jpeg";
            $base64 = base64_encode((string)$value);
            return '<img src="data:' . htmlspecialchars($mimeType, ENT_QUOTES, "UTF-8") . ';base64,' . $base64 . '" class="demo-thumbnail" alt="thumbnail">';
        }
    )
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Second Demo - Product Grid</title>
    <?= \DemoAsset::gridStylesheetTag($_GET) ?>
    <?= \DemoAsset::demoStylesheetTag() ?>
</head>
<body>
<div class="demo-shell">
    <div class="demo-header">
        <h1>Second Demo</h1>
        <div class="subtitle">Advanced Product Catalog Grid</div>
        <p>
            This advanced demo showcases the grid library with multiple column types, advanced features, and complex data relationships.
            Use this for internal testing of different field types and grid interactions.
        </p>
        <p class="demo-note">
            File upload hardening is active: URL import disabled, max 2 MB, allowed extensions: jpg/jpeg/png/gif/webp, MIME types validated via finfo.
        </p>
        <div class="demo-actions">
            <a href="index2.php?reset=1" class="reset">🔄 Reset Demo Data</a>
            <a href="index.php">← Back to First Demo</a>

            <form method="get" action="index2.php" style="display:inline-flex;gap:8px;align-items:center;">
                <?php
                $themeParams = array_filter($_GET, static fn($k) => $k !== "theme" && $k !== "reset", ARRAY_FILTER_USE_KEY);
                foreach ($themeParams as $key => $value):
                    if (!is_scalar($value)) {
                        continue;
                    }
                ?>
                    <input type="hidden" name="<?= htmlspecialchars((string)$key, ENT_QUOTES, "UTF-8") ?>" value="<?= htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8") ?>">
                <?php endforeach; ?>
                <label for="theme-select">Theme:</label>
                <select id="theme-select" name="theme" onchange="this.form.submit()">
                    <option value="default" <?= $selectedTheme === "default" ? "selected" : "" ?>>Default</option>
                    <option value="dark" <?= $selectedTheme === "dark" ? "selected" : "" ?>>Dark</option>
                    <option value="light" <?= $selectedTheme === "light" ? "selected" : "" ?>>Light</option>
                </select>
            </form>
        </div>

        <div class="demo-steps">
            <span>Rows per page:</span>
            <?php
            $baseParams = array_filter($_GET, static fn($k) => $k !== "limit" && $k !== "reset", ARRAY_FILTER_USE_KEY);
            for ($s = 1; $s <= 10; $s++):
                $params = $baseParams;
                $params["limit"] = $s;
                $url = "index2.php?" . http_build_query($params);
            ?>
            <a href="<?= htmlspecialchars($url) ?>" <?= $s === $limit ? "class=\"is-active\"" : "" ?>><?= $s ?></a>
            <?php endfor; ?>
        </div>
    </div>

    <div class="grid-section">
        <?php $grid->execute(); ?>
    </div>

    <div class="navigation">
        <a href="index.php">← First Demo (Users Table)</a>
    </div>
</div>
</body>
</html>
