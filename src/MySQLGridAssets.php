<?php

declare(strict_types=1);

/**
 * Utility helpers for publishing-aware asset URLs and HTML tags.
 */
final class MySQLGridAssets {
    /** @var array<string, string> */
    private static array $cacheTokens = array();

    /**
     * Builds a cache-busted public URL for the default stylesheet.
     */
    public static function cssUrl(
        string $publicBasePath = "/assets/phpmysqlgrid",
        string $fileName = "mysqlgrid.css",
        ?string $documentRoot = null
    ): string {
        return self::assetUrl($publicBasePath, $fileName, $documentRoot);
    }

    /**
     * Builds a cache-busted public URL for the default JavaScript asset.
     */
    public static function jsUrl(
        string $publicBasePath = "/assets/phpmysqlgrid",
        string $fileName = "mysqlgrid.js",
        ?string $documentRoot = null
    ): string {
        return self::assetUrl($publicBasePath, $fileName, $documentRoot);
    }

    /**
     * Builds a cache-busted public URL for an asset path relative to the publish base path.
     */
    public static function assetUrl(
        string $publicBasePath,
        string $fileName,
        ?string $documentRoot = null
    ): string {
        $normalizedBasePath = trim(str_replace("\\", "/", $publicBasePath), "/");
        $normalizedFileName = ltrim(str_replace("\\", "/", $fileName), "/");

        $publicPath = "/";
        if ($normalizedBasePath !== "") {
            $publicPath .= $normalizedBasePath . "/";
        }
        $publicPath .= $normalizedFileName;

        $cacheToken = self::resolveCacheToken($publicPath, $documentRoot);
        if ($cacheToken === "") {
            return $publicPath;
        }

        return $publicPath . "?v=" . rawurlencode($cacheToken);
    }

    /**
     * Returns a ready-to-render stylesheet link tag with cache-busted URL.
     */
    public static function cssTag(
        string $publicBasePath = "/assets/phpmysqlgrid",
        string $fileName = "mysqlgrid.css",
        ?string $documentRoot = null
    ): string {
        return self::stylesheetTag(self::cssUrl($publicBasePath, $fileName, $documentRoot));
    }

    /**
     * Returns a ready-to-render script tag with cache-busted URL.
     */
    public static function jsTag(
        string $publicBasePath = "/assets/phpmysqlgrid",
        string $fileName = "mysqlgrid.js",
        ?string $documentRoot = null,
        bool $defer = true
    ): string {
        return self::scriptTag(self::jsUrl($publicBasePath, $fileName, $documentRoot), $defer);
    }

    private static function resolveCacheToken(string $publicPath, ?string $documentRoot): string {
        $cacheKey = $publicPath . "|" . (string)$documentRoot;
        if (isset(self::$cacheTokens[$cacheKey])) {
            return self::$cacheTokens[$cacheKey];
        }

        $resolvedDocumentRoot = self::resolveDocumentRoot($documentRoot);
        if ($resolvedDocumentRoot !== null) {
            $manifestHash = self::resolveManifestHash($publicPath, $resolvedDocumentRoot);
            if ($manifestHash !== "") {
                self::$cacheTokens[$cacheKey] = $manifestHash;
                return $manifestHash;
            }

            $filesystemPath = self::resolveFilesystemPath($publicPath, $resolvedDocumentRoot);
            if (is_file($filesystemPath)) {
                $fileHash = sha1_file($filesystemPath);
                if (is_string($fileHash) && $fileHash !== "") {
                    $resolvedHash = substr($fileHash, 0, 12);
                    self::$cacheTokens[$cacheKey] = $resolvedHash;
                    return $resolvedHash;
                }
            }
        }

        if (class_exists("Composer\\InstalledVersions") && \Composer\InstalledVersions::isInstalled("tschueller/phpmysqlgrid")) {
            $version = \Composer\InstalledVersions::getPrettyVersion("tschueller/phpmysqlgrid");
            if (is_string($version) && $version !== "") {
                $resolvedVersion = preg_replace('/[^A-Za-z0-9._-]/', "", $version) ?? "";
                self::$cacheTokens[$cacheKey] = $resolvedVersion;
                return $resolvedVersion;
            }
        }

        return "";
    }

    private static function resolveManifestHash(string $publicPath, string $documentRoot): string {
        $filesystemPath = self::resolveFilesystemPath($publicPath, $documentRoot);
        $manifestPath = dirname($filesystemPath) . DIRECTORY_SEPARATOR . \MySQLGridAssetPublisher::MANIFEST_FILE;
        if (!is_file($manifestPath)) {
            return "";
        }

        $manifestContent = file_get_contents($manifestPath);
        if (!is_string($manifestContent) || $manifestContent === "") {
            return "";
        }

        $manifestData = json_decode($manifestContent, true);
        if (!is_array($manifestData) || !isset($manifestData["files"]) || !is_array($manifestData["files"])) {
            return "";
        }

        $fileName = basename($filesystemPath);
        if (!isset($manifestData["files"][$fileName]) || !is_array($manifestData["files"][$fileName])) {
            return "";
        }

        $fileData = $manifestData["files"][$fileName];
        if (!isset($fileData["hash"]) || !is_string($fileData["hash"]) || $fileData["hash"] === "") {
            return "";
        }

        return $fileData["hash"];
    }

    private static function resolveFilesystemPath(string $publicPath, string $documentRoot): string {
        return $documentRoot . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, ltrim($publicPath, "/"));
    }

    private static function resolveDocumentRoot(?string $documentRoot): ?string {
        if (is_string($documentRoot) && $documentRoot !== "") {
            return rtrim($documentRoot, "\\/");
        }

        if (isset($_SERVER["DOCUMENT_ROOT"]) && is_string($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"] !== "") {
            return rtrim($_SERVER["DOCUMENT_ROOT"], "\\/");
        }

        return null;
    }

    private static function escapeHtml(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
    }

    private static function stylesheetTag(string $href): string {
        return '<link rel="stylesheet" href="' . self::escapeHtml($href) . '">';
    }

    private static function scriptTag(string $src, bool $defer): string {
        $deferAttribute = $defer ? ' defer="defer"' : "";

        return '<script src="' . self::escapeHtml($src) . '"' . $deferAttribute . '></script>';
    }
}
