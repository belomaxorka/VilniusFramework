<?php declare(strict_types=1);

namespace Core\Emailer\Drivers;

use Core\Emailer\EmailDriverInterface;
use Core\Emailer\EmailMessage;
use Core\Emailer\EmailException;

/**
 * SendGrid API Email Driver
 * 
 * Отправка email через SendGrid API
 */
class SendGridDriver implements EmailDriverInterface
{
    protected string $apiKey;
    protected string $apiEndpoint = 'https://api.sendgrid.com/v3/mail/send';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';

        if (empty($this->apiKey)) {
            throw new EmailException('SendGrid API key is required');
        }
    }

    public function send(EmailMessage $message): bool
    {
        $message->validate();

        $payload = $this->buildPayload($message);

        $ch = curl_init($this->apiEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new EmailException("SendGrid API error: {$error}");
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new EmailException("SendGrid API returned status {$statusCode}: {$response}");
        }

        return true;
    }

    public function getName(): string
    {
        return 'sendgrid';
    }

    /**
     * Build SendGrid API payload
     */
    protected function buildPayload(EmailMessage $message): array
    {
        $payload = [
            'personalizations' => [
                [
                    'to' => $this->formatRecipients($message->getTo()),
                ],
            ],
            'from' => [
                'email' => $message->getFrom(),
                'name' => $message->getFromName() ?: null,
            ],
            'subject' => $message->getSubject(),
            'content' => [
                [
                    'type' => $message->isHtml() ? 'text/html' : 'text/plain',
                    'value' => $message->getBody(),
                ],
            ],
        ];

        // CC recipients
        if (!empty($message->getCc())) {
            $payload['personalizations'][0]['cc'] = $this->formatRecipients($message->getCc());
        }

        // BCC recipients
        if (!empty($message->getBcc())) {
            $payload['personalizations'][0]['bcc'] = $this->formatRecipients($message->getBcc());
        }

        // Reply-To
        if ($message->getReplyTo()) {
            $payload['reply_to'] = [
                'email' => $message->getReplyTo(),
                'name' => $message->getReplyToName() ?: null,
            ];
        }

        // Attachments
        if (!empty($message->getAttachments())) {
            $payload['attachments'] = $this->formatAttachments($message->getAttachments());
        }

        return $payload;
    }

    /**
     * Format recipients for SendGrid API
     */
    protected function formatRecipients(array $recipients): array
    {
        $formatted = [];

        foreach ($recipients as $recipient) {
            $item = ['email' => $recipient['email']];
            
            if (!empty($recipient['name'])) {
                $item['name'] = $recipient['name'];
            }

            $formatted[] = $item;
        }

        return $formatted;
    }

    /**
     * Format attachments for SendGrid API
     */
    protected function formatAttachments(array $attachments): array
    {
        $formatted = [];

        foreach ($attachments as $attachment) {
            if (isset($attachment['data'])) {
                $content = base64_encode($attachment['data']);
            } else {
                $content = base64_encode(file_get_contents($attachment['path']));
            }

            $formatted[] = [
                'content' => $content,
                'filename' => $attachment['name'],
                'type' => $attachment['type'],
                'disposition' => 'attachment',
            ];
        }

        return $formatted;
    }
}

