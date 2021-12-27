<div align="center">

![Logo](docs/assets/img/logo.png)

# Composer update check plugin

[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=eliashaeussler_composer-update-reporter&metric=coverage)](https://sonarcloud.io/dashboard?id=eliashaeussler_composer-update-reporter)
[![Tests](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/tests.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/tests.yaml)
[![CGL](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/cgl.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/cgl.yaml)
[![Latest Stable Version](http://poser.pugx.org/eliashaeussler/composer-update-reporter/v)](https://packagist.org/packages/eliashaeussler/composer-update-reporter)
[![Total Downloads](http://poser.pugx.org/eliashaeussler/composer-update-reporter/downloads)](https://packagist.org/packages/eliashaeussler/composer-update-reporter)
[![License](http://poser.pugx.org/eliashaeussler/composer-update-reporter/license)](LICENSE.md)

**:orange_book:&nbsp;[Documentation](https://docs.elias-haeussler.de/composer-update-reporter/)** |
:package:&nbsp;[Packagist](https://packagist.org/packages/eliashaeussler/composer-update-reporter) |
:floppy_disk:&nbsp;[Repository](https://github.com/eliashaeussler/composer-update-reporter) |
:bug:&nbsp;[Issue tracker](https://github.com/eliashaeussler/composer-update-reporter/issues)

</div>

A Composer plugin for 
[`eliashaeussler/composer-update-check`](https://github.com/eliashaeussler/composer-update-check)
that can be used to automatically report outdated packages to various services. This allows strong
automation in the area of quality assurance by reporting outdated packages directly to you, for
example to Slack, Mattermost or even by mail. In addition, it is possible to implement your own
services to which reports can be sent at any time.

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

## :gem: Credits

[Banner vector created by studiogstock - www.freepik.com](https://www.freepik.com/vectors/banner)

## :star: License

This project is licensed under [GNU General Public License 3.0 (or later)](LICENSE.md).
