<?php

declare(strict_types=1);

namespace MySQLGridTests;

use PhpMySQLGrid\MySQLGrid;
use PHPUnit\Framework\TestCase;

final class MySQLGridUnitTest extends TestCase {
    private array $originalGet = [];

    protected function setUp(): void {
        // Save original $_GET to prevent test interference
        $this->originalGet = $_GET;
    }

    protected function tearDown(): void {
        // Restore original $_GET
        $_GET = $this->originalGet;
    }
    public function testCountPrimariesWithCompositeKey(): void {
        $grid = new MySQLGrid();
        $grid->primary = array("id", "tenant_id");

        $this->assertSame(2, $grid->countPrimaries());
    }

    public function testCountPrimariesWithSinglePrimary(): void {
        $grid = new MySQLGrid();
        $grid->primary = "id";

        $this->assertSame(1, $grid->countPrimaries());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('htmlEntitiesProvider')]
    public function testConvertToHtmlEntitiesEscapesSpecialChars(mixed $input, string $expected): void {
        $grid = new MySQLGrid();

        $this->assertSame($expected, $grid->convertToHtmlEntities($input));
    }

    public static function htmlEntitiesProvider(): array {
        return [
            'html tags and ampersand' => ["<b>Tom & Jerry</b>", "&lt;b&gt;Tom &amp; Jerry&lt;/b&gt;"],
            'double quotes'           => ['"hello"',            '&quot;hello&quot;'],
            'single quotes not encoded' => ["it's fine",        "it's fine"],  // ENT_COMPAT leaves single quotes as-is
            'empty string'            => ['',                    ''],
            'null'                    => [null,                  ''],
        ];
    }

    public function testPrepareQueryVarsUsesGridPrefix(): void {
        $grid = new MySQLGrid();
        $grid->name = "users_grid";

        // TODO prepareQueryVars should be private, check if this can be tested indirectly through a public method that calls it
        $grid->prepareQueryVars();

        $this->assertSame("users_grid_setpage", $grid->cmdSetPage);
        $this->assertSame("users_grid_confirmadd", $grid->cmdConfirmAdd);
        $this->assertSame("users_grid_deleteid", $grid->varDeleteID);
        $this->assertSame("users_grid_editid", $grid->varEditID);
    }

    public function testValidateColumnsAppliesDefaults(): void {
        $grid = new MySQLGrid();
        $grid->columns = array(
            array("field" => "title"),
            array("field" => "id", "type" => PHPMYSQLGRID_BOOLEAN, "can_sort" => false)
        );

        // TODO validateColumns should be private, check if this can be tested indirectly through a public method that calls it
        $grid->validateColumns();

        $this->assertSame(PHPMYSQLGRID_TEXT, $grid->columns[0]["type"]);
        $this->assertTrue($grid->columns[0]["can_sort"]);
        $this->assertTrue($grid->columns[0]["can_filter"]);
        $this->assertSame(PHPMYSQLGRID_BOOLEAN, $grid->columns[1]["type"]);
        $this->assertFalse($grid->columns[1]["can_sort"]);
        $this->assertTrue($grid->columns[1]["can_filter"]);
    }

    public function testValidateActionsSetsDefaultTypeWhenMissing(): void {
        $grid = new MySQLGrid();
        $grid->actions = array(
            array("caption" => "Custom Action")
        );
        $grid->columns = array(
            array("field" => "id")
        );

        $grid->validateActions();

        $this->assertSame(PHPMYSQLGRID_TEXTBUTTON, $grid->actions[0]["type"]);
        $this->assertArrayNotHasKey("type", $grid->columns[0]);
    }

    public function testValidateActionsDoesNotModifyExistingType(): void {
        $grid = new MySQLGrid();
        $grid->actions = array(
            array("caption" => "View", "type" => PHPMYSQLGRID_IMAGEBUTTON)
        );
        $grid->columns = array();

        // TODO validateActions should be private, check if this can be tested indirectly through a public method that calls it
        $grid->validateActions();

        $this->assertSame(PHPMYSQLGRID_IMAGEBUTTON, $grid->actions[0]["type"]);
    }

    public function testValidateColumnsDoesNotOverrideExistingValues(): void {
        $grid = new MySQLGrid();
        $grid->columns = array(
            array("field" => "id", "type" => PHPMYSQLGRID_BOOLEAN, "can_sort" => false, "can_filter" => false)
        );

        // TODO validateColumns should be private, check if this can be tested indirectly through a public method that calls it
        $grid->validateColumns();

        $this->assertSame(PHPMYSQLGRID_BOOLEAN, $grid->columns[0]["type"]);
        $this->assertFalse($grid->columns[0]["can_sort"]);
        $this->assertFalse($grid->columns[0]["can_filter"]);
    }

    public function testValidateColumnsWithEmptyColumnsArray(): void {
        $grid = new MySQLGrid();
        $grid->columns = array();

        // TODO validateColumns should be private, check if this can be tested indirectly through a public method that calls it
        $grid->validateColumns();

        $this->assertSame(array(), $grid->columns);
    }

    public function testConstructorSetsDefaultSettings(): void {
        $grid = new MySQLGrid();

        $this->assertSame("localhost", $grid->hostname);
        $this->assertSame(3306, $grid->port);
        $this->assertSame("root", $grid->username);
        $this->assertSame("", $grid->password);
        $this->assertSame("", $grid->database);
        $this->assertSame("", $grid->table);

        $this->assertTrue($grid->can_add);
        $this->assertTrue($grid->can_edit);
        $this->assertTrue($grid->can_delete);
        $this->assertTrue($grid->can_sort);
        $this->assertTrue($grid->can_navigate);
        $this->assertTrue($grid->can_filter);

        $this->assertSame(PHPMYSQLGRID_VIEWMODE, $grid->mode);
        $this->assertSame(array(), $grid->columns);
        $this->assertSame(array(), $grid->actions);
    }

    public function testInternationalizeSetsAllTextProperties(): void {
        $grid = new MySQLGrid();

        $this->assertSame("Previous Page", $grid->txtPrevious);
        $this->assertSame("Next Page", $grid->txtNext);
        $this->assertSame("Delete Entry", $grid->txtDelete);
        $this->assertSame("Add Entry", $grid->txtAdd);
        $this->assertSame("Edit Entry", $grid->txtEdit);
        $this->assertSame("Confirm Changes", $grid->txtConfirm);
        $this->assertSame("Cancel Changes", $grid->txtCancel);
        $this->assertSame("Yes", $grid->txtYes);
        $this->assertSame("No", $grid->txtNo);
        $this->assertSame("File present", $grid->txtFileTrue);
        $this->assertSame("No file present", $grid->txtFileFalse);
        $this->assertSame("File", $grid->txtFile);
        $this->assertSame("URL", $grid->txtURL);
    }

    public function testPrepareQueryVarsSetsAllCommandVariables(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";

        // TODO prepareQueryVars should be private, check if this can be tested indirectly through a public method that calls it
        $grid->prepareQueryVars();

        $this->assertSame("test_grid_setpage", $grid->cmdSetPage);
        $this->assertSame("test_grid_setsort", $grid->cmdSetSort);
        $this->assertSame("test_grid_setdir", $grid->cmdSetDir);
        $this->assertSame("test_grid_setfilter", $grid->cmdSetFilter);
        $this->assertSame("test_grid_setdata", $grid->cmdSetData);
        $this->assertSame("test_grid_setfile", $grid->cmdSetFile);
        $this->assertSame("test_grid_seturl", $grid->cmdSetURL);
        $this->assertSame("test_grid_clearfile", $grid->cmdClearFile);
        $this->assertSame("test_grid_add", $grid->cmdAdd);
        $this->assertSame("test_grid_confirmadd", $grid->cmdConfirmAdd);
        $this->assertSame("test_grid_delete", $grid->cmdDelete);
        $this->assertSame("test_grid_confirmdelete", $grid->cmdConfirmDelete);
        $this->assertSame("test_grid_cancel", $grid->cmdCancel);
        $this->assertSame("test_grid_edit", $grid->cmdEdit);
        $this->assertSame("test_grid_confirmedit", $grid->cmdConfirmEdit);
        $this->assertSame("test_grid_deleteid", $grid->varDeleteID);
        $this->assertSame("test_grid_editid", $grid->varEditID);
    }

    public function testDrawHeaderOutputsFormAndTableTags(): void {
        $grid = new MySQLGrid();
        $grid->columns = array();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawHeader();
        $output = ob_get_clean();

        // TODO compare with expected output instead of just checking for presence of tags
        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('<table', $output);
    }

    public function testDrawHeaderIncludesMultipartEncodingForFileColumns(): void {
        $grid = new MySQLGrid();
        $grid->columns = array(
            array("field" => "attachment", "type" => PHPMYSQLGRID_FILE, "can_sort" => true, "can_filter" => true)
        );
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawHeader();
        $output = ob_get_clean();

        $this->assertStringContainsString('enctype="multipart/form-data"', $output);
    }

    public function testDrawHeaderOmitsMultipartEncodingWithoutFileColumns(): void {
        $grid = new MySQLGrid();
        $grid->columns = array(
            array("field" => "name", "type" => PHPMYSQLGRID_TEXT, "can_sort" => true, "can_filter" => true)
        );
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawHeader();
        $output = ob_get_clean();

        $this->assertStringNotContainsString('enctype="multipart/form-data"', $output);
    }

    public function testDrawHeaderRendersFrontendErrorSummary(): void {
        $grid = new MySQLGrid();
        $grid->columns = array();
        $_SERVER["PHP_SELF"] = "/test.php";

        $addFrontendError = new \ReflectionMethod($grid, "addFrontendError");
        $addFrontendError->invoke($grid, "Upload blocked by policy");

        ob_start();
        $grid->drawHeader();
        $output = ob_get_clean();

        $this->assertStringContainsString('phpmysqlgrid-error-summary', $output);
        $this->assertStringContainsString('role="alert"', $output);
        $this->assertStringContainsString('Upload blocked by policy', $output);
    }

    public function testDrawFooterOutputsClosingTags(): void {
        $grid = new MySQLGrid();

        ob_start();
        $grid->drawFooter();
        $output = ob_get_clean();

        // TODO compare with expected output instead of just checking for presence of tags
        $this->assertStringContainsString('</table>', $output);
        $this->assertStringContainsString('</form>', $output);
    }

    public function testDrawCaptionsOutputsTheadAndColumnCaption(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_sort = false;
        $grid->can_filter = false;
        $grid->columns = array(
            array("field" => "username", "caption" => "Username", "type" => PHPMYSQLGRID_TEXT, "can_sort" => false, "can_filter" => false)
        );
        $grid->sort = 0;
        $grid->dir = 0;
        // TODO prepareQueryVars should be private, check if this can be tested indirectly through a public method that calls it
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawCaptions();
        $output = ob_get_clean();

        $this->assertStringContainsString('<thead>', $output);
        $this->assertStringContainsString('Username', $output);
        $this->assertStringContainsString('</thead>', $output);
    }

    public function testDrawCaptionsUsesFieldNameWhenNoCaptionDefined(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_sort = false;
        $grid->can_filter = false;
        $grid->columns = array(
            array("field" => "email", "type" => PHPMYSQLGRID_TEXT, "can_sort" => false, "can_filter" => false)
        );
        $grid->sort = 0;
        $grid->dir = 0;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawCaptions();
        $output = ob_get_clean();

        $this->assertStringContainsString('email', $output);
    }

    public function testDrawNavigationOutputsTfootTag(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 0;
        $grid->page = 1;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringContainsString('<tfoot>', $output);
        $this->assertStringContainsString('</tfoot>', $output);
    }

    public function testDrawNavigationRendersAddButtonWhenCanAdd(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_add = true;
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 0;
        $grid->page = 1;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringContainsString('class="add-button"', $output);
    }

    public function testDrawNavigationOmitsAddButtonWhenCanAddIsFalse(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_add = false;
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 0;
        $grid->page = 1;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringNotContainsString('class="add-button"', $output);
    }

    public function testDrawNavigationUsesNavElementInsteadOfNestedTable(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_navigate = true;
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 50;
        $grid->page = 1;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringContainsString('class="phpmysqlgrid-pagination"', $output);
        $this->assertStringNotContainsString('<table', $output);
    }

    public function testDrawNavigationPaginationPrevDisabledOnFirstPage(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_navigate = true;
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 50;
        $grid->page = 1;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringContainsString('phpmysqlgrid-pagination-prev is-disabled', $output);
        $this->assertStringNotContainsString('phpmysqlgrid-pagination-next is-disabled', $output);
    }

    public function testDrawNavigationPaginationNextDisabledOnLastPage(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_navigate = true;
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 50;
        $grid->page = 5;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringContainsString('phpmysqlgrid-pagination-next is-disabled', $output);
        $this->assertStringNotContainsString('phpmysqlgrid-pagination-prev is-disabled', $output);
    }

    public function testDrawNavigationCurrentPageHasAriaCurrentAttribute(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_navigate = true;
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 50;
        $grid->page = 3;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringContainsString('aria-current="page"', $output);
        $this->assertStringContainsString('phpmysqlgrid-pagination-current', $output);
    }

    public function testDrawNavigationRendersEllipsisForLargePageCounts(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->can_navigate = true;
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 200;
        $grid->page = 10;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringContainsString('phpmysqlgrid-pagination-ellipsis', $output);
    }

    public function testDrawNavigationUsesCustomStylePrefixForPaginationClasses(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->style = "customgrid";
        $grid->can_navigate = true;
        $grid->columns = array(array("field" => "id"));
        $grid->rows = 50;
        $grid->page = 2;
        $grid->limit = 10;
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawNavigation();
        $output = ob_get_clean();

        $this->assertStringContainsString('class="customgrid-pagination"', $output);
        $this->assertStringContainsString('class="customgrid-pagination-prev"', $output);
        $this->assertStringContainsString('class="customgrid-pagination-next"', $output);
        $this->assertStringContainsString('class="customgrid-pagination-link"', $output);
        $this->assertStringContainsString('class="customgrid-pagination-current"', $output);
    }

    public function testDrawEditControlsAddsOddEvenModifierToActionCell(): void {
        $grid = new MySQLGrid();
        $grid->name = "test_grid";
        $grid->mode = PHPMYSQLGRID_ADDMODE;
        $grid->row = 0;
        $grid->columns = array(
            array("field" => "name", "type" => PHPMYSQLGRID_TEXT)
        );
        $grid->prepareQueryVars();
        $_SERVER["PHP_SELF"] = "/test.php";

        ob_start();
        $grid->drawEditControls(false);
        $output = ob_get_clean();

        $this->assertStringContainsString('class="phpmysqlgrid-action phpmysqlgrid-cell--even phpmysqlgrid-action--add"', $output);
    }

    public function testGetPreservedParamsFiltersGridSpecificParams(): void {
        $grid = new MySQLGrid();
        $grid->name = "users";
        $grid->prepareQueryVars();

        // Simulate GET with grid-specific and non-grid params
        $_GET = array(
            "theme" => "dark",
            "users_setpage" => "2",
            "users_sort" => "name",
            "limit" => "50",
        );

        // Use reflection to call protected method
        $reflection = new \ReflectionMethod($grid, "getPreservedParams");
        $preserved = $reflection->invoke($grid);

        // Non-grid params should be kept
        $this->assertArrayHasKey("theme", $preserved);
        $this->assertArrayHasKey("limit", $preserved);
        $this->assertSame("dark", $preserved["theme"]);
        $this->assertSame("50", $preserved["limit"]);

        // Grid-specific params should be filtered out
        $this->assertArrayNotHasKey("users_setpage", $preserved);
        $this->assertArrayNotHasKey("users_sort", $preserved);
    }

    public function testGetPreservedParamsMergesGridParams(): void {
        $grid = new MySQLGrid();
        $grid->name = "products";
        $grid->prepareQueryVars();

        $_GET = array("theme" => "light");

        // Use reflection to call protected method with arguments
        $reflection = new \ReflectionMethod($grid, "getPreservedParams");
        $result = $reflection->invoke($grid, array("cmdSort" => "price"));

        $this->assertSame("light", $result["theme"]);
        $this->assertSame("price", $result["cmdSort"]);
    }
}
