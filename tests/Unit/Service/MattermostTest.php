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
use EliasHaeussler\ComposerUpdateReporter\Service\Mattermost;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\ClientMockTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\OutputBehaviorTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use Nyholm\Psr7\Uri;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * MattermostTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class MattermostTest extends AbstractTestCase
{
    use ClientMockTrait;
    use OutputBehaviorTrait;
    use TestEnvironmentTrait;

    /**
     * @var Mattermost
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new Mattermost(new Uri('https://example.org'), 'foo', 'baz');
        $this->subject->setBehavior($this->getDefaultBehavior());
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600793015);

        new Mattermost(new Uri(''), 'foo');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600792942);

        new Mattermost(new Uri('foo'), 'foo');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfChannelNameIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600793071);

        new Mattermost(new Uri('https://example.org'), '');
    }

    /**
     * @test
     * @dataProvider fromConfigurationThrowsExceptionIfMattermostUrlIsNotSetDataProvider
     *
     * @param array<string, mixed> $configuration
     */
    public function fromConfigurationThrowsExceptionIfMattermostUrlIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('MATTERMOST_URL');

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

        Mattermost::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfChannelNameIsNotSet(): void
    {
        $this->modifyEnvironmentVariable('MATTERMOST_CHANNEL');

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

        $configuration = [
            'mattermost' => [
                'url' => 'https://example.org',
            ],
        ];
        Mattermost::fromConfiguration($configuration);
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
     */
    public function fromConfigurationReadsConfigurationFromComposerJson(): void
    {
        $this->modifyEnvironmentVariable('MATTERMOST_URL');
        $this->modifyEnvironmentVariable('MATTERMOST_CHANNEL');
        $this->modifyEnvironmentVariable('MATTERMOST_USERNAME');

        $configuration = [
            'mattermost' => [
                'url' => 'https://example.org',
                'channel' => 'foo',
                'username' => 'baz',
            ],
        ];
        /** @var Mattermost $subject */
        $subject = Mattermost::fromConfiguration($configuration);

        static::assertInstanceOf(Mattermost::class, $subject);
        static::assertSame('https://example.org', (string) $subject->getUri());
        static::assertSame('foo', $subject->getChannelName());
        static::assertSame('baz', $subject->getUsername());
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('MATTERMOST_URL', 'https://example.org');
        $this->modifyEnvironmentVariable('MATTERMOST_CHANNEL', 'foo');
        $this->modifyEnvironmentVariable('MATTERMOST_USERNAME', 'baz');

        /** @var Mattermost $subject */
        $subject = Mattermost::fromConfiguration([]);

        static::assertInstanceOf(Mattermost::class, $subject);
        static::assertSame('https://example.org', (string) $subject->getUri());
        static::assertSame('foo', $subject->getChannelName());
        static::assertSame('baz', $subject->getUsername());
    }

    /**
     * @test
     * @dataProvider reportSendsUpdateReportSuccessfullyDataProvider
     */
    public function reportSendsUpdateReportSuccessfully(bool $insecure, string $expectedSecurityNotice): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5', $insecure),
        ]);

        $this->subject->setClient($this->getClient());
        $this->mockedResponse = new MockResponse();

        static::assertTrue($this->subject->report($result));
        static::assertStringContainsString('Mattermost report was successful', $this->getIO()->getOutput());

        $expectedPayloadSubset = [
            'channel' => 'foo',
            'attachments' => [
                [
                    'color' => '#EE0000',
                ],
            ],
            'username' => 'baz',
        ];
        $this->assertPayloadOfLastRequestContainsSubset($expectedPayloadSubset);

        $payload = $this->getPayloadOfLastRequest();
        $text = $payload['attachments'][0]['text'] ?? null;
        $expected = '[foo/foo](https://packagist.org/packages/foo/foo#1.0.5) | 1.0.0'.$expectedSecurityNotice.' | **1.0.5**';
        static::assertStringContainsString($expected, $text);
    }

    /**
     * @return array<string, array>
     */
    public function fromConfigurationThrowsExceptionIfMattermostUrlIsNotSetDataProvider(): array
    {
        return [
            'no service configuration' => [
                [],
            ],
            'available service configuration' => [
                [
                    'mattermost' => [],
                ],
            ],
            'missing URL configuration' => [
                [
                    'mattermost' => [
                        'channel' => 'foo',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array>
     */
    public function reportSendsUpdateReportSuccessfullyDataProvider(): array
    {
        return [
            'secure package' => [
                false,
                '',
            ],
            'insecure package' => [
                true,
                ' :warning: **`insecure`**',
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
        parent::tearDown();
    }
}
