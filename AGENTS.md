# Agent Instructions

This repository may be edited with more than one AI assistant. This file is the shared, tool-neutral source of truth for agent behavior. Tool-specific instructions may add details, but should not contradict this file.

## Project Overview

phpMySQLGrid is a Composer-managed PHP library for rendering and managing database table data as an HTML grid with CRUD, filtering, sorting, pagination, themes, and asset publishing helpers.

- Package: `tschueller/phpmysqlgrid`
- PHP: `>=8.2`
- Runtime database layer: PDO only
- Main source: `src/MySQLGrid.php`
- Assets: `assets/css`
- Tests: `tests`

## Source Of Truth

Use these files as the primary references before changing related areas:

- Runtime/API behavior: `src/MySQLGrid.php`, `README.md`
- Composer, scripts, dependencies: `composer.json`
- Tests and fixtures: `tests`, `.github/instructions/testing.instructions.md`
- Core class rules: `.github/instructions/php-core.instructions.md`
- Assets and cache busting: `.github/instructions/assets.instructions.md`
- Styling and themes: `.github/instructions/styling.instructions.md`
- Accessibility: `.github/instructions/accessibility.instructions.md`
- Backlog and deferred work: `TODO.md`
- Version history and release notes: `CHANGELOG.md`

## Development Rules

- Keep backward compatibility for public properties, methods, constants, and documented behavior.
- All database operations use PDO. Do not reintroduce `mysqli` paths.
- Preserve `#[AllowDynamicProperties]` on `MySQLGrid`; dynamic configuration is intentional for compatibility.
- Add explicit visibility to all new methods and properties.
- Prefer existing project style and `array(...)` notation in tests and legacy-style code.
- Do not enable PHP-CS-Fixer's `single_quote` rule; double quotes are accepted and often preferred here.
- Write comments and documentation in English.
- Keep changes focused. Do not perform unrelated refactors while solving a narrow request.

## Quality Gates

Use Composer scripts for validation:

```bash
composer run lint
composer run test
```

For narrower checks:

```bash
composer run lint:syntax
composer run lint:style
composer run lint:static
composer run test -- tests/SomeTest.php
```

When changing behavior, rendering, security, public API, assets, or tooling:

- Update or add tests when appropriate.
- Update `CHANGELOG.md`, or explicitly state why no changelog entry is needed.
- Run the most relevant validation commands and report the result.

## Coordination Between AI Tools

- Treat this file as shared guidance for all agents.
- Keep `.github/copilot-instructions.md` and `.github/instructions/*.instructions.md` as Copilot-compatible detail files.
- If a general project rule changes, update this file first, then update tool-specific files only when needed.
- Do not overwrite or revert changes made by another agent or the user unless explicitly asked.
- Prefer small, reviewable changes so another tool can continue safely.

## Definition Of Done

Before finishing a change, provide a short summary that includes:

- What changed
- Changelog status
- Test/validation status
- Any remaining risk or follow-up
