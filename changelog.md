# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## 2.1.2 - 2021-01-03

### Fixed
- New render of libraries.

## 2.1.1 - 2021-01-02

### Changed
- Updated libraries and links to documentation.

## 2.1.0 - 2020-11-29

⚠️ Several changes in this release may break Firefly III's duplication detection or are backwards incompatible.

### Added

- [Issue 3510](https://github.com/firefly-iii/firefly-iii/issues/3510) You can pre-upload configuration files in `storage/configurations`.

### Changed

- ⚠️ All environment variables that used to be called "URI" are now called "URL" because I finally learned the difference between a URL and a URI.
- [Issue 3796](https://github.com/firefly-iii/firefly-iii/issues/3796) You can now set a custom Spectre identifier.
- [Issue 3910](https://github.com/firefly-iii/firefly-iii/issues/3910) The importer will consider some meta fields for the description.
 
### Fixed

- [Issue 4033](https://github.com/firefly-iii/firefly-iii/issues/4033) Better error handling when running into DNS timeouts.

## 2.0.0 - 2020-10-21

### Changed
- Requires Firefly III 5.4.3
- Commands now start with `importer` instead of `spectre`.
- Docker image has an update.

## [1.0.5] - 2020-08-14

### Added
- Reset button.

### Changed
- URL's with a slash at the end will have the slash removed for consistency.

## [1.0.4] - 2020-07-17

⚠️ Some changes in this release may break the duplicate transaction detection. Be careful importing large batches.

### Changed

- [Issue 3543](https://github.com/firefly-iii/firefly-iii/issues/3543) [issue 3544](https://github.com/firefly-iii/firefly-iii/issues/3544) [issue 3548](https://github.com/firefly-iii/firefly-iii/issues/3548) Unexpected NULL pointer
- [Issue 3549](https://github.com/firefly-iii/firefly-iii/issues/3549) Date range may be ignored when importing.
- [Issue 3592](https://github.com/firefly-iii/firefly-iii/issues/3492) Issue with integer casting
- ⚠️ [issue 3560](https://github.com/firefly-iii/firefly-iii/issues/3560) Accounts were ignored when mapping.
- ⚠️ Added option to ignore Spectre's categorization.
- Auto refresh Spectre connections.

## [1.0.3] - 2020-07-12

### Changed
- [Issue 3511](https://github.com/firefly-iii/firefly-iii/issues/3511) Can now use a vanity URL. See the example environment variables file, `.env.example` for instructions.

## [1.0.2] - 2020-07-10

### Added
- Docker image accepts the timezone with the `TZ` environment variable.

### Changed
- [Issue 3535](https://github.com/firefly-iii/firefly-iii/issues/3535) Extra debug code.

### Fixed
- [Issue 3534](https://github.com/firefly-iii/firefly-iii/issues/3534) Actually add import tag.
- [Issue 3535](https://github.com/firefly-iii/firefly-iii/issues/3535) When Spectre delivers negative amounts, decimal values get lost.
- [#4](https://github.com/firefly-iii/spectre-importer/pull/4) Correct links in readme.

## [1.0.1] - 2020-06-28

### Changed
- Switched to `main` branch.

## [1.0.0] - 2020-06-27

This release was preceded by several alpha and beta versions:

- 1.0.0-alpha.1 on 2020-06-01
- 1.0.0-alpha.2 on 2020-06-01
- 1.0.0-alpha.3 on 2020-06-01
- 1.0.0-alpha.4 on 2020-06-10

### Added
- Initial release.

### Changed
- Initial release.

### Deprecated
- Initial release.

### Removed
- Initial release.

### Fixed
- Initial release.

### Security
- Initial release.
