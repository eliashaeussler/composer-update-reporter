# Composer update reporter plugin

> Composer Plugin to report outdated packages to several external services,
> based on the Plugin [eliashaeussler/composer-update-check](https://packagist.org/packages/eliashaeussler/composer-update-check)

## Installation

```bash
composer req --dev eliashaeussler/composer-update-reporter
```

## Supported services

* [Mattermost](#mattermost)

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

### Mattermost

| `composer.json` config key | Environment variable | Type | Required |
| -------------------------- | -------------------- | ---- | -------- |
| `mattermost.enable` | `MATTERMOST_ENABLE` | `bool` | yes |
| `mattermost.url` | `MATTERMOST_URL` | `string` | yes |
| `mattermost.channel` | `MATTERMOST_CHANNEL` | `string` | yes |
| `mattermost.username` | `MATTERMOST_USERNAME` | `string` | no |

### Example

Example configuration in `composer.json`:

```json
{
  "extra": {
    "update-check": {
      "mattermost": {
        "enable": true,
        "url": "https://mattermost.example.org/hooks/5scqqjpgw3dzipuawi8fp19acy",
        "channel": "alerts",
        "username": "alertbot"
      }
    }
  }
}
```

Example configuration using environment variables:

```bash
MATTERMOST_ENABLE=1
MATTERMOST_URL="https://mattermost.example.org/hooks/5scqqjpgw3dzipuawi8fp19acy"
MATTERMOST_CHANNEL="alerts"
MATTERMOST_USERNAME="alertbot"
```

## Usage

Once services are configured properly, the update check can be executed
as usual. The update check result will then be reported to all enabled
services using the dispatched event.

```bash
composer update-check
```

## Run tests

```bash
# Clone repository
git clone https://gitlab.elias-haeussler.de/eliashaeussler/composer-update-reporter.git
cd composer-update-reporter

# Install Composer dependencies
composer install

# Run all tests
composer test
```

## License

[GPL 3.0 or later](LICENSE)
