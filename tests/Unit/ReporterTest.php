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

    protected function setUp(): void
    {
        $this->goToTestDirectory();

        DummyService::$enabled = true;

        $this->composer = Factory::create(new NullIO());
        $this->subject = new Reporter($this->composer);
    }

    /**
     * @test
     */
    public function reportThrowsExceptionIfInvalidServiceIsRegistered(): void
    {
        $this->subject->setRegisteredServices([\Exception::class]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600814017);

        $this->subject->report(new UpdateCheckResult([]));
    }

    /**
     * @test
     */
    public function reportDoesNotIncludeDisabledServicesForReporting(): void
    {
        DummyService::$enabled = false;

        $this->subject->setRegisteredServices([DummyService::class]);
        $this->subject->report(new UpdateCheckResult([]));

        static::assertFalse(DummyService::$reportWasExecuted);
    }

    /**
     * @test
     */
    public function reportIncludesEnabledServicesForReporting(): void
    {
        $this->subject->setRegisteredServices([DummyService::class]);
        $this->subject->report(new UpdateCheckResult([]));

        static::assertTrue(DummyService::$reportWasExecuted);
    }

    /**
     * @test
     */
    public function setBehaviorForwardsBehaviorToServices(): void
    {
        $behavior = new OutputBehavior(new Style(), new Verbosity(), new NullIO());

        $this->subject->setBehavior($behavior);
        $this->subject->setRegisteredServices([DummyService::class]);
        $this->subject->report(new UpdateCheckResult([]));

        static::assertSame($behavior, DummyService::$customBehavior);
    }

    /**
     * @test
     */
    public function setOptionsForwardsOptionsToServices(): void
    {
        DummyService::$enabled = true;

        $options = new Options();

        $this->subject->setOptions($options);
        $this->subject->setRegisteredServices([DummyService::class]);
        $this->subject->report(new UpdateCheckResult([]));

        static::assertSame($options, DummyService::$customOptions);
    }

    protected function tearDown(): void
    {
        $this->goBackToInitialDirectory();

        // Restore initial state of dummy service
        DummyService::reset();

        parent::tearDown();
    }
}
