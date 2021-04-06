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
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use EliasHaeussler\ComposerUpdateCheck\Event\PostUpdateCheckEvent;

/**
 * Plugin.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 * @codeCoverageIgnore
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Reporter
     */
    private $reporter;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->reporter = new Reporter($composer);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here. Just go ahead :)
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here. Just go ahead :)
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PostUpdateCheckEvent::NAME => [
                ['onPostUpdateCheck'],
            ],
        ];
    }

    public function onPostUpdateCheck(PostUpdateCheckEvent $event): void
    {
        $this->reporter->setBehavior($event->getBehavior());
        $this->reporter->setOptions($event->getOptions());
        $this->reporter->report($event->getUpdateCheckResult());
    }
}
