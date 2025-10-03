# 📧 Emailer System

Полноценная система отправки email для вашего фреймворка с поддержкой нескольких драйверов и множеством функций.

## ✨ Особенности

- 🚀 **Множественные драйверы**: SMTP, SendGrid, Mailgun, Log
- 📧 **HTML и Plain Text** emails
- 📎 **Вложения** (файлы и raw data)
- 👥 **CC, BCC, Reply-To**
- 🎨 **Шаблоны** (integration с TemplateEngine)
- ⚡ **Простой API**
- 🔍 **Debug Toolbar** интеграция
- 📊 **Статистика отправки**
- 🛡️ **Обработка ошибок**
- 🧪 **Полное покрытие тестами**

## 📁 Структура

```
core/
├── Emailer.php                          # Главный менеджер
└── Emailer/
    ├── EmailDriverInterface.php         # Интерфейс драйвера
    ├── EmailMessage.php                 # Класс сообщения
    ├── EmailException.php               # Исключения
    └── Drivers/
        ├── SmtpDriver.php              # SMTP драйвер
        ├── SendGridDriver.php          # SendGrid API
        ├── MailgunDriver.php           # Mailgun API
        └── LogDriver.php               # Log драйвер (для разработки)

core/helpers/emailer/
└── emailer.php                          # Helper функции

core/Console/Commands/
└── EmailTestCommand.php                 # Команда для тестирования

core/DebugToolbar/Collectors/
└── EmailCollector.php                   # Debug Toolbar collector

config/
└── mail.php                             # Конфигурация

docs/
├── Emailer.md                           # Полная документация
├── EmailerQuickStart.md                 # Быстрый старт
└── EmailerExamples.md                   # Примеры

tests/Unit/
└── EmailerTest.php                      # Unit тесты

resources/views/emails/
└── test.twig                            # Пример шаблона
```

## 🚀 Быстрый старт

### 1. Конфигурация

Добавьте в `.env`:

```env
MAIL_DRIVER=log
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="My Application"
```

### 2. Простая отправка

```php
use Core\Emailer;

$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Hello!')
    ->body('<h1>Hello World!</h1>', true);

Emailer::send($message);
```

### 3. С использованием helper

```php
send_email('user@example.com', 'Hello', '<p>Hello World!</p>');
```

### 4. С шаблоном

```php
send_email_view(
    'user@example.com',
    'Welcome!',
    'emails/welcome',
    ['name' => 'John']
);
```

## 💻 Примеры использования

### HTML Email

```php
Emailer::message()
    ->to('user@example.com', 'John Doe')
    ->subject('Welcome!')
    ->body('<h1>Welcome</h1><p>Thank you for joining!</p>', true)
    ->send();
```

### С вложениями

```php
Emailer::message()
    ->to('user@example.com')
    ->subject('Invoice')
    ->body('Your invoice is attached.')
    ->attach('/path/to/invoice.pdf', 'invoice.pdf')
    ->send();
```

### Множественные получатели

```php
Emailer::message()
    ->to('user1@example.com')
    ->to('user2@example.com')
    ->cc('manager@example.com')
    ->bcc('admin@example.com')
    ->subject('Team Update')
    ->body('Important update...')
    ->send();
```

### С приоритетом

```php
Emailer::message()
    ->to('user@example.com')
    ->subject('URGENT')
    ->body('Urgent message')
    ->priority(1) // 1 = High, 3 = Normal, 5 = Low
    ->send();
```

## 🔧 Драйверы

### Log Driver (для разработки)

```env
MAIL_DRIVER=log
MAIL_LOG_PATH=storage/logs/emails.log
```

### SMTP

```env
MAIL_DRIVER=smtp
MAIL_SMTP_HOST=smtp.example.com
MAIL_SMTP_PORT=587
MAIL_SMTP_USERNAME=your_email@example.com
MAIL_SMTP_PASSWORD=your_password
MAIL_SMTP_ENCRYPTION=tls
```

### SendGrid

```env
MAIL_DRIVER=sendgrid
MAIL_SENDGRID_API_KEY=your_sendgrid_api_key
```

### Mailgun

```env
MAIL_DRIVER=mailgun
MAIL_MAILGUN_API_KEY=your_mailgun_api_key
MAIL_MAILGUN_DOMAIN=mg.yourdomain.com
```

## 🧪 Тестирование

### Консольная команда

```bash
php vilnius email:test user@example.com
```

### Unit тесты

```bash
./vendor/bin/pest tests/Unit/EmailerTest.php
```

### В коде

```php
// Использовать log driver для тестов
Emailer::setDriver(new \Core\Emailer\Drivers\LogDriver([
    'path' => '/tmp/test-emails.log'
]));

// Проверить историю
$sent = Emailer::getSentEmails();
```

## 📊 Debug Toolbar

Email Collector автоматически добавляется в Debug Toolbar и показывает:

- ✉️ Количество отправленных emails
- ✅ Успешные отправки
- ❌ Ошибки
- ⏱️ Время отправки
- 📝 Детали каждого email

## 📖 API Reference

### Emailer Class

- `Emailer::message()` - создать новое сообщение
- `Emailer::send(EmailMessage $message)` - отправить сообщение
- `Emailer::sendTo(string $to, string $subject, string $body)` - быстрая отправка
- `Emailer::sendView(string $to, string $subject, string $view, array $data)` - с шаблоном
- `Emailer::getSentEmails()` - история отправок
- `Emailer::getStats()` - статистика

### EmailMessage Class

- `from(string $email, string $name = '')`
- `to(string $email, string $name = '')`
- `cc(string $email, string $name = '')`
- `bcc(string $email, string $name = '')`
- `replyTo(string $email, string $name = '')`
- `subject(string $subject)`
- `body(string $body, bool $isHtml = true)`
- `attach(string $path, string $name = '')`
- `attachData(string $data, string $name, string $type = '')`
- `priority(int $priority)` - 1-5
- `addHeader(string $name, string $value)`

### Helper Functions

- `emailer()` - создать сообщение
- `send_email($to, $subject, $body, $isHtml = true)` - быстрая отправка
- `send_email_view($to, $subject, $view, $data = [])` - с шаблоном

## 📚 Документация

- **[Emailer.md](docs/Emailer.md)** - Полная документация
- **[EmailerQuickStart.md](docs/EmailerQuickStart.md)** - Быстрый старт
- **[EmailerExamples.md](docs/EmailerExamples.md)** - Примеры использования

## 🎯 Реальные примеры

### Регистрация пользователя

```php
Emailer::sendView(
    $user->email,
    'Welcome to ' . config('app.name'),
    'emails/welcome',
    [
        'name' => $user->name,
        'activation_link' => url('/activate/' . $user->activation_token),
    ]
);
```

### Сброс пароля

```php
Emailer::sendView(
    $user->email,
    'Password Reset Request',
    'emails/password-reset',
    [
        'name' => $user->name,
        'reset_link' => url('/reset-password/' . $token),
        'expires_in' => '1 hour',
    ]
);
```

### Счет с вложением

```php
Emailer::message()
    ->to($user->email, $user->name)
    ->subject('Invoice #' . $invoice->number)
    ->body(view('emails/invoice', ['invoice' => $invoice]))
    ->attach($invoice->pdfPath(), 'invoice.pdf')
    ->send();
```

## 🛠️ Создание собственного драйвера

```php
use Core\Emailer\EmailDriverInterface;
use Core\Emailer\EmailMessage;

class CustomDriver implements EmailDriverInterface
{
    public function send(EmailMessage $message): bool
    {
        // Your custom sending logic
        return true;
    }

    public function getName(): string
    {
        return 'custom';
    }
}

// Register
Emailer::setDriver(new CustomDriver());
```

## ✅ Лучшие практики

1. ✨ **Используйте Log Driver** для разработки
2. ✅ **Валидируйте email адреса** перед отправкой
3. 🎨 **Используйте шаблоны** для сложных emails
4. 🛡️ **Обрабатывайте ошибки** с try/catch
5. 🧪 **Тестируйте** на разных email клиентах
6. 📊 **Проверяйте Debug Toolbar** для отладки
7. 🔒 **Не коммитьте** API ключи в репозиторий

## 🔥 Что дальше?

- [ ] Queue поддержка для массовой рассылки
- [ ] Rate limiting
- [ ] Email templates builder
- [ ] Markdown emails
- [ ] Tracking (opens, clicks)
- [ ] More drivers (AWS SES, Postmark, etc.)

## 📝 License

MIT

---

**Создано с ❤️ для вашего фреймворка**

