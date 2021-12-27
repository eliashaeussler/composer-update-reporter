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

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Exception\MissingConfigurationException;
use EliasHaeussler\ComposerUpdateReporter\Service\Slack;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\ClientMockTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\OutputBehaviorTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use Nyholm\Psr7\Uri;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * SlackTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class SlackTest extends AbstractTestCase
{
    use ClientMockTrait;
    use OutputBehaviorTrait;
    use TestEnvironmentTrait;

    /**
     * @var Slack
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new Slack(new Uri('https://example.org'));
        $this->subject->setBehavior($this->getDefaultBehavior());
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
     *
     * @param array<string, mixed> $configuration
     */
    public function fromConfigurationThrowsExceptionIfSlackUrlIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('SLACK_URL');

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

        Slack::fromConfiguration($configuration);
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
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
        static::assertSame('https://example.org', (string) $subject->getUri());
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('SLACK_URL', 'https://example.org');

        /** @var Slack $subject */
        $subject = Slack::fromConfiguration([]);

        static::assertInstanceOf(Slack::class, $subject);
        static::assertSame('https://example.org', (string) $subject->getUri());
    }

    /**
     * @dataProvider reportSendsUpdateReportSuccessfullyDataProvider
     * @test
     */
    public function reportSendsUpdateReportSuccessfully(bool $insecure): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5', $insecure),
        ]);

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
                    'type' => 'section',
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => '<https://packagist.org/packages/foo/foo#1.0.5|foo/foo>',
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => '`1.0.0` → *`1.0.5`*',
                        ],
                    ],
                ],
            ],
        ];

        if ($insecure) {
            $expectedPayloadSubset['blocks'][1]['fields'][1]['text'] .= ' :rotating_light:';
            $expectedPayloadSubset['blocks'][] = [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => 'Package versions with :rotating_light: are marked as insecure',
                    ],
                ],
            ];
        }

        $this->subject->setClient($this->getClient());
        $this->mockedResponse = new MockResponse();

        static::assertTrue($this->subject->report($result));
        static::assertStringContainsString('Slack report was successful', $this->getIO()->getOutput());

        $this->assertPayloadOfLastRequestContainsSubset($expectedPayloadSubset);
    }

    /**
     * @test
     */
    public function reportSendsLimitedUpdateReportIfManyPackagesAreOutdated(): void
    {
        $outdatedPackage = new OutdatedPackage('foo/foo', '1.0.0', '1.0.5');
        $result = new UpdateCheckResult(array_fill(0, 50, $outdatedPackage));

        $expectedPayloadSubset = [
            'blocks' => array_merge(
                [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => '1 outdated package',
                        ],
                    ],
                ],
                array_fill(0, 47, [
                    'type' => 'section',
                    'fields' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => '<https://packagist.org/packages/foo/foo#1.0.5|foo/foo>',
                        ],
                        [
                            'type' => 'mrkdwn',
                            'text' => '`1.0.0` → *`1.0.5`*',
                        ],
                    ],
                ]),
                [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => '... and 3 more',
                        ],
                    ],
                ]
            ),
        ];

        $this->subject->setClient($this->getClient());
        $this->mockedResponse = new MockResponse();

        static::assertTrue($this->subject->report($result));
        static::assertStringContainsString('Slack report was successful', $this->getIO()->getOutput());

        $this->assertPayloadOfLastRequestContainsSubset($expectedPayloadSubset);
    }

    /**
     * @test
     */
    public function reportIncludesProjectNameInMessage(): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5'),
        ]);

        $this->subject->setProjectName('foo/baz');
        $this->subject->setClient($this->getClient());
        $this->mockedResponse = new MockResponse();

        static::assertTrue($this->subject->report($result));

        $expectedPayloadSubset = [
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'Project: *foo/baz*',
                    ],
                ],
            ],
        ];
        $this->assertPayloadOfLastRequestContainsSubset($expectedPayloadSubset);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>}>
     */
    public function fromConfigurationThrowsExceptionIfSlackUrlIsNotSetDataProvider(): \Generator
    {
        yield 'no service configuration' => [
            [],
        ];
        yield 'available service configuration' => [
            [
                'slack' => [],
            ],
        ];
        yield 'missing URL configuration' => [
            [
                'slack' => [
                    'enable' => true,
                ],
            ],
        ];
    }

    /**
     * @return \Generator<string, array{bool}>
     */
    public function reportSendsUpdateReportSuccessfullyDataProvider(): \Generator
    {
        yield 'secure package' => [false];
        yield 'insecure package' => [true];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
        parent::tearDown();
    }
}
