<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Traits;

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

use EliasHaeussler\ComposerUpdateReporter\Exception\MissingConfigurationException;
use EliasHaeussler\ComposerUpdateReporter\Util;

/**
 * ServiceConfigurationTrait.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
trait ServiceConfigurationTrait
{
    /**
     * @param array<string, mixed> $configuration
     *
     * @return mixed
     *
     * @throws MissingConfigurationException
     */
    protected static function resolveConfigurationKey(array $configuration, string $key)
    {
        $serviceIdentifier = static::getIdentifier();
        $serviceConfiguration = $configuration[$serviceIdentifier] ?? null;

        if (is_array($serviceConfiguration) && isset($serviceConfiguration[$key])) {
            return $serviceConfiguration[$key];
        }

        $underscoredKey = Util::camelCaseToUnderscored($key);
        $envName = strtoupper($serviceIdentifier.'_'.$underscoredKey);

        if (false !== getenv($envName)) {
            return getenv($envName);
        }

        throw MissingConfigurationException::create($serviceIdentifier, $key);
    }
}
