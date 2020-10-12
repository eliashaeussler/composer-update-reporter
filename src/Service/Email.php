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

use Composer\IO\IOInterface;
use EliasHaeussler\ComposerUpdateCheck\OutdatedPackage;
use EliasHaeussler\ComposerUpdateCheck\UpdateCheckResult;
use Spatie\Emoji\Emoji;
use Spatie\Emoji\Exceptions\UnknownCharacter;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email as SymfonyEmail;

/**
 * Email
 *
 * @author Elias Häußler <elias@haeussler.dev>
 * @license GPL-3.0-or-later
 */
class Email implements ServiceInterface
{
    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var array
     */
    private $receivers;

    /**
     * @var string
     */
    private $sender;

    /**
     * @var bool
     */
    private $json = false;

    public function __construct(string $dsn, array $receivers, string $sender)
    {
        $this->transport = Transport::fromDsn($dsn);
        $this->receivers = $receivers;
        $this->sender = $sender;

        $this->validateReceivers();
        $this->validateSender();
    }

    public static function fromConfiguration(array $configuration): ServiceInterface
    {
        $extra = $configuration['email'] ?? null;

        // Parse Email DSN
        if (is_array($extra) && array_key_exists('dsn', $extra)) {
            $dsn = (string)$extra['dsn'];
        } else if (getenv('EMAIL_DSN') !== false) {
            $dsn = getenv('EMAIL_DSN');
        } else {
            throw new \RuntimeException(
                'Email DSN is not defined. Define it either in composer.json or as $EMAIL_DSN.',
                1601391909
            );
        }

        // Parse Email receivers
        if (is_array($extra) && array_key_exists('receivers', $extra)) {
            $receivers = explode(',', (string)$extra['receivers']);
        } else if (getenv('EMAIL_RECEIVERS') !== false) {
            $receivers = explode(',', getenv('EMAIL_RECEIVERS'));
        } else {
            throw new \RuntimeException(
                'Email receivers are not defined. Define it either in composer.json or as $EMAIL_RECEIVERS.',
                1601391943
            );
        }

        // Parse Email sender
        if (is_array($extra) && array_key_exists('sender', $extra)) {
            $sender = (string)$extra['sender'];
        } else if (getenv('EMAIL_SENDER') !== false) {
            $sender = getenv('EMAIL_SENDER');
        } else {
            throw new \RuntimeException(
                'Email sender is not defined. Define it either in composer.json or as $EMAIL_SENDER.',
                1601391961
            );
        }

        return new self($dsn, array_map('trim', array_filter($receivers)), $sender);
    }

    public static function isEnabled(array $configuration): bool
    {
        if (getenv('EMAIL_ENABLE') !== false && (bool)getenv('EMAIL_ENABLE')) {
            return true;
        }
        $extra = $configuration['email'] ?? null;
        return is_array($extra) && (bool)($extra['enable'] ?? false);
    }

    public function report(UpdateCheckResult $result, IOInterface $io): bool
    {
        $outdatedPackages = $result->getOutdatedPackages();

        // Do not send report if packages are up to date
        if ($outdatedPackages === []) {
            if (!$this->json) {
                $io->write(Emoji::crossMark() . ' Skipped Email report.');
            }
            return true;
        }

        // Set subject
        $count = count($outdatedPackages);
        $subject = sprintf('%d outdated package%s', $count, $count !== 1 ? 's' : '');

        // Set plain text body and html content
        $body = $this->parsePlainBody($outdatedPackages);
        $html = $this->parseHtmlBody($outdatedPackages);

        // Send email
        $email = (new SymfonyEmail())
            ->from($this->sender)
            ->to(...$this->receivers)
            ->subject($subject)
            ->text($body)
            ->html($html);
        $sentMessage = $this->transport->send($email);
        $successful = $sentMessage !== null;

        // Print report state
        if (!$successful) {
            $io->writeError(Emoji::crossMark() . ' Error during Email report.');
        } else if (!$this->json) {
            try {
                $checkMark = Emoji::checkMark();
            } /** @noinspection PhpRedundantCatchClauseInspection */ catch (UnknownCharacter $e) {
                /** @noinspection PhpUndefinedMethodInspection */
                $checkMark = Emoji::heavyCheckMark();
            }
            $io->write($checkMark . ' Email report was successful.');
        }

        return $successful;
    }

    /**
     * @param OutdatedPackage[] $outdatedPackages
     * @return string
     */
    private function parsePlainBody(array $outdatedPackages): string
    {
        $textParts = [];
        foreach ($outdatedPackages as $outdatedPackage) {
            $insecure = '';
            if (method_exists($outdatedPackage, 'isInsecure') && $outdatedPackage->isInsecure()) {
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
     * @return string
     */
    private function parseHtmlBody(array $outdatedPackages): string
    {
        $html = [];
        $html[] = '<table>';
        $html[] =   '<tr>';
        $html[] =     '<th>Package name</th>';
        $html[] =     '<th>Outdated version</th>';
        $html[] =     '<th>New version</th>';
        $html[] =   '</tr>';
        foreach ($outdatedPackages as $outdatedPackage) {
            $insecure = '';
            if (method_exists($outdatedPackage, 'isInsecure') && $outdatedPackage->isInsecure()) {
                $insecure = ' <strong style="color: red;">(insecure)</strong>';
            }
            $html[] = '<tr>';
            $html[] =   sprintf(
                '<td><a href="https://packagist.org/packages/%s">%s</a></td>',
                $outdatedPackage->getName(),
                $outdatedPackage->getName()
            );
            $html[] =   '<td>' . $outdatedPackage->getOutdatedVersion() . $insecure . '</td>';
            $html[] =   '<td><strong>' . $outdatedPackage->getNewVersion() . '</strong></td>';
            $html[] = '</tr>';
        }
        $html[] = '</table>';
        return implode(PHP_EOL, $html);
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    public function getReceivers(): array
    {
        return $this->receivers;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setJson(bool $json): ServiceInterface
    {
        $this->json = $json;
        return $this;
    }

    private function validateReceivers(): void
    {
        if ($this->receivers === []) {
            throw new \InvalidArgumentException('Email receivers must not be empty.', 1601395103);
        }
        foreach ($this->receivers as $receiver) {
            if (filter_var($receiver, FILTER_VALIDATE_EMAIL) === false) {
                throw new \InvalidArgumentException(
                    sprintf('Email receiver "%s" is no valid email address.', $receiver),
                    1601395301
                );
            }
        }
    }

    private function validateSender(): void
    {
        if (trim($this->sender) === '') {
            throw new \InvalidArgumentException('Email sender must not be empty.', 1601395109);
        }
        if (filter_var($this->sender, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Email sender is no valid email address.', 1601395313);
        }
    }
}