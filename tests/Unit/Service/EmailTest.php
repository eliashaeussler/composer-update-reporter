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
use EliasHaeussler\ComposerUpdateReporter\Service\Email;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use Prophecy\Argument;
use rpkamp\Mailhog\MailhogClient;
use rpkamp\Mailhog\Message\Contact;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * EmailTest
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class EmailTest extends AbstractTestCase
{
    use TestEnvironmentTrait;

    protected static $mailhogHost;
    protected static $mailhogSmtpPort;
    protected static $mailhogApiPort;
    protected static $mailhogSmtp;
    protected static $mailhogApi;

    /**
     * @var Email
     */
    protected $subject;

    /**
     * @var MailhogClient
     */
    protected $mailhog;

    public static function setUpBeforeClass(): void
    {
        static::$mailhogHost = getenv('MAILHOG_HOST') ?: 'localhost';
        static::$mailhogSmtpPort = (int)(getenv('MAILHOG_SMTP_PORT') ?: 2025);
        static::$mailhogApiPort = (int)(getenv('MAILHOG_API_PORT') ?: 9025);
        static::$mailhogSmtp = sprintf('smtp://%s:%d', static::$mailhogHost, static::$mailhogSmtpPort);
        static::$mailhogApi = sprintf('http://%s:%d', static::$mailhogHost, static::$mailhogApiPort);
    }

    protected function setUp(): void
    {
        $this->subject = new Email(static::$mailhogSmtp, ['foo@example.org'], 'baz@example.org');
        $this->mailhog = new MailhogClient(new HttplugClient(), new HttplugClient(), static::$mailhogApi);
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfReceiversAreEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1601395103);

        new Email(static::$mailhogSmtp, [], 'foo');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfReceiverIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1601395301);

        new Email(static::$mailhogSmtp, ['foo'], 'foo');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfSenderIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1601395109);

        new Email(static::$mailhogSmtp, ['foo@foo.com'], '');
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfSenderIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1601395313);

        new Email(static::$mailhogSmtp, ['foo@foo.com'], 'foo');
    }

    /**
     * @test
     * @dataProvider fromConfigurationThrowsExceptionIfEmailDsnIsNotSetDataProvider
     * @param array $configuration
     */
    public function fromConfigurationThrowsExceptionIfEmailDsnIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('EMAIL_DSN');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1601391909);

        Email::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfEmailReceiversAreNotSet(): void
    {
        $this->modifyEnvironmentVariable('EMAIL_RECEIVERS');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1601391943);

        $configuration = [
            'email' => [
                'dsn' => static::$mailhogSmtp,
            ],
        ];
        Email::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfEmailSenderIsNotSet(): void
    {
        $this->modifyEnvironmentVariable('EMAIL_SENDER');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1601391961);

        $configuration = [
            'email' => [
                'dsn' => static::$mailhogSmtp,
                'receivers' => 'foo@foo.com',
            ],
        ];
        Email::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromComposerJson(): void
    {
        $this->modifyEnvironmentVariable('EMAIL_DSN');
        $this->modifyEnvironmentVariable('EMAIL_RECEIVERS');
        $this->modifyEnvironmentVariable('EMAIL_SENDER');

        $configuration = [
            'email' => [
                'dsn' => static::$mailhogSmtp,
                'receivers' => 'foo@foo.com, foo@another-foo.com',
                'sender' => 'baz@baz.com',
            ],
        ];
        /** @var Email $subject */
        $subject = Email::fromConfiguration($configuration);
        $transport = $subject->getTransport();

        static::assertInstanceOf(Email::class, $subject);
        static::assertInstanceOf(EsmtpTransport::class, $transport);
        static::assertSame('', $transport->getUsername());
        static::assertSame('', $transport->getPassword());
        static::assertSame(static::$mailhogHost, $transport->getStream()->getHost());
        static::assertSame(static::$mailhogSmtpPort, $transport->getStream()->getPort());
        static::assertSame(['foo@foo.com', 'foo@another-foo.com'], $subject->getReceivers());
        static::assertSame('baz@baz.com', $subject->getSender());
    }

    /**
     * @test
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('EMAIL_DSN', static::$mailhogSmtp);
        $this->modifyEnvironmentVariable('EMAIL_RECEIVERS', 'foo@foo.com, foo@another-foo.com');
        $this->modifyEnvironmentVariable('EMAIL_SENDER', 'baz@baz.com');

        /** @var Email $subject */
        $subject = Email::fromConfiguration([]);
        $transport = $subject->getTransport();

        static::assertInstanceOf(Email::class, $subject);
        static::assertInstanceOf(EsmtpTransport::class, $transport);
        static::assertSame('', $transport->getUsername());
        static::assertSame('', $transport->getPassword());
        static::assertSame(static::$mailhogHost, $transport->getStream()->getHost());
        static::assertSame(static::$mailhogSmtpPort, $transport->getStream()->getPort());
        static::assertSame(['foo@foo.com', 'foo@another-foo.com'], $subject->getReceivers());
        static::assertSame('baz@baz.com', $subject->getSender());
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
        $this->modifyEnvironmentVariable('EMAIL_ENABLE', $environmentVariable);

        static::assertSame($expected, Email::isEnabled($configuration));
    }

    /**
     * @test
     */
    public function reportSkipsReportIfNoPackagesAreOutdated(): void
    {
        $result = new UpdateCheckResult([]);
        $io = new BufferIO();

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('Skipped Email report.', $io->getOutput());
    }

    /**
     * @test
     * @dataProvider reportSendsUpdateReportSuccessfullyDataProvider
     * @param bool $insecure
     * @param string $expectedSecurityNotice
     */
    public function reportSendsUpdateReportSuccessfully(bool $insecure, string $expectedSecurityNotice): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5', $insecure),
        ]);
        $io = new BufferIO();

        static::assertTrue($this->subject->report($result, $io));
        static::assertStringContainsString('Email report was successful.', $io->getOutput());
        static::assertSame(1, $this->mailhog->getNumberOfMessages());

        $message = $this->mailhog->getLastMessage();

        static::assertSame('1 outdated package', $message->subject);
        static::assertTrue($message->recipients->contains(new Contact('foo@example.org')));
        static::assertSame('baz@example.org', $message->sender->emailAddress);

        $expected = implode("\r\n", [
            '<table>',
            '<tr>',
            '<th>Package name</th>',
            '<th>Outdated version</th>',
            '<th>New version</th>',
            '</tr>',
            '<tr>',
            '<td><a href="https://packagist.org/packages/foo/foo">foo/foo</a></td>',
            '<td>1.0.0' . $expectedSecurityNotice . '</td>',
            '<td><strong>1.0.5</strong></td>',
            '</tr>',
            '</table>',
        ]);
        static::assertSame($expected, $message->body);
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

        // Prophesize Transport
        $transportProphecy = $this->prophesize(TransportInterface::class);
        $transportProphecy->send(Argument::type(SymfonyEmail::class))
            ->willReturn(null)
            ->shouldBeCalledOnce();

        // Inject transport prophecy into subject
        $reflectionClass = new \ReflectionClass($this->subject);
        $clientProperty = $reflectionClass->getProperty('transport');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->subject, $transportProphecy->reveal());

        static::assertFalse($this->subject->report($result, $io));
        static::assertStringContainsString('Error during Email report.', $io->getOutput());
    }

    public function fromConfigurationThrowsExceptionIfEmailDsnIsNotSetDataProvider(): array
    {
        return [
            'no service configuration' => [
                [],
            ],
            'available service configuration' => [
                [
                    'email' => [],
                ],
            ],
            'missing URL configuration' => [
                [
                    'email' => [
                        'sender' => 'foo',
                        'receivers' => 'baz',
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
                    'email' => [],
                ],
                null,
                false,
            ],
            'truthy configuration and no environment variable' => [
                [
                    'email' => [
                        'enable' => true,
                    ],
                ],
                null,
                true,
            ],
            'truthy configuration and falsy environment variable' => [
                [
                    'email' => [
                        'enable' => true,
                    ],
                ],
                '0',
                true,
            ],
            'falsy configuration and truthy environment variable' => [
                [
                    'email' => [
                        'enable' => false,
                    ],
                ],
                '1',
                true,
            ],
            'empty configuration and truthy environment variable' => [
                [
                    'email' => [],
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
                ' <strong style="color: red;">(insecure)</strong>',
            ],
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
        $this->mailhog->purgeMessages();
        parent::tearDown();
    }
}