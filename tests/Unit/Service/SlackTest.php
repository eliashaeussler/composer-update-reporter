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
use EliasHaeussler\ComposerUpdateReporter\Service\Slack;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\ClientMockTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use Nyholm\Psr7\Uri;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * SlackTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class SlackTest extends AbstractTestCase
{
    use ClientMockTrait;
    use TestEnvironmentTrait;

    /**
     * @var Slack
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new Slack(new Uri('https://example.org'));
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1602496937);

        new Slack(new Uri(''));
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1602496941);

        new Slack(new Uri('foo'));
    }

    /**
     * @test
     * @dataProvider fromConfigurationThrowsExceptionIfSlackUrlIsNotSetDataProvider
     * @param array $configuration
     */
    public function fromConfigurationThrowsExceptionIfSlackUrlIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('SLACK_URL');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1602496964);

        Slack::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromComposerJson(): void
    {
        $this->modifyEnvironmentVariable('SLACK_URL');

        $configuration = [
            'slack' => [
                'url' => 'https://example.org',
            ],
        ];
        /** @var Slack $subject */
        $subject = Slack::fromConfiguration($configuration);

        static::assertInstanceOf(Slack::class, $subject);
        static::assertSame('https://example.org', (string)$subject->getUri());
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('SLACK_URL', 'https://example.org');

        /** @var Slack $subject */
        $subject = Slack::fromConfiguration([]);

        static::assertInstanceOf(Slack::class, $subject);
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
        $this->modifyEnvironmentVariable('SLACK_ENABLE', $environmentVariable);

        static::assertSame($expected, Slack::isEnabled($configuration));
    }

    /**
     * @test
     * @throws ClientExceptionInterface
     */
    public function reportSkipsReportIfNoPackagesAreOutdated(): void
    {
        $result = new UpdateCheckResult([]);
        $io = new BufferIO();

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('Skipped Slack report.', $io->getOutput());
    }

    /**
     * @dataProvider reportSendsUpdateReportSuccessfullyDataProvider
     * @test
     * @param bool $insecure
     * @param array|null $expectedSecurityPayload
     * @throws ClientExceptionInterface
     */
    public function reportSendsUpdateReportSuccessfully(bool $insecure, ?array $expectedSecurityPayload): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5', $insecure),
        ]);
        $io = new BufferIO();

        $expectedFields = [
            [
                'type' => 'mrkdwn',
                'text' => '*Package*',
            ],
            [
                'type' => 'mrkdwn',
                'text' => '<https://packagist.org/packages/foo/foo#1.0.5|foo/foo>',
            ],
            [
                'type' => 'mrkdwn',
                'text' => '*Current version*',
            ],
            [
                'type' => 'mrkdwn',
                'text' => '`1.0.0`',
            ],
            [
                'type' => 'mrkdwn',
                'text' => '*New version*',
            ],
            [
                'type' => 'mrkdwn',
                'text' => '*`1.0.5`*',
            ],
        ];
        if ($expectedSecurityPayload !== null) {
            $expectedFields = array_merge($expectedFields, $expectedSecurityPayload);
        }
        $this->subject->setClient($this->getClient());
        $this->mockedResponse = new MockResponse();

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('Slack report was successful.', $io->getOutput());

        $expectedPayloadSubset = [
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => '1 outdated package',
                    ],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'section',
                    'fields' => $expectedFields,
                ],
            ],
        ];
        $this->assertPayloadOfLastRequestContainsSubset($expectedPayloadSubset);
    }

    /**
     * @test
     * @throws ClientExceptionInterface
     */
    public function reportsPrintsErrorOnErroneousReport(): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5'),
        ]);
        $io = new BufferIO();

        $this->subject->setClient($this->getClient());
        $this->mockHandler->append(new Response(404));

        static::assertFalse($this->subject->report($result, $io));
        static::assertStringContainsString('Error during Slack report.', $io->getOutput());
    }

    public function fromConfigurationThrowsExceptionIfSlackUrlIsNotSetDataProvider(): array
    {
        return [
            'no service configuration' => [
                [],
            ],
            'available service configuration' => [
                [
                    'slack' => [],
                ],
            ],
            'missing URL configuration' => [
                [
                    'slack' => [
                        'enable' => true,
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
                    'slack' => [],
                ],
                null,
                false,
            ],
            'truthy configuration and no environment variable' => [
                [
                    'slack' => [
                        'enable' => true,
                    ],
                ],
                null,
                true,
            ],
            'truthy configuration and falsy environment variable' => [
                [
                    'slack' => [
                        'enable' => true,
                    ],
                ],
                '0',
                true,
            ],
            'falsy configuration and truthy environment variable' => [
                [
                    'slack' => [
                        'enable' => false,
                    ],
                ],
                '1',
                true,
            ],
            'empty configuration and truthy environment variable' => [
                [
                    'slack' => [],
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
                null,
            ],
            'insecure package' => [
                true,
                [
                    [
                        'type' => 'mrkdwn',
                        'text' => '*Security state*',
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => '*Package is insecure* :warning:',
                    ],
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
        parent::tearDown();
    }
}
