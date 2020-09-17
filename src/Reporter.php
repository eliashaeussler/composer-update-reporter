<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateReporter;

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

use Composer\Composer;
use Composer\IO\IOInterface;
use EliasHaeussler\ComposerUpdateCheck\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Service\Mattermost;
use EliasHaeussler\ComposerUpdateReporter\Service\ServiceInterface;

/**
 * Reporter
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Reporter
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var array
     */
    private $configuration;

    public function __construct(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->configuration = $this->resolveConfiguration();
    }

    public function report(UpdateCheckResult $result): void
    {
        $services = $this->buildServicesFromConfiguration();
        foreach ($services as $service) {
            $service->report($result, $this->io);
        }
    }

    /**
     * @return ServiceInterface[]
     */
    private function buildServicesFromConfiguration(): array
    {
        $registeredServices = [
            Mattermost::class,
        ];
        $services = [];
        foreach ($registeredServices as $registeredService) {
            $services[] = call_user_func([$registeredService, 'fromConfiguration'], $this->configuration);
        }
        return $services;
    }

    private function resolveConfiguration(): array
    {
        return $this->composer->getPackage()->getExtra()['update-check'] ?? [];
    }
}
