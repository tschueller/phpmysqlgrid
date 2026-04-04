<?php

declare(strict_types=1);

namespace MySQLGridTests;

use PHPUnit\Framework\TestCase;

final class MySQLGridAssetsTest extends TestCase {
    public function testCssUrlUsesManifestHashWhenAvailable(): void {
        $workspaceRoot = getcwd();
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "phpmysqlgrid_manifest_" . uniqid("", true);
        $packageRoot = $tempRoot . DIRECTORY_SEPARATOR . "package";
        $hostRoot = $tempRoot . DIRECTORY_SEPARATOR . "host";
        $sourceDirectory = $packageRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "css";

        mkdir($sourceDirectory, 0775, true);
        mkdir($hostRoot, 0775, true);
        file_put_contents($sourceDirectory . DIRECTORY_SEPARATOR . "mysqlgrid.css", "body { color: #111; }");

        chdir($hostRoot);
        try {
            $result = \MySQLGridAssetPublisher::publish($packageRoot, "assets/phpmysqlgrid");
            $url = \MySQLGridAssets::cssUrl("/assets/phpmysqlgrid", "mysqlgrid.css", $hostRoot);

            $this->assertSame(
                "/assets/phpmysqlgrid/mysqlgrid.css?v=" . $result["files"][0]["hash"],
                $url
            );
        } finally {
            if (is_string($workspaceRoot) && $workspaceRoot !== "") {
                chdir($workspaceRoot);
            }
            $this->deleteDirectory($tempRoot);
        }
    }

    public function testJsUrlUsesManifestHashWhenAvailable(): void {
        $workspaceRoot = getcwd();
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "phpmysqlgrid_js_manifest_" . uniqid("", true);
        $packageRoot = $tempRoot . DIRECTORY_SEPARATOR . "package";
        $hostRoot = $tempRoot . DIRECTORY_SEPARATOR . "host";
        $cssSourceDirectory = $packageRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "css";
        $jsSourceDirectory = $packageRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "js";

        mkdir($cssSourceDirectory, 0775, true);
        mkdir($jsSourceDirectory, 0775, true);
        mkdir($hostRoot, 0775, true);
        file_put_contents($cssSourceDirectory . DIRECTORY_SEPARATOR . "mysqlgrid.css", "body { color: #111; }");
        file_put_contents($jsSourceDirectory . DIRECTORY_SEPARATOR . "mysqlgrid.js", "console.log('grid');");

        chdir($hostRoot);
        try {
            $result = \MySQLGridAssetPublisher::publish($packageRoot, "assets/phpmysqlgrid");
            $jsHash = $this->findPublishedHash($result["files"], "mysqlgrid.js");
            $url = \MySQLGridAssets::jsUrl("/assets/phpmysqlgrid", "mysqlgrid.js", $hostRoot);

            $this->assertSame(
                "/assets/phpmysqlgrid/mysqlgrid.js?v=" . $jsHash,
                $url
            );
        } finally {
            if (is_string($workspaceRoot) && $workspaceRoot !== "") {
                chdir($workspaceRoot);
            }
            $this->deleteDirectory($tempRoot);
        }
    }

    public function testCssUrlUsesContentHashWhenAssetExists(): void {
        $documentRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "phpmysqlgrid_assets_" . uniqid("", true);
        $assetDirectory = $documentRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "phpmysqlgrid";
        mkdir($assetDirectory, 0775, true);
        file_put_contents($assetDirectory . DIRECTORY_SEPARATOR . "mysqlgrid.css", "body { color: #111; }");

        $url = \MySQLGridAssets::cssUrl("/assets/phpmysqlgrid", "mysqlgrid.css", $documentRoot);

        $this->assertMatchesRegularExpression('/^\/assets\/phpmysqlgrid\/mysqlgrid\.css\?v=[A-Za-z0-9._-]{12}$/', $url);
    }

    public function testCssTagCreatesStylesheetLink(): void {
        $tag = \MySQLGridAssets::cssTag("/assets/phpmysqlgrid", "mysqlgrid.css", "C:/does-not-exist");

        $this->assertStringStartsWith('<link rel="stylesheet" href="/assets/phpmysqlgrid/mysqlgrid.css', $tag);
        $this->assertStringEndsWith('">', $tag);
    }

    public function testJsTagCreatesScriptElement(): void {
        $tag = \MySQLGridAssets::jsTag("/assets/phpmysqlgrid", "mysqlgrid.js", "C:/does-not-exist");

        $this->assertStringStartsWith('<script src="/assets/phpmysqlgrid/mysqlgrid.js', $tag);
        $this->assertStringContainsString(' defer="defer"', $tag);
        $this->assertStringEndsWith('></script>', $tag);
    }

    public function testPublisherCopiesCssIntoRelativeTargetDirectory(): void {
        $workspaceRoot = getcwd();
        $tempRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "phpmysqlgrid_publish_" . uniqid("", true);
        $packageRoot = $tempRoot . DIRECTORY_SEPARATOR . "package";
        $hostRoot = $tempRoot . DIRECTORY_SEPARATOR . "host";
        $cssSourceDirectory = $packageRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "css";
        $jsSourceDirectory = $packageRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "js";

        mkdir($cssSourceDirectory, 0775, true);
        mkdir($jsSourceDirectory, 0775, true);
        mkdir($hostRoot, 0775, true);
        file_put_contents($cssSourceDirectory . DIRECTORY_SEPARATOR . "mysqlgrid.css", "table { width: 100%; }");
        file_put_contents($jsSourceDirectory . DIRECTORY_SEPARATOR . "mysqlgrid.js", "console.log('grid');");

        chdir($hostRoot);
        try {
            $result = \MySQLGridAssetPublisher::publish($packageRoot, "assets/phpmysqlgrid");

            $publishedFilePath = $hostRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "phpmysqlgrid" . DIRECTORY_SEPARATOR . "mysqlgrid.css";
            $publishedJsFilePath = $hostRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "phpmysqlgrid" . DIRECTORY_SEPARATOR . "mysqlgrid.js";
            $manifestPath = $hostRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "phpmysqlgrid" . DIRECTORY_SEPARATOR . \MySQLGridAssetPublisher::MANIFEST_FILE;

            $this->assertSame($hostRoot . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "phpmysqlgrid", $result["target"]);
            $this->assertFileExists($publishedFilePath);
            $this->assertFileExists($publishedJsFilePath);
            $this->assertFileExists($manifestPath);
            $this->assertCount(2, $result["files"]);
            $this->assertSame(substr(sha1_file($publishedFilePath) ?: "", 0, 12), $this->findPublishedHash($result["files"], "mysqlgrid.css"));
            $this->assertSame(substr(sha1_file($publishedJsFilePath) ?: "", 0, 12), $this->findPublishedHash($result["files"], "mysqlgrid.js"));

            $manifestData = json_decode((string)file_get_contents($manifestPath), true);
            $this->assertIsArray($manifestData);
            $this->assertSame($this->findPublishedHash($result["files"], "mysqlgrid.css"), $manifestData["files"]["mysqlgrid.css"]["hash"] ?? null);
            $this->assertSame($this->findPublishedHash($result["files"], "mysqlgrid.js"), $manifestData["files"]["mysqlgrid.js"]["hash"] ?? null);
        } finally {
            if (is_string($workspaceRoot) && $workspaceRoot !== "") {
                chdir($workspaceRoot);
            }
            $this->deleteDirectory($tempRoot);
        }
    }

    /**
     * @param array<int, array{name:string, path:string, hash:string}> $publishedFiles
     */
    private function findPublishedHash(array $publishedFiles, string $fileName): string {
        foreach ($publishedFiles as $publishedFile) {
            if ($publishedFile["name"] === $fileName) {
                return $publishedFile["hash"];
            }
        }

        $this->fail("Published file not found: " . $fileName);
    }

    private function deleteDirectory(string $path): void {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === "." || $item === "..") {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
            } else {
                @unlink($itemPath);
            }
        }

        @rmdir($path);
    }
}
