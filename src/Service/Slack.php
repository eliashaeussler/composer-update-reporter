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
 * Slack
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Slack extends AbstractService
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

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $extra = $configuration['slack'] ?? null;

        // Parse Slack URL
        if (is_array($extra) && array_key_exists('url', $extra)) {
            $uri = new Uri((string)$extra['url']);
        } elseif (getenv('SLACK_URL') !== false) {
            $uri = new Uri(getenv('SLACK_URL'));
        } else {
            throw new \RuntimeException(
                'Slack URL is not defined. Define it either in composer.json or as $SLACK_URL.',
                1602496964
            );
        }

        return new self($uri);
    }

    protected static function getIdentifier(): string
    {
        return 'slack';
    }

    protected static function getName(): string
    {
        return 'Slack';
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
            'blocks' => $this->renderBlocks($outdatedPackages),
        ];

        // Send report
        if (!$this->behavior->style->isJson()) {
            $this->behavior->io->write(Emoji::rocket() . ' Sending report to Slack...');
        }
        $response = $this->sendRequest($payload);

        return $response->getStatusCode() < 400;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     * @return array
     */
    private function renderBlocks(array $outdatedPackages): array
    {
        $hasInsecurePackages = false;
        $count = count($outdatedPackages);

        // Calculate longest version numbers of all outdated packages
        $outdatedVersionNumberLength = 0;
        $newVersionNumberLength = 0;
        array_walk($outdatedPackages, function (OutdatedPackage $outdatedPackage) use (&$outdatedVersionNumberLength, &$newVersionNumberLength) {
            if (($length = mb_strlen($outdatedPackage->getOutdatedVersion())) > $outdatedVersionNumberLength) {
                $outdatedVersionNumberLength = $length;
            }
            if (($length = mb_strlen($outdatedPackage->getNewVersion())) > $newVersionNumberLength) {
                $newVersionNumberLength = $length;
            }
        });

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => sprintf('%d outdated package%s', $count, $count !== 1 ? 's' : ''),
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'plain_text',
                    'text' => $count !== 1
                        ? 'The following packages are outdated and need to be updated:'
                        : 'The following package is outdated and needs to be updated:',
                ],
            ],
        ];

        foreach ($outdatedPackages as $outdatedPackage) {
            if ($outdatedPackage->isInsecure()) {
                $hasInsecurePackages = true;
            }

            $blocks[] = [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => sprintf(
                            '<%s|%s>',
                            $outdatedPackage->getProviderLink(),
                            $outdatedPackage->getName()
                        ),
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => sprintf(
                            '`%s` → *`%s`*%s',
                            str_pad($outdatedPackage->getOutdatedVersion(), $outdatedVersionNumberLength, ' ', STR_PAD_RIGHT),
                            str_pad($outdatedPackage->getNewVersion(), $newVersionNumberLength, ' ', STR_PAD_RIGHT),
                            $outdatedPackage->isInsecure() ? ' :rotating_light:' : ''
                        ),
                    ],
                ],
            ];

            // Slack allows only a limited number of blocks, therefore
            // we have to omit the remaining packages and show a message instead
            $remainingPackages = $count - (count($blocks) - 2);
            if (count($blocks) >= 48 && $remainingPackages > 0) {
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => sprintf('... and %d more', $remainingPackages),
                    ],
                ];
                break;
            }
        }

        if ($hasInsecurePackages) {
            $blocks[] = [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => 'Package versions with :rotating_light: are marked as insecure',
                    ],
                ],
            ];
        }

        return $blocks;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    private function validateUri(): void
    {
        $uri = (string) $this->uri;
        if (trim($uri) === '') {
            throw new \InvalidArgumentException('Slack URL must not be empty.', 1602496937);
        }
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Slack URL is no valid URL.', 1602496941);
        }
    }
}
