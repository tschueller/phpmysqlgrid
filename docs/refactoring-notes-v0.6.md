# Refactoring Notes (v0.6) - Detailed Comparison to v0.5.11

## Purpose

This internal document summarizes what changed in the v0.6 refactoring work compared to v0.5.11, especially around database abstraction, security hardening, and integration testing.

## Baseline (v0.5.11)

In v0.5.11, database interaction was tightly coupled to `mysqli` and direct SQL string construction:

- Connection lifecycle: internal `mysqli_connect` / `mysqli_close` only.
- CRUD SQL generation: string interpolation plus escaping functions in write paths.
- Read/filter SQL: query fragments assembled as strings.
- Testing model: no full real DB integration coverage for DB methods.
- Alternative DB backends (for testing): no first-class injection path.

## Current State (v0.6 refactoring line)

### 1) Connection Architecture

What changed:

- Added injected connection entry point:
  - `setDatabaseConnection(mixed $connection, string $driver)`
- Default (non-injected) mode now creates a `PDO` connection internally from `hostname`, `port`, `username`, `password`, `database` properties using `mysql:host=…;port=…;dbname=…;charset=utf8mb4`.
- Injected mode supports `pdo_mysql` and `pdo_sqlite`.
- `connect()` in injected mode reuses the provided connection.
- `disconnect()` in injected mode is intentionally a no-op.
- All internal `mysqli` code removed — `mysqli` is no longer a supported or used driver.

Impact:

- Existing integrations stay backward compatible (hostname/username/password/database still work).
- Tests can run real `MySQLGrid` DB methods against in-memory SQLite.

### 2) CRUD Execution Paths

What changed:

- All CRUD methods (`addData`, `editData`, `deleteData`) use PDO exclusively.
- Legacy mysqli CRUD code removed entirely.
- All write paths use PDO prepared statements with named placeholders.

Impact:

- Significant reduction in SQL injection risk for write operations.
- Real method behavior can be tested without test-specific reimplementation.

### 3) Read/Filter and Lookup Paths

What changed:

- `prepareData` supports injected PDO mode with proper result handling.
- Active filter values in PDO path are parameterized.
- Lookup query rendering path in `drawEditControls` now uses a DB-agnostic helper.
- Guard added for risky raw SQL fragment tokens in `filter` and `lookup_filter`.

Impact:

- Better consistency between read/write hardening strategy.
- Lower risk from obvious fragment-based injection patterns.

## Security Hardening Summary

Implemented:

- PDO prepared statements in all write paths (`addData`, `editData`, `deleteData`).
- Prepared/parameterized active filters in `prepareData` path.
- Raw fragment guard for dangerous tokens in configurable filter fragments.
- Expanded XSS and SQL injection regression tests.

Still important to note:

- `filter` and `lookup_filter` are still free-form SQL fragments by design.
- The guard blocks obvious dangerous token patterns, but it is not a full SQL parser.
- Where possible, prefer controlled filter construction instead of arbitrary user-provided SQL fragments.

## Testing Model Migration

Previous model:

- Adapter-based DB tests (parallel implementation) for SQLite.

Current model:

- Real-code-path integration tests run directly against `MySQLGrid` using injected `pdo_sqlite`.
- Execute-path request/CRUD rendering flows are covered.
- Adapter layer removed.

Result:

- Test coverage now validates real production methods instead of test-only reimplementations.

## Compatibility and Risk Notes

Backward compatibility:

- Default connection mode uses an internally-created PDO connection; existing consumers setting `hostname`/`username`/`password`/`database` properties remain fully supported.
- `setDatabaseConnection()` no longer accepts `mysqli` as a driver — passing it triggers a `trigger_error`.

Behavioral risk areas to watch:

- Custom SQL fragments in `filter` / `lookup_filter` may now be rejected if they include blocked dangerous tokens.
- Any custom integration relying on previously accepted unsafe token patterns should be reviewed.

## Reference Files

- Core implementation: `MySQLGrid.php`
- Public release notes: `CHANGELOG.md`
- Testing conventions: `.github/instructions/testing.instructions.md`
- Task tracking: `TODO.md`
