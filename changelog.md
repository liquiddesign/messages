# ✉ LiquidDesign/Messages - CHANGELOG

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2023-07-13

### Changed

- Improved SQL migration of `Template`

## [2.0.0-beta.1] - 2023-06-02

Note to versioning: version 1 is skipped to match version 2 with other packages.

### Added
 
- `Template` now has `Shop` to use templates based on shops.
  - **BREAKING:** `TemplateRepository::createMessage` now filters id of message by currently selected `Shop`

### Changed

- **BREAKING:** PHP version 8.2 or higher is required
- **BREAKING:** Dropped support for Latte <3.0
- **BREAKING:** Package versions updated
- Updated codestyle
- Updated PHPStan to level 5

### Removed

### Deprecated

### Fixed