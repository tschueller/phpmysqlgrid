# Security Settings and Hardening

This document describes runtime security settings for phpMySQLGrid and recommended hardening defaults.

## File Upload Security

File uploads are security-sensitive. phpMySQLGrid provides built-in validation to reduce common risks.

### Configuration Options

```php
use PhpMySQLGrid\MySQLGrid;

$grid = new MySQLGrid();

// Default: false (URL import disabled)
$grid->allow_url_import = false;

// Optional: maximum upload size in bytes (example: 5 MB)
$grid->max_file_size = 5 * 1024 * 1024;

// Optional: extension allowlist
$grid->allowed_file_extensions = ["pdf", "doc", "docx", "xls", "xlsx"];
```

### Built-in Mitigations

1. URL import protection (SSRF mitigation)
   - URL imports are disabled by default (`allow_url_import = false`).
   - If enabled, only `http` and `https` URLs are accepted.
   - Private/reserved IP targets are blocked (including localhost and RFC1918 ranges).

2. File size limits
   - `max_file_size` enforces an upper upload bound.
   - Helps reduce disk-space exhaustion risk.

3. Extension allowlist
   - `allowed_file_extensions` restricts accepted file extensions.
   - Matching is case-insensitive.
   - Empty array (default) means no extension restriction.

## Recommended Defaults

1. Set `allowed_file_extensions` explicitly for every file column.
2. Set `max_file_size` to your operational maximum.
3. Keep `allow_url_import = false` unless there is a strict business need.
4. Add application-level validation via `convert_input` for domain-specific checks.

Example:

```php
$grid->columns[] = [
    "field" => "document",
    "type" => PHPMYSQLGRID_FILE,
    "convert_input" => function ($grid, $value, $index, $data) {
        if (is_array($value) && isset($value["size"]) && (int)$value["size"] > 0) {
            // Add custom checks here.
        }

        return $value;
    },
];
```

## Related Security Notes

- XSS protection: grid output escapes relevant HTML contexts.
- SQL injection prevention: database operations use prepared PDO statements.
- CSRF protection: implement CSRF tokens in your host application (not built-in).
