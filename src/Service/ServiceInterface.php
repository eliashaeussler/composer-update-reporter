<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Service;

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

use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\Options;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Exception\MissingConfigurationException;

/**
 * ServiceInterface.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
interface ServiceInterface
{
    public static function getIdentifier(): string;

    /**
     * @param array<string, mixed> $configuration
     *
     * @throws MissingConfigurationException
     */
    public static function fromConfiguration(array $configuration): self;

    /**
     * @param array<string, mixed> $configuration
     */
    public static function isEnabled(array $configuration): bool;

    public function report(UpdateCheckResult $result): bool;

    public function setBehavior(OutputBehavior $behavior): self;

    public function setOptions(Options $options): self;

    public function setProjectName(string $projectName): self;
}
