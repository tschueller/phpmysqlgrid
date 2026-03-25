# Contributing

Thank you for contributing to phpMySQLGrid.

## Development Setup

1. Install dependencies:

   composer install

2. Run quality checks:

   composer run lint

## Pull Request Guidelines

1. Keep changes focused and backward compatible when possible.
2. Follow the existing project style and conventions.
3. Ensure all lint checks pass before opening or updating a PR.
4. Update CHANGELOG.md for user-visible changes.
5. Add or update tests when behavior changes.

## Commit Message Guidance

Use clear, imperative messages. Prefer a simple Conventional Commit style such as
`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, or `chore:`.

Examples:

- fix: handle empty file upload safely
- feat: add configurable page size
- feat(grid): add optional column placeholder support
- feat(lookup): add lookup filter configuration
- docs: add lookup field example
- refactor: simplify request handling flow
- test: add coverage for boolean field rendering
- chore: update phpstan level notes

## Reporting Issues

Please use the issue templates and include:

- Steps to reproduce
- Expected behavior
- Actual behavior
- PHP version and environment details
