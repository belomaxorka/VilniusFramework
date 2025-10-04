<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

/**
 * Storage Setup Command
 * 
 * Создает все необходимые директории для storage
 */
class StorageSetupCommand extends Command
{
    protected string $name = 'storage:setup';
    protected string $description = 'Create required storage directories';

    public function execute(): int
    {
        $this->line('Setting up storage directories...');
        $this->line('');

        // Определяем необходимые директории
        $directories = [
            'logs' => defined('LOG_DIR') ? LOG_DIR : STORAGE_DIR . '/logs',
            'cache' => defined('CACHE_DIR') ? CACHE_DIR : STORAGE_DIR . '/cache',
            'cache/data' => (defined('CACHE_DIR') ? CACHE_DIR : STORAGE_DIR . '/cache') . '/data',
            'cache/templates' => (defined('CACHE_DIR') ? CACHE_DIR : STORAGE_DIR . '/cache') . '/templates',
            'cache/config' => (defined('CACHE_DIR') ? CACHE_DIR : STORAGE_DIR . '/cache') . '/config',
            'app' => STORAGE_DIR . '/app',
        ];

        $created = 0;
        $existed = 0;
        $failed = [];

        foreach ($directories as $name => $path) {
            if (is_dir($path)) {
                $this->info("  ✓ {$name}");
                $existed++;
            } else {
                if (@mkdir($path, 0755, true)) {
                    $this->success("  + Created: {$name}");
                    $created++;
                } else {
                    $this->error("  ✗ Failed: {$name}");
                    $failed[] = $name;
                }
            }
        }

        $this->line('');

        if (!empty($failed)) {
            $this->error('Failed to create some directories:');
            foreach ($failed as $dir) {
                $this->error("  - {$dir}");
            }
            $this->line('');
            $this->line('Please check permissions and try again.');
            return 1;
        }

        // Сводка
        $this->success('Storage setup completed!');
        $this->line("  Created: {$created}");
        $this->line("  Already existed: {$existed}");
        $this->line('');
        
        // Проверка прав на запись
        $this->line('Checking write permissions...');
        $writableChecks = [
            'logs' => $directories['logs'],
            'cache' => $directories['cache'],
            'app' => $directories['app'],
        ];

        $allWritable = true;
        foreach ($writableChecks as $name => $path) {
            if (is_writable($path)) {
                $this->success("  ✓ {$name} is writable");
            } else {
                $this->error("  ✗ {$name} is NOT writable");
                $allWritable = false;
            }
        }

        if (!$allWritable) {
            $this->line('');
            $this->warning('Some directories are not writable. Please fix permissions:');
            $this->line('  chmod -R 755 storage/');
            return 1;
        }

        $this->line('');
        $this->success('All checks passed! Your application is ready to run.');
        
        return 0;
    }
}

