<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Tests\Unit;

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

use EliasHaeussler\ComposerUpdateReporter\Exception\InvalidServiceException;
use EliasHaeussler\ComposerUpdateReporter\Registry;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\Fixtures\Service\DummyService;

/**
 * RegistryTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class RegistryTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function registerServiceThrowsExceptionIfInvalidServiceIsGiven(): void
    {
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionCode(1600814017);

        Registry::registerService(\Exception::class);
    }

    /**
     * @test
     *
     * @throws InvalidServiceException
     */
    public function unregisterServiceRemovesServiceFromListOfRegisteredServices(): void
    {
        Registry::registerService(DummyService::class);
        Registry::unregisterService(DummyService::class);

        static::assertNotContains(DummyService::class, Registry::getServices());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset registry state
        foreach (Registry::getServices() as $service) {
            Registry::unregisterService($service);
        }
    }
}
