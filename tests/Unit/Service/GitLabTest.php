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
use EliasHaeussler\ComposerUpdateCheck\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\UpdateCheckResult;
use EliasHaeussler\ComposerUpdateReporter\Service\GitLab;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Prophecy\Argument;

/**
 * GitLabTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class GitLabTest extends AbstractTestCase
{
    use TestEnvironmentTrait;

    /**
     * @var GitLab
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new GitLab(new Uri('https://example.org'), 'foo');
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
     * @param array $configuration
     */
    public function fromConfigurationThrowsExceptionIfGitLabUrlIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('GITLAB_URL');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1600852917);

        GitLab::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfAuthorizationKeyIsNotSet(): void
    {
        $this->modifyEnvironmentVariable('GITLAB_AUTH_KEY');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1600852990);

        $configuration = [
            'gitlab' => [
                'url' => 'https://example.org',
            ],
        ];
        GitLab::fromConfiguration($configuration);
    }

    /**
     * @test
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
        static::assertSame('https://example.org', (string)$subject->getUri());
        static::assertSame('foo', $subject->getAuthorizationKey());
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('GITLAB_URL', 'https://example.org');
        $this->modifyEnvironmentVariable('GITLAB_AUTH_KEY', 'foo');

        /** @var GitLab $subject */
        $subject = GitLab::fromConfiguration([]);

        static::assertInstanceOf(GitLab::class, $subject);
        static::assertSame('https://example.org', (string)$subject->getUri());
        static::assertSame('foo', $subject->getAuthorizationKey());
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
        $this->modifyEnvironmentVariable('GITLAB_ENABLE', $environmentVariable);

        static::assertSame($expected, GitLab::isEnabled($configuration));
    }

    /**
     * @test
     */
    public function reportSkipsReportIfNoPackagesAreOutdated(): void
    {
        $result = new UpdateCheckResult([]);
        $io = new BufferIO();

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('Skipped GitLab report.', $io->getOutput());
    }

    /**
     * @test
     */
    public function reportSendsUpdateReportSuccessfully(): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5'),
        ]);
        $io = new BufferIO();

        // Prophesize Client
        $clientProphecy = $this->prophesize(Client::class);
        $clientProphecy->post('', [
            RequestOptions::JSON => [
                'title' => '1 outdated package',
                'foo/foo' => 'Outdated version: 1.0.0, new version: 1.0.5',
            ],
        ])->willReturn(new Response())->shouldBeCalledOnce();

        // Inject client prophecy into subject
        $reflectionClass = new \ReflectionClass($this->subject);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->subject, $clientProphecy->reveal());

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('GitLab report was successful.', $io->getOutput());
    }

    /**
     * @test
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
        static::assertStringContainsString('Error during GitLab report.', $io->getOutput());
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
                    'gitlab' => [],
                ],
                null,
                false,
            ],
            'truthy configuration and no environment variable' => [
                [
                    'gitlab' => [
                        'enable' => true,
                    ],
                ],
                null,
                true,
            ],
            'truthy configuration and falsy environment variable' => [
                [
                    'gitlab' => [
                        'enable' => true,
                    ],
                ],
                '0',
                true,
            ],
            'falsy configuration and truthy environment variable' => [
                [
                    'gitlab' => [
                        'enable' => false,
                    ],
                ],
                '1',
                true,
            ],
            'empty configuration and truthy environment variable' => [
                [
                    'gitlab' => [],
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
        $this->restoreEnvironmentVariables();
        parent::tearDown();
    }
}
