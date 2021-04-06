<?php

declare(strict_types=1);

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

namespace EliasHaeussler\ComposerUpdateReporter\Tests\Unit;

use EliasHaeussler\ComposerUpdateReporter\Util;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * ClientMockTrait.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
trait ClientMockTrait
{
    /**
     * @var MockResponse
     */
    private $mockedResponse;

    private function getClient(): ClientInterface
    {
        $callback = function () {
            return $this->mockedResponse;
        };

        return new Psr18Client(new MockHttpClient($callback));
    }

    protected function assertPayloadOfLastRequestContainsSubset(array $expectedPayloadSubset): void
    {
        $payload = $this->getPayloadOfLastRequest();
        self::assertSame([], Util::arrayDiffRecursive($expectedPayloadSubset, $payload));
    }

    protected function getPayloadOfLastRequest(): array
    {
        $lastResponse = $this->mockedResponse;

        self::assertInstanceOf(MockResponse::class, $lastResponse, 'Unable to find last request');

        return json_decode($lastResponse->getRequestOptions()['body'], true);
    }
}
