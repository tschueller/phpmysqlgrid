# Upgrade Guide

This document contains upgrade steps for version jumps.

## v0.5 -> v0.6

v0.6 includes breaking changes. For most projects, these are the required migration steps:

1. Require PHP 8.2 or newer.
2. Update imports to the new namespace:

```php
use PhpMySQLGrid\MySQLGrid;
use PhpMySQLGrid\MySQLGridAssets;
```

3. Replace legacy `MySQLGrid\\...` imports with `PhpMySQLGrid\\...`.
4. Use Composer autoload (`vendor/autoload.php`) and stop manually including old root-level library files.
5. If you publish package assets in host projects, run:

    php vendor/bin/phpmysqlgrid-assets

6. If you used legacy `mysqli` assumptions, switch to PDO usage patterns (internal PDO mode or injected PDO via `setDatabaseConnection()`).
7. Remove separate icon-font/image dependencies for grid controls. v0.6 ships inline SVG icons by default, so external icon fonts and separate control-image assets are no longer required for standard usage.

### Notes

- Global `PHPMYSQLGRID_*` constants remain available for backward compatibility.
- Core class path moved to `src/MySQLGrid.php`; CSS moved to `assets/css/mysqlgrid.css`.
