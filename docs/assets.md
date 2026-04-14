# Asset Helpers

This document explains how to use `PhpMySQLGrid\MySQLGridAssets` for CSS/JS includes and cache busting.

## Quick Start

Configure the public asset base once:

```php
use PhpMySQLGrid\MySQLGridAssets;

MySQLGridAssets::configure("/assets/phpmysqlgrid");
```

Load default CSS (base + default theme):

```php
echo MySQLGridAssets::cssTagsFor();
```

Load dark theme:

```php
echo MySQLGridAssets::cssTagsFor("dark");
```

## Theme Model

The grid CSS is split into:
- `mysqlgrid-base.css` for shared structure/layout
- `mysqlgrid-theme-*.css` for colors/visual styling

Built-in theme names:
- `default` -> `mysqlgrid-base.css` + `mysqlgrid-theme-default.css`
- `dark` -> `mysqlgrid-base.css` + `mysqlgrid-theme-dark.css`

### Per-Grid Theme Class

For different themes on one page, set your grid CSS class accordingly:

```php
$usersGrid->cssClass = "theme-default";
$productsGrid->cssClass = "theme-dark";
```

## Custom Theme

1. Keep `mysqlgrid-base.css` unchanged.
2. Add your own theme file, for example `mysqlgrid-theme-brand.css`.
3. Include it either by theme string or explicit file list.
4. Set `$grid->cssClass` to the matching theme class, for example `theme-brand`.

Note about `MySQLGridAssets::$builtInThemes` in [src/MySQLGridAssets.php](../src/MySQLGridAssets.php):
- You do not need to change it when you follow the naming pattern `mysqlgrid-theme-<name>.css` and call `cssTagsFor("<name>")`.
- It only defines bundled aliases (`default`, `dark`).
- You only need to adapt that map if you maintain this package and want additional fixed aliases with non-standard file names.

Theme string form:

```php
echo MySQLGridAssets::cssTagsFor("brand");
```

This resolves to:
- `mysqlgrid-base.css`
- `mysqlgrid-theme-brand.css`

Explicit file list form:

```php
echo MySQLGridAssets::cssTagsFor(array(
    "mysqlgrid-base.css",
    "mysqlgrid-theme-brand.css",
));
```

## Cache Busting

URL token resolution order is:
1. Manifest hash (`phpmysqlgrid-assets.json`) in published target
2. Direct content hash of the asset file
3. Installed package version

Example output:

```text
/assets/phpmysqlgrid/mysqlgrid-base.css?v=abcdef123456
```

## Method Overview (Recommended API)

Configure defaults:

```php
MySQLGridAssets::configure("/assets/phpmysqlgrid", $_SERVER["DOCUMENT_ROOT"] ?? null);
MySQLGridAssets::setDefaultPublicBasePath("/assets/phpmysqlgrid");
MySQLGridAssets::setDefaultDocumentRoot($_SERVER["DOCUMENT_ROOT"] ?? null);
MySQLGridAssets::resetConfiguration();
```

CSS helpers:

```php
MySQLGridAssets::cssUrlFor("mysqlgrid.css");
MySQLGridAssets::cssTagFor("mysqlgrid.css");
MySQLGridAssets::cssUrlsFor();
MySQLGridAssets::cssTagsFor("dark");
MySQLGridAssets::cssTagsFor(array("mysqlgrid-base.css", "mysqlgrid-theme-default.css"));
```

JS helpers:

```php
MySQLGridAssets::jsUrlFor("mysqlgrid.js");
MySQLGridAssets::jsTagFor("mysqlgrid.js", null, true);
```

## Legacy Methods (Deprecated)

The following methods are still available for backward compatibility but are deprecated:
- `cssUrl()`
- `cssTag()`
- `cssUrls()`
- `cssTags()`
- `jsUrl()`
- `jsTag()`
- `assetUrl()`

Prefer the `*For()` methods with `configure()`.

## Migration Examples

Old:

```php
echo MySQLGridAssets::cssTags("/assets/phpmysqlgrid", null, array(
    "mysqlgrid-base.css",
    "mysqlgrid-theme-dark.css",
));
```

New:

```php
MySQLGridAssets::configure("/assets/phpmysqlgrid");
echo MySQLGridAssets::cssTagsFor("dark");
```
