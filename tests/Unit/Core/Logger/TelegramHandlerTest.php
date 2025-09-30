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
        
        protected function sendToTelegram(string $message): void {
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
        
        protected function sendToTelegram(string $message): void {
            $this->lastMessage = $message;
        }
    };
    
    $handler->handle('error', 'Test error message');
    
    expect($handler->lastMessage)->toContain('<b>ERROR</b>');
    expect($handler->lastMessage)->toContain('Test error message');
    expect($handler->lastMessage)->toContain('<i>');
});

test('TelegramHandler форматирует сообщения в Markdown', function () {
    $handler = new class('token', 'chat_id', 'Markdown', 'debug') extends TelegramHandler {
        public $lastMessage = null;
        
        protected function sendToTelegram(string $message): void {
            $this->lastMessage = $message;
        }
    };
    
    $handler->handle('info', 'Test info message');
    
    expect($handler->lastMessage)->toContain('*INFO*');
    expect($handler->lastMessage)->toContain('Test info message');
    expect($handler->lastMessage)->toContain('_');
});

test('TelegramHandler экранирует HTML спецсимволы', function () {
    $handler = new class('token', 'chat_id', 'HTML', 'debug') extends TelegramHandler {
        public $lastMessage = null;
        
        protected function sendToTelegram(string $message): void {
            $this->lastMessage = $message;
        }
    };
    
    $handler->handle('info', 'Message with <tags> & "quotes"');
    
    expect($handler->lastMessage)->toContain('&lt;tags&gt;');
    expect($handler->lastMessage)->toContain('&quot;quotes&quot;');
    expect($handler->lastMessage)->toContain('&amp;');
});

test('TelegramHandler добавляет эмодзи к уровням', function () {
    $handler = new class('token', 'chat_id', 'HTML', 'debug') extends TelegramHandler {
        public $messages = [];
        
        protected function sendToTelegram(string $message): void {
            $this->messages[] = $message;
        }
    };
    
    $handler->handle('debug', 'msg');
    $handler->handle('info', 'msg');
    $handler->handle('warning', 'msg');
    $handler->handle('error', 'msg');
    $handler->handle('critical', 'msg');
    
    expect($handler->messages[0])->toContain('🐛');
    expect($handler->messages[1])->toContain('ℹ️');
    expect($handler->messages[2])->toContain('⚠️');
    expect($handler->messages[3])->toContain('❌');
    expect($handler->messages[4])->toContain('🔥');
});

test('TelegramHandler включает timestamp в сообщение', function () {
    $handler = new class('token', 'chat_id', 'HTML', 'debug') extends TelegramHandler {
        public $lastMessage = null;
        
        protected function sendToTelegram(string $message): void {
            $this->lastMessage = $message;
        }
    };
    
    $handler->handle('info', 'Test');
    
    // Проверяем наличие даты в формате YYYY-MM-DD HH:MM:SS
    expect($handler->lastMessage)->toMatch('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');
});
