# TODO

## v0.6

- [x] Convert code style to 1TBS (one true brace style)
- [x] Require PHP >= 8.2
- [x] Add Composer lint workflow (syntax/style/static) with PHPStan level 8
- [x] Add README, CHANGELOG, and TODO documentation
- [ ] update visibility of methods and properties (currently all public, but some should be private/protected)
- [ ] Add demo page with example usage and styling options
- [ ] Add unit tests
  - [x] add first simple tests
  - [x] add tests for all non-DB methods in MySQLGrid.php
  - [x] fix test wich are testing wrong behavior (current buggy behavior)
  - [x] investigate integration testing options for DB methods (e.g. in-memory SQLite)
  - [x] add SQLite in-memory test infrastructure (fixtures + shared DB test base class)
  - [x] add tests for all DB methods in MySQLGrid.php
  - [x] add real-code-path integration tests for MySQLGrid addData/editData/deleteData via injected PDO connection
  - [x] add real-code-path integration test for MySQLGrid useAllColumns via injected PDO connection
  - [x] add real-code-path integration tests for MySQLGrid prepareData/unprepareData via injected PDO connection
  - [x] migrate former adapter-based CRUD/security integration tests to real MySQLGrid methods with injected PDO
  - [x] add real-code-path integration test for lookup query rendering in drawEditControls via injected PDO connection
  - [x] add execute()-path integration tests for confirm add/edit/delete request handling via injected PDO connection
  - [x] add security tests for SQL injection and XSS vulnerabilities
  - [x] update github copilot instructions with testing guidelines
  - [ ] More tests / check if this is already tested:
    - [ ] $this->delete_before, $this->delete_after, $this->edit_after, $this->add_before, $this->add_after, $this->edit_before hooks
    - [ ] test html output for different column types (text, textarea, select, ...) and settings (e.g. other texts, sort order, can_sort, can_filter)
    - [ ] test filter/sort/pagination behavior

- [x] Change license to MIT
- [x] Check for security issues (SQL injection, XSS) and add mitigations if needed
  - [x] Harden mysqli write/query paths by replacing raw addslashes/id interpolation with connection-aware escaping helper
  - [x] Replace mysqli string-built SQL in add/edit/delete write paths with prepared statements
  - [x] Parameterize active filter values in PDO prepareData queries (count + data select)
  - [x] Review remaining read/filter SQL construction and add raw SQL fragment guard for `filter`/`lookup_filter` dangerous tokens
- [ ] Investigate PSR coding standards
- [ ] Document public properties and methods with doc blocks
- [x] Update cspell word list for new identifiers and technical terms
- [ ] Repository Standards
  - [x] Add GitHub Actions CI workflow for composer validate + lint
  - [x] Add Dependabot configuration for Composer and GitHub Actions
  - [x] Add CONTRIBUTING.md
  - [x] Add SECURITY.md
  - [-] Add CODE_OF_CONDUCT.md (optional, if contributor base grows)
  - [x] Add issue and PR templates
  - [x] Add .editorconfig
  - [x] Add PHPUnit config and first automated test suite
  - [ ] Add release checklist document for tags and publishing
  - [ ] Add README badges (CI, license, latest release)

## Refactoring
- [x] Make DB connection injectable in `MySQLGrid` (prerequisite for proper integration testing)
  - [x] Add non-breaking injection hook via `setDatabaseConnection()` + injected connection handling in `connect()`/`disconnect()`
  - [x] Route real `addData`/`editData`/`deleteData` through injected PDO path while preserving mysqli default behavior
  - [x] Route real `useAllColumns` through injected PDO path while preserving mysqli default behavior
  - [x] Route real `prepareData`/`unprepareData` result handling through injected PDO path while preserving mysqli default behavior
  - [x] Remove obsolete `tests/MySQLGridSqliteAdapter.php` and adapter-based integration test suite
  - [x] Route lookup query path in `drawEditControls` through DB-agnostic query helper (PDO/mysqli compatible)
  - Reason: Historically, DB methods hardcoded `mysqli_*` calls, and tests exercised a parallel adapter implementation instead of real code paths.
  - Approach: Accept an optional `$db` parameter in `connect()` or constructor, or introduce a thin wrapper interface around the DB calls.
  - Breaking change: Existing consumers using `$grid->hostname` etc. are unaffected as long as the default behavior (auto-connect via mysqli) is preserved.
  - Acceptance criteria: Integration tests exercise real `MySQLGrid` DB methods directly with injected PDO connections. (done)

## Tooling / Quality
- [ ] Raise PHPStan to level 9
  - Reason: stricter type checks for mixed data paths in MySQLGrid.php
  - Effort: medium
  - Acceptance criteria: composer run lint is green

- [ ] Evaluate PHPStan level 10
  - Prerequisite: level 9 is stably green
  - Effort: medium to high
  - Acceptance criteria: composer run lint is green
