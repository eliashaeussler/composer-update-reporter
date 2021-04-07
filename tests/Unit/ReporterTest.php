<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Tests\Unit;

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
use Composer\Factory;
use Composer\IO\NullIO;
use EliasHaeussler\ComposerUpdateCheck\IO\OutputBehavior;
use EliasHaeussler\ComposerUpdateCheck\IO\Style;
use EliasHaeussler\ComposerUpdateCheck\IO\Verbosity;
use EliasHaeussler\ComposerUpdateCheck\Options;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Exception\InvalidServiceException;
use EliasHaeussler\ComposerUpdateReporter\Registry;
use EliasHaeussler\ComposerUpdateReporter\Reporter;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\Fixtures\Service\DummyService;

/**
 * ReporterTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class ReporterTest extends AbstractTestCase
{
    use TestApplicationTrait;

    /**
     * @var Reporter
     */
    protected $subject;

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @throws InvalidServiceException
     */
    protected function setUp(): void
    {
        $this->goToTestDirectory();

        DummyService::$enabled = true;
        Registry::registerService(DummyService::class);

        $this->composer = Factory::create(new NullIO());
        $this->subject = new Reporter($this->composer);
    }

    /**
     * @test
     */
    public function reportDoesNotIncludeDisabledServicesForReporting(): void
    {
        DummyService::$enabled = false;

        $this->subject->report(new UpdateCheckResult([]));

        static::assertFalse(DummyService::$reportWasExecuted);
    }

    /**
     * @test
     */
    public function reportIncludesEnabledServicesForReporting(): void
    {
        $this->subject->report(new UpdateCheckResult([]));

        static::assertTrue(DummyService::$reportWasExecuted);
    }

    /**
     * @test
     */
    public function reportForwardsBehaviorToServices(): void
    {
        $behavior = new OutputBehavior(new Style(), new Verbosity(), new NullIO());

        $this->subject->setBehavior($behavior);
        $this->subject->report(new UpdateCheckResult([]));

        static::assertSame($behavior, DummyService::$customBehavior);
    }

    /**
     * @test
     */
    public function reportForwardsOptionsToServices(): void
    {
        DummyService::$enabled = true;

        $options = new Options();

        $this->subject->setOptions($options);
        $this->subject->report(new UpdateCheckResult([]));

        static::assertSame($options, DummyService::$customOptions);
    }

    /**
     * @test
     */
    public function reportForwardsProjectNameToServices(): void
    {
        $this->subject->report(new UpdateCheckResult([]));

        static::assertSame('foo/baz', DummyService::$customProjectName);
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();

        // Restore initial state of dummy service
        DummyService::reset();

        parent::tearDown();
    }
}
