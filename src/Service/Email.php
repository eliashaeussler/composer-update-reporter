<?php

declare(strict_types=1);

namespace EliasHaeussler\ComposerUpdateReporter\Service;

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
use Spatie\Emoji\Emoji;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Email.
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Email extends AbstractService
{
    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var string[]
     */
    private $receivers;

    /**
     * @var string
     */
    private $sender;

    /**
     * @param string[] $receivers
     */
    public function __construct(string $dsn, array $receivers, string $sender)
    {
        $this->transport = Transport::fromDsn($dsn);
        $this->receivers = $receivers;
        $this->sender = $sender;

        $this->validateReceivers();
        $this->validateSender();
    }

    public static function getIdentifier(): string
    {
        return 'email';
    }

    protected static function getName(): string
    {
        return 'E-mail';
    }

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $dsn = (string) static::resolveConfigurationKey($configuration, 'dsn');
        $receivers = explode(',', (string) static::resolveConfigurationKey($configuration, 'receivers'));
        $sender = (string) static::resolveConfigurationKey($configuration, 'sender');

        return new self($dsn, array_map('trim', array_filter($receivers)), $sender);
    }

    /**
     * @throws TransportExceptionInterface
     */
    protected function sendReport(UpdateCheckResult $result): bool
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Set subject
        $count = count($outdatedPackages);
        $subject = sprintf('%d outdated package%s', $count, 1 !== $count ? 's' : '');
        if (null !== $this->projectName) {
            $subject .= sprintf(' @ %s', $this->projectName);
        }

        // Set plain text body and html content
        $body = $this->parsePlainBody($outdatedPackages);
        $html = $this->parseHtmlBody($outdatedPackages);

        // Send email
        if (!$this->behavior->style->isJson()) {
            $this->behavior->io->write(Emoji::rocket().' Sending report via Email...');
        }
        $email = (new SymfonyEmail())
            ->from($this->sender)
            ->to(...$this->receivers)
            ->subject($subject)
            ->text($body)
            ->html($html);
        $sentMessage = $this->transport->send($email);

        return null !== $sentMessage;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     */
    private function parsePlainBody(array $outdatedPackages): string
    {
        $textParts = [];

        if (null !== $this->projectName) {
            $textParts[] = sprintf('Project: "%s"', $this->projectName);
            $textParts[] = '';
        }

        foreach ($outdatedPackages as $outdatedPackage) {
            $insecure = '';
            if ($outdatedPackage->isInsecure()) {
                $insecure = ' (insecure)';
            }
            $textParts[] = sprintf(
                'Package "%s" is outdated. Outdated version: "%s"%s, new version: "%s"',
                $outdatedPackage->getName(),
                $outdatedPackage->getOutdatedVersion(),
                $insecure,
                $outdatedPackage->getNewVersion()
            );
        }

        return implode(PHP_EOL, $textParts);
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     */
    private function parseHtmlBody(array $outdatedPackages): string
    {
        $html = [];

        if (null !== $this->projectName) {
            $html[] = sprintf('<p>Project: <strong>%s</strong></p>', $this->projectName);
            $html[] = '<hr>';
        }

        $html[] = '<table>';
        $html[] = '<tr>';
        $html[] = '<th>Package name</th>';
        $html[] = '<th>Outdated version</th>';
        $html[] = '<th>New version</th>';
        $html[] = '</tr>';

        foreach ($outdatedPackages as $outdatedPackage) {
            $insecure = '';
            if ($outdatedPackage->isInsecure()) {
                $insecure = ' <strong style="color: red;">(insecure)</strong>';
            }
            $html[] = '<tr>';
            /* @noinspection HtmlUnknownTarget */
            $html[] = sprintf(
                '<td><a href="%s">%s</a></td>',
                $outdatedPackage->getProviderLink(),
                $outdatedPackage->getName()
            );
            $html[] = '<td>'.$outdatedPackage->getOutdatedVersion().$insecure.'</td>';
            $html[] = '<td><strong>'.$outdatedPackage->getNewVersion().'</strong></td>';
            $html[] = '</tr>';
        }
        $html[] = '</table>';

        return implode(PHP_EOL, $html);
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * @return string[]
     */
    public function getReceivers(): array
    {
        return $this->receivers;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    private function validateReceivers(): void
    {
        if ([] === $this->receivers) {
            throw new \InvalidArgumentException('Email receivers must not be empty.', 1601395103);
        }
        foreach ($this->receivers as $receiver) {
            if (false === filter_var($receiver, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException(sprintf('Email receiver "%s" is no valid email address.', $receiver), 1601395301);
            }
        }
    }

    private function validateSender(): void
    {
        if ('' === trim($this->sender)) {
            throw new \InvalidArgumentException('Email sender must not be empty.', 1601395109);
        }
        if (false === filter_var($this->sender, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email sender is no valid email address.', 1601395313);
        }
    }
}
