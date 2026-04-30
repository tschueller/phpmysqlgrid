# TODO

## Open Issues / Features / Future Work

### Tooling / Quality
- [ ] Raise PHPStan to level 9 or 10 (Reason: stricter type checks for mixed data paths in MySQLGrid.php)
- [ ] update phpstan to v2 (https://github.com/tschueller/phpmysqlgrid/pull/3)
- [ ] Investigate PSR coding standards
  - [ ] Define target style profile (PSR-12 baseline + project-specific exceptions)
  - [ ] Decide and document array syntax policy (`array(...)` vs `[]`) for new code
  - [ ] Add phased migration plan for legacy style cleanup (touch-and-upgrade strategy)
  - [ ] Align php-cs-fixer configuration with documented style policy- [ ]

### Accessibility
- [ ] Implement ARIA attributes and semantic HTML in MySQLGrid.php (table, pagination, form controls)
- [ ] Add keyboard navigation support (Tab order, focus indicators, escape handling)
- [ ] Ensure WCAG 2.2 Level AA color contrast compliance in gridstyle.css
- [ ] Add accessibility regression tests (ARIA presence, form labels, keyboard reachability)
- [ ] disable add button in add mode
- [ ] use automated accessibility testing tools (e.g. axe) on demo page to identify and fix issues
- [ ] add aria labels for boolean icons (currently aria-hidden="true" is set)

## Features / Improvements
- [ ] Save limit state in session/local storage for persistence across page reloads
- [ ] show total row count in footer (total and filtered)
- [ ] Better visual indication of active filters (e.g. filter icon in header, highlight active filter values)
- [ ] Second New/edit mode (configurable): Instead of inline editing, open a modal dialog with a form for editing the row. This allows more space for complex forms and better UX on mobile devices.

### Styling
  - [x] Split `mysqlgrid.css` into `mysqlgrid-base.css` (base styles) and `gridstyle-theme-default.css` (default theme overrides), and update asset publishing accordingly
  - [ ] Improve or add hover styles for action icons
  - [x] Add a second themes (TBD) to demonstrate theming capabilities
  - [ ] fix styling for tables with only a few columns (e.g. in full width mode centering content, max-width for action cells)
  - [ ] fix styling for tables with many columns (eg. horizontal scrolling, responsive collapse)
  - [x] Add CSS classes for different column types (e.g. `mysqlgrid-cell--text`, `mysqlgrid-cell--select`, etc.) to allow more specific styling of different column types
  - [ ] Implement real dark mode support (e.g. via `prefers-color-scheme` media query) in default theme
  - [ ] center header text vertically when columns with and without filter are mixed

### Demo page
  - [x] add custom theming example to demo page

### Security
  - [ ] secure file-upload handling (e.g. file type/size checks, )
    - [x] Validate file upload size via `max_file_size` property
    - [x] Validate file extensions via `allowed_file_extensions` property
    - [ ] Add MIME type validation via `allowed_file_mime_types` property
  - [x] harden URL-based file import in FILE fields (restrict/validate or disable by default)
    - [x] Disable URL imports by default (`allow_url_import = false`)
    - [x] Validate URLs to prevent SSRF attacks (only http/https, block private IPs)
    - [ ] Add optional URL whitelist for trusted domains
  - [ ] add CSRF protection for add/edit/delete actions
  - [ ] enforce POST-only handling for state-changing commands (confirm add/edit/delete)
  - [x] escape HTML attributes consistently (e.g. `placeholder`) in edit controls

### Testing
  - [ ] find other solution for @internal methods in MySQLGrid.php which are currently public for testing purposes, but should not be part of the public API (e.g. via friend class pattern or test-specific sub-classing)
  - [ ] $this->delete_before, $this->delete_after, $this->edit_after, $this->add_before, $this->add_after, $this->edit_before hooks
  - [ ] test html output for different column types (text, textarea, select, ...) and settings (e.g. other texts, sort order, can_sort, can_filter)
  - [ ] test filter/sort/pagination behavior
  - [ ] test file upload handling and security
  - [ ] add regression tests for `cssClass` as string and string-array variants
  - [ ] add regression tests for CSRF and POST-only state-changing request handling
  - [ ] add regression test for escaped `placeholder` attribute values
  - [ ] add playwright test (optional) for demo page to verify basic functionality and prevent regressions

### Documentation
  - [ ] check parameter typings. (eg. `mixed` types in MySQLGrid.php which could be more specific, especially for public API methods)
  - [ ] Documentation in readme for grid configuration, styling, columns types, convert_input/convert_output etc.
  - [ ] update readme: how to include custom styles/themes, how to customize via CSS variables, how to override icons with custom SVGs, how to use a custom theme via `$grid->cssClass`
  - [ ] Add README badges (CI, license, latest release)

### Deployment
  - [x] update manifest in asset publishing only if assets actually changed (e.g. by comparing file hashes) to avoid unnecessary asset updates in host projects

### Publishing
- [x] switch version to 1.0.0 release and use correct semantic versioning from there on
- [ ] evaluate: Set up a GitHub Pages/Wiki for demo and documentation hosting

### Cleanup
- [ ] Remove legacy/deprecated properties or set to private/protected in MySQLGridAssets (e.g. `cssUrl`, `cssTag`, `cssUrls`, `cssTags`, `jsUrl`, `jsTag`, `assetUrl`)

---


## Archive

### v0.6

- [x] Convert code style to 1TBS (one true brace style)
- [x] Require PHP >= 8.2
- [x] Add Composer lint workflow (syntax/style/static) with PHPStan level 8
- [x] Add README, CHANGELOG, and TODO documentation
- [x] update visibility of methods and properties (currently all public, but some should be private/protected)
- [x] Add demo page
  - [x] Add demo page with a simple table with lookup table
  - [x] Add reset functionality to demo page to recreate schema and seed data
  - [x] add rows/page selector to demo page
  - [x] Add more complex demo page with more column types (file-upload, select, ...) and features (only for internal testing/demo purposes, not necessarily for public documentation)
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
- [x] Documentation
  - [x] Document public properties and methods with doc blocks (ignore internal methods which are only public for testing purposes, but should not be part of the public API (@ignore))
  - [x] Add Screenshots to README
  - [x] add upgrade guide for v0.5 to v0.6 breaking changes
- [x] Improve styling / default theme
  - [x] Replace Unicode and icon-font controls with inline SVG icons (`svgIcon*` / `svgSort*` properties, Bootstrap Icons MIT)
- [x] Update cspell word list for new identifiers and technical terms
- [x] Repository Standards
  - [x] Add GitHub Actions CI workflow for composer validate + lint
  - [x] Add Dependabot configuration for Composer and GitHub Actions
  - [x] Add CONTRIBUTING.md
  - [x] Add SECURITY.md
  - [-] Add CODE_OF_CONDUCT.md (optional, if contributor base grows)
  - [x] Add issue and PR templates
  - [x] Add .editorconfig
  - [x] Add PHPUnit config and first automated test suite
  - [x] change directory structure to src/ and assets/
  - [x] Cleanup Changelog.md (use only relevant entries)
- [x] Add release checklist document for tags and publishing
- [x] Publishing
  - [x] move repository to Github
  - [x] Change branch naming to main if needed
  - [x] check if the name `PhpMySQLGrid` is available and not trademarked
  - [x] tag versions
  - [x] Add GitHub Actions workflow for releases (e.g. on tag push, with changelog generation and packagist update)
  - [x] Publish to [packagist.org/](https://packagist.org/)
  - [x] configure repository settings (branch protection, required reviews, etc.)
- [x] make 100% table width optional
