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
- `tests/MySQLGridRealCrudIntegrationTest.php`
  - Integration tests that execute real `MySQLGrid` DB methods via injected `pdo_sqlite` connection, including `addData`, `editData`, `deleteData`, `useAllColumns`, `prepareData`, `unprepareData`, and lookup-query rendering in `drawEditControls`.
- `tests/MySQLGridSqlInjectionTest.php`
  - SQL injection behavior tests executed against real `MySQLGrid` methods via injected `pdo_sqlite` connection.

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
```

## Writing New DB Integration Tests

1. Extend `DatabaseTestCase`.
2. Prefer configuring `MySQLGrid` directly with `setDatabaseConnection($pdo, "pdo_sqlite")`.
3. Keep schema/fixtures deterministic.
4. Verify both behavior and persisted DB state.
5. Prefer descriptive test names.
6. Use `array(...)` style to match repository code style.

## Current State of Real vs Adapter Coverage

- Real `MySQLGrid` methods covered via injected PDO: `addData`, `editData`, `deleteData`, `useAllColumns`, `prepareData`, `unprepareData`.
- Still not fully migrated: remaining DB-related `execute()`/render branches that may still rely on legacy `mysqli` assumptions under non-tested combinations.

The target is to migrate remaining DB methods to real-code-path tests and complete full execute()-path parity.

### Migration Plan (Updated)

1. Continue moving DB methods to injectable-connection-compatible code in `MySQLGrid`.
2. Add or move tests to execute the real methods directly with injected `pdo_sqlite`.
3. Expand real integration tests to remaining DB-dependent execute()/render branches.
## Security Tests Guidance

When adding SQL injection and XSS tests:

- Use malicious payload samples for insert/update/delete/filter paths.
- Assert database integrity (e.g., table still exists, row count unchanged when blocked).
- For output encoding checks, assert escaped HTML output explicitly.

If MySQL-specific behavior is changed in core methods, add at least one MySQL-backed validation path in CI or local verification before release.

## Recommended Workflow For DB Test Changes

1. Implement or update tests.
2. Run `composer run test`.
3. Run `composer run lint`.
4. Update `TODO.md` status items if scope milestones are completed.
