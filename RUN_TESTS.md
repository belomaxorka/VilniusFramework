# 🧪 Запуск тестов - Quick Guide

## 🚀 Быстрый старт

### Все новые CLI тесты:
```bash
vendor/bin/pest tests/Unit/Core/Console/
vendor/bin/pest tests/Unit/Core/Database/MigrationSystemTest.php
```

### Все тесты проекта:
```bash
vendor/bin/pest
```

### С покрытием кода:
```bash
vendor/bin/pest --coverage
```

---

## 📦 Тесты по категориям

### Console (Command, Input)
```bash
vendor/bin/pest tests/Unit/Core/Console/
```

### Migrations & Schema Builder
```bash
vendor/bin/pest tests/Unit/Core/Database/MigrationSystemTest.php
```

### DumpServer (включая fallback)
```bash
vendor/bin/pest tests/Unit/Core/Debug/DumpServerTest.php
```

### Logger (включая _toolbar_message)
```bash
vendor/bin/pest tests/Unit/Core/Logger/LoggerTest.php
```

---

## 🔍 Конкретные тесты

### Только fallback тесты:
```bash
vendor/bin/pest --filter="Fallback"
```

### Только _toolbar_message тесты:
```bash
vendor/bin/pest --filter="Toolbar Message"
```

### Только Schema Builder тесты:
```bash
vendor/bin/pest --filter="Schema Builder"
```

### Конкретный тест по имени:
```bash
vendor/bin/pest --filter="logs to file when server unavailable"
```

---

## 💻 С выводом деталей

### Подробный вывод:
```bash
vendor/bin/pest --verbose
```

### Только провальные тесты:
```bash
vendor/bin/pest --bail
```

### С временем выполнения:
```bash
vendor/bin/pest --profile
```

---

## 📊 Coverage Reports

### Простой отчёт в консоли:
```bash
vendor/bin/pest --coverage
```

### Минимальное покрытие (fail если ниже):
```bash
vendor/bin/pest --coverage --min=80
```

### HTML отчёт:
```bash
vendor/bin/pest --coverage --coverage-html=coverage
```

Затем откройте `coverage/index.html` в браузере.

---

## 🐛 Debugging

### Один тест с var_dump:
```php
test('something', function () {
    $result = Something::do();
    var_dump($result); // Покажет если тест упадёт
    expect($result)->toBeTrue();
});
```

### С dd():
```php
test('something', function () {
    $result = Something::do();
    dd($result); // Остановит выполнение
    expect($result)->toBeTrue();
});
```

### Запустить один файл с деталями:
```bash
vendor/bin/pest tests/Unit/Core/Console/CommandTest.php --verbose
```

---

## ✅ Проверка перед коммитом

```bash
# Запустить все тесты
vendor/bin/pest

# Проверить покрытие
vendor/bin/pest --coverage --min=80

# Проверить конкретные новые тесты
vendor/bin/pest tests/Unit/Core/Console/
vendor/bin/pest tests/Unit/Core/Database/MigrationSystemTest.php
vendor/bin/pest --filter="Fallback|Toolbar Message"
```

Если все 3 команды проходят - можно коммитить! ✅

---

## 📝 Добавление новых тестов

### 1. Создайте файл:
```bash
touch tests/Unit/Core/YourFeature/YourTest.php
```

### 2. Напишите тест:
```php
<?php declare(strict_types=1);

use Core\YourClass;

test('your feature works', function () {
    $result = YourClass::doSomething();
    expect($result)->toBeTrue();
});
```

### 3. Запустите:
```bash
vendor/bin/pest tests/Unit/Core/YourFeature/YourTest.php
```

---

## 🎯 CI/CD

### GitHub Actions пример:
```yaml
- name: Run tests
  run: vendor/bin/pest --coverage --min=80
```

### GitLab CI пример:
```yaml
test:
  script:
    - vendor/bin/pest --coverage --min=80
```

---

## 📚 Документация

Полная документация: [tests/CLI_TESTING_GUIDE.md](tests/CLI_TESTING_GUIDE.md)

---

## 🎉 Quick Check

```bash
# Все ли тесты проходят?
vendor/bin/pest

# Покрытие >80%?
vendor/bin/pest --coverage --min=80

# Новые тесты работают?
vendor/bin/pest tests/Unit/Core/Console/
vendor/bin/pest tests/Unit/Core/Database/MigrationSystemTest.php

# Fallback и Toolbar Message?
vendor/bin/pest --filter="Fallback|Toolbar Message"
```

**Если все ✅ - всё отлично!** 🎊

