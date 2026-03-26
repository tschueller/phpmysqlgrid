<?php

declare(strict_types=1);

namespace MySQLGridTests;

use MySQLGrid;
use PHPUnit\Framework\TestCase;

final class MySQLGridUnitTest extends TestCase {
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

    public function testConvertToHtmlEntitiesEscapesMarkup(): void {
        $grid = new MySQLGrid();

        $this->assertSame(
            "&lt;b&gt;Tom &amp; Jerry&lt;/b&gt;",
            $grid->convertToHtmlEntities("<b>Tom & Jerry</b>")
        );
    }

    public function testInternationalizeSetsDefaultTexts(): void {
        $grid = new MySQLGrid();
        $grid->txtNext = "Changed";

        $grid->internationalize();

        $this->assertSame("Previous", $grid->txtPrevious);
        $this->assertSame("Next", $grid->txtNext);
        $this->assertSame("Confirm", $grid->txtConfirm);
    }

    public function testPrepareQueryVarsUsesGridPrefix(): void {
        $grid = new MySQLGrid();
        $grid->name = "users_grid";

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

        $grid->validateColumns();

        $this->assertSame(PHPMYSQLGRID_TEXT, $grid->columns[0]["type"]);
        $this->assertTrue($grid->columns[0]["can_sort"]);
        $this->assertTrue($grid->columns[0]["can_filter"]);
        $this->assertSame(PHPMYSQLGRID_BOOLEAN, $grid->columns[1]["type"]);
        $this->assertFalse($grid->columns[1]["can_sort"]);
        $this->assertTrue($grid->columns[1]["can_filter"]);
    }

    // TODO: validateActions() has a bug — it sets $this->columns[$i]["type"] instead of
    //       $this->actions[$i]["type"] when a type is missing. This test documents the
    //       current (incorrect) behavior. Fix the bug and update assertions accordingly.
    public function testValidateActionsCurrentBehaviorWithMissingType(): void {
        $grid = new MySQLGrid();
        $grid->actions = array(
            array("caption" => "Custom Action")
        );
        $grid->columns = array(
            array("field" => "id")
        );

        $grid->validateActions();

        $this->assertArrayNotHasKey("type", $grid->actions[0]);
        $this->assertSame(PHPMYSQLGRID_TEXTBUTTON, $grid->columns[0]["type"]);
    }
}
