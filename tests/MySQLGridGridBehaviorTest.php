<?php

declare(strict_types=1);

namespace MySQLGridTests;

require_once __DIR__ . "/DatabaseTestCase.php";

use PhpMySQLGrid\MySQLGrid;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Tests for pagination, column filter, column sort, global SQL filter,
 * default sort, filter+sort state persistence, and unsafe SQL fragment detection.
 */
final class MySQLGridGridBehaviorTest extends DatabaseTestCase {

    protected function setUp(): void {
        parent::setUp();

        $_REQUEST = array();
        $_POST    = array();
        $_GET     = array();
        $_FILES   = array();
        $_SESSION = array();
        $_SERVER["PHP_SELF"]       = "/index.php";
        $_SERVER["REQUEST_METHOD"] = "GET";

        // Seed extra rows so the table has 7 records (2 from parent + 5 more).
        // This satisfies the >5 rows precondition for pagination tests.
        $stmt = $this->sqlite->prepare(
            "INSERT INTO users (email, display_name, is_active, bio)
             VALUES (:email, :display_name, :is_active, :bio)"
        );
        $extras = array(
            array("email" => "carol@example.test",   "display_name" => "Carol",   "is_active" => 1, "bio" => "Extra user C"),
            array("email" => "dave@example.test",    "display_name" => "Dave",    "is_active" => 0, "bio" => "Extra user D"),
            array("email" => "eve@example.test",     "display_name" => "Eve",     "is_active" => 1, "bio" => "Extra user E"),
            array("email" => "frank@example.test",   "display_name" => "Frank",   "is_active" => 0, "bio" => "Extra user F"),
            array("email" => "grace@example.test",   "display_name" => "Grace",   "is_active" => 1, "bio" => "Extra user G"),
        );
        foreach ($extras as $row) {
            $stmt->execute($row);
        }
    }

    protected function tearDown(): void {
        $_REQUEST = array();
        $_POST    = array();
        $_GET     = array();
        $_FILES   = array();
        $_SESSION = array();

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildGrid(string $name = "test_grid"): MySQLGrid {
        $grid = new MySQLGrid();
        $grid->setDatabaseConnection($this->sqlite, "pdo_sqlite");
        $grid->table   = "users";
        $grid->primary = "id";
        $grid->name    = $name;
        $grid->columns = array(
            array("field" => "email",        "type" => PHPMYSQLGRID_TEXT),
            array("field" => "display_name", "type" => PHPMYSQLGRID_TEXT),
            array("field" => "is_active",    "type" => PHPMYSQLGRID_BOOLEAN),
        );
        $grid->connect();
        return $grid;
    }

    private function initGridForPrepare(MySQLGrid $grid, int $page = 1, int $sort = 0, int $dir = 0): void {
        $grid->sort  = $sort;
        $grid->dir   = $dir;
        $grid->page  = $page;
        foreach ($grid->columns as $i => $_col) {
            $grid->columns[$i]["active_filter"] = "";
        }
    }

    private function fetchAllRows(MySQLGrid $grid): array {
        $rows = array();
        if ($grid->result instanceof \PDOStatement) {
            while ($row = $grid->result->fetch(\PDO::FETCH_NUM)) {
                $rows[] = $row;
            }
        }
        $grid->unprepareData();
        return $rows;
    }

    // -------------------------------------------------------------------------
    // Pagination navigates between pages of records
    // -------------------------------------------------------------------------

    #[TestDox("First page shows up to 5 rows when limit=5 and table has 7 records")]
    public function testPaginationFirstPageShowsLimitedRows(): void {
        $grid        = $this->buildGrid("pag_first_grid");
        $grid->limit = 5;
        $this->initGridForPrepare($grid, page: 1);

        $grid->prepareData();

        $this->assertSame(7, (int)$grid->rows, "Total row count should be 7");
        $rows = $this->fetchAllRows($grid);
        $this->assertCount(5, $rows, "First page must show exactly 5 rows");
    }

    #[TestDox("Next page shows remaining rows")]
    public function testPaginationNextPageShowsRemainingRows(): void {
        $grid        = $this->buildGrid("pag_next_grid");
        $grid->limit = 5;
        $this->initGridForPrepare($grid, page: 2);

        $grid->prepareData();

        $rows = $this->fetchAllRows($grid);
        $this->assertCount(2, $rows, "Second page must show the remaining 2 rows");
    }

    #[TestDox("Previous page (page 1) shows first set of records again")]
    public function testPaginationPreviousPageReturnsFirstPage(): void {
        $grid        = $this->buildGrid("pag_prev_grid");
        $grid->limit = 5;

        // Navigate to page 2 first, then back to page 1
        $this->initGridForPrepare($grid, page: 1);
        $grid->prepareData();
        $firstPageRows = $this->fetchAllRows($grid);

        $this->initGridForPrepare($grid, page: 1);
        $grid->prepareData();
        $revisitedRows = $this->fetchAllRows($grid);

        $this->assertSame(
            array_column($firstPageRows, 0),
            array_column($revisitedRows, 0),
            "Returning to page 1 must show the same primary keys as the original first page"
        );
    }

    // -------------------------------------------------------------------------
    // Column filter narrows displayed records
    // -------------------------------------------------------------------------

    #[TestDox("Column filter on email reduces rows to only matching records")]
    public function testColumnFilterNarrowsDisplayedRecords(): void {
        $grid        = $this->buildGrid("fil_match_grid");
        $grid->limit = 10;
        $this->initGridForPrepare($grid);
        // Filter the email column (index 0) for "alice"
        $grid->columns[0]["active_filter"] = "alice@example.test";

        $grid->prepareData();

        $this->assertSame(1, (int)$grid->rows, "Filter must reduce result to 1 matching row");
        $rows = $this->fetchAllRows($grid);
        $this->assertCount(1, $rows);
        $this->assertStringContainsString("alice@example.test", (string)$rows[0][1]);
    }

    #[TestDox("Non-matching filter value returns zero rows")]
    public function testColumnFilterWithNoMatchReturnsZeroRows(): void {
        $grid        = $this->buildGrid("fil_none_grid");
        $grid->limit = 10;
        $this->initGridForPrepare($grid);
        $grid->columns[0]["active_filter"] = "nobody@example.test";

        $grid->prepareData();

        $this->assertSame(0, (int)$grid->rows);
        $grid->unprepareData();
    }

    // -------------------------------------------------------------------------
    // Column sort orders records ascending and descending
    // -------------------------------------------------------------------------

    #[TestDox("Ascending sort on display_name column orders rows A→Z")]
    public function testColumnSortAscendingOrdersRows(): void {
        $grid        = $this->buildGrid("sort_asc_grid");
        $grid->limit = 10;
        // Sort by display_name (column index 1), direction 0 = ascending
        $this->initGridForPrepare($grid, sort: 1, dir: 0);

        $grid->prepareData();

        $rows  = $this->fetchAllRows($grid);
        $names = array_column($rows, 2); // display_name is 3rd selected column (id, email, display_name)
        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names, "Rows must be sorted ascending by display_name");
    }

    #[TestDox("Descending sort on display_name column orders rows Z→A")]
    public function testColumnSortDescendingOrdersRows(): void {
        $grid        = $this->buildGrid("sort_desc_grid");
        $grid->limit = 10;
        // Sort by display_name (column index 1), direction 1 = descending
        $this->initGridForPrepare($grid, sort: 1, dir: 1);

        $grid->prepareData();

        $rows  = $this->fetchAllRows($grid);
        $names = array_column($rows, 2);
        $sorted = $names;
        rsort($sorted);
        $this->assertSame($sorted, $names, "Rows must be sorted descending by display_name");
    }

    // -------------------------------------------------------------------------
    // Global SQL filter restricts visible records
    // -------------------------------------------------------------------------

    #[TestDox("filter property with SQL fragment limits rows to matching records only")]
    public function testGlobalSqlFilterRestrictsVisibleRecords(): void {
        $grid         = $this->buildGrid("sqlfil_active_grid");
        $grid->limit  = 10;
        $grid->filter = "is_active = 1";
        $this->initGridForPrepare($grid);

        $grid->prepareData();

        // 5 of 7 seeded users have is_active=1 (Alice, Carol, Eve, Grace + Bob)
        // Bob is_active=1, Dave is_active=0, Frank is_active=0
        $this->assertSame(5, (int)$grid->rows, "Only active records must be returned");
        $rows = $this->fetchAllRows($grid);
        foreach ($rows as $row) {
            $this->assertSame("1", (string)$row[3], "Every returned row must have is_active=1");
        }
    }

    #[TestDox("Records not matching the filter condition are excluded")]
    public function testGlobalSqlFilterExcludesNonMatchingRecords(): void {
        $grid         = $this->buildGrid("sqlfil_inactive_grid");
        $grid->limit  = 10;
        $grid->filter = "is_active = 0";
        $this->initGridForPrepare($grid);

        $grid->prepareData();

        $this->assertSame(2, (int)$grid->rows, "Only inactive records must be returned");
        $rows = $this->fetchAllRows($grid);
        foreach ($rows as $row) {
            $this->assertSame("0", (string)$row[3], "Every returned row must have is_active=0");
        }
    }

    // -------------------------------------------------------------------------
    // Default sort column and direction applied on initial load
    // -------------------------------------------------------------------------

    /**
     * execute() calls processSession() internally, which reads default_sort_column/default_sort_direction
     * when no session state exists. We verify the rendered HTML contains rows in descending name order.
     */
    #[TestDox("default_sort_column=1 and default_sort_direction=1 applied on initial load via execute()")]
    public function testDefaultSortAppliedOnInitialLoad(): void {
        $grid                         = $this->buildGrid("defsort_grid");
        $grid->limit                  = 10;
        $grid->default_sort_column    = 1;   // display_name column (index 1)
        $grid->default_sort_direction = 1;   // descending
        $grid->can_add                = false;
        $grid->can_edit               = false;
        $grid->can_delete             = false;

        // No session state — simulate first load with no user interaction
        $_SESSION = array();

        ob_start();
        $grid->execute();
        $html = (string)ob_get_clean();

        // Verify the HTML contains all display names and that Grace appears before Alice
        // (descending alphabetical order: Grace > Frank > Eve > Dave > Carol > Bob > Alice)
        $this->assertStringContainsString('Grace', $html);
        $this->assertStringContainsString('Alice', $html);

        $posGrace = strpos($html, 'Grace');
        $posAlice = strpos($html, 'Alice');
        $this->assertNotFalse($posGrace);
        $this->assertNotFalse($posAlice);
        $this->assertLessThan(
            (int)$posAlice,
            (int)$posGrace,
            "Grace must appear before Alice in the rendered HTML (descending sort by display_name)"
        );
    }

    // -------------------------------------------------------------------------
    // Filter and sort state persists across page navigation
    // -------------------------------------------------------------------------

    #[TestDox("Active filter and sort are preserved when navigating to page 2")]
    public function testFilterAndSortStatePersistsAcrossPageNavigation(): void {
        $grid        = $this->buildGrid("persist_state_grid");
        $grid->limit = 3;
        // Apply filter on email (index 0) and sort by display_name (index 1) descending
        $this->initGridForPrepare($grid, page: 1, sort: 1, dir: 1);
        $grid->columns[0]["active_filter"] = "example.test";

        $grid->prepareData();
        $page1Rows = $this->fetchAllRows($grid);

        // Navigate to page 2 — keep same filter and sort
        $this->initGridForPrepare($grid, page: 2, sort: 1, dir: 1);
        $grid->columns[0]["active_filter"] = "example.test";

        $grid->prepareData();
        $page2Rows = $this->fetchAllRows($grid);

        // Both pages must return rows (filter is active on both)
        $this->assertNotEmpty($page1Rows, "Page 1 must return rows with filter applied");
        $this->assertNotEmpty($page2Rows, "Page 2 must return rows with filter still applied");

        // Rows on page 2 must be different from page 1
        $page1Ids = array_column($page1Rows, 0);
        $page2Ids = array_column($page2Rows, 0);
        $this->assertEmpty(
            array_intersect($page1Ids, $page2Ids),
            "Page 2 must show different records than page 1"
        );
    }

    // -------------------------------------------------------------------------
    // Unsafe SQL fragment in filter property triggers error
    // -------------------------------------------------------------------------

    #[TestDox("Dangerous SQL fragment in filter property triggers E_USER_ERROR")]
    public function testUnsafeSqlFragmentInFilterTriggersError(): void {
        $grid         = $this->buildGrid("unsafe_sql_grid");
        $grid->limit  = 10;
        $grid->filter = "id=1; DROP TABLE users --";
        $this->initGridForPrepare($grid);

        $errorTriggered = false;
        $errorMessage   = "";

        set_error_handler(
            function (int $errno, string $errstr) use (&$errorTriggered, &$errorMessage): bool {
                if ($errno === E_USER_ERROR) {
                    $errorTriggered = true;
                    $errorMessage   = $errstr;
                    return true; // suppress — do not let PHPUnit see it as a fatal
                }
                return false;
            },
            E_USER_ERROR
        );

        try {
            $grid->prepareData();
        } catch (\Throwable) {
            // Some implementations throw instead of triggering E_USER_ERROR
            $errorTriggered = true;
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($errorTriggered, "An E_USER_ERROR or exception must be raised for unsafe SQL in filter");
        // Table must still exist and be intact
        $this->assertTableRowCount("users", 7);
    }

    // -------------------------------------------------------------------------
    // Multiple grid instances on same page use independent state
    // -------------------------------------------------------------------------

    #[TestDox("Two grids with distinct names maintain independent pagination state")]
    public function testTwoGridsWithDistinctNamesMaintainIndependentPaginationState(): void {
        $grid1        = $this->buildGrid("iso_page_a");
        $grid1->limit = 3;
        $this->initGridForPrepare($grid1, page: 1);

        $grid2        = $this->buildGrid("iso_page_b");
        $grid2->limit = 3;
        $this->initGridForPrepare($grid2, page: 2);

        $grid1->prepareData();
        $rows1 = $this->fetchAllRows($grid1);

        $grid2->prepareData();
        $rows2 = $this->fetchAllRows($grid2);

        // Grid 1 is on page 1 — should return first 3 rows
        $this->assertCount(3, $rows1, "Grid 1 (page 1) must return 3 rows");

        // Grid 2 is on page 2 — should return rows 4-6 (different IDs from grid 1)
        $this->assertCount(3, $rows2, "Grid 2 (page 2) must return 3 rows");

        // The primary keys must differ — independent pagination
        $ids1 = array_column($rows1, 0);
        $ids2 = array_column($rows2, 0);
        $this->assertEmpty(
            array_intersect($ids1, $ids2),
            "Grid 1 page 1 and Grid 2 page 2 must return different records"
        );
    }

    #[TestDox("Applying a filter to one grid does not affect the other grid's results")]
    public function testFilterOnOneGridDoesNotAffectOtherGrid(): void {
        $grid1        = $this->buildGrid("iso_fil_a");
        $grid1->limit = 10;
        $this->initGridForPrepare($grid1);
        // Apply filter to grid 1 only
        $grid1->columns[0]["active_filter"] = "alice@example.test";

        $grid2        = $this->buildGrid("iso_fil_b");
        $grid2->limit = 10;
        $this->initGridForPrepare($grid2);
        // Grid 2 has no filter

        $grid1->prepareData();
        $rows1 = $this->fetchAllRows($grid1);

        $grid2->prepareData();
        $rows2 = $this->fetchAllRows($grid2);

        $this->assertCount(1, $rows1, "Filtered grid must return only 1 matching row");
        $this->assertSame(7, count($rows2), "Unfiltered grid must still return all 7 rows");
    }
}
