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

use Composer\IO\BufferIO;
use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Service\Teams;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Prophecy\Argument;
use Spatie\Emoji\Emoji;

/**
 * TeamsTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class TeamsTest extends AbstractTestCase
{
    use TestEnvironmentTrait;

    /**
     * @var Teams
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new Teams(new Uri('https://example.org'));
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1612865642);

        new Teams(new Uri(''));
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1612865646);

        new Teams(new Uri('foo'));
    }

    /**
     * @test
     * @dataProvider fromConfigurationThrowsExceptionIfTeamsUrlIsNotSetDataProvider
     * @param array $configuration
     */
    public function fromConfigurationThrowsExceptionIfTeamsUrlIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('TEAMS_URL');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1612865679);

        Teams::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromComposerJson(): void
    {
        $this->modifyEnvironmentVariable('TEAMS_URL');

        $configuration = [
            'teams' => [
                'url' => 'https://example.org',
            ],
        ];
        /** @var Teams $subject */
        $subject = Teams::fromConfiguration($configuration);

        static::assertInstanceOf(Teams::class, $subject);
        static::assertSame('https://example.org', (string)$subject->getUri());
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('TEAMS_URL', 'https://example.org');

        /** @var Teams $subject */
        $subject = Teams::fromConfiguration([]);

        static::assertInstanceOf(Teams::class, $subject);
        static::assertSame('https://example.org', (string)$subject->getUri());
    }

    /**
     * @test
     * @dataProvider isEnabledReturnsStateOfAvailabilityDataProvider
     * @param array $configuration
     * @param $environmentVariable
     * @param bool $expected
     */
    public function isEnabledReturnsStateOfAvailability(array $configuration, $environmentVariable, bool $expected): void
    {
        $this->modifyEnvironmentVariable('TEAMS_ENABLE', $environmentVariable);

        static::assertSame($expected, Teams::isEnabled($configuration));
    }

    /**
     * @test
     */
    public function reportSkipsReportIfNoPackagesAreOutdated(): void
    {
        $result = new UpdateCheckResult([]);
        $io = new BufferIO();

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('Skipped MS Teams report.', $io->getOutput());
    }

    /**
     * @test
     * @dataProvider reportSendsUpdateReportSuccessfullyDataProvider
     * @param bool $insecure
     * @param string $expectedSecurityNotice
     * @throws GuzzleException
     */
    public function reportSendsUpdateReportSuccessfully(bool $insecure, string $expectedSecurityNotice): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5', $insecure),
        ]);
        $io = new BufferIO();

        // Prophesize Client
        $clientProphecy = $this->prophesize(Client::class);
        /** @noinspection PhpParamsInspection */
        $clientProphecy->post('', Argument::that(
            function (array $argument) use ($expectedSecurityNotice) {
                $json = $argument[RequestOptions::JSON];

                static::assertSame(sprintf('%s 1 outdated package', Emoji::policeCarLight()), $json['title']);
                static::assertSame('1 package is outdated', $json['summary']);

                $text = $json['sections'][0]['text'];
                $expected = implode(PHP_EOL . PHP_EOL, [
                    '# [foo/foo](https://packagist.org/packages/foo/foo#1.0.5)',
                    'Current version: **1.0.0**' . $expectedSecurityNotice,
                    'New version: **1.0.5**',
                ]);
                static::assertSame($expected, $text);
                return true;
            }
        ))->willReturn(new Response())->shouldBeCalledOnce();

        // Inject client prophecy into subject
        $reflectionClass = new \ReflectionClass($this->subject);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->subject, $clientProphecy->reveal());

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('MS Teams report was successful.', $io->getOutput());
    }

    /**
     * @test
     * @throws GuzzleException
     * @throws \ReflectionException
     */
    public function reportsPrintsErrorOnErroneousReport(): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5'),
        ]);
        $io = new BufferIO();

        // Prophesize Client
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->post('', Argument::type('array'))
            ->willReturn(new Response(404))
            ->shouldBeCalledOnce();

        // Inject client prophecy into subject
        $reflectionClass = new \ReflectionClass($this->subject);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->subject, $clientProphecy->reveal());

        static::assertFalse($this->subject->report($result, $io));
        static::assertStringContainsString('Error during MS Teams report.', $io->getOutput());
    }

    public function fromConfigurationThrowsExceptionIfTeamsUrlIsNotSetDataProvider(): array
    {
        return [
            'no service configuration' => [
                [],
            ],
            'available service configuration' => [
                [
                    'teams' => [],
                ],
            ],
            'missing URL configuration' => [
                [
                    'teams' => [
                        'foo' => 'baz',
                    ],
                ],
            ],
        ];
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
                    'teams' => [],
                ],
                null,
                false,
            ],
            'truthy configuration and no environment variable' => [
                [
                    'teams' => [
                        'enable' => true,
                    ],
                ],
                null,
                true,
            ],
            'truthy configuration and falsy environment variable' => [
                [
                    'teams' => [
                        'enable' => true,
                    ],
                ],
                '0',
                true,
            ],
            'falsy configuration and truthy environment variable' => [
                [
                    'teams' => [
                        'enable' => false,
                    ],
                ],
                '1',
                true,
            ],
            'empty configuration and truthy environment variable' => [
                [
                    'teams' => [],
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

    public function reportSendsUpdateReportSuccessfullyDataProvider(): array
    {
        return [
            'secure package' => [
                false,
                '',
            ],
            'insecure package' => [
                true,
                sprintf(' (%s insecure)', Emoji::warning()),
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
        parent::tearDown();
    }
}
