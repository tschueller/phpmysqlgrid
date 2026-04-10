# Contributing

Thank you for your interest in contributing! 🎉

This document describes the development workflow and guidelines for contributing to this project.

---

## Development Workflow

### 1. Create a branch

All changes should be made in a separate branch based on `main`.

Branch naming convention:

* `feature/...` for new features
* `bugfix/...` for bug fixes
* `chore/...` for maintenance tasks

Example:

```
git checkout -b feature/add-pagination
```

---

### 2. Make your changes

* Keep changes focused, minimal and backward compatible when possible
* Follow the existing code style and conventions
* Add or update tests if necessary

---

### 3. Run checks locally

Before submitting your changes, make sure everything passes:

```
composer install
composer run lint
composer run test
```

---

### 4. Update the changelog

All user-facing changes must be added to `CHANGELOG.md` under the `[Unreleased]` section.

Example:

```
## [Unreleased]

### Added
- Pagination support
```

---

### 5. Commit Message Guidance

Use clear, imperative messages. Prefer a simple Conventional Commit style such as
`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, or `chore:`.

Examples:

- fix: handle empty file upload safely
- feat: add configurable page size
- docs: add lookup field example
- refactor: simplify request handling flow
- test: add coverage for boolean field rendering
- chore: update phpstan level notes

---

### 6. Open a Pull Request

Open a pull request against the `main` branch.

Please ensure:

* The CI pipeline passes
* The changelog is updated
* The PR has a clear description

---

### 7. Merge

Pull requests are typically merged using **Squash & Merge** to keep the history clean.

---

## Coding Standards

This project uses:

* PHP CS Fixer for code style
* PHPStan for static analysis
* PHPUnit for testing

You can run all checks with:

```
composer run lint
composer run test
```

---

## Versioning & Releases

Releases are handled via Git tags and GitHub Actions.

Please do not create tags manually unless you are the maintainer.

For details, see [releasing.md](docs/releasing.md).

---

## Reporting Issues

Please use the issue templates and include:

- Steps to reproduce
- Expected behavior
- Actual behavior
- PHP version and environment details

---

## Questions

If you have any questions, feel free to open an issue or discussion.
