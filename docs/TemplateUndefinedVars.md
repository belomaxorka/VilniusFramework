# Логирование неопределенных переменных в шаблонах

## Проблема

При использовании переменных в шаблонах, которые не были переданы из контроллера, возникают ошибки:

```php
// Контроллер
display('user.twig', ['name' => 'Иван']);

// Шаблон user.twig
<h1>{{ name }}</h1>
<p>Email: {{ email }}</p>  <!-- ❌ $email не передан! -->
```

### Поведение в разных режимах

**Development (APP_ENV=development):**
```
┌─────────────────────────────────────────┐
│ Error - Fatal Error                    │
├─────────────────────────────────────────┤
│ Message: Undefined variable $email     │
│ File: welcome.twig:15                    │
│ Stack Trace: ...                        │
└─────────────────────────────────────────┘
```
- Ошибка отображается через красивый ErrorHandler
- Детальная информация о файле и строке
- Stack trace для отладки
- Помогает быстро найти проблему

**Production (APP_ENV=production) БЕЗ логирования:**
```
(Просто пустое место, ошибка скрыта)
```
- Пользователь не видит ошибку ✓
- Но разработчик тоже не знает о проблеме ✗
- Сложно отследить баги

**Production С логированием (наше решение):**
```
(Пустое место на странице)
+ Запись в логе: "Undefined variable $email in user.twig:15"
```
- Пользователь не видит ошибку ✓
- Разработчик видит в логах ✓
- Легко отследить и исправить ✓

## Решение

Шаблонизатор теперь автоматически:
1. Перехватывает ошибки об undefined variables
2. Логирует их в production режиме
3. Собирает статистику за сессию
4. Показывает какие переменные доступны

## Использование

### 1. Автоматическое логирование (по умолчанию)

Ничего настраивать не нужно! Логирование включено по умолчанию.

```php
// В production режиме
display('user.twig', ['name' => 'Иван']);
// Если в шаблоне используется $email - будет залогировано
```

### 2. Управление логированием

```php
use Core\TemplateEngine;

$template = TemplateEngine::getInstance();

// Отключить логирование (если не нужно)
$template->setLogUndefinedVars(false);

// Включить обратно
$template->setLogUndefinedVars(true);
```

### 3. Получение статистики

Полезно для анализа и отладки:

```php
// В конце скрипта или в debug панели
$undefinedVars = TemplateEngine::getUndefinedVars();

if (!empty($undefinedVars)) {
    echo "⚠️ Найдены неопределенные переменные:\n";
    
    foreach ($undefinedVars as $varName => $info) {
        echo sprintf(
            "- \$%s (используется %d раз) в %s:%d\n",
            $varName,
            $info['count'],
            basename($info['file']),
            $info['line']
        );
    }
}

// Очистить статистику
TemplateEngine::clearUndefinedVars();
```

### 4. Интеграция с Debug Toolbar

Вы можете добавить undefined variables в Debug Toolbar:

```php
// В методе collectTabs() класса DebugToolbar
$undefinedVars = \Core\TemplateEngine::getUndefinedVars();

if (!empty($undefinedVars)) {
    $tabs['template_errors'] = [
        'title' => 'Template Errors',
        'icon' => '⚠️',
        'content' => self::renderUndefinedVars($undefinedVars),
        'badge' => count($undefinedVars),
    ];
}
```

## Примеры

### Пример 1: Простой случай

```php
// Контроллер
public function profile()
{
    display('profile.twig', [
        'username' => 'john_doe',
        'email' => 'john@example.com'
    ]);
}
```

```html
<!-- profile.twig -->
<div>
    <h1>{{ username }}</h1>
    <p>{{ email }}</p>
    <p>{{ phone }}</p>  <!-- ❌ Не передано! -->
</div>
```

**Лог в production:**
```
[2025-09-30 14:30:12] WARNING: Template undefined variable: $phone
Message: Undefined variable $phone
File: profile.twig:15
Available variables: username, email
```

### Пример 2: Анализ проблем

```php
// В конце рендеринга страницы
$undefined = TemplateEngine::getUndefinedVars();

if (!empty($undefined)) {
    // Отправить в систему мониторинга
    foreach ($undefined as $varName => $info) {
        Monitoring::track('template.undefined_var', [
            'variable' => $varName,
            'count' => $info['count'],
            'template' => basename($info['file'])
        ]);
    }
}
```

### Пример 3: Debug панель

```php
// В admin панели
if (is_admin()) {
    $stats = TemplateEngine::getUndefinedVars();
    
    if (!empty($stats)) {
        echo '<div class="alert alert-warning">';
        echo '<strong>Template Issues:</strong><ul>';
        
        foreach ($stats as $var => $info) {
            echo "<li>\${$var} - {$info['count']} использований</li>";
        }
        
        echo '</ul></div>';
    }
}
```

### Пример 4: Автоматическое тестирование

```php
// В тестах
test('template should not have undefined variables', function () {
    TemplateEngine::clearUndefinedVars();
    
    ob_start();
    display('user.twig', ['name' => 'Test']);
    ob_end_clean();
    
    $undefined = TemplateEngine::getUndefinedVars();
    
    expect($undefined)->toBeEmpty();
});
```

## Формат лога

### Стандартный лог

```
[2025-09-30 14:30:12] WARNING: Template undefined variable: $user_avatar
Message: Undefined variable $user_avatar
File: user/profile.twig:42
Available variables: user_id, user_name, user_email, user_role
```

### Расширенный лог (с контекстом)

```
[2025-09-30 14:30:12] WARNING: Template undefined variable: $product_price
Message: Undefined variable $product_price
File: shop/product.twig:23
Available variables: product_id, product_name, product_description, product_image
Context: /shop/product/123
User: guest
```

## Настройка в config

Вы можете добавить настройку в конфигурацию:

```php
// config/app.php
return [
    'template' => [
        'log_undefined_vars' => env('TEMPLATE_LOG_UNDEFINED', true),
        'cache_enabled' => true,
        'cache_lifetime' => 3600,
    ],
];
```

```php
// В Core::init() или при инициализации шаблонизатора
$template = TemplateEngine::getInstance();
$template->setLogUndefinedVars(
    Config::get('app.template.log_undefined_vars', true)
);
```

## Рекомендации

### ✅ Хорошие практики

1. **Всегда передавайте все переменные**
   ```php
   display('template.twig', [
       'title' => $title,
       'user' => $user,
       'settings' => $settings,
   ]);
   ```

2. **Используйте значения по умолчанию**
   ```html
   {{ username | default('Guest') }}
   ```

3. **Проверяйте существование**
   ```html
   {% if user_avatar %}
       <img src="{{ user_avatar }}">
   {% endif %}
   ```

4. **Регулярно проверяйте логи**
   ```bash
   tail -f storage/logs/app.log | grep "undefined variable"
   ```

### ❌ Плохие практики

1. **Не игнорируйте undefined variables**
   ```php
   // Плохо: отключать логирование чтобы скрыть проблемы
   $template->setLogUndefinedVars(false);
   ```

2. **Не полагайтесь на ?? оператор везде**
   ```html
   <!-- Плохо: маскирует проблему -->
   {{ some_var ?? '' }}
   
   <!-- Лучше: передайте переменную -->
   ```

3. **Не оставляйте undefined vars в production**
   - Исправляйте сразу как увидите в логах

## FAQ

**Q: Влияет ли это на производительность?**  
A: Минимально. Error handler работает только когда возникает ошибка.

**Q: Будут ли логироваться все Notice/Warning?**  
A: Нет, только те, что связаны с undefined variables в шаблонах.

**Q: Как это работает с кэшированием?**  
A: Кэш компилированных шаблонов не затронут. Логирование работает при выполнении.

**Q: Можно ли отключить для конкретного шаблона?**  
A: Да, используйте `setLogUndefinedVars(false)` перед рендерингом и верните обратно после.

**Q: Что если переменная действительно может отсутствовать?**  
A: Используйте проверку `{% if variable %}` или фильтр `| default`.

## Миграция

Если у вас уже есть код с undefined variables:

1. **Включите логирование** (уже включено по умолчанию)
2. **Протестируйте на staging**
3. **Проверьте логи** после деплоя
4. **Исправьте найденные проблемы**
5. **Повторите** пока логи не будут чистыми

## Интеграция с мониторингом

### Sentry

```php
if (Environment::isProduction()) {
    $undefined = TemplateEngine::getUndefinedVars();
    
    foreach ($undefined as $var => $info) {
        Sentry\captureMessage("Template undefined variable: {$var}", [
            'level' => 'warning',
            'extra' => $info,
        ]);
    }
}
```

### Slack

```php
$undefined = TemplateEngine::getUndefinedVars();

if (count($undefined) > 5) {
    // Слишком много ошибок - уведомить команду
    SlackNotifier::send("⚠️ Найдено " . count($undefined) . " undefined variables в шаблонах!");
}
```

## Заключение

Логирование undefined variables помогает:
- ✓ Находить баги в production без показа ошибок пользователям
- ✓ Поддерживать качество кода
- ✓ Быстро реагировать на проблемы
- ✓ Улучшать опыт разработки

**По умолчанию включено** и работает автоматически! 🎉
