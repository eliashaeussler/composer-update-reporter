<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateReporter\Tests\Unit\Fixtures\Service;

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
use EliasHaeussler\ComposerUpdateReporter\Service\AbstractService;
use EliasHaeussler\ComposerUpdateReporter\Service\ServiceInterface;

/**
 * DummyService
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class DummyService extends AbstractService
{
    public static $enabled;
    public static $successful = true;
    public static $reportWasExecuted = false;
    public static $customBehavior;
    public static $customOptions;

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        return new self();
    }

    public static function reset(): void
    {
        static::$enabled = null;
        static::$successful = true;
        static::$reportWasExecuted = false;
        static::$customBehavior = null;
        static::$customOptions = null;
    }

    public static function isEnabled(array $configuration): bool
    {
        if (is_bool(static::$enabled)) {
            return static::$enabled;
        }

        return parent::isEnabled($configuration);
    }

    public function report(UpdateCheckResult $result): bool
    {
        static::$reportWasExecuted = true;
        return parent::report($result);
    }

    protected function sendReport(UpdateCheckResult $result): bool
    {
        return static::$successful;
    }

    public function setBehavior(OutputBehavior $behavior): ServiceInterface
    {
        static::$customBehavior = $behavior;
        return parent::setBehavior($behavior);
    }

    public function getBehavior(): OutputBehavior
    {
        return $this->behavior;
    }

    public function unsetBehavior(): void
    {
        $this->behavior = null;
    }

    public function setOptions(Options $options): ServiceInterface
    {
        static::$customOptions = $options;
        return parent::setOptions($options);
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    protected static function getIdentifier(): string
    {
        return 'dummy';
    }

    protected static function getName(): string
    {
        return 'Dummy';
    }
}
