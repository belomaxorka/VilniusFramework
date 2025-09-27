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
        $entry = sprintf("[%s] [%s] %s%s", date('Y-m-d H:i:s'), strtoupper($level), $message, PHP_EOL);
        file_put_contents($this->file, $entry, FILE_APPEND);
    }
}

