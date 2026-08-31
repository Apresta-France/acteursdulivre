<?php

declare(strict_types=1);

namespace Adl\Core;

use Adl\Core\Mail\SmtpTransport;
use Adl\Models\EmailTemplate;
use Adl\Models\Setting;
use Adl\Models\User;

final class Mailer
{
    public static function send(string $to, string $subject, string $html, string $text = ''): void
    {
        $from = self::fromAddress();
        $fromName = self::fromName();
        $host = self::host();

        $wrapped = View::fetch('emails/layout', [
            'subject' => $subject,
            'content' => $html,
            'appName' => Env::get('APP_NAME', 'Acteurs du Livre'),
            'appUrl' => Env::get('APP_URL', 'https://acteursdulivre.fr'),
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
            self::heloHost(),
        );
        $transport->send($from, $fromName, $to, $subject, $wrapped, $text);
    }

    public static function usesSmtp(): bool
    {
        return self::host() !== '';
    }

    public static function fromAddress(): string
    {
        return self::setting('mail_from_address', Env::get('MAIL_FROM_ADDRESS', 'bonjour@acteursdulivre.fr'));
    }

    public static function fromName(): string
    {
        return self::setting('mail_from_name', Env::get('MAIL_FROM_NAME', 'Acteurs du Livre'));
    }

    /** @return array<string, string> */
    public static function config(): array
    {
        return [
            'mail_host' => self::host(),
            'mail_port' => self::setting('mail_port', Env::get('MAIL_PORT', '587')),
            'mail_username' => self::setting('mail_username', Env::get('MAIL_USERNAME', '')),
            'mail_encryption' => self::setting('mail_encryption', Env::get('MAIL_ENCRYPTION', 'tls')),
            'mail_from_address' => self::fromAddress(),
            'mail_from_name' => self::fromName(),
            'mail_password_set' => self::setting('mail_password', Env::get('MAIL_PASSWORD', '')) !== '' ? '1' : '',
        ];
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

    /**
     * Envoi métier : respecte les préférences et n'interrompt pas l'action si SMTP échoue.
     *
     * @param array<string, mixed>|null $user
     * @param array<string, string> $vars
     */
    public static function notify(?array $user, string $channel, string $slug, array $vars = []): void
    {
        if ($user === null) {
            return;
        }
        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !User::wantsEmail($user, $channel)) {
            return;
        }
        if (!isset($vars['prenom']) || $vars['prenom'] === '') {
            $vars['prenom'] = (string) ($user['first_name'] ?? '');
        }
        try {
            self::sendTemplate($slug, $email, $vars);
        } catch (\Throwable) {
            // l'action métier reste valide si l'e-mail échoue
        }
    }

    public static function replace(string $content, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $content = str_replace(['{{ ' . $key . ' }}', '{{' . $key . '}}'], (string) $value, $content);
        }
        return $content;
    }

    private static function host(): string
    {
        return self::setting('mail_host', Env::get('MAIL_HOST', ''));
    }

    private static function heloHost(): string
    {
        $host = parse_url((string) Env::get('APP_URL', 'https://acteursdulivre.fr'), PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'acteursdulivre.fr';
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
        $safeTo = preg_replace('/[^a-z0-9]+/i', '-', $to) ?: 'destinataire';
        $file = $dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '-' . $safeTo . '.html';
        file_put_contents($file, "To: {$to}\nSubject: {$subject}\n\n{$html}");
    }
}
