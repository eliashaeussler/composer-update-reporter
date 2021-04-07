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
use Composer\IO\NullIO;
use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\IO\Style;
use EliasHaeussler\ComposerUpdateCheck\IO\Verbosity;
use EliasHaeussler\ComposerUpdateCheck\Options;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Service\ServiceInterface;

/**
 * Reporter.
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
     * @var OutputBehavior
     */
    private $behavior;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var array<string, mixed>
     */
    private $configuration;

    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
        $this->behavior = $this->getDefaultBehavior();
        $this->options = new Options();
        $this->configuration = $this->resolveConfiguration();
    }

    public function report(UpdateCheckResult $result): void
    {
        $services = $this->buildServicesFromConfiguration();
        foreach ($services as $service) {
            $service->report($result);
        }
    }

    /**
     * @return ServiceInterface[]
     */
    private function buildServicesFromConfiguration(): array
    {
        $services = [];

        // Resolve project name from root composer.json
        $projectName = $this->composer->getPackage()->getName();
        if ('__root__' === $projectName) {
            $projectName = null;
        }

        /** @var ServiceInterface $registeredService */
        foreach (Registry::getServices() as $registeredService) {
            if (!$registeredService::isEnabled($this->configuration)) {
                continue;
            }

            $service = $registeredService::fromConfiguration($this->configuration);
            $service->setBehavior($this->behavior);
            $service->setOptions($this->options);

            if (!empty($projectName)) {
                $service->setProjectName($projectName);
            }

            $services[] = $service;
        }

        return $services;
    }

    public function setBehavior(OutputBehavior $behavior): void
    {
        $this->behavior = $behavior;
    }

    public function setOptions(Options $options): void
    {
        $this->options = $options;
    }

    private function getDefaultBehavior(): OutputBehavior
    {
        return new OutputBehavior(
            new Style(Style::NORMAL),
            new Verbosity(Verbosity::NORMAL),
            new NullIO()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveConfiguration(): array
    {
        return $this->composer->getPackage()->getExtra()['update-check'] ?? [];
    }
}
