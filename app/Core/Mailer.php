<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Core\Mail\SmtpTransport;
use Adl\Models\EmailTemplate;
use Adl\Models\Setting;

final class Mailer
{
    public static function send(string $to, string $subject, string $html, string $text = ''): void
    {
        $from = self::setting('mail_from_address', Env::get('MAIL_FROM_ADDRESS', 'bonjour@acteursdulivre.fr'));
        $fromName = self::setting('mail_from_name', Env::get('MAIL_FROM_NAME', 'Acteurs du Livre'));
        $host = self::setting('mail_host', Env::get('MAIL_HOST', ''));

        $wrapped = View::fetch('emails/layout', [
            'subject' => $subject,
            'content' => $html,
            'appName' => Env::get('APP_NAME', 'Acteurs du Livre'),
            'appUrl' => Env::get('APP_URL', 'https://acteursdulivre.test'),
        ]);

        if ($host === '') {
            self::logToFile($to, $subject, $wrapped);
            return;
        }

        $transport = new SmtpTransport(
            $host,
            (int) self::setting('mail_port', Env::get('MAIL_PORT', '587')),
            self::setting('mail_username', Env::get('MAIL_USERNAME', '')),
            self::setting('mail_password', Env::get('MAIL_PASSWORD', '')),
            self::setting('mail_encryption', Env::get('MAIL_ENCRYPTION', 'tls')),
        );
        $transport->send($from, $fromName, $to, $subject, $wrapped, $text);
    }

    public static function sendTemplate(string $slug, string $to, array $vars = []): void
    {
        $template = EmailTemplate::findBySlug($slug);
        if (!$template) {
            throw new \RuntimeException('Modèle e-mail introuvable : ' . $slug);
        }
        $subject = self::replace($template['subject'], $vars);
        $html = self::replace($template['body_html'], $vars);
        self::send($to, $subject, $html);
    }

    public static function replace(string $content, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $content = str_replace(['{{ ' . $key . ' }}', '{{' . $key . '}}'], (string) $value, $content);
        }
        return $content;
    }

    private static function setting(string $key, ?string $default = null): string
    {
        try {
            $value = Setting::get($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        } catch (\Throwable) {
            // table pas encore migrée
        }
        return (string) $default;
    }

    private static function logToFile(string $to, string $subject, string $html): void
    {
        $dir = ADL_ROOT . '/storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir . '/' . date('Ymd-His') . '-' . preg_replace('/[^a-z0-9]+/i', '-', $to) . '.html';
        file_put_contents($file, "To: {$to}\nSubject: {$subject}\n\n{$html}");
    }
}
