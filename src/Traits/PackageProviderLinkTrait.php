<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateReporter\Traits;

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

/**
 * PackageProviderLinkTrait
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
trait PackageProviderLinkTrait
{
    private function getProviderLink(OutdatedPackage $outdatedPackage): string
    {
        if (method_exists($outdatedPackage, 'getProviderLink')) {
            return (string)$outdatedPackage->getProviderLink();
        }
        // Fallback for BC: Manually build provider link
        return sprintf('https://packagist.org/packages/%s', $outdatedPackage->getName());
    }
}
