<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Service;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-reporter".
 *
 * Copyright (C) 2021 Elias Häußler <elias@haeussler.dev>
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
 * Teams.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Teams extends AbstractService
{
    use RemoteServiceTrait;

    /**
     * @var UriInterface
     */
    private $uri;

    public function __construct(UriInterface $uri)
    {
        $this->uri = $uri;
        $this->requestFactory = new Psr17Factory();
        $this->client = new Psr18Client();

        $this->validateUri();
    }

    public static function getIdentifier(): string
    {
        return 'teams';
    }

    protected static function getName(): string
    {
        return 'MS Teams';
    }

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $uri = new Uri((string) static::resolveConfigurationKey($configuration, 'url'));

        return new self($uri);
    }

    /**
     * @throws ClientExceptionInterface
     */
    protected function sendReport(UpdateCheckResult $result): bool
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Build JSON payload
        $count = count($outdatedPackages);
        $multiple = 1 !== $count;
        $payload = [
            'title' => sprintf('%s %d outdated package%s', Emoji::policeCarLight(), $count, $multiple ? 's' : ''),
            'summary' => sprintf('%d package%s %s outdated', $count, $multiple ? 's' : '', $multiple ? 'are' : 'is'),
            'sections' => $this->renderSections($outdatedPackages),
        ];

        if (null !== $this->projectName) {
            $payload['title'] .= sprintf(' @ %s', $this->projectName);
        }

        // Send report
        if (!$this->behavior->style->isJson()) {
            $this->behavior->io->write(Emoji::rocket().' Sending report to MS Teams...');
        }
        $response = $this->sendRequest($payload);

        return $response->getStatusCode() < 400;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     *
     * @return array[]
     */
    private function renderSections(array $outdatedPackages): array
    {
        $sections = [];
        foreach ($outdatedPackages as $outdatedPackage) {
            $insecure = '';
            if ($outdatedPackage->isInsecure()) {
                $insecure = sprintf(' (%s insecure)', Emoji::warning());
            }
            $textParts = [];
            $textParts[] = sprintf('# [%s](%s)', $outdatedPackage->getName(), $outdatedPackage->getProviderLink());
            $textParts[] = sprintf('Current version: **%s**%s', $outdatedPackage->getOutdatedVersion(), $insecure);
            $textParts[] = sprintf('New version: **%s**', $outdatedPackage->getNewVersion());
            $sections[] = ['text' => implode(PHP_EOL.PHP_EOL, $textParts)];
        }

        return $sections;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    private function validateUri(): void
    {
        $uri = (string) $this->uri;
        if ('' === trim($uri)) {
            throw new \InvalidArgumentException('MS Teams URL must not be empty.', 1612865642);
        }
        if (false === filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('MS Teams URL is no valid URL.', 1612865646);
        }
    }
}
