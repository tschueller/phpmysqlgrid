---
name: assets
description: "Template: use when changing asset publishing, cache busting, runtime asset URLs/tags, or demo/dev asset loading modes."
applyTo:
  - "src/Asset*.php"
  - "bin/*assets*"
  - "demo/*"
  - "README.md"
---

# Asset Instructions Template

## Scope

This file is the source of truth for:
- Asset publishing from package to host project public paths
- Runtime asset URL/tag generation
- Cache busting strategy and fallback behavior
- Demo/development asset loading modes

## Directory and File Contract

Package-side assets (example):
- `assets/css/<default-style>.css`
- `assets/js/*.js` (optional)
- `assets/images/*` (optional)

Runtime/publish components (example):
- `src/<Project>Assets.php` (runtime URL/tag helper)
- `src/<Project>AssetPublisher.php` (publisher + manifest writer)
- `bin/<project>-assets` (CLI entrypoint)

## Publishing Contract

Publisher behavior:
- Copy supported assets from package `assets/` into a host target directory.
- Keep output path conventions stable (documented in README).
- Fail with clear errors when source/target is invalid.

Target path precedence (recommended):
1. CLI argument (`--target` / `--target=...`)
2. Environment variable (`<PROJECT>_ASSET_TARGET`)
3. Default (`assets/<project>`)

## Manifest Contract

Manifest filename (recommended):
- `<project>-assets.json`

Expected shape (example):
```json
{
  "generated_at": "2026-04-04T12:34:56+00:00",
  "files": {
    "style.css": { "hash": "abcdef123456" },
    "bundle.js": { "hash": "123456abcdef" }
  }
}
```

Rules:
- Store deterministic short hash (for example shortened sha1).
- Keep key names stable and backward compatible.
- Update tests if manifest shape changes.

## Runtime Helper Contract

Helper responsibilities:
- Provide URL helpers (`cssUrl`, `jsUrl`, or generic `assetUrl`).
- Provide tag helpers (`cssTag`, `jsTag`) where convenient.
- Escape generated HTML attributes.

Token resolution order (recommended):
1. Manifest hash from published target directory
2. Direct file hash fallback
3. Package/app version fallback

## Dev/Demo Asset Loading

If a demo/dev environment exists, define modes explicitly:
- `repo` mode: direct repository assets with cheap cache token (`filemtime`)
- `published` mode: published target assets via runtime helper

Mode resolution (recommended):
1. Query parameter
2. Environment variable
3. Default mode

## Documentation Requirements

When asset behavior changes, update:
- `README.md` publishing and cache-busting sections
- `CHANGELOG.md` for user-visible behavior changes
- This instruction file when contracts change

## Testing Requirements

Add or update tests for:
- Publisher copies expected files
- Manifest exists and contains expected hashes
- URL helper uses manifest hash when available
- Fallback behavior when manifest/file is missing
- JS helper behavior if JS is supported

## Security and Compatibility Notes

- Never allow unvalidated user input to choose arbitrary filesystem publish targets at runtime.
- Keep generated public URLs normalized to forward slashes.
- Avoid breaking default helper signatures without migration notes.

## Project-Specific Values To Fill In

Replace placeholders before using this template:
- `<Project>` class name prefix
- `<project>` package slug
- `<PROJECT>_ASSET_TARGET` environment variable name
- Default target path and manifest filename
