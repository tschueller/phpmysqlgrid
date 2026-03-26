# Testing Instructions

## Purpose

This file documents how tests are structured in this repository, with a focus on the SQLite in-memory integration test infrastructure.

## Test Layers

The test suite currently has two layers:

1. Unit tests for non-database behavior of `MySQLGrid`.
2. SQLite-backed integration tests for database-related behavior.

## Unit Tests (Non-DB)

Use this layer to test behavior that does not require a database connection.

Conventions:

- Test files live in `tests/`, class `MySQLGridUnitTest`, namespace `MySQLGridTests`.
- Import `MySQLGrid` explicitly via `use MySQLGrid;` to satisfy Intelephense static analysis.
- `MySQLGrid.php` is loaded via Composer classmap autoloading, so no separate bootstrap is required.
- Prefer descriptive test method names over inline comments.
- Add comments only for known-bug or non-obvious behavior.
- If a test intentionally documents current buggy behavior, add a `// TODO:` comment that explains expected behavior after the future fix.

## Run Tests

Run the full suite:

```bash
composer run test
```

Run all quality checks:

```bash
composer run lint
```

## SQLite Integration Test Infrastructure

### Why SQLite in-memory

The project production target is MySQL, but tests use SQLite in-memory to keep setup minimal, fast, and CI-friendly.

Benefits:

- No local database server required.
- No container orchestration required.
- Fully isolated database per test case setup.
- Very fast execution.

### Main Infrastructure Files

- `tests/SqliteTestDatabase.php`
  - Creates an in-memory `PDO` SQLite connection.
  - Configures PDO error mode and default fetch mode.
  - Builds schema via `createSchema()`.
  - Seeds baseline rows via `seedData()`.
- `tests/DatabaseTestCase.php`
  - Shared abstract base class for DB integration tests.
  - Creates a fresh database in `setUp()`.
  - Provides common helpers such as row-count and user fetch assertions.
- `tests/MySQLGridSqliteAdapter.php`
  - Test-only adapter that extends `MySQLGrid`.
  - Routes database operations to SQLite/PDO.
  - Provides integration-focused methods (`connect`, `disconnect`, `useAllColumns`, `addData`, `editData`, `deleteData`, `fetchRows`).
- `tests/MySQLGridDatabaseIntegrationTest.php`
  - CRUD integration tests against the adapter.

### How Isolation Works

Each test method gets a new in-memory database created in `DatabaseTestCase::setUp()`.

This means:

- Seed data starts from a known state for every test.
- Tests should not depend on side effects from other tests.

## Autoload and Test File Loading

PHPUnit file discovery in this repository does not autoload helper classes from `tests/` automatically.

Use explicit includes where needed:

```php
require_once __DIR__ . "/DatabaseTestCase.php";
require_once __DIR__ . "/MySQLGridSqliteAdapter.php";
```

## Writing New DB Integration Tests

1. Extend `DatabaseTestCase`.
2. Create and configure `MySQLGridSqliteAdapter` in `setUp()`.
3. Keep schema/fixtures deterministic.
4. Verify both behavior and persisted DB state.
5. Prefer descriptive test names.
6. Use `array(...)` style to match repository code style.

## Security Tests Guidance

When adding SQL injection and XSS tests:

- Use malicious payload samples for insert/update/delete/filter paths.
- Assert database integrity (e.g., table still exists, row count unchanged when blocked).
- For output encoding checks, assert escaped HTML output explicitly.

## Known Limitation of the Current Adapter Approach

The current integration tests use `MySQLGridSqliteAdapter`, which **overrides** the DB methods from `MySQLGrid` (`addData`, `editData`, `deleteData`, etc.) with a new SQLite/PDO implementation.

This means the tests exercise the adapter code, **not the original `MySQLGrid` methods**. A bug in the real `addData` in `MySQLGrid.php` would not be caught by the current integration tests.

This is a known trade-off accepted to get test infrastructure in place without breaking changes.

### Future Migration Plan

Once the DB connection in `MySQLGrid` is made injectable (tracked in `TODO.md` under Refactoring), the test setup will change:

1. Pass a SQLite/PDO connection directly into `MySQLGrid`.
2. The real `addData`, `editData`, `deleteData` methods run against SQLite.
3. `tests/MySQLGridSqliteAdapter.php` is deleted — it becomes obsolete.
4. `tests/MySQLGridDatabaseIntegrationTest.php` is updated to use `MySQLGrid` directly instead of the adapter.

Until that refactoring is done, treat the adapter tests as a **smoke test layer** only — they verify the overall flow, not the actual production SQL code paths.

If MySQL-specific behavior is changed in core methods, add at least one MySQL-backed validation path in CI or local verification before release.

## Recommended Workflow For DB Test Changes

1. Implement or update tests.
2. Run `composer run test`.
3. Run `composer run lint`.
4. Update `TODO.md` status items if scope milestones are completed.
