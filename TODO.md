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
  - [ ] fix test wich are testing wrong behavior (current buggy behavior)
  - [ ] investigate integration testing options for DB methods (e.g. in-memory SQLite)
  - [ ] add tests for all DB methods in MySQLGrid.php
  - [ ] add security tests for SQL injection and XSS vulnerabilities
  - [ ] update github copilot instructions with testing guidelines
- [x] Change license to MIT
- [ ] Check for security issues (SQL injection, XSS) and add mitigations if needed
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

## Tooling / Quality
- [ ] Raise PHPStan to level 9
  - Reason: stricter type checks for mixed data paths in MySQLGrid.php
  - Effort: medium
  - Acceptance criteria: composer run lint is green

- [ ] Evaluate PHPStan level 10
  - Prerequisite: level 9 is stably green
  - Effort: medium to high
  - Acceptance criteria: composer run lint is green
