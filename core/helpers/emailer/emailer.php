<?php declare(strict_types=1);

/**
 * Email Helper Functions
 */

use Core\Emailer;
use Core\Emailer\EmailMessage;

if (!function_exists('emailer')) {
    /**
     * Get Emailer instance or create a new message
     *
     * @return EmailMessage
     */
    function emailer(): EmailMessage
    {
        return Emailer::message();
    }
}

if (!function_exists('send_email')) {
    /**
     * Quick send email helper
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body
     * @param bool $isHtml Whether body is HTML
     * @return bool
     */
    function send_email(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        return Emailer::sendTo($to, $subject, $body, $isHtml);
    }
}

if (!function_exists('send_email_view')) {
    /**
     * Send email using a view template
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $view View template name
     * @param array $data Data to pass to the view
     * @return bool
     */
    function send_email_view(string $to, string $subject, string $view, array $data = []): bool
    {
        return Emailer::sendView($to, $subject, $view, $data);
    }
}

