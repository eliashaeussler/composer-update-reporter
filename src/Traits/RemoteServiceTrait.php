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

namespace EliasHaeussler\ComposerUpdateReporter\Traits;

use Nyholm\Psr7\Stream;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * RemoteServiceTrait
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
trait RemoteServiceTrait
{
    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @param array $payload
     * @param array $headers
     * @return ResponseInterface
     * @throws ClientExceptionInterface
     */
    protected function sendRequest(array $payload, array $headers = []): ResponseInterface
    {
        $request = $this->requestFactory->createRequest('POST', $this->uri)
            ->withBody(Stream::create(json_encode($payload)));
        $headers = array_merge($headers, ['Accept' => 'application/json']);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        return $this->client->sendRequest($request);
    }

    public function setClient(ClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }
}
