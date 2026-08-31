<?php

declare(strict_types=1);

namespace Adl\Core\Mail;

use RuntimeException;

final class SmtpTransport
{
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $username,
        private readonly string $password,
        private readonly string $encryption = 'tls',
        private readonly string $helo = 'acteursdulivre.fr',
        private readonly int $timeout = 20,
    ) {
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    public function send(string $from, string $fromName, string $to, string $subject, string $html, string $text = '', array $extraHeaders = []): void
    {
        $encryption = $this->resolveEncryption();
        $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $this->host . ':' . $this->port;
        $context = stream_context_create([
            'ssl' => [
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $this->host,
                'SNI_enabled' => true,
            ],
        ]);

        $fp = @stream_socket_client($remote, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context);
        if (!$fp) {
            throw new RuntimeException('SMTP inaccessible : ' . ($errstr !== '' ? $errstr : 'connexion refusée (' . $errno . ')'));
        }
        stream_set_timeout($fp, $this->timeout);

        try {
            $this->expect($fp, [220]);
            $caps = $this->ehlo($fp);

            if ($encryption === 'tls') {
                if (!str_contains($caps, 'STARTTLS')) {
                    throw new RuntimeException('Le serveur SMTP n\'annonce pas STARTTLS. Choisissez SSL (port 465) ou aucun chiffrement.');
                }
                $this->cmd($fp, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('Échec du handshake TLS SMTP.');
                }
                $caps = $this->ehlo($fp);
            }

            $this->authenticate($fp, $caps);

            $this->cmd($fp, 'MAIL FROM:<' . $from . '>', [250, 251]);
            $this->cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->cmd($fp, 'DATA', [354]);

            $payload = $this->buildMessage($from, $fromName, $to, $subject, $html, $text, $extraHeaders);
            $this->write($fp, $this->dotStuff($payload));
            $this->write($fp, ".\r\n");
            $this->expect($fp, [250]);
            try {
                $this->cmd($fp, 'QUIT', [221, 250]);
            } catch (RuntimeException) {
                // le message est déjà accepté ; un QUIT qui coupe ne doit pas relancer l'envoi
            }
        } finally {
            fclose($fp);
        }
    }

    private function resolveEncryption(): string
    {
        $encryption = strtolower(trim($this->encryption));
        if ($this->port === 465 && $encryption === 'tls') {
            return 'ssl';
        }
        return $encryption;
    }

    private function ehlo($fp): string
    {
        return strtoupper($this->cmd($fp, 'EHLO ' . $this->helo, [250]));
    }

    private function authenticate($fp, string $caps): void
    {
        if ($this->username === '') {
            return;
        }

        $authLine = '';
        if (preg_match_all('/^250[\- ]AUTH(?: |=)(.+)$/mi', $caps, $matches)) {
            $authLine = strtoupper(implode(' ', $matches[1]));
        }

        $supportsLogin = $authLine === '' || str_contains($authLine, 'LOGIN');
        $supportsPlain = $authLine === '' || str_contains($authLine, 'PLAIN');

        if ($supportsLogin) {
            try {
                $this->cmd($fp, 'AUTH LOGIN', [334]);
            } catch (RuntimeException $e) {
                if (!$supportsPlain) {
                    throw $e;
                }
                $this->cmd($fp, 'AUTH PLAIN ' . base64_encode("\0{$this->username}\0{$this->password}"), [235]);
                return;
            }
            $this->cmd($fp, base64_encode($this->username), [334]);
            $this->cmd($fp, base64_encode($this->password), [235]);
            return;
        }

        if ($supportsPlain) {
            $this->cmd($fp, 'AUTH PLAIN ' . base64_encode("\0{$this->username}\0{$this->password}"), [235]);
            return;
        }

        throw new RuntimeException('Le serveur SMTP n\'accepte ni AUTH LOGIN ni AUTH PLAIN.');
    }

    /**
     * @param array<string, string> $extraHeaders
     */
    private function buildMessage(string $from, string $fromName, string $to, string $subject, string $html, string $text, array $extraHeaders = []): string
    {
        $boundary = 'adl_' . bin2hex(random_bytes(8));
        $domain = substr(strrchr($from, '@') ?: '@acteursdulivre.fr', 1) ?: 'acteursdulivre.fr';
        $text = $text !== '' ? $text : trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $this->encodeHeader($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];
        foreach ($extraHeaders as $name => $value) {
            $name = trim((string) $name);
            $value = str_replace(["\r", "\n"], '', (string) $value);
            if ($name === '' || $value === '' || !preg_match('/^[A-Za-z0-9-]+$/', $name)) {
                continue;
            }
            $headers[] = $name . ': ' . $value;
        }

        $body = implode("\r\n", $headers) . "\r\n\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($text) . "\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($html) . "\r\n";
        $body .= '--' . $boundary . "--\r\n";

        return $body;
    }

    private function cmd($fp, string $command, array $ok): string
    {
        $this->write($fp, $command . "\r\n");
        return $this->expect($fp, $ok);
    }

    private function expect($fp, array $ok): string
    {
        $response = '';
        while (($line = fgets($fp, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ($response === '') {
            $meta = stream_get_meta_data($fp);
            if (!empty($meta['timed_out'])) {
                throw new RuntimeException('Le serveur SMTP n\'a pas répondu (délai dépassé).');
            }
            throw new RuntimeException('Le serveur SMTP a fermé la connexion.');
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $ok, true)) {
            throw new RuntimeException('Réponse SMTP inattendue (' . $code . ') : ' . trim($response));
        }
        return $response;
    }

    private function write($fp, string $data): void
    {
        $offset = 0;
        $length = strlen($data);
        while ($offset < $length) {
            $written = fwrite($fp, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Écriture SMTP interrompue.');
            }
            $offset += $written;
        }
    }

    private function encodeHeader(string $value): string
    {
        if ($value === '' || preg_match('/^[\x20-\x7E]+$/', $value)) {
            return $value;
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function dotStuff(string $body): string
    {
        $body = str_replace(["\r\n", "\r", "\n"], "\n", $body);
        $lines = explode("\n", $body);
        foreach ($lines as &$line) {
            if (str_starts_with($line, '.')) {
                $line = '.' . $line;
            }
        }
        $stuffed = implode("\r\n", $lines);
        return str_ends_with($stuffed, "\r\n") ? $stuffed : $stuffed . "\r\n";
    }
}
