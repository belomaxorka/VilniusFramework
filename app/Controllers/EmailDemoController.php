<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Emailer;

class EmailDemoController extends Controller
{
    /**
     * Send a simple test email
     */
    public function sendSimple()
    {
        try {
            $message = Emailer::message()
                ->to('user@example.com', 'Test User')
                ->subject('Simple Test Email')
                ->body('<h1>Hello!</h1><p>This is a simple test email.</p>', true);

            $result = Emailer::send($message);

            if ($result) {
                return $this->json([
                    'success' => true,
                    'message' => 'Email sent successfully!',
                    'driver' => Emailer::getDriver()->getName(),
                ]);
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send email with template
     */
    public function sendWithTemplate()
    {
        try {
            $result = Emailer::sendView(
                'user@example.com',
                'Welcome to Our Platform',
                'emails/test',
                [
                    'title' => 'Welcome!',
                    'name' => 'John Doe',
                    'message' => 'Thank you for joining our awesome platform!',
                    'button_text' => 'Get Started',
                    'button_url' => 'https://example.com/dashboard',
                    'app_name' => 'My Framework',
                ]
            );

            return $this->json([
                'success' => true,
                'message' => 'Template email sent successfully!',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send email with attachment
     */
    public function sendWithAttachment()
    {
        try {
            // Create a temporary test file
            $tmpFile = tempnam(sys_get_temp_dir(), 'email_test_');
            file_put_contents($tmpFile, "This is a test attachment.\nCreated at: " . date('Y-m-d H:i:s'));

            $message = Emailer::message()
                ->to('user@example.com')
                ->subject('Email with Attachment')
                ->body('<h1>Document Attached</h1><p>Please find the attached document.</p>', true)
                ->attach($tmpFile, 'test-document.txt', 'text/plain');

            $result = Emailer::send($message);

            // Clean up
            unlink($tmpFile);

            return $this->json([
                'success' => true,
                'message' => 'Email with attachment sent successfully!',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send email to multiple recipients
     */
    public function sendToMultiple()
    {
        try {
            $message = Emailer::message()
                ->to('user1@example.com', 'User One')
                ->to('user2@example.com', 'User Two')
                ->cc('manager@example.com', 'Manager')
                ->bcc('admin@example.com')
                ->subject('Team Update')
                ->body('<h1>Team Update</h1><p>Important update for the whole team.</p>', true);

            $result = Emailer::send($message);

            return $this->json([
                'success' => true,
                'message' => 'Email sent to multiple recipients!',
                'recipients' => [
                    'to' => count($message->getTo()),
                    'cc' => count($message->getCc()),
                    'bcc' => count($message->getBcc()),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get email statistics
     */
    public function getStats()
    {
        $stats = Emailer::getStats();
        $sentEmails = Emailer::getSentEmails();

        return $this->json([
            'stats' => $stats,
            'recent_emails' => array_slice($sentEmails, -5), // Last 5 emails
        ]);
    }

    /**
     * Quick send using helper
     */
    public function quickSend()
    {
        try {
            $result = send_email(
                'user@example.com',
                'Quick Send Test',
                '<p>This email was sent using the helper function!</p>',
                true
            );

            return $this->json([
                'success' => true,
                'message' => 'Quick send successful!',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

