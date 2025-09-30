# Система мультиязычности

Полная документация по системе локализации фреймворка.

## Обзор

Система мультиязычности реализована в едином классе `Core\Lang`, который предоставляет все необходимые функции для работы с переводами, автоопределения языка и управления локализацией.

## Основные возможности

✅ **Единый простой API** - все в одном классе  
✅ **Поддержка вложенных переводов** (точечная нотация: `user.profile.title`)  
✅ **Автоопределение языка** из HTTP заголовков браузера  
✅ **Fallback язык** для отсутствующих переводов  
✅ **Плейсхолдеры** в переводах (`:name`, `:field`)  
✅ **Логирование** недостающих ключей перевода  
✅ **RTL языки** (поддержка языков справа налево)  
✅ **Динамическое добавление** переводов в runtime  
✅ **Полное покрытие тестами** (47 тестов)

---

## Быстрый старт

### Базовое использование

```php
use Core\Lang;

// Инициализация системы языков (обычно в bootstrap)
Lang::init();

// Получение перевода
echo Lang::get('hello', ['name' => 'World']); // "Hello, World!"

// Вложенные переводы
echo Lang::get('user.profile.title'); // "User Profile"

// Проверка наличия перевода
if (Lang::has('user.greeting')) {
    echo Lang::get('user.greeting', ['username' => 'John']);
}

// Смена языка
Lang::setLang('ru', true); // true = validate
echo Lang::get('hello', ['name' => 'Мир']); // "Привет, Мир!"
```

---

## Конфигурация

Файл: `config/language.php`

```php
return [
    // Язык по умолчанию ('auto' для автоопределения)
    'default' => 'auto',
    
    // Fallback язык
    'fallback' => 'en',
    
    // Поддерживаемые языки
    'supported' => [
        'en' => 'English',
        'ru' => 'Русский',
    ],
    
    // Автоопределение из браузера
    'auto_detect' => true,
    
    // Логирование недостающих переводов
    'log_missing' => true,
    
    // RTL языки
    'rtl_languages' => ['ar', 'he'],
];
```

---

## Структура файлов переводов

### Простые переводы
`lang/en.php`:
```php
return [
    'hello' => 'Hello, :name!',
    'welcome' => 'Welcome!',
];
```

### Вложенные переводы (рекомендуется)
```php
return [
    'user' => [
        'profile' => [
            'title' => 'User Profile',
            'edit' => 'Edit Profile',
        ],
        'greeting' => 'Hello, :username!',
    ],
    
    'errors' => [
        'validation' => [
            'required' => 'The :field field is required',
            'email' => 'Invalid email format',
        ],
    ],
];
```

---

## API Reference

### Инициализация

**`Lang::init(): void`**
```php
// Инициализирует систему языков из конфигурации
Lang::init();
```

### Основные методы

**`Lang::setLang(?string $lang, bool $validate = false): bool`**
```php
Lang::setLang('ru');              // Установить конкретный язык
Lang::setLang('ru', true);        // С валидацией
Lang::setLang(null);              // Автоопределение
```

**`Lang::get(string $key, array $params = []): string`**
```php
Lang::get('hello', ['name' => 'John']);
Lang::get('user.profile.title');
Lang::get('errors.validation.min', ['field' => 'password', 'min' => '8']);
```

**`Lang::has(string $key): bool`**
```php
if (Lang::has('user.profile.title')) {
    // Перевод существует
}
```

**`Lang::getCurrentLang(): string`**
```php
$current = Lang::getCurrentLang(); // 'en'
```

**`Lang::all(): array`**
```php
$allTranslations = Lang::all(); // Все переводы текущего языка
```

### Управление состоянием

**`Lang::addMessages(string $lang, array $messages): void`**
```php
// Добавить переводы в runtime
Lang::addMessages('en', [
    'custom.key' => 'Custom value',
]);
```

**`Lang::reset(): void`**
```php
// Сброс состояния (полезно для тестирования)
Lang::reset();
```

**`Lang::getMessages(?string $lang = null): array`**
```php
$enMessages = Lang::getMessages('en');
$currentMessages = Lang::getMessages(); // текущий язык
```

**`Lang::getLoadedLanguages(): array`**
```php
$loaded = Lang::getLoadedLanguages(); // ['en', 'ru']
```

### Fallback язык

**`Lang::setFallbackLang(string $lang): void`**
```php
Lang::setFallbackLang('en');
```

**`Lang::getFallbackLang(): string`**
```php
$fallback = Lang::getFallbackLang(); // 'en'
```

### Валидация

**`Lang::isValidLanguage(string $lang): bool`**
```php
if (Lang::isValidLanguage('ru')) {
    // Язык поддерживается
}
```

### Информация о языках

**`Lang::getSupportedLanguages(): array`**
```php
$langs = Lang::getSupportedLanguages();
// ['en', 'ru']
```

**`Lang::getSupportedLanguagesWithNames(): array`**
```php
$langs = Lang::getSupportedLanguagesWithNames();
// ['en' => 'English', 'ru' => 'Русский']
```

**`Lang::getLanguageName(string $lang): string`**
```php
$name = Lang::getLanguageName('en'); // 'English'
```

**`Lang::getAvailableLanguages(): array`**
```php
// Получить все языки из директории lang/
$available = Lang::getAvailableLanguages();
```

### RTL поддержка

**`Lang::isRTL(?string $lang = null): bool`**
```php
if (Lang::isRTL('ar')) {
    // Арабский язык - RTL
}

if (Lang::isRTL()) {
    // Проверка текущего языка
}
```

---

## Автоопределение языка

Система автоматически определяет язык пользователя из HTTP заголовка `Accept-Language`.

### Приоритет:
1. Язык из конфигурации `language.default` (если не 'auto')
2. Автоопределение из браузера (если `default = 'auto'` и `auto_detect = true`)
3. Fallback язык

### Пример работы:
```
HTTP Header: Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7
Supported: ['en', 'ru']
Result: 'ru' ✅
```

```
HTTP Header: Accept-Language: fr-FR,de;q=0.9
Supported: ['en', 'ru']
Result: 'en' (fallback) ✅
```

---

## Логирование недостающих переводов

Если `log_missing = true`, система будет логировать все отсутствующие ключи:

```php
// config/language.php
'log_missing' => true,

// При обращении к несуществующему ключу:
Lang::get('nonexistent.key');

// В лог попадет:
// Missing translation key "nonexistent.key" for language "ru" (fallback: "en")
```

Логирование использует `Core\Logger` если доступен, иначе `error_log()`.

---

## Плейсхолдеры

Используйте префикс `:` для плейсхолдеров:

```php
// Перевод
'greeting' => 'Hello, :name! You have :count messages.',

// Использование
Lang::get('greeting', [
    'name' => 'John',
    'count' => 5
]);
// Результат: "Hello, John! You have 5 messages."
```

---

## Примеры использования

### Переключатель языков

```php
<select onchange="changeLanguage(this.value)">
    <?php foreach (Lang::getSupportedLanguagesWithNames() as $code => $name): ?>
        <option value="<?= $code ?>" 
                <?= Lang::getCurrentLang() === $code ? 'selected' : '' ?>>
            <?= $name ?>
        </option>
    <?php endforeach; ?>
</select>
```

### Валидация форм

```php
$errors = [];

if (empty($email)) {
    $errors[] = Lang::get('errors.validation.required', ['field' => 'Email']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = Lang::get('errors.validation.email');
}
```

### Динамические переводы

```php
// Загрузить переводы из базы данных
$customTranslations = $db->query("SELECT * FROM translations WHERE lang = 'en'");

Lang::addMessages('en', $customTranslations);
```

### Безопасная смена языка

```php
// С валидацией
if (Lang::setLang($_GET['lang'] ?? 'en', true)) {
    echo "Язык успешно изменен";
} else {
    echo "Неподдерживаемый язык";
}
```

---

## Тестирование

Система полностью покрыта тестами (47 тестов).

### Запуск тестов:
```bash
php vendor/bin/pest tests/Unit/Core/LangTest.php
```

### Пример теста:
```php
it('translates nested keys with placeholders', function (): void {
    Lang::setLang('en');
    
    $result = Lang::get('errors.validation.min', [
        'field' => 'password',
        'min' => '8'
    ]);
    
    expect($result)->toBe('The password must be at least 8 characters');
});
```

---

## Архитектурные улучшения

### Что было изменено в новой версии:

#### ❌ Удалено
- **Класс `LanguageManager`** - избыточная прослойка

#### ✅ Улучшено
- **Единый класс `Lang`** - вся функциональность в одном месте
- **Упрощенный API** - меньше дублирования
- **Прямая валидация** - `setLang($lang, true)` для безопасности
- **Лучшая производительность** - меньше уровней абстракции

### Миграция с предыдущей версии:

```php
// БЫЛО (с LanguageManager)
LanguageManager::init();
LanguageManager::setLanguage('ru');
LanguageManager::getCurrentLanguage();
LanguageManager::getSupportedLanguages();

// СТАЛО (только Lang)
Lang::init();
Lang::setLang('ru', true);
Lang::getCurrentLang();
Lang::getSupportedLanguages();
```

### Преимущества новой архитектуры:

1. ✅ **Проще** - один класс вместо двух
2. ✅ **Быстрее** - нет лишних proxy-методов
3. ✅ **Понятнее** - весь API в одном месте
4. ✅ **Меньше кода** - убрано ~170 строк избыточного кода
5. ✅ **Легче поддерживать** - единая ответственность

---

## Best Practices

1. **Используйте вложенную структуру** для организации переводов по категориям
2. **Всегда указывайте плейсхолдеры** для динамических данных
3. **Проверяйте наличие ключа** перед использованием с `has()`
4. **Включите логирование** в режиме разработки для отслеживания недостающих переводов
5. **Используйте короткие коды** языков (ISO 639-1: en, ru, de, fr)
6. **Валидируйте пользовательский ввод** - используйте `setLang($lang, true)`
7. **Тестируйте переводы** для всех поддерживаемых языков

---

## Производительность

- Переводы загружаются **лениво** (только при первом обращении к языку)
- Fallback язык **предзагружается** вместе с основным
- Все переводы **кешируются** в памяти на время запроса
- Нет обращений к файловой системе после первой загрузки
- **Меньше вызовов методов** благодаря упрощенной архитектуре

---

## Часто задаваемые вопросы

### Как добавить новый язык?

1. Создайте файл `lang/fr.php` с переводами
2. Добавьте язык в `config/language.php`:
   ```php
   'supported' => [
       'en' => 'English',
       'ru' => 'Русский',
       'fr' => 'Français',
   ],
   ```
3. Язык автоматически станет доступен

### Как работать с сессиями для сохранения выбранного языка?

```php
// При выборе языка пользователем
if (isset($_GET['lang']) && Lang::isValidLanguage($_GET['lang'])) {
    $_SESSION['user_lang'] = $_GET['lang'];
    Lang::setLang($_GET['lang']);
}

// При инициализации
if (!empty($_SESSION['user_lang'])) {
    Lang::setLang($_SESSION['user_lang']);
} else {
    Lang::init(); // Автоопределение
}
```

### Можно ли загрузить переводы из базы данных?

Да:
```php
$translations = $db->fetchAll("SELECT key, value FROM translations WHERE lang = ?", ['en']);

$messages = [];
foreach ($translations as $row) {
    $messages[$row['key']] = $row['value'];
}

Lang::addMessages('en', $messages);
```

### Как обрабатывать плюрализацию?

Используйте разные ключи:
```php
// lang/en.php
return [
    'items.zero' => 'No items',
    'items.one' => ':count item',
    'items.many' => ':count items',
];

// В коде
function getItemsText($count) {
    $key = $count === 0 ? 'items.zero' : ($count === 1 ? 'items.one' : 'items.many');
    return Lang::get($key, ['count' => $count]);
}
```

---

## Changelog

### v2.0.0 (текущая версия)
- ✅ Удален класс `LanguageManager`
- ✅ Вся функциональность объединена в `Lang`
- ✅ Улучшена валидация языков
- ✅ Упрощен API
- ✅ Обновлены тесты (47 тестов)
- ✅ Улучшена документация

### v1.0.0
- ✅ Начальная реализация с двумя классами
- ✅ Базовая функциональность переводов
- ✅ Автоопределение языка

---

## Поддержка

Если у вас возникли вопросы или предложения по улучшению:
1. Создайте issue в репозитории
2. Запустите тесты для проверки работоспособности
3. Обратитесь к примерам в `lang/en.php` и `lang/ru.php`