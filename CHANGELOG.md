# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]
- Convert code style to 1TBS.
- Require PHP >= 8.2.
- Add Composer lint workflow (syntax/style/static) with PHPStan level 8.
- Add README and TODO documentation.
- Change license from BSD-3-Clause to MIT.
- Add PHPUnit configuration and automated tests.

### Added
- Add `MySQLGridAssets` static helper for stylesheet URL and `<link>` generation with cache-busting query parameter.
- Add `jsUrl()` / `jsTag()` helpers and asset publishing support for future JavaScript files in `assets/js`.
- Add `MySQLGridAssetPublisher` and CLI command `phpmysqlgrid-assets` for publishing package CSS into host project paths.
- Add demo asset resolver with `repo`/`published` mode toggle via query (`asset_mode`) or environment variable (`PHPMYSQLGRID_DEMO_ASSET_MODE`).

### Changed
- Move core library file from `MySQLGrid.php` to `src/MySQLGrid.php`.
- Use more friendly colors for grid header, footer, and action elements (changed from dark gray #545e67 to modern blue #5B8FBE with WCAG AA contrast compliance for better readability and aesthetics).
- Add icon color uses separate variable `--phpmysqlgrid-icon-add` (amber #F59E0B) instead of confirm color for better contrast and visual distinction on blue background.
- Move default stylesheet from `gridstyle.css` to `assets/css/mysqlgrid.css`.
- Update Composer autoload and tooling paths for `src/` layout.
- Update demo pages to use hash-based stylesheet URLs for cache busting.

### Migration
- Replace direct includes of `MySQLGrid.php` with Composer autoload usage.
- Replace old stylesheet path references with published asset path (for example `/assets/phpmysqlgrid/mysqlgrid.css`).

### Added
- Add inline SVG icon support: nine new public properties (`svgIconEdit`, `svgIconDelete`, `svgIconConfirm`, `svgIconCancel`, `svgIconAdd`, `svgSortAscActive`, `svgSortAscInactive`, `svgSortDescActive`, `svgSortDescInactive`) default to Bootstrap Icons (MIT). Override any property to use custom SVGs.
- Add `initSvgIcons()` internal method for SVG icon initialization.
- Add `renderIcon()` private helper replacing `renderUnicodeControl()` for all action and sort controls.

### Added
- Add injected database connection support via `setDatabaseConnection(mixed $connection, string $driver)`.
- Add support for PDO drivers (`pdo_mysql`, `pdo_sqlite`) via `setDatabaseConnection()`.
- Add internal DB helper methods for PDO query execution, result row fetching, and numeric-row lookup queries.
- Replace internal `mysqli` connection with an internally-created `PDO` connection when using legacy hostname/username/password/database properties — fully backward-compatible.
- Add real integration tests for DB methods using injected `pdo_sqlite`.
- Add execute-path integration tests for confirm add/edit/delete request flows.
- Add security regression tests for SQL injection payload handling and XSS encoding scenarios.
- Add testing guidance in `.github/instructions/testing.instructions.md`.
- Add internal refactoring notes in `docs/refactoring-notes-v0.6.md`.
- Add a manual SQLite demo page at `demo/index.php` with seeded user data for interactive grid testing.
- Add Composer script `demo:start` to run the local demo server.

### Changed
- Expand `MySQLGrid` class-level PHPDoc with a clearer API overview and comprehensive `@property` documentation for dynamic public configuration.
- Document relevant default values for public dynamic properties and internationalized text labels in class PHPDoc.
- Mark public test-only internals with `@internal` + `@ignore` to keep generated public API docs focused on supported surface.
- Move class PHPDoc placement so IDE hover/help reliably resolves `@property` metadata.
- Add `getPrimaryColumnForSingleColumnContext()` helper and narrow single-column SQL identifier usage for clearer static analysis typing.
- Add a new README section describing the `MySQLGrid` class responsibilities and `execute()` lifecycle at a glance.
- Update PHP core instruction rules to require class-level API overview docs and `@property` defaults for dynamic public configuration.
- Replace Unicode character glyphs and FontAwesome icon-font controls with inline SVG icons using `fill="currentColor"` — icon colors continue to be driven by CSS custom properties (`--phpmysqlgrid-icon-edit`, `--phpmysqlgrid-icon-delete`, `--phpmysqlgrid-icon-confirm`).
- Remove `$use_icon_font` property and all FontAwesome markup generation (breaking change: the property is no longer recognised).
- Update `gridstyle.css`: rename `.phpmysqlgrid-unicode-icon` selector family to `.phpmysqlgrid-icon`; add SVG sizing rules; remove `.fa-*` icon-font color selectors.
- Remove empty `gridstyle_icon_font.css` file.
- Remove FontAwesome CDN link from demo page.
- Refactor DB write paths and query methods to PDO exclusively; remove all internal `mysqli` code.
- Migrate former adapter-based DB tests to real `MySQLGrid` code-path integration tests.
- Remove obsolete test adapter layer and adapter-specific integration test suite.
- Route lookup query rendering in `drawEditControls()` through a DB-agnostic query helper.
- Update `prepareData()` behavior for injected PDO mode to use consistent query/result handling.
- Migrate renderer and theme usage to semantic grid class names (for example `phpmysqlgrid-header`, `phpmysqlgrid-action`, `phpmysqlgrid-cell`, and state modifiers).
- Add deprecation guidance for legacy concatenated CSS class selectors and document semantic-class migration in README.

### Security
- Replace mysqli string-built SQL in add/edit/delete write paths with PDO prepared statements.
- Parameterize active filter values in PDO `prepareData()` queries (both count and data selects).
- Add raw SQL fragment guard for dangerous token patterns in `filter` and `lookup_filter` (`;`, `--`, `/*`, `*/`, null byte).
- Keep HTML output encoding test coverage for XSS payloads in `convertToHtmlEntities()`.

### Testing
- Cover real DB method execution paths for: `addData`, `editData`, `deleteData`, `useAllColumns`, `prepareData`, `unprepareData`.
- Cover lookup rendering query path in `drawEditControls()`.
- Cover execute/render/request integration path against injected PDO connection.


## [0.5.11] - 2024-03-04
- Add `thead`, `tbody`, and `tfoot`.
- Add row id as `data-id` attribute.

## [0.5.10] - 2024-02-29
- Fix PHP 8.2 warnings.

## [0.5.9] - 2023-11-15
- Add `#[AllowDynamicProperties]` annotation to fix PHP 8.2 warnings.

## [0.5.8] - 2020-09-03
- Add a new `convert_output` parameter that signals edit mode.

## [0.5.7] - 2020-02-18
- Fix PHP 7 deprecation warning (`get_magic_quotes_gpc`).

## [0.5.6] - 2018-11-30
- Fix default sort order.

## [0.5.5] - 2018-11-30
- Add default values for text, textarea, and select.

## [0.5.4] - 2018-01-10
- Fix PHP 7 deprecation warning.

## [0.5.3] - 2017-09-29
- Minor footer style and alignment changes.
- Scroll to bottom for add mode.

## [0.5.2] - 2017-09-27
- Add `cssClass`.
- Remove hardcoded colors.
- Add grid styles.

## [0.5.1] - unknown
- Add icon fonts.
- Fix PHP warnings.
- Additional minor changes.
