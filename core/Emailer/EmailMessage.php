<?php declare(strict_types=1);

namespace Core\Emailer;

/**
 * Email Message
 * 
 * Представляет email сообщение с поддержкой HTML, вложений, CC, BCC и т.д.
 */
class EmailMessage
{
    protected string $from = '';
    protected string $fromName = '';
    protected array $to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected string $replyTo = '';
    protected string $replyToName = '';
    protected string $subject = '';
    protected string $body = '';
    protected string $altBody = '';
    protected bool $isHtml = true;
    protected array $attachments = [];
    protected array $headers = [];
    protected string $charset = 'UTF-8';
    protected int $priority = 3; // 1 = High, 3 = Normal, 5 = Low

    /**
     * Set sender email
     */
    public function from(string $email, string $name = ''): self
    {
        $this->from = $email;
        $this->fromName = $name;
        return $this;
    }

    /**
     * Add recipient
     */
    public function to(string $email, string $name = ''): self
    {
        $this->to[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * Add CC recipient
     */
    public function cc(string $email, string $name = ''): self
    {
        $this->cc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * Add BCC recipient
     */
    public function bcc(string $email, string $name = ''): self
    {
        $this->bcc[] = ['email' => $email, 'name' => $name];
        return $this;
    }

    /**
     * Set reply-to address
     */
    public function replyTo(string $email, string $name = ''): self
    {
        $this->replyTo = $email;
        $this->replyToName = $name;
        return $this;
    }

    /**
     * Set email subject
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set email body
     */
    public function body(string $body, bool $isHtml = true): self
    {
        $this->body = $body;
        $this->isHtml = $isHtml;
        return $this;
    }

    /**
     * Set alternative plain text body (for HTML emails)
     */
    public function altBody(string $altBody): self
    {
        $this->altBody = $altBody;
        return $this;
    }

    /**
     * Attach a file
     */
    public function attach(string $path, string $name = '', string $type = ''): self
    {
        if (!file_exists($path)) {
            throw new EmailException("Attachment file not found: {$path}");
        }

        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?: basename($path),
            'type' => $type ?: mime_content_type($path),
        ];

        return $this;
    }

    /**
     * Attach raw data as a file
     */
    public function attachData(string $data, string $name, string $type = 'application/octet-stream'): self
    {
        $this->attachments[] = [
            'data' => $data,
            'name' => $name,
            'type' => $type,
        ];

        return $this;
    }

    /**
     * Add custom header
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set message priority
     */
    public function priority(int $priority): self
    {
        $this->priority = max(1, min(5, $priority));
        return $this;
    }

    /**
     * Set charset
     */
    public function charset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    // Getters

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    public function getReplyToName(): string
    {
        return $this->replyToName;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getAltBody(): string
    {
        return $this->altBody;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Validate the message
     */
    public function validate(): bool
    {
        if (empty($this->from)) {
            throw new EmailException('Sender email is required');
        }

        if (empty($this->to)) {
            throw new EmailException('At least one recipient is required');
        }

        if (empty($this->subject)) {
            throw new EmailException('Subject is required');
        }

        if (empty($this->body)) {
            throw new EmailException('Body is required');
        }

        return true;
    }

    /**
     * Clone the message
     */
    public function clone(): self
    {
        return clone $this;
    }
}

