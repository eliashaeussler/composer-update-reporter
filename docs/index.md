---
hide:
- toc
---

[![Pipeline]({{ repository.url }}/badges/master/pipeline.svg)]({{ repository.url }}/-/pipelines)
[![Coverage]({{ repository.url }}/badges/master/coverage.svg)]({{ repository.url }}/-/pipelines)
[![Packagist](https://badgen.net/packagist/v/eliashaeussler/composer-update-reporter)](https://packagist.org/packages/eliashaeussler/composer-update-reporter)
[![License](https://badgen.net/packagist/license/eliashaeussler/composer-update-reporter)](license.md)

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
