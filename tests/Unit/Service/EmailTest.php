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
use EliasHaeussler\ComposerUpdateReporter\Service\Email;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\AbstractTestCase;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\OutputBehaviorTrait;
use EliasHaeussler\ComposerUpdateReporter\Tests\Unit\TestEnvironmentTrait;
use rpkamp\Mailhog\MailhogClient;
use rpkamp\Mailhog\Message\Contact;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;

/**
 * EmailTest.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class EmailTest extends AbstractTestCase
{
    use OutputBehaviorTrait;
    use TestEnvironmentTrait;

    /**
     * @var string
     */
    protected static $mailhogHost;

    /**
     * @var int
     */
    protected static $mailhogSmtpPort;

    /**
     * @var int
     */
    protected static $mailhogApiPort;

    /**
     * @var string
     */
    protected static $mailhogSmtp;

    /**
     * @var string
     */
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
        static::$mailhogSmtpPort = (int) (getenv('MAILHOG_SMTP_PORT') ?: 2025);
        static::$mailhogApiPort = (int) (getenv('MAILHOG_API_PORT') ?: 9025);
        static::$mailhogSmtp = sprintf('smtp://%s:%d', static::$mailhogHost, static::$mailhogSmtpPort);
        static::$mailhogApi = sprintf('http://%s:%d', static::$mailhogHost, static::$mailhogApiPort);
    }

    protected function setUp(): void
    {
        $this->subject = new Email(static::$mailhogSmtp, ['foo@example.org'], 'baz@example.org');
        $this->subject->setBehavior($this->getDefaultBehavior());
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
     *
     * @dataProvider fromConfigurationThrowsExceptionIfEmailDsnIsNotSetDataProvider
     *
     * @param array<string, mixed> $configuration
     */
    public function fromConfigurationThrowsExceptionIfEmailDsnIsNotSet(array $configuration): void
    {
        $this->modifyEnvironmentVariable('EMAIL_DSN');

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

        Email::fromConfiguration($configuration);
    }

    /**
     * @test
     */
    public function fromConfigurationThrowsExceptionIfEmailReceiversAreNotSet(): void
    {
        $this->modifyEnvironmentVariable('EMAIL_RECEIVERS');

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

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

        $this->expectException(MissingConfigurationException::class);
        $this->expectExceptionCode(1617805421);

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
     *
     * @throws MissingConfigurationException
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
        /** @var EsmtpTransport $transport */
        $transport = $subject->getTransport();
        /** @var SocketStream $stream */
        $stream = $transport->getStream();

        static::assertInstanceOf(Email::class, $subject);
        static::assertInstanceOf(EsmtpTransport::class, $transport);
        static::assertSame('', $transport->getUsername());
        static::assertSame('', $transport->getPassword());
        static::assertSame(static::$mailhogHost, $stream->getHost());
        static::assertSame(static::$mailhogSmtpPort, $stream->getPort());
        static::assertSame(['foo@foo.com', 'foo@another-foo.com'], $subject->getReceivers());
        static::assertSame('baz@baz.com', $subject->getSender());
    }

    /**
     * @test
     *
     * @throws MissingConfigurationException
     */
    public function fromConfigurationReadsConfigurationFromEnvironmentVariables(): void
    {
        $this->modifyEnvironmentVariable('EMAIL_DSN', static::$mailhogSmtp);
        $this->modifyEnvironmentVariable('EMAIL_RECEIVERS', 'foo@foo.com, foo@another-foo.com');
        $this->modifyEnvironmentVariable('EMAIL_SENDER', 'baz@baz.com');

        /** @var Email $subject */
        $subject = Email::fromConfiguration([]);
        /** @var EsmtpTransport $transport */
        $transport = $subject->getTransport();
        /** @var SocketStream $stream */
        $stream = $transport->getStream();

        static::assertInstanceOf(Email::class, $subject);
        static::assertInstanceOf(EsmtpTransport::class, $transport);
        static::assertSame('', $transport->getUsername());
        static::assertSame('', $transport->getPassword());
        static::assertSame(static::$mailhogHost, $stream->getHost());
        static::assertSame(static::$mailhogSmtpPort, $stream->getPort());
        static::assertSame(['foo@foo.com', 'foo@another-foo.com'], $subject->getReceivers());
        static::assertSame('baz@baz.com', $subject->getSender());
    }

    /**
     * @test
     *
     * @dataProvider reportSendsUpdateReportSuccessfullyDataProvider
     */
    public function reportSendsUpdateReportSuccessfully(bool $insecure, string $expectedSecurityNotice): void
    {
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5', $insecure),
        ]);

        static::assertTrue($this->subject->report($result));
        static::assertStringContainsString('E-mail report was successful', $this->getIO()->getOutput());
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
            '<td><a href="https://packagist.org/packages/foo/foo#1.0.5">foo/foo</a></td>',
            '<td>1.0.0'.$expectedSecurityNotice.'</td>',
            '<td><strong>1.0.5</strong></td>',
            '</tr>',
            '</table>',
        ]);
        static::assertSame($expected, $message->body);
    }

    /**
     * @test
     */
    public function reportIncludesProjectNameInSubjectAndBody(): void
    {
        $this->subject->setProjectName('foo/baz');
        $result = new UpdateCheckResult([
            new OutdatedPackage('foo/foo', '1.0.0', '1.0.5'),
        ]);

        $this->subject->report($result);

        $message = $this->mailhog->getLastMessage();
        static::assertSame('1 outdated package @ foo/baz', $message->subject);

        $expected = implode("\r\n", [
            '<p>Project: <strong>foo/baz</strong></p>',
            '<hr>',
            '<table>',
            '<tr>',
            '<th>Package name</th>',
            '<th>Outdated version</th>',
            '<th>New version</th>',
            '</tr>',
            '<tr>',
            '<td><a href="https://packagist.org/packages/foo/foo#1.0.5">foo/foo</a></td>',
            '<td>1.0.0</td>',
            '<td><strong>1.0.5</strong></td>',
            '</tr>',
            '</table>',
        ]);
        static::assertSame($expected, $message->body);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>}>
     */
    public function fromConfigurationThrowsExceptionIfEmailDsnIsNotSetDataProvider(): \Generator
    {
        yield 'no service configuration' => [
            [],
        ];
        yield 'available service configuration' => [
            [
                'email' => [],
            ],
        ];
        yield 'missing URL configuration' => [
            [
                'email' => [
                    'sender' => 'foo',
                    'receivers' => 'baz',
                ],
            ],
        ];
    }

    /**
     * @return \Generator<string, array{bool, string}>
     */
    public function reportSendsUpdateReportSuccessfullyDataProvider(): \Generator
    {
        yield 'secure package' => [
            false,
            '',
        ];
        yield 'insecure package' => [
            true,
            ' <strong style="color: red;">(insecure)</strong>',
        ];
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironmentVariables();
        $this->mailhog->purgeMessages();
        parent::tearDown();
    }
}
