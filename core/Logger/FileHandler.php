<?php declare(strict_types=1);

namespace Core\Logger;

class FileHandler implements LogHandlerInterface
{
    protected string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function handle(string $level, string $message): void
    {
        // Создаем директорию если не существует
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $entry = sprintf("[%s] [%s] %s%s", date('Y-m-d H:i:s'), strtoupper($level), $message, PHP_EOL);
        file_put_contents($this->file, $entry, FILE_APPEND);
    }
}

