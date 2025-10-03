<?php declare(strict_types=1);

use Core\Emailer;
use Core\Emailer\EmailMessage;
use Core\Emailer\EmailException;
use Core\Emailer\Drivers\LogDriver;

beforeEach(function () {
    Emailer::reset();
});

afterEach(function () {
    Emailer::reset();
});

test('can create email message', function () {
    $message = Emailer::message();
    
    expect($message)->toBeInstanceOf(EmailMessage::class);
});

test('can send simple email', function () {
    Emailer::setDriver(new LogDriver(['path' => '/tmp/test-emails.log']));
    
    $message = Emailer::message()
        ->to('test@example.com')
        ->subject('Test Email')
        ->body('This is a test');
    
    $result = Emailer::send($message);
    
    expect($result)->toBeTrue();
    expect(Emailer::getSentEmails())->toHaveCount(1);
});

test('validates required fields', function () {
    Emailer::setDriver(new LogDriver(['path' => '/tmp/test-emails.log']));
    
    $message = Emailer::message()
        ->subject('Test');
    
    Emailer::send($message);
})->throws(EmailException::class, 'At least one recipient is required');

test('can add multiple recipients', function () {
    $message = Emailer::message()
        ->to('user1@example.com', 'User 1')
        ->to('user2@example.com', 'User 2')
        ->cc('manager@example.com')
        ->bcc('admin@example.com');
    
    expect($message->getTo())->toHaveCount(2);
    expect($message->getCc())->toHaveCount(1);
    expect($message->getBcc())->toHaveCount(1);
});

test('can attach files', function () {
    $tmpFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tmpFile, 'test content');
    
    $message = Emailer::message()
        ->attach($tmpFile, 'test.txt');
    
    expect($message->getAttachments())->toHaveCount(1);
    
    unlink($tmpFile);
});

test('can attach raw data', function () {
    $message = Emailer::message()
        ->attachData('test content', 'test.txt', 'text/plain');
    
    $attachments = $message->getAttachments();
    
    expect($attachments)->toHaveCount(1);
    expect($attachments[0]['data'])->toBe('test content');
    expect($attachments[0]['name'])->toBe('test.txt');
});

test('tracks sent email statistics', function () {
    Emailer::setDriver(new LogDriver(['path' => '/tmp/test-emails.log']));
    
    // Send 3 emails
    for ($i = 0; $i < 3; $i++) {
        $message = Emailer::message()
            ->to("test{$i}@example.com")
            ->subject("Test {$i}")
            ->body('Test');
        
        Emailer::send($message);
    }
    
    $stats = Emailer::getStats();
    
    expect($stats['total'])->toBe(3);
    expect($stats['successful'])->toBe(3);
    expect($stats['failed'])->toBe(0);
});

test('quick send helper works', function () {
    Emailer::setDriver(new LogDriver(['path' => '/tmp/test-emails.log']));
    
    $result = Emailer::sendTo(
        'test@example.com',
        'Quick Test',
        '<p>Quick send test</p>',
        true
    );
    
    expect($result)->toBeTrue();
    expect(Emailer::getSentEmails())->toHaveCount(1);
});

test('can set message priority', function () {
    $message = Emailer::message()
        ->priority(1);
    
    expect($message->getPriority())->toBe(1);
});

test('can add custom headers', function () {
    $message = Emailer::message()
        ->addHeader('X-Custom', 'value')
        ->addHeader('X-Campaign-ID', '12345');
    
    $headers = $message->getHeaders();
    
    expect($headers)->toHaveKey('X-Custom');
    expect($headers['X-Custom'])->toBe('value');
    expect($headers['X-Campaign-ID'])->toBe('12345');
});

test('can set reply-to address', function () {
    $message = Emailer::message()
        ->replyTo('reply@example.com', 'Reply Team');
    
    expect($message->getReplyTo())->toBe('reply@example.com');
    expect($message->getReplyToName())->toBe('Reply Team');
});

test('can clone message', function () {
    $original = Emailer::message()
        ->to('test@example.com')
        ->subject('Original');
    
    $clone = $original->clone();
    $clone->subject('Cloned');
    
    expect($original->getSubject())->toBe('Original');
    expect($clone->getSubject())->toBe('Cloned');
});

