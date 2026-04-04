# Contributing

Thank you for contributing to phpMySQLGrid.

## Development Setup

Install dependencies:

    composer install

Run quality checks:

    composer run lint

Run unit tests:

    composer run test


## Pull Request Guidelines

1. Keep changes focused and backward compatible when possible.
2. Follow the existing project style and conventions.
3. Ensure all lint checks and unit tests pass before opening or updating a PR.
4. Update CHANGELOG.md for your changes.
5. Add or update tests when behavior changes.

## Commit Message Guidance

Use clear, imperative messages. Prefer a simple Conventional Commit style such as
`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, or `chore:`.

Examples:

- fix: handle empty file upload safely
- feat: add configurable page size
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
