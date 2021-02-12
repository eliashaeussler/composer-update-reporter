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

use Composer\IO\IOInterface;
use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Traits\PackageProviderLinkTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;
use Spatie\Emoji\Emoji;
use Spatie\Emoji\Exceptions\UnknownCharacter;

/**
 * Teams
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Teams implements ServiceInterface
{
    use PackageProviderLinkTrait;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var bool
     */
    private $json = false;

    public function __construct(UriInterface $uri)
    {
        $this->uri = $uri;
        $this->client = new Client(['base_uri' => (string)$this->uri]);

        $this->validateUri();
    }

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $extra = $configuration['teams'] ?? null;

        // Parse MS Teams URL
        if (is_array($extra) && array_key_exists('url', $extra)) {
            $uri = new Uri((string)$extra['url']);
        } elseif (getenv('TEAMS_URL') !== false) {
            $uri = new Uri(getenv('TEAMS_URL'));
        } else {
            throw new \RuntimeException(
                'MS Teams URL is not defined. Define it either in composer.json or as $TEAMS_URL.',
                1612865679
            );
        }

        return new self($uri);
    }

    public static function isEnabled(array $configuration): bool
    {
        if (getenv('TEAMS_ENABLE') !== false && (bool)getenv('TEAMS_ENABLE')) {
            return true;
        }
        $extra = $configuration['teams'] ?? null;
        return is_array($extra) && (bool)($extra['enable'] ?? false);
    }

    public function report(UpdateCheckResult $result, IOInterface $io): bool
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Do not send report if packages are up to date
        if ($outdatedPackages === []) {
            if (!$this->json) {
                $io->write(Emoji::crossMark() . ' Skipped MS Teams report.');
            }
            return true;
        }

        // Build JSON payload
        $count = count($outdatedPackages);
        $multiple = $count !== 1;
        $payload = [
            'title' => sprintf('%s %d outdated package%s', Emoji::policeCarLight(), $count, $multiple ? 's' : ''),
            'summary' => sprintf('%d package%s %s outdated', $count, $multiple ? 's' : '', $multiple ? 'are' : 'is'),
            'sections' => $this->renderSections($outdatedPackages),
        ];

        // Send report
        if (!$this->json) {
            $io->write(Emoji::rocket() . ' Sending report to MS Teams...');
        }
        $response = $this->client->post('', [RequestOptions::JSON => $payload]);
        $successful = $response->getStatusCode() < 400;

        // Print report state
        if (!$successful) {
            $io->writeError(Emoji::crossMark() . ' Error during MS Teams report.');
        } elseif (!$this->json) {
            try {
                $checkMark = Emoji::checkMark();
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UnknownCharacter $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                $checkMark = Emoji::heavyCheckMark();
            }
            $io->write($checkMark . ' MS Teams report was successful.');
        }

        return $successful;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     * @return array
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
            $textParts[] = sprintf('# [%s](%s)', $outdatedPackage->getName(), $this->getProviderLink($outdatedPackage));
            $textParts[] = sprintf('Current version: **%s**%s', $outdatedPackage->getOutdatedVersion(), $insecure);
            $textParts[] = sprintf('New version: **%s**', $outdatedPackage->getNewVersion());
            $sections[] = ['text' => implode(PHP_EOL . PHP_EOL, $textParts)];
        }
        return $sections;
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

    private function validateUri(): void
    {
        $uri = (string)$this->uri;
        if (trim($uri) === '') {
            throw new \InvalidArgumentException('MS Teams URL must not be empty.', 1612865642);
        }
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('MS Teams URL is no valid URL.', 1612865646);
        }
    }
}
