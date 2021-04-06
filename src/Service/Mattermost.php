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

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Traits\RemoteServiceTrait;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Emoji\Emoji;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Mattermost
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Mattermost extends AbstractService
{
    use RemoteServiceTrait;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var string
     */
    private $channelName;

    /**
     * @var string|null
     */
    private $username;

    public function __construct(UriInterface $uri, string $channelName, string $username = null)
    {
        $this->uri = $uri;
        $this->channelName = $channelName;
        $this->username = $username;
        $this->requestFactory = new Psr17Factory();
        $this->client = new Psr18Client();

        $this->validateUri();
        $this->validateChannelName();
    }

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $extra = $configuration['mattermost'] ?? null;

        // Parse Mattermost URL
        if (is_array($extra) && array_key_exists('url', $extra)) {
            $uri = new Uri((string)$extra['url']);
        } elseif (getenv('MATTERMOST_URL') !== false) {
            $uri = new Uri(getenv('MATTERMOST_URL'));
        } else {
            throw new \RuntimeException(
                'Mattermost URL is not defined. Define it either in composer.json or as $MATTERMOST_URL.',
                1600283681
            );
        }

        // Parse Mattermost channel name
        if (is_array($extra) && array_key_exists('channel', $extra)) {
            $channelName = (string)$extra['channel'];
        } elseif (getenv('MATTERMOST_CHANNEL') !== false) {
            $channelName = getenv('MATTERMOST_CHANNEL');
        } else {
            throw new \RuntimeException(
                'Mattermost channel name is not defined. Define it either in composer.json or as $MATTERMOST_CHANNEL.',
                1600284246
            );
        }

        // Parse Mattermost username
        $username = null;
        if (is_array($extra) && array_key_exists('username', $extra)) {
            $username = (string)$extra['username'];
        } elseif (getenv('MATTERMOST_USERNAME') !== false) {
            $username = getenv('MATTERMOST_USERNAME');
        }

        return new self($uri, $channelName, $username);
    }

    protected static function getIdentifier(): string
    {
        return 'mattermost';
    }

    protected static function getName(): string
    {
        return 'Mattermost';
    }

    /**
     * @inheritDoc
     * @throws ClientExceptionInterface
     */
    protected function sendReport(UpdateCheckResult $result): bool
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Build JSON payload
        $payload = [
            'channel' => $this->channelName,
            'attachments' => [
                [
                    'color' => '#EE0000',
                    'text' => $this->renderText($outdatedPackages),
                ],
            ],
        ];
        if ($this->username !== null) {
            $payload['username'] = $this->username;
        }

        // Send report
        if (!$this->behavior->style->isJson()) {
            $this->behavior->io->write(Emoji::rocket() . ' Sending report to Mattermost...');
        }
        $response = $this->sendRequest($payload);

        return $response->getStatusCode() < 400;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     * @return string
     */
    private function renderText(array $outdatedPackages): string
    {
        $count = count($outdatedPackages);
        $textParts = [
            sprintf('#### :rotating_light: %d outdated package%s', $count, $count !== 1 ? 's' : ''),
            '| Package | Current version | New version |',
            '|:------- |:--------------- |:----------- |',
        ];
        foreach ($outdatedPackages as $outdatedPackage) {
            $insecure = '';
            if ($outdatedPackage->isInsecure()) {
                $insecure = ' :warning: **`insecure`**';
            }
            $textParts[] = sprintf(
                '| [%s](%s) | %s%s | **%s** |',
                $outdatedPackage->getName(),
                $outdatedPackage->getProviderLink(),
                $outdatedPackage->getOutdatedVersion(),
                $insecure,
                $outdatedPackage->getNewVersion()
            );
        }
        return implode(PHP_EOL, $textParts);
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    private function validateUri(): void
    {
        $uri = (string) $this->uri;
        if (trim($uri) === '') {
            throw new \InvalidArgumentException('Mattermost URL must not be empty.', 1600793015);
        }
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Mattermost URL is no valid URL.', 1600792942);
        }
    }

    private function validateChannelName(): void
    {
        if (trim($this->channelName) === '') {
            throw new \InvalidArgumentException('Mattermost channel name must not be empty.', 1600793071);
        }
    }
}
