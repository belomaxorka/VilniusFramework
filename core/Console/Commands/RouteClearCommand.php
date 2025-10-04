<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

/**
 * Route Clear Command
 * 
 * Очистить кэш роутов
 */
class RouteClearCommand extends Command
{
    protected string $signature = 'route:clear';
    protected string $description = 'Remove the route cache file';

    public function handle(): int
    {
        $this->info('Clearing route cache...');

        $routeCache = STORAGE_DIR . '/cache/routes.php';
        
        if ($this->deleteCacheFile($routeCache)) {
            $this->newLine();
            $this->success('Route cache cleared successfully!');
        } else {
            $this->newLine();
            $this->warning('Route cache file not found or already cleared.');
        }
        
        return 0;
    }
}

