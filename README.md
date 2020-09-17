# Composer update reporter plugin

> Composer Plugin to report outdated packages to several external services,
> based on the Plugin "eliashaeussler/composer-update-report"

## Installation

```bash
composer req --dev eliashaeussler/composer-update-reporter
```

## Usage

```bash
# Define Mattermost configuration
export MATTERMOST_ENABLE=1
export MATTERMOST_URL=https://mattermost.example.org/hooks/5scqqjpgw3dzipuawi8fp19acy
export MATTERMOST_CHANNEL=alerts

# Run update check
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
