<?php

declare(strict_types=1);

use PhpMySQLGrid\MySQLGridAssets;

/**
 * Resolves demo stylesheet and script tags for local development and published-asset simulation.
 *
 * Modes:
 * - repo: loads assets directly from this repository and uses filemtime-based cache tokens.
 * - published: loads assets from the configured public asset base and delegates cache busting
 *   to MySQLGridAssets (manifest/content hash/version fallback).
 */
final class DemoAsset {
    /**
     * Builds stylesheet link tags for the grid assets based on demo mode and selected theme.
     *
     * @param array<string, mixed> $query Request query parameters.
     */
    public static function gridStylesheetTag(array $query): string {
        $assetMode = self::resolveAssetMode($query);
        $themeFile = self::resolveThemeFile($query);
        $cssFiles = array("mysqlgrid-base.css", $themeFile);

        if ($assetMode === "published") {
            $publishedBasePath = getenv("PHPMYSQLGRID_DEMO_ASSET_BASE") ?: "/assets/phpmysqlgrid";
            MySQLGridAssets::configure($publishedBasePath);
            return MySQLGridAssets::cssTagsFor($cssFiles);
        }

        $baseToken = self::fileMTimeToken(__DIR__ . "/../assets/css/mysqlgrid-base.css");
        $themeToken = self::fileMTimeToken(__DIR__ . "/../assets/css/" . $themeFile);

        return '<link rel="stylesheet" href="/assets/css/mysqlgrid-base.css?v=' . self::escapeHtml($baseToken) . '">'
            . "\n"
            . '<link rel="stylesheet" href="/assets/css/' . self::escapeHtml($themeFile) . '?v=' . self::escapeHtml($themeToken) . '">';
    }

    public static function demoStylesheetTag(): string {
        $cacheToken = self::fileMTimeToken(__DIR__ . "/demo.css");
        return '<link rel="stylesheet" href="/demo/demo.css?v=' . self::escapeHtml($cacheToken) . '">';
    }

    /**
     * Builds the grid JavaScript tag for repo or published mode.
     *
     * @param array<string, mixed> $query Request query parameters.
     * @param bool $defer Whether the script should be loaded with defer.
     */
    public static function gridScriptTag(array $query, bool $defer = true): string {
        $assetMode = self::resolveAssetMode($query);

        if ($assetMode === "published") {
            $publishedBasePath = getenv("PHPMYSQLGRID_DEMO_ASSET_BASE") ?: "/assets/phpmysqlgrid";
            MySQLGridAssets::configure($publishedBasePath);
            return MySQLGridAssets::jsTagFor("mysqlgrid.js", null, $defer);
        }

        $scriptPath = __DIR__ . "/../assets/js/mysqlgrid.js";
        if (!is_file($scriptPath)) {
            return "";
        }

        $cacheToken = self::fileMTimeToken($scriptPath);
        $deferAttribute = $defer ? ' defer="defer"' : "";

        return '<script src="/assets/js/mysqlgrid.js?v=' . self::escapeHtml($cacheToken) . '"' . $deferAttribute . '></script>';
    }

    /**
     * Builds the demo page script tag.
     *
     * @param bool $defer Whether the script should be loaded with defer.
     */
    public static function demoScriptTag(bool $defer = true): string {
        $scriptPath = __DIR__ . "/demo.js";
        if (!is_file($scriptPath)) {
            return "";
        }

        $cacheToken = self::fileMTimeToken($scriptPath);
        $deferAttribute = $defer ? ' defer="defer"' : "";

        return '<script src="/demo/demo.js?v=' . self::escapeHtml($cacheToken) . '"' . $deferAttribute . '></script>';
    }

    private static function resolveAssetMode(array $query): string {
        if (isset($query["asset_mode"]) && is_string($query["asset_mode"])) {
            $requestedMode = strtolower(trim($query["asset_mode"]));
            if ($requestedMode === "published" || $requestedMode === "repo") {
                return $requestedMode;
            }
        }

        $environmentMode = getenv("PHPMYSQLGRID_DEMO_ASSET_MODE");
        if (is_string($environmentMode)) {
            $environmentMode = strtolower(trim($environmentMode));
            if ($environmentMode === "published" || $environmentMode === "repo") {
                return $environmentMode;
            }
        }

        return "repo";
    }

    private static function resolveThemeFile(array $query): string {
        $theme = "default";

        if (isset($query["theme"]) && is_string($query["theme"])) {
            $theme = strtolower(trim($query["theme"]));
        }

        if ($theme === "dark") {
            return "mysqlgrid-theme-dark.css";
        }

        if ($theme === "light") {
            return "mysqlgrid-theme-light.css";
        }

        return "mysqlgrid-theme-default.css";
    }

    private static function fileMTimeToken(string $path): string {
        if (!is_file($path)) {
            return "dev";
        }

        $modifiedAt = filemtime($path);
        if (!is_int($modifiedAt) || $modifiedAt <= 0) {
            return "dev";
        }

        return (string)$modifiedAt;
    }

    private static function escapeHtml(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }
}
