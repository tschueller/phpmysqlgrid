# MySQLGrid Workspace Instructions

## Project Overview

**phpMySQLGrid** is a flexible MySQL data grid library for PHP that provides a reusable class for displaying and managing MySQL database records in a grid/table format. The project is maintained as a Composer package under `tschueller/phpmysqlgrid`.

- **Current Version**: See `../CHANGELOG.md` for the latest release entry
- **PHP Requirement**: >= 8.2
- **Main Files**: `MySQLGrid.php` (core class), CSS stylesheets for theming
- **Package Type**: PHP library (Composer-managed)

## Architecture & Key Components

### Core Class: `MySQLGrid`

The main class (`MySQLGrid`) is a flexible data grid widget that:
- Connects to MySQL databases and displays table data
- Supports multiple modes: `VIEWMODE`, `ADDMODE`, `EDITMODE`, `DELETEMODE`
- Provides field types: TEXT, BOOLEAN, LOOKUP, PASSWORD, SELECTION, MULTILINETEXT, FILE
- Offers full CRUD operations with customizable buttons and actions
- Includes filtering, sorting, and pagination capabilities

### Key Configuration Properties

```php
$grid->hostname, $grid->port, $grid->username, $grid->password  // DB connection
$grid->database, $grid->table
$grid->primary                    // Primary key column(s)
$grid->mode                       // Current operation mode
$grid->can_add, $grid->can_edit, $grid->can_delete, $grid->can_sort
$grid->can_filter, $grid->can_navigate
$grid->columns, $grid->actions    // Field and action definitions
```

### Styling & Themes

- **gridstyle.css** - Main stylesheet for default theme
- **gridstyle_icon_font.css** - Icon font variant
- Uses class-based styling approach (customizable via `$grid->cssClass`)

## Development Conventions

### PHP & Code Style

- Uses `#[AllowDynamicProperties]` attribute for PHP 8.2+ compatibility (line 48 of MySQLGrid.php)
- Dynamic property usage is intentional and expected (due to project's age and backward compatibility needs)
- Class constructor initializes all public properties with sensible defaults
- Follow existing code style in MySQLGrid.php for consistency
- Write English comments and doc blocks where necessary, especially for new methods or complex logic
- Write new documentation files in English (for example `README.md` and `TODO.md`)

### Tooling & Quality

- Run syntax checks with `composer run lint:syntax`
- Run style checks with `composer run lint:style`
- Run static analysis with `composer run lint:static`
- Run the full quality gate with `composer run lint`
- Static analysis config lives in `phpstan.neon.dist`
- Current PHPStan level is `8`
- CI is configured in `../.github/workflows/ci.yml`
- Dependabot is configured in `../.github/dependabot.yml`

### Repository Standards

- Keep `CONTRIBUTING.md` and `SECURITY.md` up to date.
- Use issue templates from `../.github/ISSUE_TEMPLATE` and PR template from `../.github/pull_request_template.md`.
- `CODE_OF_CONDUCT.md` is optional for now and can be added when contributor activity grows.
- Prefer simple Conventional Commit-style messages such as `feat:`, `fix:`, `docs:`, `refactor:`, `test:`, and `chore:`.
- For new features, prefer `feat:` messages with a short, imperative summary (optionally with a scope like `feat(grid): ...`).

### Constants & Naming

All magic constants are prefixed with `PHPMYSQLGRID_`:
- Field types: `PHPMYSQLGRID_TEXT`, `PHPMYSQLGRID_BOOLEAN`, etc.
- Modes: `PHPMYSQLGRID_VIEWMODE`, `PHPMYSQLGRID_ADDMODE`, `PHPMYSQLGRID_EDITMODE`, `PHPMYSQLGRID_DELETEMODE`
- Button styles: `PHPMYSQLGRID_TEXTBUTTON`, `PHPMYSQLGRID_IMAGEBUTTON`

Refer to `.vscode/settings.json` for spell-check exceptions (cSpell words list).

### Maintenance & Updates

- Check [CHANGELOG.md](../CHANGELOG.md) for version history and changelog
- Maintain backward compatibility—this library is used in production systems
- Test with PHP 8.2+ for compatibility warnings (see v0.5.9+ fixes)
- When fixing issues, add entry to CHANGELOG.md with date and description
- Keep README examples in sync with real project behavior
- Track deferred work items (for example future PHPStan level increases) in `TODO.md`

## When Working on MySQLGrid

### Understanding Grid Rendering

- The grid renders database results as HTML tables with styling
- Support for thead, tbody, tfoot elements (added in v0.5.11)
- Row IDs stored as data attributes (data-id attribute on each row)
- CSS classes control visual appearance and responsiveness

### Common Tasks

1. **Adding Field Types**: Add new constant (e.g., `PHPMYSQLGRID_NEWTYPE`), implement rendering/input logic in class methods
2. **Fixing PHP Warnings**: Look for deprecated functions (e.g., `get_magic_quotes_gpc` removed in PHP 8.1)
3. **CSS Adjustments**: Modify `gridstyle.css` or `gridstyle_icon_font.css` while maintaining theme consistency
4. **Database Operations**: Ensure primary key handling is correct—it can be composite (array of columns)

### Important Caveats

- **Dynamic Properties**: The `MySQLGrid` class uses dynamic properties extensively; this is expected behavior
- **MySQL Connectivity**: Test with actual MySQL database; connection parameters are instance properties
- **Security**: Ensure SQL injection protection is maintained when modifying database operations
- **Legacy Code**: This library originated in 2003; some patterns reflect older PHP practices but work reliably

## Resources

- Package info: See [composer.json](../composer.json) for dependencies and autoloading
- Setup and usage examples: See [README.md](../README.md)
- Version history: See [CHANGELOG.md](../CHANGELOG.md) for evolution and fixes
- Deferred work items: See [TODO.md](../TODO.md)
- Authors: Klaus Reimer (original), Thorsten Schüller (current maintainer)
