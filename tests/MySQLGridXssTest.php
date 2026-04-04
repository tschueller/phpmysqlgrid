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
}
