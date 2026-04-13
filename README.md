# phpMySQLGrid

A flexible MySQL data grid library for PHP.

phpMySQLGrid provides a reusable class to display and manage MySQL table data in a grid with filtering, sorting, pagination, and CRUD actions.

![Example Grid](docs/images/grid1.png)


## Features

- Table rendering with customizable columns
- CRUD modes: view, add, edit, delete
- Field types: text, boolean, lookup, password, selection, multiline text, file
- Sorting, filtering, and pagination
- PDO-based database access (MySQL, MariaDB, SQLite)
- Hook callbacks for add, edit, and delete workflows
- CSS-based styling with included themes


## Requirements

- PHP 8.2 or newer
- MySQL-compatible database


## Installation

Install with Composer:

    composer require tschueller/phpmysqlgrid

Assets (CSS/JS) are not automatically published. Use the provided CLI command from your host project (see [Asset Publishing](#asset-publishing-for-host-projects) below) to copy them into your web root.:

    php vendor/bin/phpmysqlgrid-assets


For development in this repository:

    composer install


## Upgrade Guide (v0.5 to v1.0)

See the dedicated upgrade guide in [docs/upgrade-guide.md](docs/upgrade-guide.md#v05---v10).


## Simple Example

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpMySQLGrid\MySQLGrid;

session_start();

$grid = new MySQLGrid();
$grid->hostname = '127.0.0.1';
$grid->username = 'root';
$grid->password = 'secret-password';
$grid->database = 'test_db';
$grid->table = 'users';
$grid->primary = 'id';
$grid->name = 'users_grid';
$grid->limit = 10;

$grid->columns = array(
    array('field' => 'id', 'caption' => 'ID', 'can_sort' => true, 'can_filter' => false),
    array('field' => 'username', 'caption' => 'Username'),
    array('field' => 'email', 'caption' => 'Email'),
    array('field' => 'active', 'caption' => 'Active', 'type' => PHPMYSQLGRID_BOOLEAN),
);

$grid->execute();
```


## Database Connection Modes

phpMySQLGrid supports two connection modes:

1. Default mode (legacy-compatible): internal PDO connection created automatically from `hostname`, `port`, `username`, `password`, `database` properties.
2. Injected connection mode: externally managed PDO connection via `setDatabaseConnection()`.

### 1) Default mode (internal PDO)

Set the connection properties and call `execute()`. The class creates a `PDO` connection internally using `mysql:host=…;port=…;dbname=…;charset=utf8mb4`. This mode is fully backward compatible with existing integrations that set these properties.

```php
$grid = new PhpMySQLGrid\MySQLGrid();
$grid->hostname = "127.0.0.1";
$grid->username = "root";
$grid->password = "secret-password";
$grid->database = "test_db";
$grid->table = "users";
$grid->primary = "id";
$grid->execute();
```

### 2) Injected PDO mode

Use this mode when you already manage your DB connection externally or when you want to run integration tests against SQLite.

```php
$pdo = new PDO("mysql:host=127.0.0.1;dbname=test_db;charset=utf8mb4", "root", "secret-password");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$grid = new PhpMySQLGrid\MySQLGrid();
$grid->setDatabaseConnection($pdo, "pdo_mysql");
$grid->table = "users";
$grid->primary = "id";
$grid->execute();
```

Notes:

- In injected mode, the provided PDO connection is reused for all database operations.
- In injected mode, the caller owns the connection lifecycle.
- All database operations use PDO exclusively; mysqli is no longer supported.


## Demo Page

This repository includes a manual demo page with seeded user data and a persistent SQLite database file.

Start the demo server from the project root:

    composer run demo:start

Then open:

    http://127.0.0.1:8000/

Notes:

- The demo database is stored at `demo/demo.sqlite` and is kept across page loads.
- Use `http://127.0.0.1:8000/demo/index.php?reset=1` to recreate schema and seed data.
- Add/edit/delete/filter/sort can be tested directly in the browser.


## Asset Publishing for Host Projects

By default, package assets are not automatically copied into your web root.
Publish them from your host project with:

    php vendor/bin/phpmysqlgrid-assets

Default target is:

    assets/phpmysqlgrid

Override target path with either an argument

    php vendor/bin/phpmysqlgrid-assets --target public/assets/phpmysqlgrid

or an environment variable:

    # Bash
    PHPMYSQLGRID_ASSET_TARGET=public/assets/phpmysqlgrid php vendor/bin/phpmysqlgrid-assets

    # PowerShell:
    $env:PHPMYSQLGRID_ASSET_TARGET="public/assets/phpmysqlgrid"; php vendor/bin/phpmysqlgrid-assets

### Automatic Publishing in a Host Project

In your host project's `composer.json`, you can run asset publishing automatically after install/update:

```json
{
    "scripts": {
        "post-install-cmd": [
            "@grid-assets:publish"
        ],
        "post-update-cmd": [
            "@grid-assets:publish"
        ],
        "grid-assets:publish": [
            "php vendor/bin/phpmysqlgrid-assets --target public/assets/phpmysqlgrid"
        ]
    }
}
```


## Include CSS

Use `MySQLGridAssets` for cache-busted URLs/tags.

The default CSS include is split into:
- `mysqlgrid-base.css`
- `mysqlgrid-theme-default.css`

Recommended default theme usage:

```php
use PhpMySQLGrid\MySQLGridAssets;

MySQLGridAssets::configure('/assets/phpmysqlgrid');
echo MySQLGridAssets::cssTagsFor();
```

Dark theme example:

```php
use PhpMySQLGrid\MySQLGridAssets;

MySQLGridAssets::configure('/assets/phpmysqlgrid');
echo MySQLGridAssets::cssTagsFor('dark');
```

When you use themes, set `$grid->cssClass` so the grid uses the matching theme scope:

```php
$grid->cssClass = "theme-default";
// or
$grid->cssClass = "theme-dark";
```

For full asset helper documentation (themes, custom themes, cache busting internals, method reference, and legacy/deprecated methods), see [docs/assets.md](docs/assets.md).


## Code Quality Checks / Unit Tests

Run all quality checks:

    composer run lint

Run checks individually:

    composer run lint:syntax
    composer run lint:style
    composer run lint:static

Run unit tests:

    composer run test


## Contributing

For contribution details, see [CONTRIBUTING.md](CONTRIBUTING.md).


##  Releasing

For release process details, see [releasing.md](docs/releasing.md).


## Security

Please report vulnerabilities according to [SECURITY.md](SECURITY.md).


## License

[MIT](LICENSE)
