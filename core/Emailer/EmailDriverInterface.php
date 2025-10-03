<?php declare(strict_types=1);

namespace Core\Emailer;

interface EmailDriverInterface
{
    /**
     * Send an email message
     *
     * @param EmailMessage $message The email message to send
     * @return bool True if sent successfully
     * @throws EmailException If sending fails
     */
    public function send(EmailMessage $message): bool;

    /**
     * Get the driver name
     *
     * @return string
     */
    public function getName(): string;
}

