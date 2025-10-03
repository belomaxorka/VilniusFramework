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
        $templateCacheDir = STORAGE_DIR . '/cache/templates';
        
        if (is_dir($templateCacheDir)) {
            $files = glob($templateCacheDir . '/*.php');
            $count = 0;
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
            
            $this->line("  ✓ Cleared {$count} template cache files");
        }
    }

    /**
     * Очистить кэш конфигурации
     */
    private function clearConfigCache(): void
    {
        $configCache = STORAGE_DIR . '/cache/config.php';
        
        if (file_exists($configCache)) {
            unlink($configCache);
            $this->line("  ✓ Cleared config cache");
        }
    }

    /**
     * Очистить кэш роутов
     */
    private function clearRouteCache(): void
    {
        $routeCache = STORAGE_DIR . '/cache/routes.php';
        
        if (file_exists($routeCache)) {
            unlink($routeCache);
            $this->line("  ✓ Cleared route cache");
        }
    }
}

