<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Service;

/*
 * This file is part of the Composer plugin "eliashaeussler/composer-update-check".
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

use Composer\IO\NullIO;
use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\IO\Style;
use EliasHaeussler\ComposerUpdateCheck\IO\Verbosity;
use EliasHaeussler\ComposerUpdateCheck\Options;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use Spatie\Emoji\Emoji;
use Spatie\Emoji\Exceptions\UnknownCharacter;

/**
 * AbstractService.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
abstract class AbstractService implements ServiceInterface
{
    /**
     * @var OutputBehavior
     */
    protected $behavior;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @param array<string, mixed> $configuration
     */
    public static function isEnabled(array $configuration): bool
    {
        $identifier = static::getIdentifier();
        $envVariable = strtoupper($identifier.'_enable');
        $extra = $configuration[strtolower($identifier)] ?? null;

        if (false !== getenv($envVariable)) {
            return (bool) getenv($envVariable);
        }

        return is_array($extra) && (bool) ($extra['enable'] ?? false);
    }

    abstract protected static function getName(): string;

    public function report(UpdateCheckResult $result): bool
    {
        // Fall back to default output behavior if no custom behavior is defined
        if (null === $this->behavior) {
            $this->behavior = $this->getDefaultBehavior();
        }

        $outdatedPackages = $result->getOutdatedPackages();

        // Do not send report if packages are up to date
        if ([] === $outdatedPackages) {
            if (!$this->behavior->style->isJson()) {
                $this->behavior->io->write(
                    sprintf('%s Skipped %s report', Emoji::prohibited(), static::getName())
                );
            }

            return true;
        }

        $successful = $this->sendReport($result);

        // Print report state
        if (!$successful) {
            $this->behavior->io->writeError(
                sprintf('%s <error>Error during %s report</error>', Emoji::crossMark(), static::getName())
            );

            return false;
        }

        if (!$this->behavior->style->isJson()) {
            try {
                $checkMark = Emoji::getCharacter('checkMark');
            } catch (UnknownCharacter $e) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $checkMark = Emoji::getCharacter('heavyCheckMark');
            }
            $this->behavior->io->write(
                sprintf('%s <info>%s report was successful</info>', $checkMark, static::getName())
            );
        }

        return true;
    }

    abstract protected function sendReport(UpdateCheckResult $result): bool;

    public function setBehavior(OutputBehavior $behavior): ServiceInterface
    {
        $this->behavior = $behavior;

        return $this;
    }

    public function setOptions(Options $options): ServiceInterface
    {
        $this->options = $options;

        return $this;
    }

    private function getDefaultBehavior(): OutputBehavior
    {
        return new OutputBehavior(
            new Style(Style::NORMAL),
            new Verbosity(Verbosity::NORMAL),
            new NullIO()
        );
    }
}
