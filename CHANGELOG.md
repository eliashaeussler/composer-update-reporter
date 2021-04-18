# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Support for PHP 8.0
- Registry to register and unregister single services
- PHPStan for static code analysis
- Normalization of `composer.json`
- Introduce `AbstractService`
- Add method `getIdentifier()` to `ServiceInterface`
- Include root package name in service reports
- Project documentation

### Changed

- Replace Guzzle by more lightweight libraries
- Require versions ^1.0.0 of `eliashaeussler/composer-update-check`
- Use Symfony rules in PHP-CS-Fixer
- Decrease priority of post update check event listener

### Fixed

- Ensure enable state of services is correctly read
- Set correct `Content-Type` header for requests sent to remote services

## [0.8.0] - 2021-02-12

### Added

- Support for Microsoft Teams

## [0.7.0] - 2021-01-18

### Changed

- Support all versions of `eliashaeussler/composer-update-check` between 0.4.0 < 1.0.0
- Make code PSR-2 compliant

### Fixed

- Ensure simulated application is properly cleaned up

## [0.6.2] - 2020-11-23

### Fixed

- Implement missing methods to assure Composer 2.0 compatibility

## [0.6.1] - 2020-11-20

### Added

- Support for `eliashaeussler/composer-update-check` 0.6.x

## [0.6.0] - 2020-11-16

### Added

- Support for Composer 2.0

## [0.5.0] - 2020-10-26

### Added

- Support for Slack

### Changed

- Require at least version 0.4.0 of `eliashaeussler/composer-update-check`
- Read provider link from `OutdatedPackage` and fall back to custom link generation

## [0.4.0] - 2020-09-29

### Added

- Support for E-mail reports
- Include security state of outdated packages in service reports
- Abstract test case collecting garbage in `tearDown()` method

## [0.3.2] - 2020-09-25

### Added

- Missing PHP requirement in `composer.json`

## [0.3.0] - 2020-09-23

### Added

- Support for GitLab Alerts
- Application simulation script
- Unit tests
- Pass `--json` flag from original user input to each registered service
- Validate registered service against implementation of the `ServiceInterface`

### Fixed

- Loosen Composer requirements to support various dependent versions and assure support for PHP 7.1
- Validate Mattermost url and channel name

## [0.2.0] - 2020-09-17

### Added

- Allow to explicitly enable or disable single services

## [0.1.2] - 2020-09-17

### Fixed

- Add missing composer dependency to `spatie/emoji`

## [0.1.0] - 2020-09-17

Initial release

[Unreleased]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.8.0...develop
[0.8.0]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.7.0...0.8.0
[0.7.0]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.6.2...0.7.0
[0.6.2]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.6.1...0.6.2
[0.6.1]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.6.0...0.6.1
[0.6.0]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.5.0...0.6.0
[0.5.0]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.4.0...0.5.0
[0.4.0]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.3.0...0.4.0
[0.3.2]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.3.0...0.3.2
[0.3.0]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.2.0...0.3.0
[0.2.0]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.1.2...0.2.0
[0.1.2]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/compare/0.1.0...0.1.2
[0.1.0]: https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/tags/0.1.0
