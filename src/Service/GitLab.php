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
 * GitLab
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class GitLab extends AbstractService
{
    use RemoteServiceTrait;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var string
     */
    private $authorizationKey;

    public function __construct(UriInterface $uri, string $authorizationKey)
    {
        $this->uri = $uri;
        $this->authorizationKey = $authorizationKey;
        $this->requestFactory = new Psr17Factory();
        $this->client = new Psr18Client();

        $this->validateUri();
        $this->validateAuthorizationKey();
    }

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $extra = $configuration['gitlab'] ?? null;

        // Parse GitLab URL
        if (is_array($extra) && array_key_exists('url', $extra)) {
            $uri = new Uri((string)$extra['url']);
        } elseif (getenv('GITLAB_URL') !== false) {
            $uri = new Uri(getenv('GITLAB_URL'));
        } else {
            throw new \RuntimeException(
                'GitLab URL is not defined. Define it either in composer.json or as $GITLAB_URL.',
                1600852917
            );
        }

        // Parse GitLab authorization key
        if (is_array($extra) && array_key_exists('authKey', $extra)) {
            $authKey = (string)$extra['authKey'];
        } elseif (getenv('GITLAB_AUTH_KEY') !== false) {
            $authKey = getenv('GITLAB_AUTH_KEY');
        } else {
            throw new \RuntimeException(
                'GitLab authorization key is not defined. Define it either in composer.json or as $GITLAB_AUTH_KEY.',
                1600852990
            );
        }

        return new self($uri, $authKey);
    }

    protected static function getIdentifier(): string
    {
        return 'gitlab';
    }

    protected static function getName(): string
    {
        return 'GitLab';
    }

    /**
     * @inheritDoc
     * @throws ClientExceptionInterface
     */
    protected function sendReport(UpdateCheckResult $result): bool
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Build JSON payload
        $count = count($outdatedPackages);
        $payload = array_merge([
            'title' => sprintf('%d outdated package%s', $count, $count !== 1 ? 's' : ''),
        ], $this->getPackagesPayload($outdatedPackages));

        // Send report
        if (!$this->behavior->style->isJson()) {
            $this->behavior->io->write(Emoji::rocket() . ' Sending report to GitLab...');
        }
        $response = $this->sendRequest($payload, ['Authorization' => 'Bearer ' . $this->authorizationKey,]);

        return $response->getStatusCode() < 400;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     * @return array
     */
    private function getPackagesPayload(array $outdatedPackages): array
    {
        $payload = [];
        foreach ($outdatedPackages as $outdatedPackage) {
            $insecure = '';
            if ($outdatedPackage->isInsecure()) {
                $insecure = ' (insecure)';
            }
            $payload[$outdatedPackage->getName()] = sprintf(
                'Outdated version: %s%s, new version: %s',
                $outdatedPackage->getOutdatedVersion(),
                $insecure,
                $outdatedPackage->getNewVersion()
            );
        }
        return $payload;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getAuthorizationKey(): string
    {
        return $this->authorizationKey;
    }

    private function validateUri(): void
    {
        $uri = (string) $this->uri;
        if (trim($uri) === '') {
            throw new \InvalidArgumentException('GitLab URL must not be empty.', 1600852837);
        }
        if (filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('GitLab URL is no valid URL.', 1600852841);
        }
    }

    private function validateAuthorizationKey(): void
    {
        if (trim($this->authorizationKey) === '') {
            throw new \InvalidArgumentException('GitLab authorization key must not be empty.', 1600852864);
        }
    }
}
