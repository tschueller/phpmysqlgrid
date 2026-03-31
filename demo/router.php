<?php

declare(strict_types=1);

$requestPath = parse_url((string)($_SERVER["REQUEST_URI"] ?? "/"), PHP_URL_PATH);
$path = is_string($requestPath) ? $requestPath : "/";
$projectRoot = dirname(__DIR__);
$requestedFile = $projectRoot . $path;

if ($path === "/demo/demo.sqlite") {
    http_response_code(403);
    echo "Forbidden";
    return true;
}

if ($path !== "/" && is_file($requestedFile)) {
    return false;
}

if ($path === "/" || $path === "/index.php" || $path === "/demo" || $path === "/demo/" || $path === "/demo/index.php") {
    require __DIR__ . "/index.php";
    return true;
}

if (
    $path === "/index2.php" ||
    $path === "/demo/index2.php"
) {
    require __DIR__ . "/index2.php";
    return true;
}

http_response_code(404);
echo "Not Found";
return true;
