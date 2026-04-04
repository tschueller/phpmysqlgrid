<?php

declare(strict_types=1);

/**
 * Publishes package assets into a host project's web-accessible directory.
 */
final class MySQLGridAssetPublisher {
    public const MANIFEST_FILE = "phpmysqlgrid-assets.json";

    /** @var array<string, string> */
    private const ASSET_PATTERNS = array(
        "css" => "*.css",
        "js" => "*.js",
    );

    /**
     * @return array{target:string, files:array<int, array{name:string, path:string, hash:string}>}
     */
    public static function publish(string $packageRoot, string $targetPath): array {
        $assetsRoot = rtrim($packageRoot, "\\/") . DIRECTORY_SEPARATOR . "assets";
        if (!is_dir($assetsRoot)) {
            throw new RuntimeException("Assets directory not found: " . $assetsRoot);
        }

        $resolvedTargetPath = self::resolveFilesystemTargetPath($targetPath);
        if (!is_dir($resolvedTargetPath) && !mkdir($resolvedTargetPath, 0775, true) && !is_dir($resolvedTargetPath)) {
            throw new RuntimeException("Unable to create target directory: " . $resolvedTargetPath);
        }

        $publishedFiles = array();
        foreach (self::ASSET_PATTERNS as $directoryName => $pattern) {
            $sourceDirectory = $assetsRoot . DIRECTORY_SEPARATOR . $directoryName;
            if (!is_dir($sourceDirectory)) {
                continue;
            }

            $sourceFiles = glob($sourceDirectory . DIRECTORY_SEPARATOR . $pattern) ?: array();
            foreach ($sourceFiles as $sourceFilePath) {
                if (!is_file($sourceFilePath)) {
                    continue;
                }

                $fileName = basename($sourceFilePath);
                $destinationPath = $resolvedTargetPath . DIRECTORY_SEPARATOR . $fileName;
                if (!copy($sourceFilePath, $destinationPath)) {
                    throw new RuntimeException("Failed to copy asset file: " . $fileName);
                }

                $fileHash = sha1_file($destinationPath);
                $publishedFiles[] = array(
                    "name" => $fileName,
                    "path" => $destinationPath,
                    "hash" => is_string($fileHash) ? substr($fileHash, 0, 12) : "",
                );
            }
        }

        self::writeManifest($resolvedTargetPath, $publishedFiles);

        return array(
            "target" => $resolvedTargetPath,
            "files" => $publishedFiles,
        );
    }

    public static function resolveTargetPathFromInput(array $argv, ?string $environmentTarget = null, string $defaultTarget = "assets/phpmysqlgrid"): string {
        $argumentTarget = self::readTargetArgument($argv);
        if ($argumentTarget !== null && $argumentTarget !== "") {
            return $argumentTarget;
        }

        if (is_string($environmentTarget) && trim($environmentTarget) !== "") {
            return trim($environmentTarget);
        }

        return $defaultTarget;
    }

    private static function readTargetArgument(array $argv): ?string {
        $count = count($argv);
        for ($index = 1; $index < $count; $index++) {
            $value = (string) $argv[$index];
            if (str_starts_with($value, "--target=")) {
                return substr($value, 9);
            }

            if ($value === "--target" && isset($argv[$index + 1])) {
                return (string) $argv[$index + 1];
            }
        }

        return null;
    }

    private static function resolveFilesystemTargetPath(string $targetPath): string {
        $trimmedTargetPath = trim($targetPath);
        if ($trimmedTargetPath === "") {
            throw new InvalidArgumentException("Target path must not be empty.");
        }

        if (self::isAbsolutePath($trimmedTargetPath)) {
            return rtrim($trimmedTargetPath, "\\/");
        }

        $currentWorkingDirectory = getcwd();
        if (!is_string($currentWorkingDirectory) || $currentWorkingDirectory === "") {
            throw new RuntimeException("Unable to resolve current working directory.");
        }

        return rtrim($currentWorkingDirectory, "\\/") . DIRECTORY_SEPARATOR . str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $trimmedTargetPath);
    }

    private static function isAbsolutePath(string $path): bool {
        return (bool) preg_match('/^(?:[A-Za-z]:[\\\\\/]|\\\\\\\\|\/)/', $path);
    }

    /**
     * @param array<int, array{name:string, path:string, hash:string}> $publishedFiles
     */
    private static function writeManifest(string $targetPath, array $publishedFiles): void {
        $files = array();
        foreach ($publishedFiles as $publishedFile) {
            $files[$publishedFile["name"]] = array(
                "hash" => $publishedFile["hash"],
            );
        }

        $manifest = array(
            "generated_at" => gmdate("c"),
            "files" => $files,
        );

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($manifestJson) || $manifestJson === "") {
            throw new RuntimeException("Failed to encode asset manifest.");
        }

        $manifestPath = $targetPath . DIRECTORY_SEPARATOR . self::MANIFEST_FILE;
        if (file_put_contents($manifestPath, $manifestJson . PHP_EOL) === false) {
            throw new RuntimeException("Failed to write asset manifest.");
        }
    }
}
