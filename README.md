# phpMySQLGrid

A flexible MySQL data grid library for PHP.

phpMySQLGrid provides a reusable class to display and manage MySQL table data in a grid with filtering, sorting, pagination, and CRUD actions.

## Features

- Table rendering with customizable columns
- CRUD modes: view, add, edit, delete
- Field types: text, boolean, lookup, password, selection, multiline text, file
- Sorting, filtering, and pagination
- Hook callbacks for add, edit, and delete workflows
- CSS-based styling with included themes

## Requirements

- PHP 8.2 or newer
- MySQL-compatible database

## Installation

Install with Composer:

    composer require tschueller/phpmysqlgrid

For development in this repository:

    composer install

## Simple Example

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/MySQLGrid.php';

session_start();

$grid = new MySQLGrid();
$grid->hostname = '127.0.0.1';
$grid->username = 'root';
$grid->password = '';
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

Include one of the CSS files in your page to style the grid:

```html
<link rel="stylesheet" href="gridstyle.css">
```

## Project Files

- MySQLGrid.php: Main library class
- gridstyle.css: Default grid style
- gridstyle_icon_font.css: Icon font style variant
- phpstan.neon.dist: Static analysis configuration
- TODO.md: Planned follow-up tasks
- CHANGELOG.md: Release history

## Quality Checks

Run all checks:

    composer run lint

Run checks individually:

    composer run lint:syntax
    composer run lint:style
    composer run lint:static

## Current Static Analysis Level

The project currently uses PHPStan level 8.

## Contributing

1. Keep changes backward compatible when possible.
2. Follow the existing coding style in the repository.
3. Run lint checks before submitting changes.
4. Update CHANGELOG.md for user-visible fixes and improvements.

## License

BSD-3-Clause
