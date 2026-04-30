<?php

declare(strict_types=1);

namespace MySQLGridTests;

use PhpMySQLGrid\MySQLGrid;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * XSS output-encoding tests.
 *
 * These are pure unit tests and exercise the real MySQLGrid::convertToHtmlEntities()
 * code directly — no database or adapter involved.
 */
final class MySQLGridXssTest extends TestCase {
    #[DataProvider('xssPayloadProvider')]
    public function testConvertToHtmlEntitiesEncodesXssPayloads(string $payload, string $expected): void {
        $grid = new MySQLGrid();

        $this->assertSame($expected, $grid->convertToHtmlEntities($payload));
    }

    public static function xssPayloadProvider(): array {
        return [
            'script tag'                   => [
                '<script>alert("xss")</script>',
                '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            ],
            'img onerror'                  => [
                '"><img src=x onerror=alert(1)>',
                '&quot;&gt;&lt;img src=x onerror=alert(1)&gt;',
            ],
            'svg onload'                   => [
                '<svg onload=alert(1)>',
                '&lt;svg onload=alert(1)&gt;',
            ],
            'javascript protocol'          => [
                'javascript:alert("xss")',
                'javascript:alert(&quot;xss&quot;)',
            ],
            'html entity bypass attempt'   => [
                '&lt;script&gt;',
                '&amp;lt;script&amp;gt;',
            ],
            'angle brackets and ampersand' => [
                '<b>Tom & Jerry</b>',
                '&lt;b&gt;Tom &amp; Jerry&lt;/b&gt;',
            ],
        ];
    }

    public function testConvertToHtmlEntitiesEncodesDoubleQuotes(): void {
        $grid = new MySQLGrid();

        // double quotes must be encoded to prevent breaking out of HTML attributes
        $this->assertSame('&quot;', $grid->convertToHtmlEntities('"'));
    }

    public function testConvertToHtmlEntitiesLeavesSingleQuotesLiteral(): void {
        $grid = new MySQLGrid();

        // ENT_COMPAT does not encode single quotes — document this known gap
        // TODO: consider switching to ENT_QUOTES as part of security hardening
        //       (see TODO.md) to cover single-quote injection in HTML attributes.
        $this->assertSame("it's", $grid->convertToHtmlEntities("it's"));
    }

    /**
     * Test that placeholder attribute in TEXT field is properly escaped
     * to prevent XSS attacks via field configuration.
     */
    public function testPlaceholderAttributeEscapedInEditControls(): void {
        $grid = new MySQLGrid();
        $grid->columns = [
            [
                'field' => 'test',
                'caption' => 'Test',
                'type' => PHPMYSQLGRID_TEXT,
                'placeholder' => '" onload="alert(1)">',
            ],
        ];
        $grid->mode = PHPMYSQLGRID_ADDMODE;

        ob_start();
        $grid->drawEditControls(false);
        $output = ob_get_clean();

        // The dangerous quote should be encoded as &quot;
        $this->assertStringContainsString(
            'placeholder="&quot; onload=&quot;alert(1)&quot;&gt;"',
            $output,
            'Placeholder attribute must be escaped to prevent XSS'
        );
    }

    /**
     * Test that align attribute is properly escaped to prevent XSS attacks.
     */
    public function testAlignAttributeEscapedInEditControls(): void {
        $grid = new MySQLGrid();
        $grid->columns = [
            [
                'field' => 'test',
                'caption' => 'Test',
                'type' => PHPMYSQLGRID_TEXT,
                'align' => '" onload="alert(1)">',
            ],
        ];
        $grid->mode = PHPMYSQLGRID_ADDMODE;

        ob_start();
        $grid->drawEditControls(false);
        $output = ob_get_clean();

        // The dangerous quote should be encoded as &quot;
        $this->assertStringContainsString(
            'align="&quot; onload=&quot;alert(1)&quot;&gt;"',
            $output,
            'Align attribute must be escaped to prevent XSS'
        );
    }

    /**
     * Test that accept attribute in FILE field is properly escaped.
     */
    public function testAcceptAttributeEscapedInEditControls(): void {
        $grid = new MySQLGrid();
        $grid->columns = [
            [
                'field' => 'file_field',
                'caption' => 'File',
                'type' => PHPMYSQLGRID_FILE,
                'accept' => '" onload="alert(1)">',
            ],
        ];
        $grid->mode = PHPMYSQLGRID_ADDMODE;

        ob_start();
        $grid->drawEditControls(false);
        $output = ob_get_clean();

        // The dangerous quote should be encoded as &quot;
        $this->assertStringContainsString(
            'accept="&quot; onload=&quot;alert(1)&quot;&gt;"',
            $output,
            'Accept attribute must be escaped to prevent XSS'
        );
    }

    /**
     * Test that size attribute in form fields is cast to int to prevent injection.
     */
    public function testSizeAttributeCastedToIntegerInEditControls(): void {
        $grid = new MySQLGrid();
        $grid->columns = [
            [
                'field' => 'test',
                'caption' => 'Test',
                'type' => PHPMYSQLGRID_TEXT,
                'size' => '" onload="alert(1)">',  // Non-numeric, should cast to 0
            ],
        ];
        $grid->mode = PHPMYSQLGRID_ADDMODE;

        ob_start();
        $grid->drawEditControls(false);
        $output = ob_get_clean();

        // Non-numeric string should be cast to 0, preventing injection
        $this->assertStringContainsString(
            'size="0"',
            $output,
            'Size attribute must be cast to integer'
        );
        $this->assertStringNotContainsString(
            'onload=',
            $output,
            'Malicious onload attribute must not appear in output'
        );
    }

    /**
     * Test that maxlength attribute is cast to int to prevent injection.
     */
    public function testMaxlengthAttributeCastedToIntegerInEditControls(): void {
        $grid = new MySQLGrid();
        $grid->columns = [
            [
                'field' => 'test',
                'caption' => 'Test',
                'type' => PHPMYSQLGRID_PASSWORD,
                'maxlength' => '" onload="alert(1)">',  // Non-numeric, should cast to 0
            ],
        ];
        $grid->mode = PHPMYSQLGRID_ADDMODE;

        ob_start();
        $grid->drawEditControls(false);
        $output = ob_get_clean();

        // Non-numeric string should be cast to 0
        $this->assertStringContainsString(
            'maxlength="0"',
            $output,
            'Maxlength attribute must be cast to integer'
        );
        $this->assertStringNotContainsString(
            'onload=',
            $output,
            'Malicious onload attribute must not appear in output'
        );
    }

    /**
     * Test that width in style attribute is cast to int to prevent injection.
     */
    public function testWidthInStyleAttributeCastedToIntegerInEditControls(): void {
        $grid = new MySQLGrid();
        $grid->columns = [
            [
                'field' => 'test',
                'caption' => 'Test',
                'type' => PHPMYSQLGRID_MULTILINETEXT,
                'width' => '" onload="alert(1);>',  // Non-numeric, should cast to 0
            ],
        ];
        $grid->mode = PHPMYSQLGRID_ADDMODE;

        ob_start();
        $grid->drawEditControls(false);
        $output = ob_get_clean();

        // Width should be cast to int (0 for non-numeric string)
        $this->assertStringContainsString(
            'style="width:0px;"',
            $output,
            'Width in style must be cast to integer'
        );
        $this->assertStringNotContainsString(
            'onload=',
            $output,
            'Malicious onload attribute must not appear in output'
        );
    }

    /**
     * Test that height in style attribute is cast to int to prevent injection.
     */
    public function testHeightInStyleAttributeCastedToIntegerInEditControls(): void {
        $grid = new MySQLGrid();
        $grid->columns = [
            [
                'field' => 'test',
                'caption' => 'Test',
                'type' => PHPMYSQLGRID_MULTILINETEXT,
                'height' => '" onload="alert(1);>',  // Non-numeric, should cast to 0
            ],
        ];
        $grid->mode = PHPMYSQLGRID_ADDMODE;

        ob_start();
        $grid->drawEditControls(false);
        $output = ob_get_clean();

        // Height should be cast to int
        $this->assertStringContainsString(
            'style="height:0px;"',
            $output,
            'Height in style must be cast to integer'
        );
        $this->assertStringNotContainsString(
            'onload=',
            $output,
            'Malicious onload attribute must not appear in output'
        );
    }
}
