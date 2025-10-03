<?php declare(strict_types=1);

namespace Core\Emailer\Drivers;

use Core\Emailer\EmailDriverInterface;
use Core\Emailer\EmailMessage;
use Core\Emailer\EmailException;

/**
 * Mailgun API Email Driver
 * 
 * Отправка email через Mailgun API
 */
class MailgunDriver implements EmailDriverInterface
{
    protected string $apiKey;
    protected string $domain;
    protected string $endpoint;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->domain = $config['domain'] ?? '';
        $this->endpoint = $config['endpoint'] ?? 'api.mailgun.net';

        if (empty($this->apiKey)) {
            throw new EmailException('Mailgun API key is required');
        }

        if (empty($this->domain)) {
            throw new EmailException('Mailgun domain is required');
        }
    }

    public function send(EmailMessage $message): bool
    {
        $message->validate();

        $url = "https://{$this->endpoint}/v3/{$this->domain}/messages";
        $postFields = $this->buildPostFields($message);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $this->apiKey);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new EmailException("Mailgun API error: {$error}");
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new EmailException("Mailgun API returned status {$statusCode}: {$response}");
        }

        return true;
    }

    public function getName(): string
    {
        return 'mailgun';
    }

    /**
     * Build POST fields for Mailgun API
     */
    protected function buildPostFields(EmailMessage $message): array
    {
        $fields = [
            'from' => $message->getFromName() 
                ? $message->getFromName() . ' <' . $message->getFrom() . '>'
                : $message->getFrom(),
            'subject' => $message->getSubject(),
        ];

        // To recipients
        foreach ($message->getTo() as $recipient) {
            $fields['to'][] = $recipient['name']
                ? $recipient['name'] . ' <' . $recipient['email'] . '>'
                : $recipient['email'];
        }

        // CC recipients
        foreach ($message->getCc() as $recipient) {
            $fields['cc'][] = $recipient['name']
                ? $recipient['name'] . ' <' . $recipient['email'] . '>'
                : $recipient['email'];
        }

        // BCC recipients
        foreach ($message->getBcc() as $recipient) {
            $fields['bcc'][] = $recipient['name']
                ? $recipient['name'] . ' <' . $recipient['email'] . '>'
                : $recipient['email'];
        }

        // Body
        if ($message->isHtml()) {
            $fields['html'] = $message->getBody();
            
            if ($message->getAltBody()) {
                $fields['text'] = $message->getAltBody();
            }
        } else {
            $fields['text'] = $message->getBody();
        }

        // Reply-To
        if ($message->getReplyTo()) {
            $fields['h:Reply-To'] = $message->getReplyToName()
                ? $message->getReplyToName() . ' <' . $message->getReplyTo() . '>'
                : $message->getReplyTo();
        }

        // Attachments
        foreach ($message->getAttachments() as $attachment) {
            if (isset($attachment['data'])) {
                // For raw data, we need to use CURLFile with a temporary file
                $tmpFile = tempnam(sys_get_temp_dir(), 'mailgun_');
                file_put_contents($tmpFile, $attachment['data']);
                $fields['attachment'][] = new \CURLFile($tmpFile, $attachment['type'], $attachment['name']);
            } else {
                $fields['attachment'][] = new \CURLFile($attachment['path'], $attachment['type'], $attachment['name']);
            }
        }

        return $fields;
    }
}

