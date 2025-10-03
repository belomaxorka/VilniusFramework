# ✅ Создан класс Core\Path

## Проблема

Функция `normalize_path()` была удалена вместе с хелперами, но использовалась в коде для нормализации путей.

## Решение

Вместо простой замены на `str_replace()` создан полноценный класс-утилита `Core\Path` с расширенным функционалом.

---

## 📦 Класс Core\Path

**Файл:** `core/Path.php`  
**Строк кода:** ~150  
**Методов:** 13  

### Основные методы

#### Нормализация и преобразование
```php
Path::normalize($path)      // Нормализация (\ → /)
Path::relative($path)       // Абсолютный → относительный
Path::join(...$parts)       // Объединение частей пути
```

#### Информация о файлах
```php
Path::extension($path)      // Расширение файла
Path::filename($path)       // Имя без расширения
Path::basename($path)       // Имя с расширением
Path::dirname($path)        // Директория
```

#### Проверки
```php
Path::isAbsolute($path)     // Абсолютный ли путь
Path::exists($path)         // Существует ли
Path::isDirectory($path)    // Это директория?
Path::isFile($path)         // Это файл?
```

---

## 📊 Сравнение

### Было: normalize_path() хелпер

```php
// Глобальная функция
$path = normalize_path($filePath);

// Ограничения:
// - Только 1 операция
// - Глобальная область
// - Нет дополнительных методов
```

### Стало: Path класс

```php
use Core\Path;

// Нормализация
$path = Path::normalize($filePath);

// + Относительные пути
$relative = Path::relative($absolutePath);

// + Объединение
$config = Path::join(ROOT, 'config', 'app.php');

// + Информация о файлах
$ext = Path::extension($filename);

// Преимущества:
// ✅ 13 методов вместо 1
// ✅ Явный namespace
// ✅ IDE автодополнение
// ✅ Легко расширять
```

---

## 🔄 Миграция в коде

### core/DumpClient.php

**Было:**
```php
'file' => str_replace('\\', '/', $caller['file'] ?? 'unknown'),

$relativePath = str_replace([ROOT . '/', ROOT . '\\'], '', $file);
$relativePath = str_replace('\\', '/', $relativePath);
$normalizedLogFile = str_replace('\\', '/', $logFile);
```

**Стало:**
```php
'file' => \Core\Path::normalize($caller['file'] ?? 'unknown'),

$relativePath = \Core\Path::relative($file);
$normalizedLogFile = \Core\Path::normalize($logFile);
```

**Улучшение:** 4 строки → 2 строки, более явный код

### core/DumpServer.php

**Было:**
```php
$relativePath = str_replace([ROOT . '/', ROOT . '\\'], '', $file);
$relativePath = str_replace('\\', '/', $relativePath);
```

**Стало:**
```php
$relativePath = \Core\Path::relative($file);
```

**Улучшение:** 2 строки → 1 строка

---

## 💡 Примеры использования

### Базовое использование

```php
use Core\Path;

// Нормализация Windows путей
$path = Path::normalize('C:\Users\John\Documents\file.txt');
// Результат: 'C:/Users/John/Documents/file.txt'

// Относительные пути для логов
$shortPath = Path::relative('/var/www/project/app/Controllers/Home.php');
// Результат: 'app/Controllers/Home.php'

// Безопасное объединение путей
$logPath = Path::join(ROOT, 'storage', 'logs', 'app.log');
// Результат: 'C:/project/storage/logs/app.log'
```

### В реальном коде

```php
use Core\Path;
use Core\Logger;

class FileLogger {
    public function log(string $message): void {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file = $backtrace[0]['file'] ?? 'unknown';
        $line = $backtrace[0]['line'] ?? 0;
        
        // Короткий путь для вывода
        $shortFile = Path::relative($file);
        
        Logger::info("{$shortFile}:{$line} - {$message}");
    }
}
```

### Работа с загруженными файлами

```php
use Core\Path;

function processUpload(array $file): string {
    $originalName = $file['name'];
    $extension = Path::extension($originalName);
    
    // Генерируем безопасное имя
    $safeName = uniqid() . '.' . $extension;
    
    // Путь для сохранения
    $uploadDir = Path::join(ROOT, 'storage', 'uploads');
    $targetPath = Path::join($uploadDir, $safeName);
    
    move_uploaded_file($file['tmp_name'], $targetPath);
    
    return Path::normalize($targetPath);
}
```

---

## 📚 Документация

Создана полная документация: **[docs/Path.md](docs/Path.md)**

Включает:
- API Reference для всех методов
- Примеры использования
- Паттерны DO/DON'T
- Реальные кейсы применения

---

## ✅ Преимущества решения

### 1. Функциональность
- ✅ 13 методов вместо 1 функции
- ✅ Покрывает большинство операций с путями
- ✅ Легко расширяется

### 2. Философия фреймворка
- ✅ Классы вместо глобальных функций
- ✅ Явные зависимости
- ✅ Namespace Core\

### 3. Developer Experience
- ✅ IDE автодополнение
- ✅ PHPDoc для всех методов
- ✅ Понятные имена методов

### 4. Код качество
- ✅ Код стал короче и читабельнее
- ✅ Меньше дублирования
- ✅ Легче тестировать

---

## 🎯 Итог

Вместо простой замены `normalize_path()` на `str_replace()` создан полноценный класс-утилита, который:

✅ Решает исходную проблему (нормализация путей)  
✅ Добавляет дополнительную функциональность  
✅ Соответствует философии фреймворка  
✅ Улучшает качество кода  
✅ Упрощает работу разработчиков  

**Path - ваш универсальный инструмент для работы с путями! 🛠️**

---

_Создано: 2025-10-03_  
_Версия: 1.0_  
_Статус: ✅ READY_

