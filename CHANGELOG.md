# Changelog

All notable changes to this project are documented in this file.

## [0.6.0 - Unreleased]
- Require PHP >= 8.2; change license from BSD-3-Clause to MIT.
- tidy up repository structure; update `composer.json`, add `README.md`, and other useful documentation files; .
- Convert code style to 1TBS; add Composer lint workflow with PHPStan level 8.
- Add PHPUnit test suite covering unit, CRUD integration, SQL injection, and XSS scenarios.
- Expand class-level PHPDoc with API overview and `@property` documentation; add visibility modifiers to all properties and methods; add type hints and return types;.
- Move core library to `src/MySQLGrid.php`; move stylesheet to `assets/css/mysqlgrid.css`.
- Replace internal `mysqli` with PDO; add `setDatabaseConnection()` for injected PDO connections (`pdo_mysql`, `pdo_sqlite`); backward-compatible with legacy hostname/username/password properties.
- Replace all SQL string building with PDO prepared statements; parameterize filter values; add raw SQL fragment guard for dangerous token patterns in `filter` and `lookup_filter`.
- Replace Unicode/FontAwesome icon controls with inline SVG icons; remove `$use_icon_font` property (breaking change).
- Add `MySQLGridAssets` helper for stylesheet/JS URLs with cache busting; add `MySQLGridAssetPublisher` and `phpmysqlgrid-assets` CLI for publishing assets into host projects.
- use namespace `PhpMySQLGrid` for all public classes; add PSR-4 autoloading support for host projects.
- Migrate CSS class names to semantic CSS class names.
- Add demo pages with a focus on the basic features.
- add copilot instructions for future contributors.



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
