---
name: testing
description: "Use when: creating or modifying test files, especially integration tests (MySQLGridRealCrudIntegrationTest, MySQLGridSqlInjectionTest, DatabaseTestCase). For test conventions, SQLite setup, DB mocking, fixture patterns, and test organization."
applyTo: "tests/**/*.php"
---

# Testing Instructions for MySQLGrid

## Test Layers and Decision Tree

MySQLGrid tests are organized into two layers:

### 1) Unit Tests (Non-Database Behavior)

**When to use:** Testing logic that does not require a database connection.

- Grid mode handling, column filtering, action rendering
- Input validation and state transitions
- CSS class generation
- Property initialization and configuration

**Files:** `MySQLGridUnitTest.php`, `MySQLGridXssTest.php`

**Setup:** No database initialization needed. Create a `MySQLGrid` instance and test behavior directly.

```php
$grid = new MySQLGrid();
$grid->table = "users";
$grid->primary = "id";
// Test grid behavior without connecting to DB
```

### 2) SQLite Integration Tests (Database Behavior)

**When to use:** Testing real database operations (CRUD, filtering, lookup rendering, execute-path workflows).

- `addData`, `editData`, `deleteData` with injected PDO
- `prepareData`, `unprepareData` result handling
- Lookup query rendering in `drawEditControls`
- Filter/sort/pagination with real SQL
- Security boundaries (SQL injection, XSS)

**Files:** `MySQLGridRealCrudIntegrationTest.php`, `MySQLGridSqlInjectionTest.php`

**Setup:** Extend `DatabaseTestCase`, which provides a fresh in-memory SQLite database per test.

```php
class MyTest extends DatabaseTestCase
{
    public function testSomethingWithDatabase(): void
    {
        $grid = new MySQLGrid();
        $grid->setDatabaseConnection($this->pdo, 'pdo_sqlite');
        $grid->table = 'users';
        $grid->primary = 'id';
        // Database is seeded; test real behavior
    }
}
```

## SQLite In-Memory Infrastructure

### Key Files

- **`tests/SqliteTestDatabase.php`**
  Creates an in-memory PDO SQLite connection, configures error mode, builds schema, seeds baseline rows.

- **`tests/DatabaseTestCase.php`**
  Shared abstract base class. Creates a fresh database in `setUp()`, provides row-count and fetch assertions.

- **`tests/MySQLGridRealCrudIntegrationTest.php`**
  Real `MySQLGrid` DB methods tested via injected `pdo_sqlite`.

- **`tests/MySQLGridSqlInjectionTest.php`**
  Security regression tests for SQL injection and XSS.

### How Isolation Works

Each test method receives a new in-memory database created in `DatabaseTestCase::setUp()`:

- Seed data starts from a known state.
- Tests should not depend on side effects from other tests.
- Each test is atomic; database changes are discarded.

### Using Fixtures in Tests

Schema and seed data are defined in `SqliteTestDatabase`:

```php
private function createSchema(): void
{
    $this->pdo->exec("CREATE TABLE users ...");
}

private function seedData(): void
{
    $this->pdo->exec("INSERT INTO users (name, active) VALUES ('Alice', 1)");
}
```

To add new fixtures for a new test, extend `SqliteTestDatabase` or override `createSchema()`/`seedData()` in your test class. Keep fixtures deterministic and minimal.

## Test File Loading and Includes

PHPUnit file discovery does not autoload helper classes automatically. Use explicit includes:

```php
require_once __DIR__ . "/DatabaseTestCase.php";

class MyTest extends DatabaseTestCase { ... }
```

## Writing Tests: Best Practices

### 1) Naming

Use descriptive method names:
- `testAddDataInsertsRowInDatabase` ✅
- `testEditDataValidatesInput` ✅
- `test1` ❌

### 2) Assertions

Prefer explicit, meaningful assertions:

```php
// ✅ CLEAR
$this->assertEquals(2, $this->getUserCount());
$this->assertSame('Alice', $user['name']);

// ⚠ VAGUE
$this->assertTrue($result);
```

### 3) Test Organization

Keep related tests in the same file. If a file grows beyond ~200 lines, consider splitting by theme (e.g., separate files for CRUD vs rendering).

### 4) Comments

Use comments for known-bug or non-obvious behavior:

```php
public function testEditDataWithNullValueHandling(): void
{
    // TODO: Null handling in edit mode is inconsistent; this test documents current behavior.
    // See TODO.md for planned fix
    $this->markSkipped("Null handling not yet implemented");
}
```

## Security Test Patterns

### SQL Injection Tests

Test that dangerous payloads are blocked or escaped:

```php
public function testAddDataBlocksSqlInjectionInTextField(): void
{
    $_POST['field'] = "'; DROP TABLE users; --";
    // addData should escape or reject
    $grid->addData();
    $this->assertTableExists('users', 'SQL injection was not blocked');
}
```

### XSS Tests

Test that user input is escaped in HTML output:

```php
public function testDrawEditControlsEscapesHtmlInTextInput(): void
{
    $grid->columns = array(
        array('field' => 'name', 'type' => PHPMYSQLGRID_TEXT, 'value' => '<script>alert("xss")</script>')
    );
    $html = $grid->drawEditControls();
    $this->assertStringNotContainsString('<script>', $html);
}
```

## Running Tests

Run the full suite:

```bash
composer run test
```

Run a single test file:

```bash
composer run test -- tests/MySQLGridUnitTest.php
```

Run tests matching a pattern:

```bash
composer run test -- --filter testAddData
```

## Code Array Style

Maintain consistency with the repository style. Use `array(...)` notation:

```php
// ✅ CORRECT (matches repository style)
$fixture = array(
    'field' => 'name',
    'type' => PHPMYSQLGRID_TEXT,
);

// ⚠ NOT USED (modern PHP but not this repo's style)
$fixture = [
    'field' => 'name',
    'type' => PHPMYSQLGRID_TEXT,
];
```

## Integration with Linting and Static Analysis

After writing tests:

```bash
composer run lint       # Full linting (syntax, style, static analysis)
composer run test       # Run all tests
```

If a test intentionally deviates from style or contains PHPStan violations, mark it:

```php
/** @phpstan-ignore-next-line */
public function testSomethingUnsafe(): void { ... }
```

But use this sparingly. Prefer fixing the underlying issue.
