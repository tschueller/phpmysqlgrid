# Changelog

All notable changes to this project are documented in this file.
This changelog bases on the [Keep a Changelog](https://keepachangelog.com/) format and follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.2.1] - 2026-06-19

### Added

- Add comprehensive test suite for field type rendering: Boolean, Lookup, Password, Selection, and Multiline text field types in both view and edit modes.
- Add grid behavior tests for pagination, column filtering, and column sorting.
- Add lifecycle hook tests: `add_before`/`add_after`, `edit_before`/`edit_after`, `delete_before`/`delete_after` with abort scenarios.
- Add multiple grid isolation tests: verify independent pagination and filter state across grid instances.
- Add global SQL filter tests and unsafe SQL fragment detection with error triggering validation.
- Add default sort column and direction tests applied on initial load.
- Add filter and sort state persistence tests across page navigation.
- Add enhanced file security tests with MIME type spoofing detection and improved validation coverage.
- Add UI regression tests for CSS class variants, internationalization labels, and SVG icon customization.


## [1.2.0] - 2026-05-18

### Added
- Add file upload security: `allow_url_import` (default: false), `max_file_size`, `allowed_file_extensions`, `allowed_file_mime_types`, and `allowed_url_domains` properties for server-side validation and SSRF prevention.
- Add `show_url_input` and `show_file_input` column options to control file upload control visibility.
- Add grid-level frontend error summary (`*-error-summary`) for validation and security failures.
- Add optional built-in CSRF protection for state-changing actions via `csrf_protection_enabled`.

### Fixed
- Fix XSS and attribute-injection vectors in HTML output: escape `placeholder`, `align`, `accept`, edit ID `value`, and `data-id`; encode single quotes via `ENT_QUOTES`; sanitize `$grid->name` in DOM IDs, anchor fragments, and inline JS handlers; cast numeric attributes (`size`, `maxlength`, `width`, `height`) to integers.
- Fix state-changing confirm actions (`confirmadd`, `confirmedit`, `confirmdelete`) to POST requests only.
- Fix custom row action `href`/`src` to allow only relative, `http`, and `https` schemes; cast image dimensions to integers.
- Fix dynamic SQL identifier validation for table, field, and lookup identifiers before query building.
- Fix "Delete file" checkbox shown when no file is present in edit mode.
- Fix validation failure messages shown in-grid instead of as PHP warnings.

### Changed
- Change light theme `--phpmysqlgrid-icon-add` from CSS keyword `green` to `#2f9e44`.



## [1.1.0] - 2026-04-14

### Added
- Add more CSS classes for different column types.
- Add split CSS helper methods `MySQLGridAssets::cssUrls()` and `MySQLGridAssets::cssTags()` for loading `mysqlgrid-base.css` + `mysqlgrid-theme-default.css`.
- Add new dark theme CSS file (`mysqlgrid-theme-dark.css`) and use it in the second demo page.
- Add simplified configuration-based asset helper API: `configure()`, `setDefaultPublicBasePath()`, `setDefaultDocumentRoot()`, `resetConfiguration()`, and `*For()` helper methods.
- Add light theme CSS file (`mysqlgrid-theme-light.css`) and a theme selection to the second demo page.
- Preserve non-grid GET parameters (e.g. `theme`, `limit`) across all grid links and form submissions (pagination, sorting, row actions, add/edit/delete, filter).

### Fixed
- Fix XSS vulnerability: `$_SERVER["PHP_SELF"]` is now HTML-escaped via `selfUrl()` before output in all `href` and `action` attributes.

### Changed
- Update asset manifest writing logic to prevent unnecessary overwrites on each publish.
- Split grid styling into base layout (`mysqlgrid-base.css`) and default theme (`mysqlgrid-theme-default.css`) with legacy compatibility via `mysqlgrid.css`.
- Make pagination class output respect the configured `$grid->style` prefix instead of using hardcoded `phpmysqlgrid-*` classes.
- Mark legacy asset helper methods as deprecated (`cssUrl()`, `cssTag()`, `cssUrls()`, `cssTags()`, `jsUrl()`, `jsTag()`, `assetUrl()`) and document migration to `*For()` methods.


## [1.0.0] - 2026-04-10

### Added
- PHPUnit test suite covering unit, CRUD integration, SQL injection, and XSS scenarios.
- `MySQLGridAssets` helper for stylesheet/JS URLs with cache busting.
- `MySQLGridAssetPublisher` and `phpmysqlgrid-assets` CLI for publishing assets into host projects.
- Demo pages with a focus on the basic features.
- Copilot instructions for future contributors.

### Changed
- Require PHP >= 8.2.
- Change license from BSD-3-Clause to MIT.
- Tidy up repository structure; update `composer.json`, add `README.md`, and other useful documentation files.
- Convert code style to 1TBS; add Composer lint workflow with PHPStan level 8.
- Expand class-level PHPDoc with API overview and `@property` documentation; add visibility modifiers to all properties and methods; add type hints and return types.
- Move core library to `src/MySQLGrid.php`; move stylesheet to `assets/css/mysqlgrid.css`.
- Use namespace `PhpMySQLGrid` for all public classes; add PSR-4 autoloading support for host projects.
- Migrate CSS class names to semantic CSS class names.
- Allow `$cssClass` to be configured as a string or an array of strings to support multiple custom CSS classes.

### Removed
- Remove `$use_icon_font` property (breaking change).
- Remove Unicode/FontAwesome icon controls; replace with inline SVG icons.

### Fixed
- Replace all SQL string building with PDO prepared statements; parameterize filter values; add raw SQL fragment guard for dangerous token patterns in `filter` and `lookup_filter`.
- Replace internal `mysqli` with PDO; add `setDatabaseConnection()` for injected PDO connections (`pdo_mysql`, `pdo_sqlite`); backward-compatible with legacy hostname/username/password properties.


## [0.5.11] - 2024-03-04
### Added
- Add `thead`, `tbody`, and `tfoot`.
- Add row id as `data-id` attribute.


## [0.5.10] - 2024-02-29
### Fixed
- Fix PHP 8.2 warnings.


## [0.5.9] - 2023-11-15
### Added
- Add `#[AllowDynamicProperties]` annotation to fix PHP 8.2 warnings.


## [0.5.8] - 2020-09-03
### Added
- Add a new `convert_output` parameter that signals edit mode.


## [0.5.7] - 2020-02-18
### Fixed
- Fix PHP 7 deprecation warning (`get_magic_quotes_gpc`).

## [0.5.6] - 2018-11-30
### Fixed
- Fix default sort order.


## [0.5.5] - 2018-11-30
### Added
- Add default values for text, textarea, and select.


## [0.5.4] - 2018-01-10
### Fixed
- Fix PHP 7 deprecation warning.


## [0.5.3] - 2017-09-29
### Changed
- Minor footer style and alignment changes.
- Scroll to bottom for add mode.


## [0.5.2] - 2017-09-27
### Added
- Add `cssClass`.
- Add grid styles.
### Removed
- Remove hardcoded colors.


## [0.5.1] - 2017-09-23
### Added
- Add icon fonts.
### Fixed
- Fix PHP warnings.
### Changed
- Additional minor changes.
