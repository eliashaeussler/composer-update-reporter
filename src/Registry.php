<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter;

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
use EliasHaeussler\ComposerUpdateReporter\Service\ServiceInterface;

/**
 * Registry.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
final class Registry
{
    /**
     * @var string[]
     */
    private static $services = [];

    /**
     * @throws InvalidServiceException
     */
    public static function registerService(string $className): void
    {
        if (!in_array(ServiceInterface::class, class_implements($className), true)) {
            throw InvalidServiceException::create($className);
        }

        if (!in_array($className, self::$services, true)) {
            self::$services[] = $className;
        }
    }

    public static function unregisterService(string $className): void
    {
        $key = array_search($className, self::$services, true);

        if (false !== $key) {
            unset(self::$services[$key]);
        }
    }

    /**
     * @return string[]
     */
    public static function getServices(): array
    {
        return self::$services;
    }
}
