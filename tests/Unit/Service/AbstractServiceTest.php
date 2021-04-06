<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Tests\Unit\Service;

/*
 * This file is part of the Composer package "eliashaeussler/composer-update-reporter".
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
use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\Fixtures\Service\DummyService;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\OutputBehaviorTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;

/**
 * AbstractServiceTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class AbstractServiceTest extends AbstractTestCase
{
    use OutputBehaviorTrait;
    use TestEnvironmentTrait;

    /**
     * @var DummyService
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new DummyService();
        $this->subject->setBehavior($this->getDefaultBehavior());
    }

    /**
     * @test
     * @dataProvider isEnabledReturnsStateOfAvailabilityDataProvider
     *
     * @param $environmentVariable
     */
    public function isEnabledReturnsStateOfAvailability(array $configuration, $environmentVariable, bool $expected): void
    {
        $this->modifyEnvironmentVariable('DUMMY_ENABLE', $environmentVariable);

        static::assertSame($expected, DummyService::isEnabled($configuration));
    }

    /**
     * @test
     */
    public function reportUsesDefaultBehaviorIfNoBehaviorIsDefined(): void
    {
        $this->behavior->io = new NullIO();
        $this->subject->unsetBehavior();

        $this->subject->report(new UpdateCheckResult([]));

        static::assertEquals($this->behavior, $this->subject->getBehavior());
    }

    /**
     * @test
     */
    public function reportSkipsReportIfNoPackagesAreOutdated(): void
    {
        $result = new UpdateCheckResult([]);

        static::assertTrue($this->subject->report($result));
        static::assertStringContainsString('Skipped Dummy report', $this->getIO()->getOutput());
    }

    /**
     * @test
     */
    public function reportsPrintsErrorOnErroneousReport(): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5'),
        ]);

        DummyService::$successful = false;

        static::assertFalse($this->subject->report($result));
        static::assertStringContainsString('Error during Dummy report', $this->getIO()->getOutput());
    }

    /**
     * @test
     */
    public function reportSendsUpdateReportSuccessfully(): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5'),
        ]);

        static::assertTrue($this->subject->report($result));
        static::assertStringContainsString('Dummy report was successful', $this->getIO()->getOutput());
    }

    public function isEnabledReturnsStateOfAvailabilityDataProvider(): array
    {
        return [
            'no configuration and no environment variable' => [
                [],
                null,
                false,
            ],
            'empty configuration and no environment variable' => [
                [
                    'dummy' => [],
                ],
                null,
                false,
            ],
            'truthy configuration and no environment variable' => [
                [
                    'dummy' => [
                        'enable' => true,
                    ],
                ],
                null,
                true,
            ],
            'truthy configuration and falsy environment variable' => [
                [
                    'dummy' => [
                        'enable' => true,
                    ],
                ],
                '0',
                false,
            ],
            'falsy configuration and truthy environment variable' => [
                [
                    'dummy' => [
                        'enable' => false,
                    ],
                ],
                '1',
                true,
            ],
            'empty configuration and truthy environment variable' => [
                [
                    'dummy' => [],
                ],
                '1',
                true,
            ],
            'no configuration and truthy environment variable' => [
                [],
                '1',
                true,
            ],
        ];
    }

    protected function tearDown(): void
    {
        // Restore initial state of dummy service
        DummyService::reset();

        parent::tearDown();
    }
}
