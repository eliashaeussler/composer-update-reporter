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
use EliasHaeussler\ComposerUpdateReporter\Service\Email;
use EliasHaeussler\ComposerUpdateReporter\Service\GitLab;
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
     * @var bool
     */
    private $json;

    /**
     * @var string[]
     */
    private $registeredServices;

    /**
     * @var array
     */
    private $configuration;

    public function __construct(Composer $composer, IOInterface $io, bool $json = false)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->json = $json;
        $this->registeredServices = $this->getDefaultServices();
        $this->configuration = $this->resolveConfiguration();
    }

    public function report(UpdateCheckResult $result): void
    {
        $services = $this->buildServicesFromConfiguration();
        foreach ($services as $service) {
            $service->setJson($this->json);
            $service->report($result, $this->io);
        }
    }

    /**
     * @return ServiceInterface[]
     */
    private function buildServicesFromConfiguration(): array
    {
        $services = [];
        /** @var ServiceInterface $registeredService */
        foreach ($this->registeredServices as $registeredService) {
            if (!in_array(ServiceInterface::class, class_implements($registeredService), true)) {
                throw new \InvalidArgumentException(
                    sprintf('Service "%s" must implement "%s".', $registeredService, ServiceInterface::class),
                    1600814017
                );
            }
            if ($registeredService::isEnabled($this->configuration)) {
                $service = $registeredService::fromConfiguration($this->configuration);
                $service->setJson($this->json);
                $services[] = $service;
            }
        }
        return $services;
    }

    public function setJson(bool $json): self
    {
        $this->json = $json;
        return $this;
    }

    public function setRegisteredServices(array $registeredServices): self
    {
        $this->registeredServices = $registeredServices;
        return $this;
    }

    private function getDefaultServices(): array
    {
        return [
            Email::class,
            GitLab::class,
            Mattermost::class,
        ];
    }

    private function resolveConfiguration(): array
    {
        return $this->composer->getPackage()->getExtra()['update-check'] ?? [];
    }
}
