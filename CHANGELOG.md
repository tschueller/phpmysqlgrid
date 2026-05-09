# Changelog

All notable changes to this project are documented in this file.
This changelog bases on the [Keep a Changelog](https://keepachangelog.com/) format and follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Add file upload security: `allow_url_import` (default: false), `max_file_size`, `allowed_file_extensions`, and `allowed_file_mime_types` properties for server-side upload validation and SSRF prevention.
- Add `allowed_url_domains` property to restrict URL imports to trusted hostnames.
- Add `show_url_input` and `show_file_input` column options to control visibility of file upload controls.
- Add grid-level frontend error summary (`*-error-summary`) to show validation/security failures in the UI.
- Add optional built-in CSRF protection for state-changing confirm actions via `csrf_protection_enabled`.

### Fixed
- Fix XSS/attribute injection in edit controls by escaping `placeholder`/`align`/`accept` and enforcing integer casts for numeric attributes, including `width` in LOOKUP/SELECTION controls.
- Fix unsafe name-based DOM output by sanitizing `$grid->name` for generated form/footer IDs, add-button anchor fragments, and submit handlers.
- Fix "Delete file" checkbox being shown even when no file is present in edit mode.
- Fix noisy PHP warning output for validation failures by reporting messages in-grid and logging server-side instead.
- Fix state-changing confirm actions (`confirmadd`, `confirmedit`, `confirmdelete`) to run on POST requests only.
- Fix custom row action output to sanitize `href`/`src` URL schemes (strict allowlist: relative + `http`/`https`) and cast image dimensions to safe integers.
- Fix request-derived edit ID hidden field rendering by escaping the `value` attribute in edit mode.


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
