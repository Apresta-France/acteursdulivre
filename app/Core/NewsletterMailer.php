<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Core\Mail\SmtpTransport;
use Adl\Models\EmailLog;
use Adl\Models\Newsletter;
use Adl\Models\Setting;

final class NewsletterMailer
{
    public static function send(string $to, string $subject, string $html, string $unsubUrl = '', string $text = ''): void
    {
        $from = self::fromAddress();
        $fromName = self::fromName();
        $host = self::host();

        $wrapped = View::fetch('emails/layout', [
            'subject' => $subject,
            'content' => $html,
            'appName' => Env::get('APP_NAME', 'Acteurs du Livre'),
            'appUrl' => Env::get('APP_URL', 'https://acteursdulivre.fr'),
            'unsubscribeUrl' => $unsubUrl,
        ]);

        $headers = [];
        if ($unsubUrl !== '') {
            $headers['List-Unsubscribe'] = '<' . $unsubUrl . '>';
            $headers['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
        }

        if ($host === '') {
            Mailer::send($to, $subject, $html, $text, [
                'unsubscribe_url' => $unsubUrl,
                'headers' => $headers,
                'source' => 'newsletter',
            ]);
            return;
        }

        try {
            $transport = new SmtpTransport(
                $host,
                (int) self::setting('newsletter_smtp_port', self::fallback('mail_port', Env::get('MAIL_PORT', '587'))),
                self::setting('newsletter_smtp_username', self::fallback('mail_username', Env::get('MAIL_USERNAME', ''))),
                self::setting('newsletter_smtp_password', self::fallback('mail_password', Env::get('MAIL_PASSWORD', ''))),
                self::setting('newsletter_smtp_encryption', self::fallback('mail_encryption', Env::get('MAIL_ENCRYPTION', 'tls'))),
                self::heloHost(),
            );
            $transport->send($from, $fromName, $to, $subject, $wrapped, $text, $headers);
        } catch (\Throwable $e) {
            EmailLog::record([
                'recipient' => $to,
                'subject' => $subject,
                'body_html' => $wrapped,
                'body_text' => $text,
                'source' => 'newsletter',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        EmailLog::record([
            'recipient' => $to,
            'subject' => $subject,
            'body_html' => $wrapped,
            'body_text' => $text,
            'source' => 'newsletter',
            'status' => 'sent',
        ]);
    }

    public static function usesSmtp(): bool
    {
        return self::host() !== '';
    }

    public static function fromAddress(): string
    {
        $own = self::setting('newsletter_smtp_from_address', '');
        return $own !== '' ? $own : Mailer::fromAddress();
    }

    public static function fromName(): string
    {
        $own = self::setting('newsletter_smtp_from_name', '');
        return $own !== '' ? $own : Mailer::fromName();
    }

    /** @return array<string, string> */
    public static function config(): array
    {
        return [
            'newsletter_smtp_host' => self::setting('newsletter_smtp_host', ''),
            'newsletter_smtp_port' => self::setting('newsletter_smtp_port', '587'),
            'newsletter_smtp_username' => self::setting('newsletter_smtp_username', ''),
            'newsletter_smtp_encryption' => self::setting('newsletter_smtp_encryption', 'tls'),
            'newsletter_smtp_from_address' => self::setting('newsletter_smtp_from_address', ''),
            'newsletter_smtp_from_name' => self::setting('newsletter_smtp_from_name', 'Acteurs du Livre'),
            'newsletter_smtp_password_set' => self::setting('newsletter_smtp_password', '') !== '' ? '1' : '',
            'uses_fallback' => self::setting('newsletter_smtp_host', '') === '' && Mailer::usesSmtp() ? '1' : '',
            'uses_smtp' => self::usesSmtp() ? '1' : '',
        ];
    }

    private static function host(): string
    {
        $own = self::setting('newsletter_smtp_host', '');
        if ($own !== '') {
            return $own;
        }
        return Mailer::usesSmtp() ? self::fallback('mail_host', Env::get('MAIL_HOST', '')) : '';
    }

    private static function heloHost(): string
    {
        $host = parse_url((string) Env::get('APP_URL', 'https://acteursdulivre.fr'), PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'acteursdulivre.fr';
    }

    private static function setting(string $key, string $default = ''): string
    {
        return Newsletter::setting($key, $default);
    }

    private static function fallback(string $key, string $default = ''): string
    {
        try {
            $value = Setting::get($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        } catch (\Throwable) {
        }
        return $default;
    }
}
