# Core\Path - Утилиты для работы с путями

Класс-утилита для работы с файловыми путями, обеспечивает кроссплатформенность и удобные методы.

## 🎯 Основные возможности

- Нормализация путей (Windows ↔ Unix)
- Работа с относительными путями
- Объединение частей пути
- Получение информации о файлах
- Проверка типов путей

---

## 📖 API Reference

### Нормализация путей

#### `Path::normalize(string $path): string`

Нормализует путь - заменяет обратные слеши `\` на прямые `/`.

**Использование:**
```php
use Core\Path;

// Windows путь
$path = Path::normalize('C:\Users\John\Documents\file.txt');
// Результат: 'C:/Users/John/Documents/file.txt'

// Уже нормализованный
$path = Path::normalize('/var/www/html/index.php');
// Результат: '/var/www/html/index.php'

// Смешанные слеши
$path = Path::normalize('app\Controllers/HomeController.php');
// Результат: 'app/Controllers/HomeController.php'
```

**Зачем нужно:**
- Кроссплатформенность кода
- Единообразие в логах и выводе
- Корректная работа с путями на разных ОС

---

### Относительные пути

#### `Path::relative(string $path): string`

Преобразует абсолютный путь в относительный (убирает ROOT из начала).

**Использование:**
```php
use Core\Path;

// Абсолютный путь
$absolute = 'C:/OSPanel/home/project/app/Controllers/HomeController.php';

// Относительный путь
$relative = Path::relative($absolute);
// Результат: 'app/Controllers/HomeController.php'

// Уже относительный - вернется как есть
$relative = Path::relative('app/Models/User.php');
// Результат: 'app/Models/User.php'
```

**Применение:**
- Логирование (короткие пути)
- Debug вывод
- Отображение в ошибках

---

### Объединение путей

#### `Path::join(string ...$parts): string`

Объединяет части пути с автоматической нормализацией.

**Использование:**
```php
use Core\Path;

// Простое объединение
$path = Path::join('app', 'Controllers', 'HomeController.php');
// Результат: 'app/Controllers/HomeController.php'

// С ROOT
$path = Path::join(ROOT, 'storage', 'logs', 'app.log');
// Результат: 'C:/project/storage/logs/app.log' (нормализовано)

// Со слешами
$path = Path::join('app/', '/Controllers/', 'Admin/');
// Результат: 'app/Controllers/Admin'
```

**Преимущества:**
- Не нужно заботиться о слешах
- Автоматическая нормализация
- Читаемый код

---

### Информация о файлах

#### `Path::extension(string $path): string`

Получить расширение файла (без точки).

```php
use Core\Path;

$ext = Path::extension('document.pdf');
// Результат: 'pdf'

$ext = Path::extension('archive.tar.gz');
// Результат: 'gz'

$ext = Path::extension('no-extension');
// Результат: ''
```

#### `Path::filename(string $path): string`

Получить имя файла без расширения.

```php
$name = Path::filename('/path/to/document.pdf');
// Результат: 'document'

$name = Path::filename('archive.tar.gz');
// Результат: 'archive.tar'
```

#### `Path::basename(string $path): string`

Получить имя файла с расширением.

```php
$name = Path::basename('/path/to/document.pdf');
// Результат: 'document.pdf'

$name = Path::basename('C:\Users\file.txt');
// Результат: 'file.txt'
```

#### `Path::dirname(string $path): string`

Получить директорию из пути (нормализованную).

```php
$dir = Path::dirname('/path/to/file.txt');
// Результат: '/path/to'

$dir = Path::dirname('C:\Users\John\file.txt');
// Результат: 'C:/Users/John'
```

---

### Проверка путей

#### `Path::isAbsolute(string $path): bool`

Проверить, является ли путь абсолютным.

```php
use Core\Path;

// Unix абсолютный
Path::isAbsolute('/var/www/html');
// true

// Windows абсолютный
Path::isAbsolute('C:/Users/John');
// true

Path::isAbsolute('C:\Users\John');
// true

// Относительный
Path::isAbsolute('app/Controllers');
// false

Path::isAbsolute('./config/app.php');
// false
```

#### `Path::exists(string $path): bool`

Проверить, существует ли файл или директория.

```php
if (Path::exists('/path/to/file.txt')) {
    echo "Файл существует";
}
```

#### `Path::isDirectory(string $path): bool`

Проверить, является ли путь директорией.

```php
if (Path::isDirectory('/path/to/folder')) {
    echo "Это директория";
}
```

#### `Path::isFile(string $path): bool`

Проверить, является ли путь файлом.

```php
if (Path::isFile('/path/to/file.txt')) {
    echo "Это файл";
}
```

---

## 💡 Примеры использования

### Debug вывод с короткими путями

```php
use Core\Path;
use Core\Debug;

function debugLog($message, $data) {
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $file = $backtrace[0]['file'] ?? 'unknown';
    $line = $backtrace[0]['line'] ?? 0;
    
    $shortPath = Path::relative($file);
    
    Debug::dump([
        'file' => $shortPath,
        'line' => $line,
        'message' => $message,
        'data' => $data
    ]);
}
```

### Безопасное создание путей к файлам

```php
use Core\Path;

class LogManager {
    private string $logDir;
    
    public function __construct() {
        $this->logDir = Path::join(ROOT, 'storage', 'logs');
    }
    
    public function getLogPath(string $channel): string {
        $filename = $channel . '-' . date('Y-m-d') . '.log';
        return Path::join($this->logDir, $filename);
    }
}

$manager = new LogManager();
$path = $manager->getLogPath('app');
// Результат: 'C:/project/storage/logs/app-2025-10-03.log'
```

### Работа с загруженными файлами

```php
use Core\Path;

class FileUploader {
    public function processUpload(array $file): array {
        $originalName = $file['name'];
        $extension = Path::extension($originalName);
        $filename = Path::filename($originalName);
        
        // Генерируем безопасное имя
        $safeName = uniqid() . '.' . $extension;
        
        // Путь для сохранения
        $uploadDir = Path::join(ROOT, 'storage', 'uploads');
        $targetPath = Path::join($uploadDir, $safeName);
        
        return [
            'original' => $originalName,
            'filename' => $filename,
            'extension' => $extension,
            'saved_as' => $safeName,
            'path' => Path::normalize($targetPath)
        ];
    }
}
```

### Проверка и создание директорий

```php
use Core\Path;

function ensureDirectoryExists(string $path): void {
    if (!Path::exists($path)) {
        if (!Path::isAbsolute($path)) {
            $path = Path::join(ROOT, $path);
        }
        
        if (!mkdir($path, 0755, true)) {
            throw new \RuntimeException("Failed to create directory: $path");
        }
    }
}

ensureDirectoryExists('storage/cache/views');
```

---

## 🆚 Сравнение

### Было: normalize_path() хелпер

```php
// Хелпер-функция
$path = normalize_path($filePath);

// Проблемы:
// - Глобальная функция
// - Только 1 операция
// - Нужно помнить имя функции
```

### Стало: Path класс

```php
use Core\Path;

// Нормализация
$path = Path::normalize($filePath);

// Относительный путь
$path = Path::relative($filePath);

// Объединение
$path = Path::join('app', 'Controllers', 'HomeController.php');

// Преимущества:
// ✅ Явный класс
// ✅ Много методов
// ✅ IDE автодополнение
// ✅ Namespace
```

---

## 🎨 Паттерны использования

### DO ✅

```php
use Core\Path;

// Всегда нормализуйте пути из внешних источников
$userPath = Path::normalize($_POST['path']);

// Используйте join для построения путей
$configPath = Path::join(ROOT, 'config', 'app.php');

// Используйте relative для логов
$shortPath = Path::relative($longPath);
```

### DON'T ❌

```php
// Не создавайте пути конкатенацией
$path = ROOT . '/storage/' . $file; // ❌

// Используйте Path::join
$path = Path::join(ROOT, 'storage', $file); // ✅

// Не используйте str_replace напрямую
$path = str_replace('\\', '/', $path); // ❌

// Используйте Path::normalize
$path = Path::normalize($path); // ✅
```

---

## 🔗 См. также

- [Helpers Migration Guide](HelpersMigrationGuide.md)
- [Deprecated Helpers](DeprecatedHelpers.md)

---

**Класс Path - ваш универсальный инструмент для работы с путями! 🛠️**

