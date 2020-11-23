<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateReporter\Service;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-reporter".
 * 
 * Copyright (C) 2020 Elias Häußler <elias@haeussler.dev>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Composer\IO\IOInterface;
use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Util;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Spatie\Emoji\Emoji;
use Spatie\Emoji\Exceptions\UnknownCharacter;

/**
 * BetterUptime
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class BetterUptime implements ServiceInterface
{
    public const DEFAULT_API_URL = 'https://betteruptime.com/api/v1/incident';

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var string
     */
    private $authToken;

    /**
     * @var string
     */
    private $requester;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $json = false;

    public function __construct(string $authToken, string $requester, array $options = [], UriInterface $uri = null)
    {
        $this->authToken = $authToken;
        $this->requester = $requester;
        $this->options = $options;
        $this->uri = $uri ?? new Uri(self::DEFAULT_API_URL);
        $this->client = new Client([
            'base_uri' => (string) $this->uri,
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $this->authToken,
            ],
        ]);

        $this->validateAuthToken();
        $this->validateRequester();
        $this->validateUri();
    }

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $extra = $configuration['betterUptime'] ?? null;

        // Parse BetterUptime auth token
        if (is_array($extra) && array_key_exists('authToken', $extra)) {
            $authToken = (string)$extra['authToken'];
        } else if (getenv('BETTER_UPTIME_AUTH_TOKEN') !== false) {
            $authToken = getenv('BETTER_UPTIME_AUTH_TOKEN');
        } else {
            throw new \RuntimeException(
                'BetterUptime auth token is not defined. Define it either in composer.json or as $BETTER_UPTIME_AUTH_TOKEN.',
                1606157616
            );
        }

        // Parse BetterUptime requester
        if (is_array($extra) && array_key_exists('requester', $extra)) {
            $requester = (string)$extra['requester'];
        } else if (getenv('BETTER_UPTIME_REQUESTER') !== false) {
            $requester = getenv('BETTER_UPTIME_REQUESTER');
        } else {
            throw new \RuntimeException(
                'BetterUptime requester is not defined. Define it either in composer.json or as $BETTER_UPTIME_REQUESTER.',
                1606161576
            );
        }

        // Parse BetterUptime options
        $options = [];
        if (is_array($extra) && array_key_exists('options', $extra)) {
            $options = static::parseOptions($extra['options']);
        } else if (getenv('BETTER_UPTIME_OPTIONS') !== false) {
            $options = static::parseOptions(getenv('BETTER_UPTIME_OPTIONS'));
        }

        // Parse BetterUptime URL
        $uri = null;
        if (is_array($extra) && array_key_exists('url', $extra)) {
            $uri = new Uri((string)$extra['url']);
        } else if (getenv('BETTER_UPTIME_URL') !== false) {
            $uri = new Uri(getenv('BETTER_UPTIME_URL'));
        }

        return new self($authToken, $requester, $options, $uri);
    }

    public static function isEnabled(array $configuration): bool
    {
        if (getenv('BETTER_UPTIME_ENABLE') !== false && (bool)getenv('BETTER_UPTIME_ENABLE')) {
            return true;
        }
        $extra = $configuration['betterUptime'] ?? null;
        return is_array($extra) && (bool)($extra['enable'] ?? false);
    }

    /**
     * @inheritDoc
     * @throws GuzzleException
     */
    public function report(UpdateCheckResult $result, IOInterface $io): bool
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Do not send report if packages are up to date
        if ($outdatedPackages === []) {
            if (!$this->json) {
                $io->write(Emoji::crossMark() . ' Skipped BetterUptime report.');
            }
            return true;
        }

        // Build JSON payload
        $count = count($outdatedPackages);
        $payload = array_merge([
            'requester_email' => $this->requester,
            'summary' => sprintf('%d outdated package%s', $count, $count !== 1 ? 's' : ''),
            'description' => $this->renderDescription($outdatedPackages),
        ], $this->options);

        // Send report
        if (!$this->json) {
            $io->write(Emoji::rocket() . ' Sending report to BetterUptime...');
        }
        $response = $this->client->post('', [RequestOptions::JSON => $payload]);
        $successful = $response->getStatusCode() < 400;

        // Print report state
        if (!$successful) {
            $io->writeError(Emoji::crossMark() . ' Error during BetterUptime report.');
        } else if (!$this->json) {
            try {
                $checkMark = Emoji::checkMark();
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UnknownCharacter $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                $checkMark = Emoji::heavyCheckMark();
            }
            $io->write($checkMark . ' BetterUptime report was successful.');
        }

        return $successful;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     * @return string
     */
    private function renderDescription(array $outdatedPackages): string
    {
        $descriptionParts = [];
        foreach ($outdatedPackages as $outdatedPackage) {
            $insecure = '';
            if ($outdatedPackage->isInsecure()) {
                $insecure = ' [insecure]';
            }
            $descriptionParts[] = sprintf(
                '%s (%s%s => %s)',
                $outdatedPackage->getName(),
                $outdatedPackage->getOutdatedVersion(),
                $insecure,
                $outdatedPackage->getNewVersion()
            );
        }
        return implode(', ', $descriptionParts);
    }

    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    public function getRequester(): string
    {
        return $this->requester;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function setJson(bool $json): ServiceInterface
    {
        $this->json = $json;
        return $this;
    }

    private static function parseOptions($options): array
    {
        if (is_string($options)) {
            $options = Util::trimExplode(',', $options);
        }
        if (!is_array($options)) {
            throw new \InvalidArgumentException(
                'Unsupported value for BetterUptime options given.',
                1606157858
            );
        }
        $parsedOptions = [];
        foreach ($options as $option => $value) {
            if (is_int($option)) {
                if (!is_string($value)) {
                    throw new \InvalidArgumentException(
                        sprintf('Invalid value provided for BetterUptime option #%d.', $option),
                        1606157977
                    );
                }
                [$option, $value] = Util::trimExplode('=', $value, 2, true);
            }
            if (trim($option) === '') {
                throw new \InvalidArgumentException('BetterUptime option must not be empty.', 1606164612);
            }
            if ($value === null) {
                throw new \InvalidArgumentException('No value provided for BetterUptime option.', 1606158226);
            }
            if (!static::isReservedOption($option)) {
                $parsedOptions[$option] = $value;
            }
        }
        return $parsedOptions;
    }

    private static function isReservedOption(string $option): bool
    {
        return in_array(strtolower($option), ['requester_email', 'summary', 'description'], true);
    }

    private function validateAuthToken(): void
    {
        if (trim($this->authToken) === '') {
            throw new \InvalidArgumentException('BetterUptime auth token must not be empty.', 1606157503);
        }
    }

    private function validateRequester(): void
    {
        if (trim($this->requester) === '') {
            throw new \InvalidArgumentException('BetterUptime requester not be empty.', 1606161641);
        }
        if (filter_var($this->requester, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('BetterUptime requester is no valid email address.', 1606161665);
        }
    }

    private function validateUri(): void
    {
        $uri = (string) $this->uri;
        if (trim($uri) === '') {
            throw new \InvalidArgumentException('BetterUptime URL must not be empty.', 1606157479);
        }
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('BetterUptime URL is no valid URL.', 1606157488);
        }
    }
}
