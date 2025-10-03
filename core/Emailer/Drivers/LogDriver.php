<?php declare(strict_types=1);

namespace Core\Emailer\Drivers;

use Core\Emailer\EmailDriverInterface;
use Core\Emailer\EmailMessage;
use Core\Logger;

/**
 * Log Email Driver
 * 
 * Вместо отправки email записывает его в лог (полезно для разработки)
 */
class LogDriver implements EmailDriverInterface
{
    protected string $logFile;

    public function __construct(array $config)
    {
        $this->logFile = $config['path'] ?? LOG_DIR . '/emails.log';
    }

    public function send(EmailMessage $message): bool
    {
        $message->validate();

        $logEntry = $this->formatMessage($message);

        // Create directory if not exists
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->logFile, $logEntry . PHP_EOL . PHP_EOL, FILE_APPEND);

        Logger::info('Email logged instead of sent', [
            'to' => array_column($message->getTo(), 'email'),
            'subject' => $message->getSubject(),
        ]);

        return true;
    }

    public function getName(): string
    {
        return 'log';
    }

    /**
     * Format message for logging
     */
    protected function formatMessage(EmailMessage $message): string
    {
        $parts = [];
        $parts[] = '========================================';
        $parts[] = 'Email Message - ' . date('Y-m-d H:i:s');
        $parts[] = '========================================';
        $parts[] = 'From: ' . $message->getFrom() . ($message->getFromName() ? ' (' . $message->getFromName() . ')' : '');
        
        $to = [];
        foreach ($message->getTo() as $recipient) {
            $to[] = $recipient['email'] . ($recipient['name'] ? ' (' . $recipient['name'] . ')' : '');
        }
        $parts[] = 'To: ' . implode(', ', $to);

        if (!empty($message->getCc())) {
            $cc = [];
            foreach ($message->getCc() as $recipient) {
                $cc[] = $recipient['email'] . ($recipient['name'] ? ' (' . $recipient['name'] . ')' : '');
            }
            $parts[] = 'CC: ' . implode(', ', $cc);
        }

        if (!empty($message->getBcc())) {
            $bcc = [];
            foreach ($message->getBcc() as $recipient) {
                $bcc[] = $recipient['email'] . ($recipient['name'] ? ' (' . $recipient['name'] . ')' : '');
            }
            $parts[] = 'BCC: ' . implode(', ', $bcc);
        }

        if ($message->getReplyTo()) {
            $parts[] = 'Reply-To: ' . $message->getReplyTo() . ($message->getReplyToName() ? ' (' . $message->getReplyToName() . ')' : '');
        }

        $parts[] = 'Subject: ' . $message->getSubject();
        $parts[] = 'Format: ' . ($message->isHtml() ? 'HTML' : 'Plain Text');

        if (!empty($message->getAttachments())) {
            $attachments = [];
            foreach ($message->getAttachments() as $attachment) {
                $attachments[] = $attachment['name'] . ' (' . $attachment['type'] . ')';
            }
            $parts[] = 'Attachments: ' . implode(', ', $attachments);
        }

        $parts[] = '----------------------------------------';
        $parts[] = 'Body:';
        $parts[] = '----------------------------------------';
        $parts[] = $message->getBody();
        $parts[] = '========================================';

        return implode(PHP_EOL, $parts);
    }
}

