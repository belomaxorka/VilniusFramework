<?php declare(strict_types=1);

namespace Core\Emailer\Drivers;

use Core\Emailer\EmailDriverInterface;
use Core\Emailer\EmailMessage;
use Core\Emailer\EmailException;

/**
 * SMTP Email Driver
 * 
 * Отправка email через SMTP сервер
 */
class SmtpDriver implements EmailDriverInterface
{
    protected string $host;
    protected int $port;
    protected string $username;
    protected string $password;
    protected string $encryption; // tls, ssl, or empty
    protected int $timeout;
    protected $connection = null;

    public function __construct(array $config)
    {
        $this->host = $config['host'] ?? 'localhost';
        $this->port = $config['port'] ?? 587;
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->encryption = $config['encryption'] ?? 'tls';
        $this->timeout = $config['timeout'] ?? 30;
    }

    public function send(EmailMessage $message): bool
    {
        $message->validate();

        try {
            $this->connect();
            $this->authenticate();
            $this->sendMessage($message);
            $this->disconnect();
            return true;
        } catch (\Exception $e) {
            $this->disconnect();
            throw new EmailException("SMTP send failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function getName(): string
    {
        return 'smtp';
    }

    /**
     * Connect to SMTP server
     */
    protected function connect(): void
    {
        $host = $this->host;
        
        if ($this->encryption === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $this->connection = @fsockopen($host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->connection) {
            throw new EmailException("Failed to connect to SMTP server: {$errstr} ({$errno})");
        }

        stream_set_timeout($this->connection, $this->timeout);

        // Read server greeting
        $this->getResponse();

        // Send EHLO/HELO
        $this->sendCommand('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));

        // Start TLS if needed
        if ($this->encryption === 'tls') {
            $this->sendCommand('STARTTLS');
            
            if (!stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new EmailException('Failed to enable TLS encryption');
            }

            // Send EHLO again after TLS
            $this->sendCommand('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }
    }

    /**
     * Authenticate with SMTP server
     */
    protected function authenticate(): void
    {
        if (empty($this->username)) {
            return; // No authentication needed
        }

        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode($this->username));
        $this->sendCommand(base64_encode($this->password));
    }

    /**
     * Send the email message
     */
    protected function sendMessage(EmailMessage $message): void
    {
        // MAIL FROM
        $this->sendCommand('MAIL FROM: <' . $message->getFrom() . '>');

        // RCPT TO
        foreach ($message->getTo() as $recipient) {
            $this->sendCommand('RCPT TO: <' . $recipient['email'] . '>');
        }

        foreach ($message->getCc() as $recipient) {
            $this->sendCommand('RCPT TO: <' . $recipient['email'] . '>');
        }

        foreach ($message->getBcc() as $recipient) {
            $this->sendCommand('RCPT TO: <' . $recipient['email'] . '>');
        }

        // DATA
        $this->sendCommand('DATA');

        // Headers and body
        $data = $this->buildMessage($message);
        $this->sendData($data);
        $this->sendCommand('.');
    }

    /**
     * Build email message with headers and body
     */
    protected function buildMessage(EmailMessage $message): string
    {
        $eol = "\r\n";
        $parts = [];

        // From header
        $from = $message->getFromName() 
            ? $this->encodeHeader($message->getFromName()) . ' <' . $message->getFrom() . '>'
            : $message->getFrom();
        $parts[] = 'From: ' . $from;

        // To header
        $to = [];
        foreach ($message->getTo() as $recipient) {
            $to[] = $recipient['name'] 
                ? $this->encodeHeader($recipient['name']) . ' <' . $recipient['email'] . '>'
                : $recipient['email'];
        }
        $parts[] = 'To: ' . implode(', ', $to);

        // CC header
        if (!empty($message->getCc())) {
            $cc = [];
            foreach ($message->getCc() as $recipient) {
                $cc[] = $recipient['name']
                    ? $this->encodeHeader($recipient['name']) . ' <' . $recipient['email'] . '>'
                    : $recipient['email'];
            }
            $parts[] = 'Cc: ' . implode(', ', $cc);
        }

        // Reply-To header
        if ($message->getReplyTo()) {
            $replyTo = $message->getReplyToName()
                ? $this->encodeHeader($message->getReplyToName()) . ' <' . $message->getReplyTo() . '>'
                : $message->getReplyTo();
            $parts[] = 'Reply-To: ' . $replyTo;
        }

        // Subject
        $parts[] = 'Subject: ' . $this->encodeHeader($message->getSubject());

        // Date
        $parts[] = 'Date: ' . date('r');

        // Message-ID
        $parts[] = 'Message-ID: <' . md5(uniqid((string)time())) . '@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>';

        // MIME Version
        $parts[] = 'MIME-Version: 1.0';

        // Priority
        if ($message->getPriority() !== 3) {
            $parts[] = 'X-Priority: ' . $message->getPriority();
        }

        // Custom headers
        foreach ($message->getHeaders() as $name => $value) {
            $parts[] = "{$name}: {$value}";
        }

        // Content type and body
        if (!empty($message->getAttachments())) {
            // Email with attachments
            $boundary = '----=_Part_' . md5(uniqid((string)time()));
            $parts[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
            $parts[] = '';
            $parts[] = 'This is a multi-part message in MIME format.';
            $parts[] = '';
            $parts[] = '--' . $boundary;

            // Body part
            if ($message->isHtml()) {
                $parts[] = 'Content-Type: text/html; charset=' . $message->getCharset();
                $parts[] = 'Content-Transfer-Encoding: base64';
                $parts[] = '';
                $parts[] = chunk_split(base64_encode($message->getBody()));
            } else {
                $parts[] = 'Content-Type: text/plain; charset=' . $message->getCharset();
                $parts[] = 'Content-Transfer-Encoding: quoted-printable';
                $parts[] = '';
                $parts[] = quoted_printable_encode($message->getBody());
            }

            // Attachments
            foreach ($message->getAttachments() as $attachment) {
                $parts[] = '--' . $boundary;
                $parts[] = 'Content-Type: ' . $attachment['type'] . '; name="' . $attachment['name'] . '"';
                $parts[] = 'Content-Transfer-Encoding: base64';
                $parts[] = 'Content-Disposition: attachment; filename="' . $attachment['name'] . '"';
                $parts[] = '';

                if (isset($attachment['data'])) {
                    $data = $attachment['data'];
                } else {
                    $data = file_get_contents($attachment['path']);
                }

                $parts[] = chunk_split(base64_encode($data));
            }

            $parts[] = '--' . $boundary . '--';
        } else {
            // Simple email without attachments
            if ($message->isHtml()) {
                $parts[] = 'Content-Type: text/html; charset=' . $message->getCharset();
                $parts[] = 'Content-Transfer-Encoding: quoted-printable';
            } else {
                $parts[] = 'Content-Type: text/plain; charset=' . $message->getCharset();
                $parts[] = 'Content-Transfer-Encoding: quoted-printable';
            }
            $parts[] = '';
            $parts[] = quoted_printable_encode($message->getBody());
        }

        return implode($eol, $parts);
    }

    /**
     * Encode header for non-ASCII characters
     */
    protected function encodeHeader(string $text): string
    {
        if (mb_detect_encoding($text, 'ASCII', true)) {
            return $text;
        }

        return '=?UTF-8?B?' . base64_encode($text) . '?=';
    }

    /**
     * Send SMTP command
     */
    protected function sendCommand(string $command): void
    {
        fwrite($this->connection, $command . "\r\n");
        $this->getResponse();
    }

    /**
     * Send data (without reading response)
     */
    protected function sendData(string $data): void
    {
        fwrite($this->connection, $data . "\r\n");
    }

    /**
     * Get SMTP server response
     */
    protected function getResponse(): string
    {
        $response = '';
        
        while ($line = fgets($this->connection, 515)) {
            $response .= $line;
            
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);

        if ($code >= 400) {
            throw new EmailException("SMTP error: {$response}");
        }

        return $response;
    }

    /**
     * Disconnect from SMTP server
     */
    protected function disconnect(): void
    {
        if ($this->connection) {
            @fwrite($this->connection, "QUIT\r\n");
            @fclose($this->connection);
            $this->connection = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}

