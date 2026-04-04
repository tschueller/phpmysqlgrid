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
    public static function gridStylesheetTag(array $query): string {
        $assetMode = self::resolveAssetMode($query);

        if ($assetMode === "published") {
            $publishedBasePath = getenv("PHPMYSQLGRID_DEMO_ASSET_BASE") ?: "/assets/phpmysqlgrid";
            return MySQLGridAssets::cssTag($publishedBasePath, "mysqlgrid.css");
        }

        $cacheToken = self::fileMTimeToken(__DIR__ . "/../assets/css/mysqlgrid.css");
        return '<link rel="stylesheet" href="/assets/css/mysqlgrid.css?v=' . self::escapeHtml($cacheToken) . '">';
    }

    public static function demoStylesheetTag(): string {
        $cacheToken = self::fileMTimeToken(__DIR__ . "/demo.css");
        return '<link rel="stylesheet" href="/demo/demo.css?v=' . self::escapeHtml($cacheToken) . '">';
    }

    public static function gridScriptTag(array $query, bool $defer = true): string {
        $assetMode = self::resolveAssetMode($query);

        if ($assetMode === "published") {
            $publishedBasePath = getenv("PHPMYSQLGRID_DEMO_ASSET_BASE") ?: "/assets/phpmysqlgrid";
            return MySQLGridAssets::jsTag($publishedBasePath, "mysqlgrid.js", null, $defer);
        }

        $scriptPath = __DIR__ . "/../assets/js/mysqlgrid.js";
        if (!is_file($scriptPath)) {
            return "";
        }

        $cacheToken = self::fileMTimeToken($scriptPath);
        $deferAttribute = $defer ? ' defer="defer"' : "";

        return '<script src="/assets/js/mysqlgrid.js?v=' . self::escapeHtml($cacheToken) . '"' . $deferAttribute . '></script>';
    }

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
