# TODO

## v0.6 (next major version, currently in development)

- [x] Convert code style to 1TBS (one true brace style)
- [x] Require PHP >= 8.2
- [x] Add Composer lint workflow (syntax/style/static) with PHPStan level 8
- [x] Add README, CHANGELOG, and TODO documentation
- [x] update visibility of methods and properties (currently all public, but some should be private/protected)
- [ ] Add demo page
  - [x] Add demo page with a simple table with lookup table
  - [x] Add reset functionality to demo page to recreate schema and seed data
  - [x] add rows/page selector to demo page
  - [x] Add more complex demo page with more column types (fileupload, select, ...) and features (only for internal testing/demo purposes, not necessarily for public documentation)
  - [x] Add more convert_input/convert_output column type examples (e.g. date, datetime, numeric)
  - [x] restyle demo pages for better modern look
  - [x] use more friendly colors for grid header and footer (currently dark gray, but maybe a softer color would be better for readability and aesthetics)
- [x] Add unit tests
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
  - [x] update github copilot instructions with specialized instruction files (.github/instructions/)
- [x] Change license to MIT
- [x] Check for security issues (SQL injection, XSS) and add mitigations if needed
  - [x] Harden mysqli write/query paths by replacing raw addslashes/id interpolation with connection-aware escaping helper
  - [x] Replace mysqli string-built SQL in add/edit/delete write paths with prepared statements
  - [x] Parameterize active filter values in PDO prepareData queries (count + data select)
  - [x] Review remaining read/filter SQL construction and add raw SQL fragment guard for `filter`/`lookup_filter` dangerous tokens
- [ ] Documentation
  - [x] Document public properties and methods with doc blocks (ignore internal methods which are only public for testing purposes, but should not be part of the public API (@ignore))
  - [x] Add Screenshots to README
  - [ ] add upgrade guide for v0.5 to v0.6 breaking changes
- [ ] Improve styling / default theme
  - [x] Replace Unicode and icon-font controls with inline SVG icons (`svgIcon*` / `svgSort*` properties, Bootstrap Icons MIT)
  - [ ] Split `mysqlgrid.css` into `mysqlgrid-base.css` (base styles) and `gridstyle-theme-default.css` (default theme overrides), and update asset publishing accordingly
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
  - [x] change directory structure to src/ and assets/
  - [ ] Cleanup Changelog.md (use only relevant entries, maybe remove old v0.5.11 history which is not relevant for new repository)

## v0.7 (no timeline yet)
- [ ] Repository features
  - [ ] Add release checklist document for tags and publishing
  - [ ] Add README badges (CI, license, latest release)
  - [ ] update phpstan to v2

- [ ] Styling improvements
  - [ ] Improve or add hover styles for action icons
  - [ ] Add a second themes (TBD) to demonstrate theming capabilities
  - [ ] fix styling for tables with only a few columns (eg. remove 100% table width)
  - [ ] fix styling for tables with many columns (eg. horizontal scrolling, responsive collapse)

- [ ] Demo page improvements
  - [ ] add custom themeing example to demo page

- [ ] Security improvements
  - [ ] secure fileupload handling (e.g. file type/size checks, )

- [ ] Testing improvements
  - [ ] find other solution for @internal methods in MySQLGrid.php which are currently public for testing purposes, but should not be part of the public API (e.g. via friend class pattern or test-specific subclassing)
  - [ ] $this->delete_before, $this->delete_after, $this->edit_after, $this->add_before, $this->add_after, $this->edit_before hooks
  - [ ] test html output for different column types (text, textarea, select, ...) and settings (e.g. other texts, sort order, can_sort, can_filter)
  - [ ] test filter/sort/pagination behavior
  - [ ] test file upload handling and security

- [ ] Documentation improvements
    - [ ] Documentation in readme for grid configuration, styling, columns types, convert_input/convert_output etc.
    - [ ] update readme: how to include custom styles/themes, how to customize via CSS variables, how to override icons with custom SVGs, how to use a custom theme via `$grid->cssClass`


## Open Issues / Features / Future Work

### Publishing
- [ ] move repository to Github
  - [ ] Change branch naming to main if needed
  - [ ] check if the name `MySQLGrid` is available and not trademarked
  - [ ] Switch to main branch naming if needed
  - [ ] evaluate: Set up a GitHub Pages for demo and documentation hosting
  - [ ] add the first the old v0.5.11 version to the new repository and then add the v0.6 changes on top of it, so that the
  - [ ] tag versions
- Publish to [packagist.org/](https://packagist.org/)


### Tooling / Quality
- [ ] Raise PHPStan to level 9 or 10 (Reason: stricter type checks for mixed data paths in MySQLGrid.php)
- [ ] Investigate PSR coding standards
  - [ ] Define target style profile (PSR-12 baseline + project-specific exceptions)
  - [ ] Decide and document array syntax policy (`array(...)` vs `[]`) for new code
  - [ ] Add phased migration plan for legacy style cleanup (touch-and-upgrade strategy)
  - [ ] Align php-cs-fixer configuration with documented style policy


### Accessibility
- [ ] Implement ARIA attributes and semantic HTML in MySQLGrid.php (table, pagination, form controls)
- [ ] Add keyboard navigation support (Tab order, focus indicators, escape handling)
- [ ] Ensure WCAG 2.2 Level AA color contrast compliance in gridstyle.css
- [ ] Add accessibility regression tests (ARIA presence, form labels, keyboard reachability)

## Features
- [ ] Save limit state in session/local storage for persistence across page reloads
- [ ] show total row count in footer (total and filtered)
- [ ] Hover effects for action icons
