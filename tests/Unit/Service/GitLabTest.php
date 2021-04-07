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
use EliasHaeussler\ComposerUpdateReporter\Service\GitLab;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\ClientMockTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\OutputBehaviorTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use Nyholm\Psr7\Uri;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * GitLabTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class GitLabTest extends AbstractTestCase
{
    use ClientMockTrait;
    use OutputBehaviorTrait;
    use TestEnvironmentTrait;

    /**
     * @var GitLab
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new GitLab(new Uri('https://example.org'), 'foo');
        $this->subject->setBehavior($this->getDefaultBehavior());
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600852837);

        new GitLab(new Uri(''), 'foo');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfUriIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600852841);

        new GitLab(new Uri('foo'), 'foo');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfAuthorizationKeyIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1600852864);

        new GitLab(new Uri('https://example.org'), '');
    }

    /**
     * @test
     * @dataProvider fromConfigurationThrowsExceptionIfGitLabUrlIsNotSetDataProvider
     *
     * @param array<string, mixed> $configuration
     */
    public function fromConfigurationThrowsExceptionIfGitLabUrlIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('GITLAB_URL');

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

        GitLab::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfAuthorizationKeyIsNotSet(): void
    {
        $this->modifyEnvironmentVariable('GITLAB_AUTH_KEY');

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

        $configuration = [
            'gitlab' => [
                'url' => 'https://example.org',
            ],
        ];
        GitLab::fromConfiguration($configuration);
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
     */
    public function fromConfigurationReadsConfigurationFromComposerJson(): void
    {
        $this->modifyEnvironmentVariable('GITLAB_URL');
        $this->modifyEnvironmentVariable('GITLAB_AUTH_KEY');

        $configuration = [
            'gitlab' => [
                'url' => 'https://example.org',
                'authKey' => 'foo',
            ],
        ];
        /** @var GitLab $subject */
        $subject = GitLab::fromConfiguration($configuration);

        static::assertInstanceOf(GitLab::class, $subject);
        static::assertSame('https://example.org', (string) $subject->getUri());
        static::assertSame('foo', $subject->getAuthorizationKey());
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('GITLAB_URL', 'https://example.org');
        $this->modifyEnvironmentVariable('GITLAB_AUTH_KEY', 'foo');

        /** @var GitLab $subject */
        $subject = GitLab::fromConfiguration([]);

        static::assertInstanceOf(GitLab::class, $subject);
        static::assertSame('https://example.org', (string) $subject->getUri());
        static::assertSame('foo', $subject->getAuthorizationKey());
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
        static::assertStringContainsString('GitLab report was successful', $this->getIO()->getOutput());

        $expectedPayloadSubset = [
            'title' => '1 outdated package',
            'foo/foo' => 'Outdated version: 1.0.0'.$expectedSecurityNotice.', new version: 1.0.5',
        ];
        $this->assertPayloadOfLastRequestContainsSubset($expectedPayloadSubset);
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

        $expectedPayloadSubset = [
            'title' => '1 outdated package @ foo/baz',
        ];
        $this->assertPayloadOfLastRequestContainsSubset($expectedPayloadSubset);
    }

    /**
     * @return array<string, array>
     */
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
                ' (insecure)',
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
        parent::tearDown();
    }
}
