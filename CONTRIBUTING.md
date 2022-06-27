# Contributing

Contributions to the Composer update reporter plugin are very welcome.

Please follow the guide on this page if you want to contribute. Make sure
that all required code quality checks are green. If you need help, feel free
to file an issue and I will try to assist you wherever needed.

## Preparation

```bash
# Clone repository
git clone https://github.com/eliashaeussler/composer-update-reporter.git
cd composer-update-reporter

# Install Composer dependencies
composer install
```

## Check code quality

Code quality can be checked by running the following commands:

```bash
# Run linters
composer lint

# Run static code analysis
composer sca
```

## Run tests

Unit tests can be executed using the provided Composer script `test`.
You can pass all available arguments to PHPUnit.

```bash
# Run tests
composer test

# Run tests and generate code coverage
composer test:coverage
```

## Simulate application

A Composer script `simulate` exists which lets you run the Composer
command `update-check`. All parameters passed to the script will be
redirected to the Composer command.

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

## Build documentation

```bash
# Build documentation and watch for changes
composer docs

# Build documentation for production use
composer docs:build
```
