<div align="center">

![Logo](docs/assets/img/logo.png)

# Composer update reporter plugin

[![Coverage](https://codecov.io/gh/eliashaeussler/composer-update-reporter/branch/develop/graph/badge.svg?token=4GZI1QWP5X)](https://codecov.io/gh/eliashaeussler/composer-update-reporter)
[![Maintainability](https://api.codeclimate.com/v1/badges/06d55184455feeee3652/maintainability)](https://codeclimate.com/github/eliashaeussler/composer-update-reporter/maintainability)
[![Tests](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/tests.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/tests.yaml)
[![CGL](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/cgl.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/cgl.yaml)
[![Latest Stable Version](https://poser.pugx.org/eliashaeussler/composer-update-reporter/v)](https://packagist.org/packages/eliashaeussler/composer-update-reporter)
[![Total Downloads](https://poser.pugx.org/eliashaeussler/composer-update-reporter/downloads)](https://packagist.org/packages/eliashaeussler/composer-update-reporter)
[![License](https://poser.pugx.org/eliashaeussler/composer-update-reporter/license)](LICENSE.md)

**:orange_book:&nbsp;[Documentation](https://composer-update-reporter.elias-haeussler.de/)** |
:package:&nbsp;[Packagist](https://packagist.org/packages/eliashaeussler/composer-update-reporter) |
:floppy_disk:&nbsp;[Repository](https://github.com/eliashaeussler/composer-update-reporter) |
:bug:&nbsp;[Issue tracker](https://github.com/eliashaeussler/composer-update-reporter/issues)

</div>

A Composer plugin for 
[`eliashaeussler/composer-update-check`](https://github.com/eliashaeussler/composer-update-check)
that can be used to automatically report outdated packages to various services. This allows strong
automation in the area of quality assurance by reporting outdated packages directly to you, for
example via Slack, Mattermost or by mail. It is even possible to implement your own service to
which reports can be sent at any time.

## :rocket: Features

* Send report to various services (Slack, Teams, Mattermost, E-mail etc.)
* Include security scan results in report
* API to create custom service reports
* Smooth integration into Composer lifecycle
* Various configuration options (`composer.json` or via environment variables)

## :fire: Installation

```bash
composer require eliashaeussler/composer-update-reporter
```

## :ship: Changelog

View all notable release notes in the [Changelog](CHANGELOG.md).

## :technologist: Contributing

Please have a look at [`CONTRIBUTING.md`](CONTRIBUTING.md).

## :gem: Credits

[Banner vector created by studiogstock - www.freepik.com](https://www.freepik.com/vectors/banner)

## :star: License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).
