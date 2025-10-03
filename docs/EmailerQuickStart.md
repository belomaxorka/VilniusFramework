# Emailer Quick Start

Быстрый старт для работы с системой отправки email.

## Установка

Система Emailer уже встроена в фреймворк и не требует дополнительной установки.

## Базовая настройка

### 1. Переменные окружения

Добавьте в `.env`:

```env
# Базовые настройки
MAIL_DRIVER=log
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="My Application"
```

### 2. Для разработки (Log Driver)

Log driver записывает email в файл вместо отправки - идеально для разработки:

```env
MAIL_DRIVER=log
MAIL_LOG_PATH=storage/logs/emails.log
```

### 3. Для production (SMTP)

```env
MAIL_DRIVER=smtp
MAIL_SMTP_HOST=smtp.example.com
MAIL_SMTP_PORT=587
MAIL_SMTP_USERNAME=your_email@example.com
MAIL_SMTP_PASSWORD=your_password
MAIL_SMTP_ENCRYPTION=tls
```

## Простые примеры

### Отправить простой email

```php
use Core\Emailer;

$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Hello!')
    ->body('<h1>Hello World!</h1>', true);

Emailer::send($message);
```

### Быстрая отправка

```php
send_email('user@example.com', 'Hello', '<p>Hello World!</p>');
```

### С шаблоном

```php
send_email_view(
    'user@example.com',
    'Welcome!',
    'emails/welcome',
    ['name' => 'John']
);
```

## Тестирование

### Консольная команда

```bash
php vilnius email:test your-email@example.com
```

### Проверка логов

При использовании log driver, проверьте файл:

```bash
cat storage/logs/emails.log
```

## Настройка разных драйверов

### SendGrid

1. Получите API ключ: https://app.sendgrid.com/settings/api_keys

2. Добавьте в `.env`:

```env
MAIL_DRIVER=sendgrid
MAIL_SENDGRID_API_KEY=your_api_key_here
```

### Mailgun

1. Получите API ключ и домен: https://app.mailgun.com/

2. Добавьте в `.env`:

```env
MAIL_DRIVER=mailgun
MAIL_MAILGUN_API_KEY=your_api_key
MAIL_MAILGUN_DOMAIN=mg.yourdomain.com
MAIL_MAILGUN_ENDPOINT=api.mailgun.net
```

## Примеры использования

### Email с именем получателя

```php
Emailer::message()
    ->to('user@example.com', 'John Doe')
    ->subject('Personal Message')
    ->body('Hello John!')
    ->send();
```

### Множественные получатели

```php
Emailer::message()
    ->to('user1@example.com', 'User 1')
    ->to('user2@example.com', 'User 2')
    ->cc('manager@example.com')
    ->subject('Team Update')
    ->body('Important update for the team');
```

### С вложениями

```php
Emailer::message()
    ->to('user@example.com')
    ->subject('Your Invoice')
    ->body('Please find your invoice attached.')
    ->attach('/path/to/invoice.pdf', 'invoice.pdf')
    ->send();
```

### Использование шаблона

Создайте шаблон `resources/views/emails/welcome.twig`:

```html
<!DOCTYPE html>
<html>
<body>
    <h1>Welcome, {{ name }}!</h1>
    <p>Thank you for joining {{ app_name }}.</p>
</body>
</html>
```

Отправьте:

```php
Emailer::sendView(
    'user@example.com',
    'Welcome!',
    'emails/welcome',
    [
        'name' => 'John',
        'app_name' => 'MyApp'
    ]
);
```

## Debug Toolbar

Emailer автоматически интегрируется с Debug Toolbar. Вы увидите:

- Количество отправленных email
- Статус (успешно/ошибка)
- Время отправки
- Детали каждого email

## Обработка ошибок

```php
use Core\Emailer\EmailException;

try {
    Emailer::send($message);
    echo "Email sent successfully!";
} catch (EmailException $e) {
    echo "Failed to send email: " . $e->getMessage();
}
```

## Полезные советы

1. **Используйте log driver для разработки** - не нужно настраивать SMTP
2. **Валидируйте email адреса** перед отправкой
3. **Тестируйте шаблоны** на разных email клиентах
4. **Проверяйте Debug Toolbar** для отладки
5. **Обрабатывайте ошибки** - сеть может быть недоступна

## Следующие шаги

- [Полная документация](Emailer.md)
- [Создание шаблонов](TemplateEngine.md)
- [Настройка SMTP](Emailer.md#smtp-driver)
- [API Reference](Emailer.md#api-reference)

