[![Pipeline](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/badges/master/pipeline.svg)](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/pipelines)
[![Coverage](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/badges/master/coverage.svg)](https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter/-/pipelines)
[![Packagist](https://badgen.net/packagist/v/eliashaeussler/composer-update-reporter)](https://packagist.org/packages/eliashaeussler/composer-update-reporter)
[![License](https://badgen.net/packagist/license/eliashaeussler/composer-update-reporter)](LICENSE)

# Composer update reporter plugin

> Composer Plugin to report outdated packages to several external services,
> based on the Plugin [eliashaeussler/composer-update-check](https://packagist.org/packages/eliashaeussler/composer-update-check)

## Installation

```bash
composer req --dev eliashaeussler/composer-update-reporter
```

## Supported services

* [Email](#email)
* [GitLab](#gitlab)
* [Mattermost](#mattermost)
* [Slack](#slack)

## Configuration

Services need to be enabled and configured, either in the `composer.json`
file of your project or using environment variables.

Configuration in `composer.json` needs to be placed in the `extra`
section like follows:

```json
{
  "extra": {
    "update-check": {
      "<service name>": {
        "<config key>": "<config value>"
      }
    }
  }
}
```

### Email

Email reports are being processed using the [Symfony Mailer](https://packagist.org/packages/symfony/mailer).
Consult the [official documentation](https://symfony.com/doc/current/mailer.html) for help regarding DSN.

| `composer.json` config key | Environment variable | Type | Required |
| -------------------------- | -------------------- | ---- | -------- |
| `email.enable` | `EMAIL_ENABLE` | `bool` | yes |
| `email.dsn` | `EMAIL_DSN` | `string` | yes |
| `email.receivers` | `EMAIL_RECEIVERS` | `string` (comma-separated list) | yes |
| `email.sender` | `EMAIL_SENDER` | `string` | yes |

### GitLab

Learn more about GitLab Alerts in the
[official documentation](https://docs.gitlab.com/ee/operations/incident_management/alert_integrations.html#generic-http-endpoint).

| `composer.json` config key | Environment variable | Type | Required |
| -------------------------- | -------------------- | ---- | -------- |
| `gitlab.enable` | `GITLAB_ENABLE` | `bool` | yes |
| `gitlab.url` | `GITLAB_URL` | `string` | yes |
| `gitlab.authKey` | `GITLAB_AUTH_KEY` | `string` | yes |

### Mattermost

Learn more about Mattermost webhooks in the
[offical documentation](https://docs.mattermost.com/developer/webhooks-incoming.html).

| `composer.json` config key | Environment variable | Type | Required |
| -------------------------- | -------------------- | ---- | -------- |
| `mattermost.enable` | `MATTERMOST_ENABLE` | `bool` | yes |
| `mattermost.url` | `MATTERMOST_URL` | `string` | yes |
| `mattermost.channel` | `MATTERMOST_CHANNEL` | `string` | yes |
| `mattermost.username` | `MATTERMOST_USERNAME` | `string` | no |

### Slack

Learn more about Incoming Webhooks in the
[offical Slack documentation](https://api.slack.com/messaging/webhooks).

| `composer.json` config key | Environment variable | Type | Required |
| -------------------------- | -------------------- | ---- | -------- |
| `slack.enable` | `SLACK_ENABLE` | `bool` | yes |
| `slack.url` | `SLACK_URL` | `string` | yes |

### Example

Example configuration in `composer.json`:

```json
{
  "extra": {
    "update-check": {
      "email": {
        "enable": true,
        "dsn": "smtp://foo:baz@smtp.example.com:25",
        "receivers": "john@example.org, marc@example.org",
        "sender": "alerts@example.org"
      },
      "gitlab": {
        "enable": true,
        "url": "https://gitlab.example.org/vendor/project/alerts/notify.json",
        "authKey": "5scqqjpgw3dzipuawi8fp19acy"
      },
      "mattermost": {
        "enable": true,
        "url": "https://mattermost.example.org/hooks/5scqqjpgw3dzipuawi8fp19acy",
        "channel": "alerts",
        "username": "alertbot"
      },
      "slack": {
        "enable": true,
        "url": "https://hooks.slack.com/services/TU5C6A7/B01J/ZG5AR77F/5scqqjpgw3dzipuawi8fp19acy"
      }
    }
  }
}
```

Example configuration using environment variables:

```bash
EMAIL_ENABLE=1
EMAIL_DSN="smtp://foo:baz@smtp.example.com:25"
EMAIL_RECEIVERS="john@example.org, marc@example.org"
EMAIL_SENDER="alerts@example.org"

GITLAB_ENABLE=1
GITLAB_URL="https://gitlab.example.org/vendor/project/alerts/notify.json"
GITLAB_AUTH_KEY="5scqqjpgw3dzipuawi8fp19acy"

MATTERMOST_ENABLE=1
MATTERMOST_URL="https://mattermost.example.org/hooks/5scqqjpgw3dzipuawi8fp19acy"
MATTERMOST_CHANNEL="alerts"
MATTERMOST_USERNAME="alertbot"

SLACK_ENABLE=1
SLACK_URL="https://hooks.slack.com/services/TU5C6A7/B01J/ZG5AR77F/5scqqjpgw3dzipuawi8fp19acy"
```

## Usage

Once services are configured properly, the update check can be executed
as usual. The update check result will then be reported to all enabled
services using the dispatched event.

```bash
composer update-check
```

## Development

### Requirements

* [Docker](https://docs.docker.com/get-docker/) and [Docker Compose](https://docs.docker.com/compose/install/)

### Preparation

```bash
# Clone repository
git clone https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter.git
cd composer-update-reporter

# Install Composer dependencies
composer install
```

### Run tests

Unit tests of this plugin can be executed using the provided Composer
script `test`. You can pass all available arguments to PHPUnit.

```bash
# Run tests
composer test

# Run tests and print coverage result
composer test -- --coverage-text
```

### Simulate application

A Composer script `simulate` exists which lets you test the update
check reporter, which is provided by this plugin. All parameters
passed to the script will be redirected to the relevant Composer
command `update-check`.

```bash
# Run "composer update-check" command without parameters
composer simulate

# Pass parameters to "composer update-check" command
composer simulate -- -i "composer/*"
composer simulate -- --no-dev
```

Alternatively, this script can be called without Composer context:

```bash
./bin/simulate-application.sh
```

## License

[GPL 3.0 or later](LICENSE)
