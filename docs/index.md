---
hide:
- toc
---

[![Coverage](https://codecov.io/gh/eliashaeussler/composer-update-reporter/branch/master/graph/badge.svg?token=4GZI1QWP5X)](https://codecov.io/gh/eliashaeussler/composer-update-reporter)
[![Maintainability](https://api.codeclimate.com/v1/badges/06d55184455feeee3652/maintainability)](https://codeclimate.com/github/eliashaeussler/composer-update-reporter/maintainability)
[![Tests](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/tests.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/tests.yaml)
[![CGL](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/cgl.yaml/badge.svg)](https://github.com/eliashaeussler/composer-update-reporter/actions/workflows/cgl.yaml)
[![Latest Stable Version](http://poser.pugx.org/eliashaeussler/composer-update-reporter/v)](https://packagist.org/packages/eliashaeussler/composer-update-reporter)
[![Total Downloads](http://poser.pugx.org/eliashaeussler/composer-update-reporter/downloads)](https://packagist.org/packages/eliashaeussler/composer-update-reporter)
[![License](http://poser.pugx.org/eliashaeussler/composer-update-reporter/license)](license.md)

# Composer update reporter plugin

> A Composer Plugin to report outdated packages to several external services,<br>
> based on the Plugin [eliashaeussler/composer-update-check](https://packagist.org/packages/eliashaeussler/composer-update-check).

## :rocket: Supported services

The following services are currently natively supported. You are free to implement your own
service or unregister one of the default ones.

* [x] E-mail
* [x] GitLab Alerts
* [x] Mattermost
* [x] Slack
* [x] Microsoft Teams

## :star: License

This project is licensed under
[GNU General Public License 3.0 (or later)](license.md).
