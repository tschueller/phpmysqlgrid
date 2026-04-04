# MySQLGrid Workspace Instructions

## Project Overview

**phpMySQLGrid** is a flexible MySQL data grid library for PHP that provides a reusable class for displaying and managing MySQL database records in a grid/table format. The project is maintained as a Composer package under `tschueller/phpmysqlgrid`.

- **Current Version**: See `../CHANGELOG.md` for the latest release entry
- **PHP Requirement**: >= 8.2
- **Main Files**: `src/MySQLGrid.php` (core class), `assets/css/mysqlgrid.css` (default theme)
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

- **assets/css/mysqlgrid.css** - Main stylesheet for default theme
- **assets/js/** - Optional JavaScript assets (published together with CSS)
- **src/MySQLGridAssets.php** - Runtime asset URL/tag helpers with cache busting
- **src/MySQLGridAssetPublisher.php** - Publish-time asset copier and manifest writer
- **bin/phpmysqlgrid-assets** - CLI entrypoint used by host projects (`vendor/bin/phpmysqlgrid-assets`)
- Uses class-based styling approach (customizable via `$grid->cssClass`)
- For styling architecture, naming, migration phases, and accessibility goals, see `.github/instructions/styling.instructions.md`

## Source of Truth Map

Each topic has a primary source where guidance is authoritative. When working, consult the source for that topic:

| Topic | Primary Source | Context/References |
|-------|---|---|
| **src/MySQLGrid.php: visibility, backward compatibility, @internal markers** | `.github/instructions/php-core.instructions.md` | See also: copilot-instructions.md (this file) |
| **Tests: unit vs integration, SQLite fixtures, DB testing patterns** | `.github/instructions/testing.instructions.md` | See also: README.md (quick facts) |
| **Styling: CSS naming, theme migration, semantic classes, contrast** | `.github/instructions/styling.instructions.md` | See also: assets/css/mysqlgrid.css |
| **Accessibility: ARIA, labels, keyboard navigation, semantic HTML** | `.github/instructions/accessibility.instructions.md` | See also: styling.instructions.md (contrast rules) |
| **Current DB architecture: PDO, connections, why it changed** | `README.md` (Connection Modes section) | Historical context: `docs/refactoring-notes-v0.6.md` |
| **Asset publishing and cache busting (manifest + helpers)** | `.github/instructions/assets.instructions.md` | Runtime helper: `src/MySQLGridAssets.php`; publish command: `bin/phpmysqlgrid-assets`; usage examples: README.md |
| **What is being built next** | `TODO.md` | (product backlog only) |

## Development Conventions

### PHP & Code Style

- Uses `#[AllowDynamicProperties]` attribute for PHP 8.2+ compatibility in `src/MySQLGrid.php`
- Dynamic property usage is intentional and expected (due to project's age and backward compatibility needs)
- Class constructor initializes all public properties with sensible defaults
- **All new methods and properties must declare explicit visibility** (`public`, `protected`, or `private`). Never rely on the implicit `public` default.
- Prefer double quotes for string literals in project code when practical.
- Do not enable PHP-CS-Fixer's `single_quote` rule for this repository.
- Internal implementation methods that must stay `public` for unit-testability are annotated with `/** @internal */`. This signals to consumers that the method is not part of the public API, and PHPStan will warn if such methods are called from outside the package. Example:
  ```php
  /** @internal */
  public function validateColumns(): void { ... }
  ```
- Follow existing code style in `src/MySQLGrid.php` for consistency
- Write English comments and doc blocks where necessary, especially for new methods or complex logic
- Write new documentation files in English (for example `README.md` and `TODO.md`)

### Tooling & Quality

- Run syntax checks with `composer run lint:syntax`
- Run style checks with `composer run lint:style`
- Run static analysis with `composer run lint:static`
- Run the full quality gate with `composer run lint`
- Run unit tests with `composer run test`
- Static analysis config lives in `phpstan.neon.dist`
- PHPUnit config lives in `phpunit.xml.dist`
- Current PHPStan level is `8`
- CI is configured in `../.github/workflows/ci.yml`
- Dependabot is configured in `../.github/dependabot.yml`

### Testing Conventions

- For all testing conventions (unit + DB integration), see `.github/instructions/testing.instructions.md`

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

Refer to `.vscode/settings.json` for spell-check exceptions (cSpell words list). When adding new identifiers, constants, or technical terms that are flagged by the spell checker, update the `cSpell.words` list in `.vscode/settings.json` accordingly.

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
- Support for `<thead>`, `<tbody>`, `<tfoot>` elements (added in v0.5.11)
- Row IDs stored as data attributes (data-id attribute on each row)
- CSS classes control visual appearance and responsiveness

### Common Tasks

1. **Adding Field Types**: Add new constant (e.g., `PHPMYSQLGRID_NEWTYPE`), implement rendering/input logic in class methods
2. **Fixing PHP Warnings**: Look for deprecated functions (e.g., `get_magic_quotes_gpc` removed in PHP 8.1)
3. **CSS Adjustments**: Modify `assets/css/mysqlgrid.css` while maintaining theme consistency
4. **Asset Publishing**: Keep `src/MySQLGridAssetPublisher.php` and `src/MySQLGridAssets.php` in sync when changing filenames/paths (CSS and optional JS)
5. **Database Operations**: Ensure primary key handling is correct—it can be composite (array of columns)

### Important Caveats

- **Dynamic Properties**: The `MySQLGrid` class uses dynamic properties extensively; this is expected behavior
- **MySQL Connectivity**: Test with actual MySQL database; connection parameters are instance properties
- **Security**: Ensure SQL injection protection is maintained when modifying database operations
- **Legacy Code**: This library originated in 2003; some patterns reflect older PHP practices but work reliably

## Planning & Task Tracking

Before planning or implementing any feature or improvement:

1. **Read [TODO.md](../TODO.md)** to understand what is already planned, in progress, or deferred.
2. Avoid duplicating work that is already tracked there.
3. When completing a tracked item, mark it as done (`[x]`) in `TODO.md`.
4. When proposing new work that goes beyond the current request, add it to `TODO.md` rather than implementing it unsolicited.

## Resources

- Package info: See [composer.json](../composer.json) for dependencies and autoloading
- Setup and usage examples: See [README.md](../README.md)
- Version history: See [CHANGELOG.md](../CHANGELOG.md) for evolution and fixes
- Deferred work items: See [TODO.md](../TODO.md)
- Authors: Klaus Reimer (original), Thorsten Schüller (current maintainer)
