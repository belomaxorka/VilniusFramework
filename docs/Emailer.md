# Emailer System

Система отправки email с поддержкой различных драйверов (SMTP, SendGrid, Mailgun) и множеством функций.

## Быстрый старт

### Основное использование

```php
use Core\Emailer;

// Создать и отправить email
$message = Emailer::message()
    ->to('user@example.com', 'John Doe')
    ->subject('Welcome!')
    ->body('<h1>Welcome to our platform!</h1>', true);

Emailer::send($message);
```

### Используя helper функции

```php
// Быстрая отправка
send_email('user@example.com', 'Hello', '<p>Hello World!</p>');

// Отправка с использованием шаблона
send_email_view('user@example.com', 'Welcome', 'emails/welcome', [
    'name' => 'John',
]);
```

## Конфигурация

Конфигурация находится в `config/mail.php`:

```php
return [
    'default' => env('MAIL_DRIVER', 'log'),
    
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_FROM_NAME', 'My App'),
    ],
    
    'drivers' => [
        'smtp' => [...],
        'sendgrid' => [...],
        'mailgun' => [...],
        'log' => [...],
    ],
];
```

### Переменные окружения (.env)

```env
# Основные настройки
MAIL_DRIVER=smtp
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="My Application"

# SMTP
MAIL_SMTP_HOST=smtp.mailtrap.io
MAIL_SMTP_PORT=587
MAIL_SMTP_USERNAME=your_username
MAIL_SMTP_PASSWORD=your_password
MAIL_SMTP_ENCRYPTION=tls

# SendGrid
MAIL_SENDGRID_API_KEY=your_api_key

# Mailgun
MAIL_MAILGUN_API_KEY=your_api_key
MAIL_MAILGUN_DOMAIN=your_domain.com
```

## Драйверы

### SMTP Driver

Стандартная отправка через SMTP сервер:

```php
'smtp' => [
    'driver' => 'smtp',
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'user@example.com',
    'password' => 'secret',
    'encryption' => 'tls', // tls, ssl, или пусто
    'timeout' => 30,
]
```

### SendGrid Driver

Отправка через SendGrid API:

```php
'sendgrid' => [
    'driver' => 'sendgrid',
    'api_key' => 'your_sendgrid_api_key',
]
```

### Mailgun Driver

Отправка через Mailgun API:

```php
'mailgun' => [
    'driver' => 'mailgun',
    'api_key' => 'your_mailgun_api_key',
    'domain' => 'your-domain.com',
    'endpoint' => 'api.mailgun.net', // или api.eu.mailgun.net для EU
]
```

### Log Driver

Логирование email вместо отправки (для разработки):

```php
'log' => [
    'driver' => 'log',
    'path' => LOG_DIR . '/emails.log',
]
```

## Создание сообщений

### Базовое сообщение

```php
$message = Emailer::message()
    ->to('user@example.com', 'John Doe')
    ->subject('Hello')
    ->body('Hello, World!', false); // false = plain text
```

### HTML сообщение

```php
$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Welcome!')
    ->body('<h1>Welcome!</h1><p>Thank you for joining.</p>', true);
```

### Множественные получатели

```php
$message = Emailer::message()
    ->to('user1@example.com', 'User 1')
    ->to('user2@example.com', 'User 2')
    ->cc('manager@example.com')
    ->bcc('admin@example.com')
    ->subject('Team Update')
    ->body('...');
```

### Reply-To адрес

```php
$message = Emailer::message()
    ->to('user@example.com')
    ->replyTo('support@example.com', 'Support Team')
    ->subject('Support Response')
    ->body('...');
```

### Вложения

```php
// Прикрепить файл
$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Invoice')
    ->body('Please find attached invoice.')
    ->attach('/path/to/invoice.pdf', 'invoice.pdf');

// Прикрепить данные
$message->attachData($pdfContent, 'report.pdf', 'application/pdf');
```

### Приоритет

```php
$message = Emailer::message()
    ->to('user@example.com')
    ->subject('URGENT: Server Down')
    ->body('...')
    ->priority(1); // 1 = High, 3 = Normal, 5 = Low
```

### Пользовательские заголовки

```php
$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Newsletter')
    ->body('...')
    ->addHeader('X-Campaign-ID', '123456')
    ->addHeader('X-Mailer', 'MyFramework');
```

## Отправка с шаблонами

### Создание email шаблона

`resources/views/emails/welcome.twig`:

```html
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .button { background: #007bff; color: white; padding: 10px 20px; }
    </style>
</head>
<body>
    <h1>Welcome, {{ name }}!</h1>
    <p>Thank you for joining {{ app_name }}.</p>
    <a href="{{ activation_link }}" class="button">Activate Account</a>
</body>
</html>
```

### Использование шаблона

```php
Emailer::sendView(
    'user@example.com',
    'Welcome to Our Platform',
    'emails/welcome',
    [
        'name' => 'John Doe',
        'app_name' => 'MyApp',
        'activation_link' => 'https://example.com/activate/token',
    ]
);
```

## API Reference

### Emailer Class

#### `Emailer::message(): EmailMessage`

Создать новое email сообщение с настройками по умолчанию.

#### `Emailer::send(EmailMessage $message): bool`

Отправить email сообщение.

#### `Emailer::sendTo(string $to, string $subject, string $body, bool $isHtml = true): bool`

Быстрая отправка email.

#### `Emailer::sendView(string $to, string $subject, string $view, array $data = []): bool`

Отправка email с использованием шаблона.

#### `Emailer::getSentEmails(): array`

Получить историю отправленных email (для Debug Toolbar).

#### `Emailer::getStats(): array`

Получить статистику отправки.

### EmailMessage Class

#### Методы установки

- `from(string $email, string $name = ''): self`
- `to(string $email, string $name = ''): self`
- `cc(string $email, string $name = ''): self`
- `bcc(string $email, string $name = ''): self`
- `replyTo(string $email, string $name = ''): self`
- `subject(string $subject): self`
- `body(string $body, bool $isHtml = true): self`
- `altBody(string $altBody): self` - альтернативный plain text для HTML
- `attach(string $path, string $name = '', string $type = ''): self`
- `attachData(string $data, string $name, string $type = ''): self`
- `addHeader(string $name, string $value): self`
- `priority(int $priority): self` - 1-5
- `charset(string $charset): self`

#### Методы получения

- `getFrom(): string`
- `getFromName(): string`
- `getTo(): array`
- `getCc(): array`
- `getBcc(): array`
- `getSubject(): string`
- `getBody(): string`
- И другие...

## Тестирование

### Консольная команда

```bash
php vilnius email:test user@example.com
```

### В тестах

```php
use Core\Emailer;

// Используйте log driver для тестов
Emailer::setDriver(new \Core\Emailer\Drivers\LogDriver([
    'path' => '/tmp/test-emails.log'
]));

// Отправить email
$message = Emailer::message()
    ->to('test@example.com')
    ->subject('Test')
    ->body('Test email');

Emailer::send($message);

// Проверить историю
$sent = Emailer::getSentEmails();
assert(count($sent) === 1);
assert($sent[0]['subject'] === 'Test');
```

## Debug Toolbar

Email Collector автоматически собирает информацию обо всех отправленных email:

- Количество отправленных
- Успешные / неудачные
- Время отправки
- Драйвер
- Детали каждого email

## Примеры использования

### Регистрация пользователя

```php
public function sendWelcomeEmail(User $user): void
{
    Emailer::sendView(
        $user->email,
        'Welcome to ' . Config::get('app.name'),
        'emails/welcome',
        [
            'user' => $user,
            'activation_link' => url('/activate/' . $user->activation_token),
        ]
    );
}
```

### Сброс пароля

```php
public function sendPasswordReset(User $user, string $token): void
{
    $message = Emailer::message()
        ->to($user->email, $user->name)
        ->subject('Password Reset Request')
        ->body(view('emails/password-reset', [
            'user' => $user,
            'reset_link' => url('/reset-password/' . $token),
            'expires_at' => now()->addHours(2),
        ]));

    Emailer::send($message);
}
```

### Счет с вложением

```php
public function sendInvoice(User $user, Invoice $invoice): void
{
    $pdfPath = $invoice->generatePdf();

    $message = Emailer::message()
        ->to($user->email, $user->name)
        ->subject('Invoice #' . $invoice->number)
        ->body(view('emails/invoice', [
            'user' => $user,
            'invoice' => $invoice,
        ]))
        ->attach($pdfPath, 'invoice-' . $invoice->number . '.pdf');

    Emailer::send($message);
}
```

## Обработка ошибок

```php
use Core\Emailer\EmailException;

try {
    Emailer::send($message);
} catch (EmailException $e) {
    Logger::error('Failed to send email: ' . $e->getMessage());
    // Handle error...
}
```

## Создание собственного драйвера

```php
use Core\Emailer\EmailDriverInterface;
use Core\Emailer\EmailMessage;

class MyCustomDriver implements EmailDriverInterface
{
    public function send(EmailMessage $message): bool
    {
        // Ваша логика отправки
        return true;
    }

    public function getName(): string
    {
        return 'custom';
    }
}

// Регистрация
Emailer::setDriver(new MyCustomDriver());
```

## Best Practices

1. **Используйте Log Driver для разработки** - избегайте отправки реальных email во время разработки
2. **Валидируйте email адреса** перед отправкой
3. **Используйте шаблоны** для сложных email
4. **Обрабатывайте ошибки** - отправка может не сработать
5. **Тестируйте шаблоны** на разных email клиентах
6. **Используйте очереди** для массовой рассылки (будущая функция)
7. **Логируйте отправку** для аудита

