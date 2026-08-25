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
        private readonly int $timeout = 20,
    ) {
    }

    public function send(string $from, string $fromName, string $to, string $subject, string $html, string $text = ''): void
    {
        $remote = ($this->encryption === 'ssl' ? 'ssl://' : '') . $this->host . ':' . $this->port;
        $fp = @stream_socket_client($remote, $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            throw new RuntimeException('SMTP inaccessible : ' . $errstr);
        }
        stream_set_timeout($fp, $this->timeout);

        $this->expect($fp, [220]);
        $this->cmd($fp, 'EHLO acteursdulivre.fr', [250]);

        if ($this->encryption === 'tls') {
            $this->cmd($fp, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Échec du handshake TLS SMTP.');
            }
            $this->cmd($fp, 'EHLO acteursdulivre.fr', [250]);
        }

        if ($this->username !== '') {
            $this->cmd($fp, 'AUTH LOGIN', [334]);
            $this->cmd($fp, base64_encode($this->username), [334]);
            $this->cmd($fp, base64_encode($this->password), [235]);
        }

        $this->cmd($fp, 'MAIL FROM:<' . $from . '>', [250]);
        $this->cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
        $this->cmd($fp, 'DATA', [354]);

        $boundary = 'adl_' . bin2hex(random_bytes(8));
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $this->encodeHeader($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $text = $text !== '' ? $text : trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
        $body = implode("\r\n", $headers) . "\r\n\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n" . $text . "\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n" . $html . "\r\n";
        $body .= '--' . $boundary . "--\r\n.";

        fwrite($fp, $this->dotStuff($body) . "\r\n");
        $this->expect($fp, [250]);
        $this->cmd($fp, 'QUIT', [221, 250]);
        fclose($fp);
    }

    private function cmd($fp, string $command, array $ok): void
    {
        fwrite($fp, $command . "\r\n");
        $this->expect($fp, $ok);
    }

    private function expect($fp, array $ok): void
    {
        $response = '';
        while (($line = fgets($fp, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $ok, true)) {
            throw new RuntimeException('Réponse SMTP inattendue (' . $code . ') : ' . trim($response));
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
        return implode("\r\n", $lines);
    }
}
