<?php
declare(strict_types=1);
namespace EliasHaeussler\ComposerUpdateReporter\Tests\Unit\Service;

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

use Composer\IO\BufferIO;
use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Service\BetterUptime;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Prophecy\Argument;

/**
 * BetterUptimeTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class BetterUptimeTest extends AbstractTestCase
{
    use TestEnvironmentTrait;

    /**
     * @var BetterUptime
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new BetterUptime(
            'foo',
            'foo@example.org',
            ['foo' => 'baz'],
            new Uri('https://example.org')
        );
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAuthTokenIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1606157503);

        new BetterUptime('', 'foo@example.org');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfRequesterIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1606161641);

        new BetterUptime('foo', '');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfRequesterIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1606161665);

        new BetterUptime('foo', 'baz');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1606157479);

        new BetterUptime('foo', 'foo@example.org', [], new Uri(''));
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1606157488);

        new BetterUptime('foo', 'foo@example.org', [], new Uri('foo'));
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfAuthTokenIsNotSet(): void
    {
        $this->modifyEnvironmentVariable('BETTER_UPTIME_AUTH_TOKEN');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1606157616);

        $configuration = [
            'betterUptime' => [
                'requester' => 'foo@example.org',
            ],
        ];
        BetterUptime::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfRequesterIsNotSet(): void
    {
        $this->modifyEnvironmentVariable('BETTER_UPTIME_REQUESTER');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1606161576);

        $configuration = [
            'betterUptime' => [
                'authToken' => 'foo',
            ],
        ];
        BetterUptime::fromConfiguration($configuration);
    }

    /**
     * @dataProvider fromConfigurationThrowsExceptionIfOptionsFromComposerJsonAreInvalidDataProvider
     * @test
     * @param string|array|null $options
     * @param int $expectedExceptionCode
     */
    public function fromConfigurationThrowsExceptionIfOptionsFromComposerJsonAreInvalid(
        $options,
        int $expectedExceptionCode
    ): void
    {
        $this->modifyEnvironmentVariable('BETTER_UPTIME_AUTH_TOKEN', 'foo');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_REQUESTER', 'foo@example.org');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_OPTIONS');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_URL', 'https://example.org');

        $configuration = [
            'betterUptime' => [
                'options' => $options,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);

        BetterUptime::fromConfiguration($configuration);
    }

    /**
     * @dataProvider fromConfigurationThrowsExceptionIfOptionsFromEnvironmentVariablesAreInvalidDataProvider
     * @test
     * @param string $options
     * @param int $expectedExceptionCode
     */
    public function fromConfigurationThrowsExceptionIfOptionsFromEnvironmentVariablesAreInvalid(
        string $options,
        int $expectedExceptionCode
    ): void
    {
        $this->modifyEnvironmentVariable('BETTER_UPTIME_AUTH_TOKEN', 'foo');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_REQUESTER', 'foo@example.org');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_OPTIONS', $options);
        $this->modifyEnvironmentVariable('BETTER_UPTIME_URL', 'https://example.org');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedExceptionCode);

        BetterUptime::fromConfiguration([]);
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromComposerJson(): void
    {
        $this->modifyEnvironmentVariable('BETTER_UPTIME_AUTH_TOKEN');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_REQUESTER');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_OPTIONS');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_URL');

        $configuration = [
            'betterUptime' => [
                'authToken' => 'foo',
                'requester' => 'foo@example.org',
                'options' => ['foo' => 'baz'],
                'url' => 'https://example.org',
            ],
        ];
        /** @var BetterUptime $subject */
        $subject = BetterUptime::fromConfiguration($configuration);

        static::assertInstanceOf(BetterUptime::class, $subject);
        static::assertSame('foo', $subject->getAuthToken());
        static::assertSame('foo@example.org', $subject->getRequester());
        static::assertSame(['foo' => 'baz'], $subject->getOptions());
        static::assertSame('https://example.org', (string)$subject->getUri());
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('BETTER_UPTIME_AUTH_TOKEN', 'foo');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_REQUESTER', 'foo@example.org');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_OPTIONS', 'foo=baz');
        $this->modifyEnvironmentVariable('BETTER_UPTIME_URL', 'https://example.org');

        /** @var BetterUptime $subject */
        $subject = BetterUptime::fromConfiguration([]);

        static::assertInstanceOf(BetterUptime::class, $subject);
        static::assertSame('foo', $subject->getAuthToken());
        static::assertSame('foo@example.org', $subject->getRequester());
        static::assertSame(['foo' => 'baz'], $subject->getOptions());
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
        $this->modifyEnvironmentVariable('BETTER_UPTIME_ENABLE', $environmentVariable);

        static::assertSame($expected, BetterUptime::isEnabled($configuration));
    }

    /**
     * @test
     * @throws GuzzleException
     */
    public function reportSkipsReportIfNoPackagesAreOutdated(): void
    {
        $result = new UpdateCheckResult([]);
        $io = new BufferIO();

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('Skipped BetterUptime report.', $io->getOutput());
    }

    /**
     * @test
     * @dataProvider reportSendsUpdateReportSuccessfullyDataProvider
     * @param bool $insecure
     * @param string $expectedSecurityNotice
     * @throws GuzzleException
     * @throws \ReflectionException
     */
    public function reportSendsUpdateReportSuccessfully(bool $insecure, string $expectedSecurityNotice): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5', $insecure),
        ]);
        $io = new BufferIO();

        // Prophesize Client
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->post('', [
            RequestOptions::JSON => [
                'requester_email' => 'foo@example.org',
                'summary' => '1 outdated package',
                'description' => 'foo/foo (1.0.0' . $expectedSecurityNotice . ' => 1.0.5)',
                'foo' => 'baz',
            ],
        ])->willReturn(new Response())->shouldBeCalledOnce();

        // Inject client prophecy into subject
        $reflectionClass = new \ReflectionClass($this->subject);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->subject, $clientProphecy->reveal());

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('BetterUptime report was successful.', $io->getOutput());
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
        static::assertStringContainsString('Error during BetterUptime report.', $io->getOutput());
    }

    public function fromConfigurationThrowsExceptionIfGitLabUrlIsNotSetDataProvider(): array
    {
        return [
            'no service configuration' => [
                [],
            ],
            'available service configuration' => [
                [
                    'gitlab' => [],
                ],
            ],
            'missing URL configuration' => [
                [
                    'gitlab' => [
                        'authKey' => 'foo',
                    ],
                ],
            ],
        ];
    }

    public function fromConfigurationThrowsExceptionIfOptionsFromComposerJsonAreInvalidDataProvider(): array
    {
        return [
            'null' => [
                null,
                1606157858,
            ],
            'missing option value in string representation' => [
                'foo',
                1606158226,
            ],
            'empty option key in string representation' => [
                '=foo',
                1606164612,
            ],
            'unsupported option value in array representation' => [
                [
                    null,
                ],
                1606157977
            ],
            'missing option value in array representation' => [
                [
                    'foo',
                ],
                1606158226,
            ],
            'empty option key in array representation' => [
                [
                    '=foo',
                ],
                1606164612
            ],
        ];
    }

    public function fromConfigurationThrowsExceptionIfOptionsFromEnvironmentVariablesAreInvalidDataProvider(): array
    {
        return [
            'missing option value' => [
                'foo',
                1606158226,
            ],
            'empty option key' => [
                '=foo',
                1606164612,
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
                    'betterUptime' => [],
                ],
                null,
                false,
            ],
            'truthy configuration and no environment variable' => [
                [
                    'betterUptime' => [
                        'enable' => true,
                    ],
                ],
                null,
                true,
            ],
            'truthy configuration and falsy environment variable' => [
                [
                    'betterUptime' => [
                        'enable' => true,
                    ],
                ],
                '0',
                true,
            ],
            'falsy configuration and truthy environment variable' => [
                [
                    'betterUptime' => [
                        'enable' => false,
                    ],
                ],
                '1',
                true,
            ],
            'empty configuration and truthy environment variable' => [
                [
                    'betterUptime' => [],
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
                ' [insecure]',
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
        parent::tearDown();
    }
}
