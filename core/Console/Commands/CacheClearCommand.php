<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Cache;

/**
 * Cache Clear Command
 * 
 * Очистить весь кэш приложения
 */
class CacheClearCommand extends Command
{
    protected string $signature = 'cache:clear';
    protected string $description = 'Clear the application cache';

    public function handle(): int
    {
        $this->info('Clearing application cache...');

        try {
            // Очищаем основной кэш
            Cache::clear();

            // Очищаем кэш шаблонов
            $this->clearTemplateCache();

            // Очищаем кэш конфигурации
            $this->clearConfigCache();

            // Очищаем кэш роутов
            $this->clearRouteCache();

            $this->newLine();
            $this->success('Application cache cleared successfully!');

            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed to clear cache: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Очистить кэш шаблонов
     */
    private function clearTemplateCache(): void
    {
        $count = $this->deleteFiles(STORAGE_DIR . '/cache/templates/*.php');
        
        if ($count > 0) {
            $this->line("  ✓ Cleared {$count} template cache files");
        }
    }

    /**
     * Очистить кэш конфигурации
     */
    private function clearConfigCache(): void
    {
        if ($this->deleteCacheFile(STORAGE_DIR . '/cache/config.php')) {
            $this->line("  ✓ Cleared config cache");
        }
    }

    /**
     * Очистить кэш роутов
     */
    private function clearRouteCache(): void
    {
        if ($this->deleteCacheFile(STORAGE_DIR . '/cache/routes.php')) {
            $this->line("  ✓ Cleared route cache");
        }
    }
}

