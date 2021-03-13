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
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * ClientMockTrait
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
trait ClientMockTrait
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var array{request: RequestInterface, response: ResponseInterface|null, error: string|mixed, options: array}
     */
    private $requestContainer = [];

    private function getClient(): ClientInterface
    {
        $this->mockHandler = $this->mockHandler ?? new MockHandler();
        $this->mockHandler->reset();
        $history = Middleware::history($this->requestContainer);
        $handlerStack = HandlerStack::create($this->mockHandler);
        $handlerStack->push($history);
        return new Client(['handler' => $handlerStack]);
    }

    protected function assertPayloadOfLastRequestContainsSubset(array $expectedPayloadSubset): void
    {
        $payload = $this->getPayloadOfLastRequest();
        self::assertSame([], Util::arrayDiffRecursive($expectedPayloadSubset, $payload));
    }

    protected function getPayloadOfLastRequest(): array
    {
        self::assertNotEmpty($this->requestContainer, 'Unable to find last request');
        /** @var RequestInterface $request */
        $request = end($this->requestContainer)['request'];
        self::assertInstanceOf(RequestInterface::class, $request);
        $request->getBody()->rewind();
        return json_decode($request->getBody()->getContents(), true);
    }
}
