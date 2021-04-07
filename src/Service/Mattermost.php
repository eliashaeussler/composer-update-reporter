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
 * Mattermost.
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

    public static function getIdentifier(): string
    {
        return 'mattermost';
    }

    protected static function getName(): string
    {
        return 'Mattermost';
    }

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $uri = new Uri((string) static::resolveConfigurationKey($configuration, 'url'));
        $channelName = (string) static::resolveConfigurationKey($configuration, 'channel');
        $username = (string) static::resolveConfigurationKey($configuration, 'username');

        return new self($uri, $channelName, $username);
    }

    /**
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
        if (null !== $this->username) {
            $payload['username'] = $this->username;
        }

        // Send report
        if (!$this->behavior->style->isJson()) {
            $this->behavior->io->write(Emoji::rocket().' Sending report to Mattermost...');
        }
        $response = $this->sendRequest($payload);

        return $response->getStatusCode() < 400;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     */
    private function renderText(array $outdatedPackages): string
    {
        $count = count($outdatedPackages);
        $textParts = [
            sprintf('#### :rotating_light: %d outdated package%s', $count, 1 !== $count ? 's' : ''),
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
        if ('' === trim($uri)) {
            throw new \InvalidArgumentException('Mattermost URL must not be empty.', 1600793015);
        }
        if (false === filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Mattermost URL is no valid URL.', 1600792942);
        }
    }

    private function validateChannelName(): void
    {
        if ('' === trim($this->channelName)) {
            throw new \InvalidArgumentException('Mattermost channel name must not be empty.', 1600793071);
        }
    }
}
