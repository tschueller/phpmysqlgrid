---
name: assets
description: "Use when: changing asset publishing, cache busting, helper URLs/tags, or demo asset loading modes. Applies to publisher/helper runtime code and related docs."
applyTo: "{src/MySQLGridAssets.php,src/MySQLGridAssetPublisher.php,bin/phpmysqlgrid-assets,demo/DemoAsset.php,README.md}"
---

# Asset Instructions for MySQLGrid

## Scope

This file is the source of truth for:
- Asset publishing from package to host project public paths
- Runtime asset URL/tag generation
- Cache busting strategy and fallback behavior
- Demo asset loading (`repo` vs `published` mode)

## Directory and File Contract

Package-side assets:
- `assets/css/mysqlgrid.css` (default stylesheet)
- `assets/js/*.js` (optional JavaScript assets)

Runtime/publish components:
- `src/MySQLGridAssets.php` (`PhpMySQLGrid\\MySQLGridAssets`, runtime URL/tag helper)
- `src/MySQLGridAssetPublisher.php` (`PhpMySQLGrid\\MySQLGridAssetPublisher`, publisher + manifest writer)
- `bin/phpmysqlgrid-assets` (CLI entrypoint)

## Publishing Contract

Publisher behavior in `src/MySQLGridAssetPublisher.php`:
- Copies supported assets from package `assets/` into target directory.
- Current supported source patterns:
  - `assets/css/*.css`
  - `assets/js/*.js`
- Writes manifest file `phpmysqlgrid-assets.json` in the publish target.

Target path precedence:
1. CLI argument (`--target` / `--target=...`)
2. Environment variable (`PHPMYSQLGRID_ASSET_TARGET`)
3. Default (`assets/phpmysqlgrid`)

## Manifest Contract

Manifest filename:
- `phpmysqlgrid-assets.json`

Expected shape:
```json
{
  "generated_at": "2026-04-04T12:34:56+00:00",
  "files": {
    "mysqlgrid.css": { "hash": "abcdef123456" }
  }
}
```

Rules:
- Store short deterministic hash (`sha1` shortened to 12 chars).
- Keep keys stable and backward compatible.
- Update tests when manifest shape changes.

## Runtime Helper Contract

Helper behavior in `src/MySQLGridAssets.php`:
- Recommended API: `configure()`, `setDefaultPublicBasePath()`, `setDefaultDocumentRoot()`, `resetConfiguration()`, `cssUrlFor()`, `cssTagFor()`, `cssUrlsFor()`, `cssTagsFor()`, `jsUrlFor()`, `jsTagFor()`
- Legacy API (deprecated, keep backward compatibility): `cssUrl()` / `cssTag()` / `cssUrls()` / `cssTags()` / `jsUrl()` / `jsTag()` / `assetUrl()`
- Shared token resolution should follow this order:
1. Manifest hash from published directory
2. Direct file hash (when manifest missing)
3. Installed package version fallback

Runtime helper rules:
- Escape all generated HTML attributes.
- Normalize paths to forward slashes for public URLs.
- Keep backward compatibility for existing default arguments.

Documentation rule for methods in asset/runtime classes:
- For all `public` or `protected` methods, add a short method description sentence in PHPDoc.
- For all `public` or `protected` methods with parameters, document **every parameter** in PHPDoc using `@param`.
- Partial parameter documentation (only one of several params) is not allowed.
- For `private` methods or public methods with `internal` annotation, parameter PHPDoc is optional and should be used for complex shapes/context.

## Demo Asset Loading

`demo/DemoAsset.php` supports two modes:
- `repo` (default): direct repository assets, filemtime-based tokens
- `published`: use `PhpMySQLGrid\\MySQLGridAssets` helper with published base path

Mode resolution:
1. `asset_mode` query parameter (`repo` / `published`)
2. `PHPMYSQLGRID_DEMO_ASSET_MODE` environment variable
3. Default `repo`

## Documentation Requirements

When changing asset behavior, update:
- `README.md` sections for publishing and cache busting
- `CHANGELOG.md` for behavior changes
- This instruction file if contracts change

## Testing Requirements

When changing asset behavior, cover:
- Publish copies expected files
- Manifest exists and contains expected hashes
- Helper URLs contain cache token from manifest when available
- Fallback behavior when manifest or file is missing
- JS helper behavior (`jsUrl`, `jsTag`) when JS files are present

Run quality checks:
- `composer run test`
- `composer run lint:syntax`
- `composer run lint:static`
