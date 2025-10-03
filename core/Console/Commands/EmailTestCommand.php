<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Emailer;

/**
 * Email Test Command
 * 
 * Отправляет тестовое email сообщение
 */
class EmailTestCommand extends Command
{
    protected string $signature = 'email:test';
    protected string $description = 'Send a test email';

    public function handle(): int
    {
        $this->info('Sending test email...');

        try {
            $to = $this->input->getArgument(1) ?? 'test@example.com';
            
            $message = Emailer::message()
                ->to($to)
                ->subject('Test Email from Framework')
                ->body('<h1>Hello!</h1><p>This is a test email from your framework.</p>', true);

            $result = Emailer::send($message);

            if ($result) {
                $this->success("Test email sent successfully to {$to}");
                $this->info("Driver used: " . Emailer::getDriver()->getName());
                return 0;
            } else {
                $this->error('Failed to send test email');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}

