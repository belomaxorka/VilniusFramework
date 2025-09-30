# Система мультиязычности

Полная документация по системе локализации фреймворка.

## Обзор

Система мультиязычности состоит из двух основных классов:
- `Core\Lang` - низкоуровневый класс для работы с переводами
- `Core\LanguageManager` - высокоуровневый менеджер для управления языками

## Основные возможности

✅ **Поддержка вложенных переводов** (точечная нотация: `user.profile.title`)  
✅ **Автоопределение языка** из HTTP заголовков браузера  
✅ **Fallback язык** для отсутствующих переводов  
✅ **Плейсхолдеры** в переводах (`:name`, `:field`)  
✅ **Логирование** недостающих ключей перевода  
✅ **RTL языки** (поддержка языков справа налево)  
✅ **Динамическое добавление** переводов в runtime  
✅ **Полное покрытие тестами** (31 тест)

---

## Быстрый старт

### Базовое использование

```php
use Core\LanguageManager;
use Core\Lang;

// Инициализация системы языков
LanguageManager::init();

// Получение перевода
echo Lang::get('hello', ['name' => 'World']); // "Hello, World!"

// Вложенные переводы
echo Lang::get('user.profile.title'); // "User Profile"

// Проверка наличия перевода
if (Lang::has('user.greeting')) {
    echo Lang::get('user.greeting', ['username' => 'John']);
}

// Смена языка
LanguageManager::setLanguage('ru');
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

### Core\Lang

#### Основные методы

**`Lang::setLang(?string $lang): void`**
```php
Lang::setLang('ru');           // Установить конкретный язык
Lang::setLang(null);           // Автоопределение
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

#### Управление состоянием

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

#### Fallback язык

**`Lang::setFallbackLang(string $lang): void`**
```php
Lang::setFallbackLang('en');
```

**`Lang::getFallbackLang(): string`**
```php
$fallback = Lang::getFallbackLang(); // 'en'
```

**`Lang::getLoadedLanguages(): array`**
```php
$loaded = Lang::getLoadedLanguages(); // ['en', 'ru']
```

---

### Core\LanguageManager

#### Инициализация

**`LanguageManager::init(): void`**
```php
// Инициализирует систему языков
LanguageManager::init();
```

#### Управление языками

**`LanguageManager::setLanguage(string $lang): bool`**
```php
if (LanguageManager::setLanguage('ru')) {
    // Язык успешно изменен
} else {
    // Язык не поддерживается
}
```

**`LanguageManager::getCurrentLanguage(): string`**
```php
$current = LanguageManager::getCurrentLanguage();
```

#### Валидация

**`LanguageManager::isValidLanguage(string $lang): bool`**
```php
if (LanguageManager::isValidLanguage('ru')) {
    // Язык поддерживается
}
```

#### Информация о языках

**`LanguageManager::getSupportedLanguages(): array`**
```php
$langs = LanguageManager::getSupportedLanguages();
// ['en', 'ru']
```

**`LanguageManager::getSupportedLanguagesWithNames(): array`**
```php
$langs = LanguageManager::getSupportedLanguagesWithNames();
// ['en' => 'English', 'ru' => 'Русский']
```

**`LanguageManager::getLanguageName(string $lang): string`**
```php
$name = LanguageManager::getLanguageName('en'); // 'English'
```

**`LanguageManager::getAvailableLanguages(): array`**
```php
// Получить все языки из директории lang/
$available = LanguageManager::getAvailableLanguages();
```

#### RTL поддержка

**`LanguageManager::isRTL(?string $lang = null): bool`**
```php
if (LanguageManager::isRTL('ar')) {
    // Арабский язык - RTL
}

if (LanguageManager::isRTL()) {
    // Проверка текущего языка
}
```

---

## Автоопределение языка

Система автоматически определяет язык пользователя из HTTP заголовка `Accept-Language`.

### Приоритет:
1. Явно установленный язык через `setLanguage()`
2. Язык из конфигурации `language.default`
3. Автоопределение из браузера (если `auto_detect = true`)
4. Fallback язык

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
    <?php foreach (LanguageManager::getSupportedLanguagesWithNames() as $code => $name): ?>
        <option value="<?= $code ?>" 
                <?= LanguageManager::getCurrentLanguage() === $code ? 'selected' : '' ?>>
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

---

## Тестирование

Система полностью покрыта тестами (31 тест).

### Запуск тестов:
```bash
php vendor/bin/pest tests/Unit/Core/LangTest.php
php vendor/bin/pest tests/Unit/Core/LanguageManagerTest.php
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

## Улучшения и изменения

### Что было улучшено:

1. ✅ **Исправлен метод `has()`** - теперь корректно работает с вложенными ключами
2. ✅ **Добавлено логирование** недостающих переводов с интеграцией в `Core\Logger`
3. ✅ **Метод `reset()`** для удобного тестирования
4. ✅ **Убран закомментированный код** в `LanguageManager`
5. ✅ **Новые методы**:
   - `Lang::getMessages()` - получение переводов конкретного языка
   - `Lang::addMessages()` - добавление переводов в runtime
   - `LanguageManager::getAvailableLanguages()` - список языков из директории
6. ✅ **Улучшена обработка edge cases**:
   - Корректная работа при получении массива вместо строки
   - Правильная проверка поддерживаемых языков при автоопределении
7. ✅ **Расширены примеры переводов** с вложенной структурой
8. ✅ **Полное покрытие тестами** (31 тест для `Lang`, 27 тестов для `LanguageManager`)

### Исправленные баги:

- 🐛 Метод `has()` не работал с вложенными ключами (использовал `isset` вместо `getNestedValue`)
- 🐛 Возврат массива вместо строки при обращении к вложенной структуре
- 🐛 Автоопределение не проверяло список поддерживаемых языков корректно
- 🐛 TODO комментарий с нереализованным логированием

---

## Best Practices

1. **Используйте вложенную структуру** для организации переводов по категориям
2. **Всегда указывайте плейсхолдеры** для динамических данных
3. **Проверяйте наличие ключа** перед использованием с `has()`
4. **Включите логирование** в режиме разработки для отслеживания недостающих переводов
5. **Используйте короткие коды** языков (ISO 639-1: en, ru, de, fr)
6. **Тестируйте переводы** для всех поддерживаемых языков

---

## Производительность

- Переводы загружаются **лениво** (только при первом обращении к языку)
- Fallback язык **предзагружается** вместе с основным
- Все переводы **кешируются** в памяти на время запроса
- Нет обращений к файловой системе после первой загрузки

---

## Миграция с предыдущей версии

Если у вас уже были переводы, изменения **полностью обратно совместимы**.

### Что осталось без изменений:
- ✅ Все публичные API методы
- ✅ Формат файлов переводов
- ✅ Синтаксис плейсхолдеров
- ✅ Конфигурация

### Что добавлено (опционально):
- Новые методы для расширенной функциональности
- Поддержка вложенных переводов (старые простые ключи продолжают работать)

---

## Поддержка

Если у вас возникли вопросы или предложения по улучшению:
1. Создайте issue в репозитории
2. Запустите тесты для проверки работоспособности
3. Обратитесь к примерам в `lang/en.php` и `lang/ru.php`
