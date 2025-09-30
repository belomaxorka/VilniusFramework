<?php

use Core\Logger\TelegramHandler;

test('TelegramHandler создается с правильными параметрами', function () {
    $handler = new TelegramHandler(
        'bot_token',
        'chat_id',
        'HTML',
        'error'
    );

    expect($handler)->toBeInstanceOf(TelegramHandler::class);
});

test('TelegramHandler не отправляет если токен или chat_id пустые', function () {
    $handler1 = new TelegramHandler('', 'chat_id', 'HTML', 'error');
    $handler2 = new TelegramHandler('token', '', 'HTML', 'error');

    // Не должно быть исключений
    $handler1->handle('error', 'Test');
    $handler2->handle('error', 'Test');

    expect(true)->toBeTrue();
});

test('TelegramHandler фильтрует по минимальному уровню', function () {
    $handler = new class('token', 'chat_id', 'HTML', 'error') extends TelegramHandler {
        public $sentMessages = [];

        protected function sendToTelegram(string $message): void
        {
            $this->sentMessages[] = $message;
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

test('TelegramHandler форматирует сообщения в HTML', function () {
    $handler = new class('token', 'chat_id', 'HTML', 'debug') extends TelegramHandler {
        public $lastMessage = null;

        protected function sendToTelegram(string $message): void
        {
            $this->lastMessage = $message;
        }
    };

    $handler->handle('error', 'Test error message');

    expect(str_contains($handler->lastMessage, '<b>ERROR</b>'))->toBeTrue();
    expect(str_contains($handler->lastMessage, 'Test error message'))->toBeTrue();
    expect(str_contains($handler->lastMessage, '<i>'))->toBeTrue();
});

test('TelegramHandler форматирует сообщения в Markdown', function () {
    $handler = new class('token', 'chat_id', 'Markdown', 'debug') extends TelegramHandler {
        public $lastMessage = null;

        protected function sendToTelegram(string $message): void
        {
            $this->lastMessage = $message;
        }
    };

    $handler->handle('info', 'Test info message');

    expect(str_contains($handler->lastMessage, '*INFO*'))->toBeTrue();
    expect(str_contains($handler->lastMessage, 'Test info message'))->toBeTrue();
    expect(str_contains($handler->lastMessage, '_'))->toBeTrue();
});

test('TelegramHandler экранирует HTML спецсимволы', function () {
    $handler = new class('token', 'chat_id', 'HTML', 'debug') extends TelegramHandler {
        public $lastMessage = null;

        protected function sendToTelegram(string $message): void
        {
            $this->lastMessage = $message;
        }
    };

    $handler->handle('info', 'Message with <tags> & "quotes"');

    expect(str_contains($handler->lastMessage, '&lt;tags&gt;'))->toBeTrue();
    expect(str_contains($handler->lastMessage, '&quot;quotes&quot;'))->toBeTrue();
    expect(str_contains($handler->lastMessage, '&amp;'))->toBeTrue();
});

test('TelegramHandler добавляет эмодзи к уровням', function () {
    $handler = new class('token', 'chat_id', 'HTML', 'debug') extends TelegramHandler {
        public $messages = [];

        protected function sendToTelegram(string $message): void
        {
            $this->messages[] = $message;
        }
    };

    $handler->handle('debug', 'msg');
    $handler->handle('info', 'msg');
    $handler->handle('warning', 'msg');
    $handler->handle('error', 'msg');
    $handler->handle('critical', 'msg');

    expect(str_contains($handler->messages[0], '🐛'))->toBeTrue();
    expect(str_contains($handler->messages[1], 'ℹ️'))->toBeTrue();
    expect(str_contains($handler->messages[2], '⚠️'))->toBeTrue();
    expect(str_contains($handler->messages[3], '❌'))->toBeTrue();
    expect(str_contains($handler->messages[4], '🔥'))->toBeTrue();
});

test('TelegramHandler включает timestamp в сообщение', function () {
    $handler = new class('token', 'chat_id', 'HTML', 'debug') extends TelegramHandler {
        public $lastMessage = null;

        protected function sendToTelegram(string $message): void
        {
            $this->lastMessage = $message;
        }
    };

    $handler->handle('info', 'Test');

    // Проверяем наличие даты в формате YYYY-MM-DD HH:MM:SS
    expect($handler->lastMessage)->toMatch('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');
});
