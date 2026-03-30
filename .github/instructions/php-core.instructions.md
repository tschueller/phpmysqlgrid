---
name: php-core
description: "Use when: working on MySQLGrid.php, adding methods, fixing properties, or writing unit tests (MySQLGridUnitTest, MySQLGridXssTest). For visibility rules, backward compatibility, @internal markers, and dynamic properties guidance."
applyTo:
  - "MySQLGrid.php"
  - "tests/MySQLGridUnitTest.php"
  - "tests/MySQLGridXssTest.php"
---

# PHP Core Instructions for MySQLGrid

## Visibility Declarations (Hard Rule)

**All new methods and properties must declare explicit visibility** (`public`, `protected`, or `private`). Never rely on the implicit `public` default.

```php
// ✅ CORRECT
public function myPublicMethod(): void { ... }
protected function myInternalHelper(): void { ... }
private $cachedValue;

// ❌ WRONG
function myMethod() { ... }  // implicit public, not allowed
var $something;              // implicit public, not allowed
```

## Legacy Visibility Policy (Existing Methods)

This project contains legacy methods with implicit `public` visibility. These are tolerated temporarily for backward compatibility, but they are not the target style.

Rules for legacy methods:

- Do not mass-convert legacy methods in unrelated refactors.
- When you touch a legacy method for functional changes, add explicit visibility in the same change.
- Keep externally consumed API methods `public`.
- Prefer `protected`/`private` for implementation details when safe and backward compatible.
- `@internal` does not replace visibility; use both when needed.

Recommended migration pattern:

```php
/** @internal */
public function drawHeader(): void
{
  // ...
}
```

For backward compatibility reasons, the legacy PHP4-style constructor (`function MySQLGrid(): void`) may remain public while it is still supported.

## Constructor Policy (PHP 7/8 Style)

Use the modern constructor style for all new and touched code:

```php
public function __construct()
{
  // ...
}
```

Rules:
- Prefer `public function __construct()` as the only constructor API.
- Do not introduce new PHP4-style constructors (`function ClassName()`).
- If a legacy PHP4-style constructor exists, keep it only as a temporary backward-compatibility wrapper and mark it `@deprecated`.
- Route any legacy wrapper directly to `__construct()`.

## Dynamic Properties

The `MySQLGrid` class uses `#[AllowDynamicProperties]` (line 48) for PHP 8.2+ compatibility. This is intentional and expected due to the library's age and backward compatibility needs.

- Do not remove or disable this attribute.
- Do not add properties that could be made static to the dynamic pool—be intentional.
- When adding a dynamic property in normal code paths (not tests), document it in a command-style doc block if it affects public behavior.

## Class-Level API Documentation (Required)

For `MySQLGrid`, keep a class-level doc block that explains the class as an API entry point.

Required content:

- A short purpose summary (what the class does).
- A compact capability overview (rendering, CRUD, filtering, sorting, pagination, hooks).
- A short note about runtime model (internal connection vs injected PDO, execute lifecycle).

Keep this section concise but informative so IDE hover and generated API docs provide useful context without reading implementation details.

## `@property` Documentation for Dynamic Public Configuration (Required)

Because public runtime configuration is exposed via dynamic properties, keep `@property` annotations in the class doc block up to date.

Rules:

- Document relevant public dynamic properties with type and purpose.
- Include default values when they are set by constructor or initialization methods and relevant for users.
- Keep defaults accurate when constructor/internationalize defaults change.
- Mark test-only public internals with `@internal` and `@ignore` on methods; do not expose them as public API properties.

## @internal Marker for Internal Implementation Methods

Methods that must be `public` for unit-testability (because PHPUnit needs reflection access) but are not part of the public API should be marked `/** @internal */`:

```php
/**
 * Validates column configuration. Internal use only; not part of public API.
 * PHPStan will warn if called from outside the package.
 *
 * @internal
 */
public function validateColumns(): void
{
    // ...
}
```

This signals to consumers and static analysis that the method is subject to change.

## String Literals and Quoting

Prefer double quotes for string literals in MySQLGrid.php when practical:

```php
// ✅ PREFER
$grid->database = "test_db";
$label = "Active Status";

// ⚠ ACCEPTABLE (if contains double quotes already)
$json = '{"key": "value"}';
```

**Do not enable PHP-CS-Fixer's `single_quote` rule** for this repository. The `phpcs` and lint rules are configured to allow double quotes.

## Backward Compatibility

- Maintain backward compatibility for all public properties and methods.
- Existing consumers setting `hostname`, `port`, `username`, `password`, `database` must continue to work.
- Database connection defaults to internal `PDO` creation; do not remove this path.
- Do not remove previously exported constants like `PHPMYSQLGRID_TEXT`, `PHPMYSQLGRID_ADDMODE`, etc.

When fixing buggy behavior, check [TODO.md](../../TODO.md) to see if it is documented as intentional for a future major version.

## Working with PDO (All DB Paths)

All database operations use PDO exclusively:
- `addData`, `editData`, `deleteData` use prepared statements with named placeholders.
- `prepareData` handles result fetching; supports `pdo_mysql` (MySQL) and `pdo_sqlite` (tests).
- Injected connections are set via `setDatabaseConnection($pdo, 'pdo_mysql')` or `setDatabaseConnection($pdo, 'pdo_sqlite')`.
- Default mode (no injection) creates a PDO connection internally from `hostname`, `port`, `username`, `password`, `database`.

**Old `mysqli` code paths no longer exist.** If you find references, they are legacy comments and should be removed.

## PHPStan Level 8

Current level is **8**. When adding or modifying code:

- Run `composer run lint:static` before committing.
- If PHPStan reports errors, fix them or add `/** @phpstan-ignore-next-line */` with justification.
- Do not lower the level or suppress categories without discussing first.

## Internationalization Properties

All user-visible text (labels, button text, ARIA labels, tooltips) must be backed by public properties initialized in `internationalize()`:

```php
public $txtAdd = "Add";
public $txtEdit = "Edit";
public $txtDelete = "Delete";

// In internationalize():
$this->txtAdd = $customizedLabel ?? $this->txtAdd;
```

Never hardcode visible text or accessibility strings directly in HTML output. This allows users to customize without forking.

## Code Style and Comments

- Follow the existing code style in MySQLGrid.php.
- Use English comments and doc blocks for new methods or complex logic.
- Keep comments concise; prefer clear naming and type hints over verbose explanations.
- Add comments only for non-obvious behavior or known-bug documentation.

## Testing Non-DB Behavior

For unit tests (MySQLGridUnitTest.php):
- Do not require a database connection.
- Test rendering logic, mode handling, column configuration, and state changes.
- Test input validation and error conditions.
- Use descriptive test method names (e.g., `testCanAddRowReturnsTrue`).

See [.github/instructions/testing.instructions.md](./testing.instructions.md) for database behavior testing.
