# Releasing

This project follows a simple and automated release workflow based on Git tags and GitHub Actions.

## Release Process

### 1. Make changes

Implement your changes using feature branches and merge them into `main` via pull requests.

### 2. Update the changelog

All user-facing changes must be documented in `CHANGELOG.md` under the `[Unreleased]` section:

```
## [Unreleased]

### Added
- New feature

### Fixed
- Bug fix
```

### 3. Prepare the release

Before creating a release, move all entries from `[Unreleased]` into a new version section:

```
## [Unreleased]

## [1.2.0] - 2026-04-06

### Added
- New feature

### Fixed
- Bug fix
```

Make sure:

* The version matches the upcoming release tag
* The date is in `YYYY-MM-DD` format
* The `[Unreleased]` section is empty afterward

### 4. Create a Git tag

Commit the changelog changes

```
git add CHANGELOG.md
git commit -m "Prepare release v1.2.0"
```

and create and push a version tag:

```
git tag v1.2.0
git push origin v1.2.0
```

### 5. Automated release

Pushing the tag will trigger the GitHub Actions release workflow, which will:

* run linting and tests
* validate the changelog
* extract the correct changelog section
* create a GitHub release with release notes

## Validation

The release workflow will fail if:

* no changelog entry exists for the version
* the `[Unreleased]` section is not empty
* the version entry is missing a valid date

## Versioning

This project follows Semantic Versioning:

* `PATCH` → bug fixes
* `MINOR` → new features (backwards compatible)
* `MAJOR` → breaking changes
