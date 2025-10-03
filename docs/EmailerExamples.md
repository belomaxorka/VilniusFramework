# Emailer Examples

Практические примеры использования системы Emailer.

## Содержание

1. [Простая отправка](#простая-отправка)
2. [Использование шаблонов](#использование-шаблонов)
3. [Вложения](#вложения)
4. [Множественные получатели](#множественные-получатели)
5. [Реальные сценарии](#реальные-сценарии)

## Простая отправка

### Текстовый email

```php
use Core\Emailer;

$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Plain Text Email')
    ->body('This is a plain text email.', false);

Emailer::send($message);
```

### HTML email

```php
$message = Emailer::message()
    ->to('user@example.com')
    ->subject('HTML Email')
    ->body('<h1>Hello!</h1><p>This is an <strong>HTML</strong> email.</p>', true);

Emailer::send($message);
```

### С именем отправителя и получателя

```php
$message = Emailer::message()
    ->from('sender@example.com', 'John Sender')
    ->to('recipient@example.com', 'Jane Recipient')
    ->subject('Personalized Email')
    ->body('<p>Hello Jane!</p>');

Emailer::send($message);
```

## Использование шаблонов

### Базовый шаблон

Шаблон `resources/views/emails/welcome.twig`:

```twig
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { 
            background: #007bff; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome, {{ name }}!</h1>
        <p>{{ message }}</p>
        <a href="{{ link }}" class="button">{{ button_text }}</a>
    </div>
</body>
</html>
```

Использование:

```php
Emailer::sendView(
    'user@example.com',
    'Welcome to Our Platform',
    'emails/welcome',
    [
        'name' => 'John',
        'message' => 'Thank you for joining us!',
        'link' => 'https://example.com/activate',
        'button_text' => 'Activate Account',
    ]
);
```

### Шаблон счета

Шаблон `resources/views/emails/invoice.twig`:

```twig
<!DOCTYPE html>
<html>
<body>
    <h1>Invoice #{{ invoice.number }}</h1>
    <p>Date: {{ invoice.date }}</p>
    
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            {% for item in invoice.items %}
            <tr>
                <td>{{ item.name }}</td>
                <td>{{ item.quantity }}</td>
                <td>${{ item.price }}</td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
    
    <p><strong>Total: ${{ invoice.total }}</strong></p>
</body>
</html>
```

Использование:

```php
Emailer::sendView(
    $customer->email,
    'Invoice #' . $invoice->number,
    'emails/invoice',
    ['invoice' => $invoice->toArray()]
);
```

## Вложения

### Прикрепить файл

```php
$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Document Attached')
    ->body('<p>Please find the document attached.</p>')
    ->attach('/path/to/document.pdf', 'document.pdf');

Emailer::send($message);
```

### Прикрепить несколько файлов

```php
$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Multiple Attachments')
    ->body('<p>Multiple documents attached.</p>')
    ->attach('/path/to/file1.pdf', 'document1.pdf')
    ->attach('/path/to/file2.pdf', 'document2.pdf')
    ->attach('/path/to/image.jpg', 'photo.jpg');

Emailer::send($message);
```

### Прикрепить сгенерированные данные

```php
// Сгенерировать PDF
$pdfContent = generatePdf($data);

$message = Emailer::message()
    ->to('user@example.com')
    ->subject('Generated Report')
    ->body('<p>Your report is ready.</p>')
    ->attachData($pdfContent, 'report.pdf', 'application/pdf');

Emailer::send($message);
```

### Прикрепить CSV

```php
// Сгенерировать CSV
$csvData = "Name,Email,Status\n";
$csvData .= "John,john@example.com,Active\n";
$csvData .= "Jane,jane@example.com,Active\n";

$message = Emailer::message()
    ->to('admin@example.com')
    ->subject('User Export')
    ->body('<p>User export attached.</p>')
    ->attachData($csvData, 'users.csv', 'text/csv');

Emailer::send($message);
```

## Множественные получатели

### CC и BCC

```php
$message = Emailer::message()
    ->to('primary@example.com', 'Primary User')
    ->cc('manager@example.com', 'Manager')
    ->cc('supervisor@example.com', 'Supervisor')
    ->bcc('admin@example.com') // Скрытая копия
    ->subject('Project Update')
    ->body('<p>Project status update...</p>');

Emailer::send($message);
```

### Массовая рассылка (простой способ)

```php
$recipients = [
    ['email' => 'user1@example.com', 'name' => 'User 1'],
    ['email' => 'user2@example.com', 'name' => 'User 2'],
    ['email' => 'user3@example.com', 'name' => 'User 3'],
];

foreach ($recipients as $recipient) {
    $message = Emailer::message()
        ->to($recipient['email'], $recipient['name'])
        ->subject('Newsletter')
        ->body(view('emails/newsletter', ['name' => $recipient['name']]));
    
    Emailer::send($message);
}
```

### Персонализированная массовая рассылка

```php
$users = User::all();

foreach ($users as $user) {
    Emailer::sendView(
        $user->email,
        'Personalized Offer',
        'emails/offer',
        [
            'name' => $user->name,
            'discount' => $user->calculateDiscount(),
            'products' => $user->recommendedProducts(),
        ]
    );
}
```

## Реальные сценарии

### 1. Регистрация пользователя

```php
class UserController extends Controller
{
    public function register(Request $request)
    {
        // Create user
        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => password_hash($request->get('password'), PASSWORD_DEFAULT),
        ]);

        // Generate activation token
        $token = bin2hex(random_bytes(32));
        $user->activation_token = $token;
        $user->save();

        // Send welcome email
        Emailer::sendView(
            $user->email,
            'Welcome to ' . config('app.name'),
            'emails/welcome',
            [
                'name' => $user->name,
                'activation_link' => url('/activate/' . $token),
            ]
        );

        return $this->json(['message' => 'Registration successful! Check your email.']);
    }
}
```

### 2. Сброс пароля

```php
class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $email = $request->get('email');
        $user = User::findByEmail($email);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $user->reset_token = $token;
        $user->reset_expires = time() + 3600; // 1 hour
        $user->save();

        // Send reset email
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

        return $this->json(['message' => 'Reset link sent to your email']);
    }
}
```

### 3. Уведомление о заказе

```php
class OrderController extends Controller
{
    public function placeOrder(Request $request)
    {
        $order = Order::create([
            'user_id' => auth()->id(),
            'total' => $request->get('total'),
            // ...
        ]);

        // Send confirmation email
        Emailer::sendView(
            auth()->user()->email,
            'Order Confirmation #' . $order->id,
            'emails/order-confirmation',
            [
                'order' => $order,
                'user' => auth()->user(),
                'items' => $order->items,
                'shipping' => $order->shipping,
            ]
        );

        // Notify admin
        Emailer::sendView(
            config('mail.admin_email'),
            'New Order #' . $order->id,
            'emails/admin/new-order',
            ['order' => $order]
        );

        return $this->json(['order_id' => $order->id]);
    }
}
```

### 4. Ежедневный отчет

```php
class ReportCommand extends Command
{
    protected string $signature = 'report:daily';
    protected string $description = 'Send daily report to admins';

    public function handle(): int
    {
        $stats = [
            'new_users' => User::createdToday()->count(),
            'orders' => Order::today()->count(),
            'revenue' => Order::today()->sum('total'),
        ];

        // Generate CSV
        $csvData = "Metric,Value\n";
        $csvData .= "New Users,{$stats['new_users']}\n";
        $csvData .= "Orders,{$stats['orders']}\n";
        $csvData .= "Revenue,{$stats['revenue']}\n";

        $message = Emailer::message()
            ->to(config('mail.admin_email'))
            ->subject('Daily Report - ' . date('Y-m-d'))
            ->body(view('emails/daily-report', ['stats' => $stats]))
            ->attachData($csvData, 'daily-report.csv', 'text/csv');

        Emailer::send($message);

        $this->success('Daily report sent!');
        return 0;
    }
}
```

### 5. Уведомление с высоким приоритетом

```php
class AlertService
{
    public function sendSecurityAlert(User $user, string $message)
    {
        $email = Emailer::message()
            ->to($user->email, $user->name)
            ->subject('⚠️ Security Alert')
            ->body(view('emails/security-alert', [
                'user' => $user,
                'message' => $message,
                'ip' => request()->ip(),
                'time' => date('Y-m-d H:i:s'),
            ]))
            ->priority(1) // High priority
            ->addHeader('X-Alert-Type', 'security');

        Emailer::send($email);
    }
}
```

### 6. Email с Reply-To

```php
class SupportController extends Controller
{
    public function sendResponse(Request $request)
    {
        $ticket = Ticket::find($request->get('ticket_id'));

        $message = Emailer::message()
            ->to($ticket->user->email)
            ->replyTo('support@example.com', 'Support Team')
            ->subject('Re: Support Ticket #' . $ticket->id)
            ->body($request->get('response'));

        Emailer::send($message);

        return $this->json(['message' => 'Response sent']);
    }
}
```

## Helper Functions

### Простая отправка

```php
// Вместо
Emailer::sendTo('user@example.com', 'Subject', 'Body');

// Используйте
send_email('user@example.com', 'Subject', 'Body');
```

### С шаблоном

```php
// Вместо
Emailer::sendView('user@example.com', 'Subject', 'view', $data);

// Используйте
send_email_view('user@example.com', 'Subject', 'view', $data);
```

### Создание сообщения

```php
// Вместо
$message = Emailer::message();

// Используйте
$message = emailer();
```

## Заключение

Система Emailer предоставляет гибкий и мощный API для отправки email с поддержкой:

- Множественных драйверов (SMTP, SendGrid, Mailgun, Log)
- HTML и plain text
- Вложений
- Шаблонов
- CC, BCC, Reply-To
- Приоритетов
- Debug Toolbar интеграции

Для более подробной информации см. [полную документацию](Emailer.md).

