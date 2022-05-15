# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2022-05-15

### Fixed

- Add missing dependencies to `composer.json`
- Installation from source when testing `composer-update-check`

### Added

- Code quality assurance with CodeClimate and codecov
- Various CGL checks
- Dependabot updates for GitHub Actions

### Changed

- Switch to GitHub Pages for documentation
- Switch from `master` to `main` branch
- Switch to GitHub issue forms

## [1.1.2] - 2022-01-06

### Added

- Support for Symfony 6 components

## [1.1.1] - 2021-12-27

### Fixed

- Various CI fixes

## [1.1.0] - 2021-12-27

### Fixed

- Various requirements for dependencies installed with `--prefer-lowest`
- Requirements for PHP 8.0 compatibility

### Added

- Support for Composer 2.2
- Code quality assurance with SonarCloud

### Changed

- Migrate project from GitLab to GitHub
- Upgrade PHP-CS-Fixer to 3.x
- Upgrade PHPStan to 1.x

### Documentation

- Improved README.md
- Add logo

## [1.0.0] - 2021-04-19

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

[Unreleased]: https://github.com/eliashaeussler/composer-update-reporter/compare/1.2.0...develop
[1.2.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/1.1.2...1.2.0
[1.1.2]: https://github.com/eliashaeussler/composer-update-reporter/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/eliashaeussler/composer-update-reporter/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.8.0...1.0.0
[0.8.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.7.0...0.8.0
[0.7.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.6.2...0.7.0
[0.6.2]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.6.1...0.6.2
[0.6.1]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.6.0...0.6.1
[0.6.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.3.0...0.4.0
[0.3.2]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.3.0...0.3.2
[0.3.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.1.2...0.2.0
[0.1.2]: https://github.com/eliashaeussler/composer-update-reporter/compare/0.1.0...0.1.2
[0.1.0]: https://github.com/eliashaeussler/composer-update-reporter/tree/0.1.0
