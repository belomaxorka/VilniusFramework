# Поддержка цветов в консоли

## Обзор

Консольная система фреймворка поддерживает цветной вывод на всех платформах, включая Windows.

## Поддерживаемые платформы

### Windows 10+

✅ **Windows 10 (build 10586+)** - полная поддержка ANSI цветов  
✅ **Windows 11** - полная поддержка  
✅ **Windows Terminal** - полная поддержка  
✅ **PowerShell 5.1+** - полная поддержка  
✅ **PowerShell Core 7+** - полная поддержка  
✅ **VS Code Terminal** - полная поддержка  
✅ **ConEmu** - полная поддержка  
✅ **Cmder** - полная поддержка  

### Linux / macOS

✅ Все современные терминалы с поддержкой ANSI

## Как это работает

### Автоматическая активация на Windows

При создании объекта `Output` автоматически:

1. Отправляется ANSI escape-последовательность для активации виртуального терминала
2. Проверяется версия Windows (build number)
3. Проверяются переменные окружения терминала

### Проверка поддержки цветов

Система проверяет в следующем порядке:

1. **Переменная `NO_COLOR`** - принудительно отключает цвета
2. **Переменная `FORCE_COLOR`** - принудительно включает цвета
3. **Windows**:
   - Старые эмуляторы: `ANSICON`, `ConEmuANSI`
   - Windows 10 build 10586+ (автоматически)
   - `TERM` переменная (например, `xterm`)
   - Windows Terminal (`WT_SESSION`)
   - VS Code (`TERM_PROGRAM=vscode`)
4. **Unix**: проверка через `posix_isatty(STDOUT)`

### Кэширование

Результат проверки кэшируется при первом вызове, что улучшает производительность.

## Управление цветами

### Отключить цвета

```bash
# Через переменную окружения
$env:NO_COLOR = "1"
php vilnius db:seed

# Или в PowerShell
$env:NO_COLOR = "1"; php vilnius db:seed
```

### Принудительно включить цвета

```bash
# Через переменную окружения
$env:FORCE_COLOR = "1"
php vilnius db:seed
```

## Доступные цвета

### Стандартные цвета
- `black` (черный)
- `red` (красный)
- `green` (зеленый)
- `yellow` (желтый)
- `blue` (синий)
- `magenta` (пурпурный)
- `cyan` (голубой)
- `white` (белый)

### Яркие цвета
- `bright_red` (ярко-красный)
- `bright_green` (ярко-зеленый)
- `bright_yellow` (ярко-желтый)

## Использование в командах

### Предопределенные методы с иконками

```php
$this->info('Информационное сообщение');     // ℹ голубой
$this->success('Успешно выполнено!');        // ✓ ярко-зеленый
$this->error('Ошибка!');                     // ✗ ярко-красный
$this->warning('Предупреждение');            // ⚠ ярко-желтый
```

### Произвольные цвета

```php
// Через метод color()
$text = $this->color('Важный текст', 'green');
$this->line($text);

// Inline
$this->line("Обычный текст " . $this->color('зеленый', 'green') . " и снова обычный");
```

## Примеры вывода

### Пример 1: Статус операций

```php
$this->line("  " . $this->color('Created:', 'green') . " User#1");
$this->line("  " . $this->color('Updated:', 'yellow') . " User#2");
$this->line("  " . $this->color('Deleted:', 'red') . " User#3");
```

**Вывод**:
```
  Created: User#1  (зеленый)
  Updated: User#2  (желтый)
  Deleted: User#3  (красный)
```

### Пример 2: Прогресс

```php
foreach ($users as $user) {
    if ($user->save()) {
        $this->line("  " . $this->color('✓', 'green') . " {$user->name}");
    } else {
        $this->line("  " . $this->color('✗', 'red') . " {$user->name}");
    }
}
```

## Технические детали

### Версии Windows

| Версия Windows | Build | Поддержка ANSI |
|----------------|-------|----------------|
| Windows 10 RTM | 10240 | ❌ Нет |
| Windows 10 1511 (TH2) | 10586 | ✅ Да |
| Windows 10 1607+ | 14393+ | ✅ Да |
| Windows 11 | 22000+ | ✅ Да |

**Ваша система**: Windows 10.0.19045 - ✅ **Поддерживается**

### Формат ANSI escape-кодов

Используется стандартный формат:
```
\033[{код_цвета}m{текст}\033[0m
```

Где:
- `\033[` - начало escape-последовательности (ESC [)
- `{код_цвета}` - код цвета (например, `0;32` для зеленого)
- `m` - окончание команды цвета
- `\033[0m` - сброс форматирования

## Отладка

### Проверить поддержку цветов

Создайте тестовую команду:

```php
class ColorTestCommand extends Command
{
    protected string $signature = 'color:test';
    protected string $description = 'Test color support';

    public function handle(): int
    {
        $this->info('Info message');
        $this->success('Success message');
        $this->warning('Warning message');
        $this->error('Error message');
        
        $this->newLine();
        
        foreach (['red', 'green', 'yellow', 'blue', 'magenta', 'cyan'] as $color) {
            $this->line($this->color("This is {$color}", $color));
        }
        
        return 0;
    }
}
```

### Проверить переменные окружения

```powershell
# PowerShell
Get-ChildItem Env: | Where-Object { $_.Name -match "TERM|COLOR|ANSI|WT_" }

# Проверить версию Windows
[System.Environment]::OSVersion
```

## Известные проблемы

### Проблема: Цвета не работают в старом cmd.exe

**Решение**: Используйте PowerShell или Windows Terminal

### Проблема: Цвета работают, но иконки выглядят странно

**Решение**: 
1. Установите шрифт с поддержкой Unicode (например, Cascadia Code)
2. В настройках терминала включите UTF-8

### Проблема: Цвета отображаются как escape-коды

**Решение**:
```powershell
# Принудительно включить цвета
$env:FORCE_COLOR = "1"
```

## Best Practices

1. **Используйте предопределенные методы** (`info`, `success`, `error`, `warning`) для стандартных сообщений
2. **Не злоупотребляйте цветами** - слишком много цветов затрудняет чтение
3. **Будьте последовательны** - используйте один цвет для одного типа информации
4. **Тестируйте** - проверяйте вывод как с цветами, так и без них

## Совместимость

Система полностью обратно совместима:
- Если терминал не поддерживает цвета, выводится обычный текст
- Escape-коды не добавляются, если цвета отключены
- Работает на всех версиях PHP 7.4+

## Дополнительные ресурсы

- [ANSI escape codes (Wikipedia)](https://en.wikipedia.org/wiki/ANSI_escape_code)
- [Windows Console Virtual Terminal Sequences](https://docs.microsoft.com/en-us/windows/console/console-virtual-terminal-sequences)
- [NO_COLOR standard](https://no-color.org/)

