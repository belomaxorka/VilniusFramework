<?php

use Core\Logger\SlackHandler;

test('SlackHandler is created with correct parameters', function () {
    $handler = new SlackHandler(
        'https://hooks.slack.com/test',
        '#logs',
        'Bot',
        ':robot:',
        'error'
    );

    expect($handler)->toBeInstanceOf(SlackHandler::class);
});

test('SlackHandler does not send if webhook is empty', function () {
    $handler = new SlackHandler('', '#logs', 'Bot', ':robot:', 'error');

    // Не должно быть исключений
    $handler->handle('error', 'Test message');

    expect(true)->toBeTrue();
});

test('SlackHandler filters by minimum level', function () {
    // Создаем mock handler чтобы проверить фильтрацию
    $handler = new class('https://hooks.slack.com/test', '#logs', 'Bot', ':robot:', 'error') extends SlackHandler {
        public $sentMessages = [];

        protected function sendToSlack(array $payload): void
        {
            $this->sentMessages[] = $payload;
        }
    };

    // Эти не должны отправиться
    $handler->handle('debug', 'Debug');
    $handler->handle('info', 'Info');
    $handler->handle('warning', 'Warning');

    expect($handler->sentMessages)->toHaveCount(0);

    // Эти должны отправиться
    $handler->handle('error', 'Error');
    $handler->handle('critical', 'Critical');

    expect($handler->sentMessages)->toHaveCount(2);
});

test('SlackHandler builds correct payload', function () {
    $handler = new class('https://hooks.slack.com/test', '#logs', 'TestBot', ':robot:', 'debug') extends SlackHandler {
        public $lastPayload = null;

        protected function sendToSlack(array $payload): void
        {
            $this->lastPayload = $payload;
        }
    };

    $handler->handle('error', 'Test error message');

    expect($handler->lastPayload)->toHaveKey('channel');
    expect($handler->lastPayload)->toHaveKey('username');
    expect($handler->lastPayload)->toHaveKey('icon_emoji');
    expect($handler->lastPayload)->toHaveKey('attachments');

    expect($handler->lastPayload['channel'])->toBe('#logs');
    expect($handler->lastPayload['username'])->toBe('TestBot');
    expect($handler->lastPayload['icon_emoji'])->toBe(':robot:');

    $attachment = $handler->lastPayload['attachments'][0];
    expect($attachment)->toHaveKey('color');
    expect($attachment)->toHaveKey('title');
    expect($attachment)->toHaveKey('text');
    expect($attachment['text'])->toBe('Test error message');
    expect(str_contains($attachment['title'], 'ERROR'))->toBeTrue();
});

test('SlackHandler uses correct colors for levels', function () {
    $handler = new class('https://hooks.slack.com/test', '#logs', 'Bot', ':robot:', 'debug') extends SlackHandler {
        public $colors = [];

        protected function sendToSlack(array $payload): void
        {
            $this->colors[] = $payload['attachments'][0]['color'];
        }
    };

    $handler->handle('debug', 'msg');
    $handler->handle('info', 'msg');
    $handler->handle('warning', 'msg');
    $handler->handle('error', 'msg');
    $handler->handle('critical', 'msg');

    expect($handler->colors)->toHaveCount(5);
    expect($handler->colors[0])->toBe('#6c757d'); // debug - серый
    expect($handler->colors[1])->toBe('#17a2b8'); // info - голубой
    expect($handler->colors[2])->toBe('#ffc107'); // warning - желтый
    expect($handler->colors[3])->toBe('#dc3545'); // error - красный
    expect($handler->colors[4])->toBe('#721c24'); // critical - темно-красный
});

test('SlackHandler adds emoji to levels', function () {
    $handler = new class('https://hooks.slack.com/test', '#logs', 'Bot', ':robot:', 'debug') extends SlackHandler {
        public $titles = [];

        protected function sendToSlack(array $payload): void
        {
            $this->titles[] = $payload['attachments'][0]['title'];
        }
    };

    $handler->handle('debug', 'msg');
    $handler->handle('info', 'msg');
    $handler->handle('warning', 'msg');
    $handler->handle('error', 'msg');
    $handler->handle('critical', 'msg');

    expect(str_contains($handler->titles[0], '🐛'))->toBeTrue();
    expect(str_contains($handler->titles[1], 'ℹ️'))->toBeTrue();
    expect(str_contains($handler->titles[2], '⚠️'))->toBeTrue();
    expect(str_contains($handler->titles[3], '❌'))->toBeTrue();
    expect(str_contains($handler->titles[4], '🔥'))->toBeTrue();
});
