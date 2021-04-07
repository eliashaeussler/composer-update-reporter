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

use EliasHaeussler\ComposerUpdateCheck\Package\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\Package\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Exception\MissingConfigurationException;
use EliasHaeussler\ComposerUpdateReporter\Service\Teams;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\ClientMockTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\OutputBehaviorTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use Nyholm\Psr7\Uri;
use Spatie\Emoji\Emoji;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * TeamsTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class TeamsTest extends AbstractTestCase
{
    use ClientMockTrait;
    use OutputBehaviorTrait;
    use TestEnvironmentTrait;

    /**
     * @var Teams
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new Teams(new Uri('https://example.org'));
        $this->subject->setBehavior($this->getDefaultBehavior());
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
     *
     * @param array<string, mixed> $configuration
     */
    public function fromConfigurationThrowsExceptionIfTeamsUrlIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('TEAMS_URL');

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

        Teams::fromConfiguration($configuration);
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
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
        static::assertSame('https://example.org', (string) $subject->getUri());
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('TEAMS_URL', 'https://example.org');

        /** @var Teams $subject */
        $subject = Teams::fromConfiguration([]);

        static::assertInstanceOf(Teams::class, $subject);
        static::assertSame('https://example.org', (string) $subject->getUri());
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
        static::assertStringContainsString('MS Teams report was successful', $this->getIO()->getOutput());

        $payload = $this->getPayloadOfLastRequest();
        static::assertSame(sprintf('%s 1 outdated package', Emoji::policeCarLight()), $payload['title']);
        static::assertSame('1 package is outdated', $payload['summary']);

        $text = $payload['sections'][0]['text'];
        $expected = implode(PHP_EOL.PHP_EOL, [
            '# [foo/foo](https://packagist.org/packages/foo/foo#1.0.5)',
            'Current version: **1.0.0**'.$expectedSecurityNotice,
            'New version: **1.0.5**',
        ]);
        static::assertSame($expected, $text);
    }

    /**
     * @test
     */
    public function reportIncludesProjectNameInTitle(): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5'),
        ]);

        $this->subject->setProjectName('foo/baz');
        $this->subject->setClient($this->getClient());
        $this->mockedResponse = new MockResponse();

        static::assertTrue($this->subject->report($result));

        $payload = $this->getPayloadOfLastRequest();
        static::assertSame(sprintf('%s 1 outdated package @ foo/baz', Emoji::policeCarLight()), $payload['title']);
    }

    /**
     * @return array<string, array>
     */
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
