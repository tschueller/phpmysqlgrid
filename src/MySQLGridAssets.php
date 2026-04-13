<?php

declare(strict_types=1);

namespace PhpMySQLGrid;

use PhpMySQLGrid\MySQLGridAssetPublisher;

/**
 * Utility helpers for publishing-aware asset URLs and HTML tags.
 */
final class MySQLGridAssets {
    /** @var array<string, string> */
    private static array $cacheTokens = array();

    private static string $defaultPublicBasePath = "/assets/phpmysqlgrid";

    private static ?string $defaultDocumentRoot = null;

    /** @var array<string, array<int, string>> */
    private static array $builtInThemes = array(
        "default" => array("mysqlgrid-base.css", "mysqlgrid-theme-default.css"),
        "dark" => array("mysqlgrid-base.css", "mysqlgrid-theme-dark.css"),
    );

    /**
     * Sets default runtime options used by the simplified helper methods.
     *
     * @param string $publicBasePath Public base path of published assets.
     * @param string|null $documentRoot Optional document root override.
     */
    public static function configure(string $publicBasePath = "/assets/phpmysqlgrid", ?string $documentRoot = null): void {
        self::setDefaultPublicBasePath($publicBasePath);
        self::setDefaultDocumentRoot($documentRoot);
    }

    /**
     * Sets the default public base path for simplified helper methods.
     *
     * @param string $publicBasePath Public base path of published assets.
     */
    public static function setDefaultPublicBasePath(string $publicBasePath): void {
        self::$defaultPublicBasePath = $publicBasePath;
    }

    /**
     * Sets the default document root for simplified helper methods.
     *
     * @param string|null $documentRoot Optional document root override.
     */
    public static function setDefaultDocumentRoot(?string $documentRoot): void {
        self::$defaultDocumentRoot = $documentRoot;
    }

    /**
     * Resets helper configuration and runtime caches to defaults.
     */
    public static function resetConfiguration(): void {
        self::$defaultPublicBasePath = "/assets/phpmysqlgrid";
        self::$defaultDocumentRoot = null;
        self::$cacheTokens = array();
    }

    /**
     * Builds a cache-busted stylesheet URL using configured defaults.
     *
     * @param string $fileName Stylesheet file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root override.
     * @param string|null $publicBasePath Optional public base path override.
     */
    public static function cssUrlFor(
        string $fileName = "mysqlgrid.css",
        ?string $documentRoot = null,
        ?string $publicBasePath = null
    ): string {
        return self::assetUrl(
            self::resolvePublicBasePath($publicBasePath),
            $fileName,
            self::resolveConfiguredDocumentRoot($documentRoot)
        );
    }

    /**
     * Builds a cache-busted JavaScript URL using configured defaults.
     *
     * @param string $fileName Script file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root override.
     * @param string|null $publicBasePath Optional public base path override.
     */
    public static function jsUrlFor(
        string $fileName = "mysqlgrid.js",
        ?string $documentRoot = null,
        ?string $publicBasePath = null
    ): string {
        return self::assetUrl(
            self::resolvePublicBasePath($publicBasePath),
            $fileName,
            self::resolveConfiguredDocumentRoot($documentRoot)
        );
    }

    /**
     * Returns a stylesheet link tag using configured defaults.
     *
     * @param string $fileName Stylesheet file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root override.
     * @param string|null $publicBasePath Optional public base path override.
     */
    public static function cssTagFor(
        string $fileName = "mysqlgrid.css",
        ?string $documentRoot = null,
        ?string $publicBasePath = null
    ): string {
        return self::stylesheetTag(self::cssUrlFor($fileName, $documentRoot, $publicBasePath));
    }

    /**
     * Returns stylesheet URLs for built-in themes or an explicit file list.
     *
     * @param string|array<int, string>|null $themeOrFileNames Theme name (default/dark), a theme file name, or an explicit file list.
     * @param string|null $documentRoot Optional document root override.
     * @param string|null $publicBasePath Optional public base path override.
     * @return array<int, string>
     */
    public static function cssUrlsFor(
        string|array|null $themeOrFileNames = null,
        ?string $documentRoot = null,
        ?string $publicBasePath = null
    ): array {
        $resolvedFileNames = self::resolveCssFileNames($themeOrFileNames);
        $urls = array();

        foreach ($resolvedFileNames as $fileName) {
            if (!is_string($fileName) || $fileName === "") {
                continue;
            }
            $urls[] = self::cssUrlFor($fileName, $documentRoot, $publicBasePath);
        }

        return $urls;
    }

    /**
     * Returns stylesheet link tags for built-in themes or an explicit file list.
     *
     * @param string|array<int, string>|null $themeOrFileNames Theme name (default/dark), a theme file name, or an explicit file list.
     * @param string|null $documentRoot Optional document root override.
     * @param string|null $publicBasePath Optional public base path override.
     */
    public static function cssTagsFor(
        string|array|null $themeOrFileNames = null,
        ?string $documentRoot = null,
        ?string $publicBasePath = null
    ): string {
        $tags = array();
        foreach (self::cssUrlsFor($themeOrFileNames, $documentRoot, $publicBasePath) as $href) {
            $tags[] = self::stylesheetTag($href);
        }

        return implode("\n", $tags);
    }

    /**
     * Returns a script tag using configured defaults.
     *
     * @param string $fileName Script file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root override.
     * @param bool $defer Whether to include the defer attribute.
     * @param string|null $publicBasePath Optional public base path override.
     */
    public static function jsTagFor(
        string $fileName = "mysqlgrid.js",
        ?string $documentRoot = null,
        bool $defer = true,
        ?string $publicBasePath = null
    ): string {
        return self::scriptTag(self::jsUrlFor($fileName, $documentRoot, $publicBasePath), $defer);
    }

    /**
     * Builds a cache-busted public URL for the default stylesheet.
     *
     * @param string $publicBasePath Public base path of published assets.
     * @param string $fileName Stylesheet file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root for filesystem resolution.
     * @deprecated Use cssUrlFor() with configure()/setDefaultPublicBasePath() instead.
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
     *
     * @param string $publicBasePath Public base path of published assets.
     * @param string $fileName Script file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root for filesystem resolution.
     * @deprecated Use jsUrlFor() with configure()/setDefaultPublicBasePath() instead.
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
     *
     * @param string $publicBasePath Public base path of published assets.
     * @param string $fileName Asset file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root for filesystem resolution.
     * @deprecated Use cssUrlFor()/jsUrlFor() for simplified configuration-based usage.
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
     *
     * @param string $publicBasePath Public base path of published assets.
     * @param string $fileName Stylesheet file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root for filesystem resolution.
     * @deprecated Use cssTagFor() with configure()/setDefaultPublicBasePath() instead.
     */
    public static function cssTag(
        string $publicBasePath = "/assets/phpmysqlgrid",
        string $fileName = "mysqlgrid.css",
        ?string $documentRoot = null
    ): string {
        return self::stylesheetTag(self::cssUrl($publicBasePath, $fileName, $documentRoot));
    }

    /**
     * Returns the default split CSS file names (base + default theme).
     *
     * @return array<int, string>
     */
    public static function defaultCssFiles(): array {
        return array("mysqlgrid-base.css", "mysqlgrid-theme-default.css");
    }

    /**
     * Returns cache-busted URLs for multiple stylesheet files.
     *
     * @param string $publicBasePath Public base path of published assets.
     * @param string|null $documentRoot Optional document root for filesystem resolution.
     * @param string|array<int, string>|null $fileNames
     * @return array<int, string>
     * @deprecated Use cssUrlsFor() with configure()/setDefaultPublicBasePath() instead.
     */
    public static function cssUrls(
        string $publicBasePath = "/assets/phpmysqlgrid",
        ?string $documentRoot = null,
        string|array|null $fileNames = null
    ): array {
        return self::cssUrlsFor($fileNames, $documentRoot, $publicBasePath);
    }

    /**
     * Returns ready-to-render stylesheet tags for multiple CSS files.
     *
     * @param string $publicBasePath Public base path of published assets.
     * @param string|null $documentRoot Optional document root for filesystem resolution.
     * @param string|array<int, string>|null $fileNames
     * @deprecated Use cssTagsFor() with configure()/setDefaultPublicBasePath() instead.
     */
    public static function cssTags(
        string $publicBasePath = "/assets/phpmysqlgrid",
        ?string $documentRoot = null,
        string|array|null $fileNames = null
    ): string {
        return self::cssTagsFor($fileNames, $documentRoot, $publicBasePath);
    }

    /**
     * Returns a ready-to-render script tag with cache-busted URL.
     *
     * @param string $publicBasePath Public base path of published assets.
     * @param string $fileName Script file name relative to the publish base path.
     * @param string|null $documentRoot Optional document root for filesystem resolution.
     * @param bool $defer Whether to include the defer attribute.
     * @deprecated Use jsTagFor() with configure()/setDefaultPublicBasePath() instead.
     */
    public static function jsTag(
        string $publicBasePath = "/assets/phpmysqlgrid",
        string $fileName = "mysqlgrid.js",
        ?string $documentRoot = null,
        bool $defer = true
    ): string {
        return self::jsTagFor($fileName, $documentRoot, $defer, $publicBasePath);
    }

    /**
     * Returns the built-in CSS file list for a named theme.
     *
     * @param string $themeName Theme name (for example: default, dark).
     * @return array<int, string>
     */
    public static function themeCssFiles(string $themeName): array {
        $normalizedTheme = strtolower(trim($themeName));
        if ($normalizedTheme === "") {
            return self::defaultCssFiles();
        }

        if (isset(self::$builtInThemes[$normalizedTheme])) {
            return self::$builtInThemes[$normalizedTheme];
        }

        if (str_ends_with($normalizedTheme, ".css")) {
            return array("mysqlgrid-base.css", $normalizedTheme);
        }

        return array("mysqlgrid-base.css", "mysqlgrid-theme-" . $normalizedTheme . ".css");
    }

    /**
     * @param string|array<int, string>|null $themeOrFileNames
     * @return array<int, string>
     */
    private static function resolveCssFileNames(string|array|null $themeOrFileNames): array {
        if (is_array($themeOrFileNames)) {
            return $themeOrFileNames;
        }

        if (is_string($themeOrFileNames) && $themeOrFileNames !== "") {
            return self::themeCssFiles($themeOrFileNames);
        }

        return self::defaultCssFiles();
    }

    private static function resolvePublicBasePath(?string $publicBasePath): string {
        if (is_string($publicBasePath) && $publicBasePath !== "") {
            return $publicBasePath;
        }

        return self::$defaultPublicBasePath;
    }

    private static function resolveConfiguredDocumentRoot(?string $documentRoot): ?string {
        if ($documentRoot !== null) {
            return $documentRoot;
        }

        return self::$defaultDocumentRoot;
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
        $manifestPath = dirname($filesystemPath) . DIRECTORY_SEPARATOR . MySQLGridAssetPublisher::MANIFEST_FILE;
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
