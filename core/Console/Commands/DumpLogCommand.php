<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

/**
 * Dump Log Command
 * 
 * Показать логи dump-server (когда сервер был недоступен)
 */
class DumpLogCommand extends Command
{
    protected string $signature = 'dump:log';
    protected string $description = 'Show dump server fallback logs';

    public function handle(): int
    {
        $logFile = STORAGE_DIR . '/logs/dumps.log';
        
        if (!file_exists($logFile)) {
            $this->warning('No dump logs found.');
            $this->line('Logs are created when dump server is unavailable.');
            return 0;
        }
        
        // Проверяем опцию --clear
        if ($this->option('clear') || $this->option('c')) {
            return $this->clearLog($logFile);
        }
        
        // Проверяем опцию --tail
        $tail = $this->option('tail') ?: $this->option('n');
        if ($tail) {
            return $this->showTail($logFile, (int)$tail);
        }
        
        // Показываем весь файл
        $this->info('Dump Server Logs:');
        $this->line('File: ' . $logFile);
        $this->line('Size: ' . $this->formatBytes(filesize($logFile)));
        $this->newLine();
        
        $content = file_get_contents($logFile);
        echo $content;
        
        return 0;
    }
    
    /**
     * Очистить лог
     */
    private function clearLog(string $logFile): int
    {
        if (unlink($logFile)) {
            $this->success('Dump logs cleared!');
            return 0;
        }
        
        $this->error('Failed to clear dump logs.');
        return 1;
    }
    
    /**
     * Показать последние N строк
     */
    private function showTail(string $logFile, int $lines): int
    {
        $this->info("Showing last {$lines} dump entries:");
        $this->newLine();
        
        $content = file_get_contents($logFile);
        
        // Разделяем по разделителям
        $entries = explode(str_repeat('─', 80), $content);
        $entries = array_filter($entries, fn($e) => trim($e) !== '');
        
        // Берём последние N записей (каждая запись = 2 блока разделителей)
        $entriesCount = count($entries);
        $start = max(0, $entriesCount - ($lines * 2));
        $recentEntries = array_slice($entries, $start);
        
        echo str_repeat('─', 80);
        echo implode(str_repeat('─', 80), $recentEntries);
        
        return 0;
    }
    
    /**
     * Форматировать размер файла
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

