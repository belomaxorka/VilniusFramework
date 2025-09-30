<?php declare(strict_types=1);

// Define paths
define('ROOT', realpath(__DIR__ . '/../'));
define('CONFIG_DIR', ROOT . '/config');
define('LANG_DIR', ROOT . '/lang');
define('RESOURCES_DIR', ROOT . '/resources');
define('STORAGE_DIR', ROOT . '/storage');
define('CACHE_DIR', STORAGE_DIR . '/cache');
define('LOG_DIR', STORAGE_DIR . '/logs');

// Load composer
require_once ROOT . '/vendor/autoload.php';

// Early error handling initialization
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', LOG_DIR . '/php_errors.log');

// Load helper groups
\Core\HelperLoader::loadHelperGroups([
    'app',          // Core application functions (config, lang, view, env)
    'environment',  // Environment detection (is_debug, is_dev, is_prod)
    'debug',        // Debug functions (dd, dump, trace)
    'profiler',     // Performance profiling (timer, memory, benchmark)
    'database',     // Database debugging (query_log, query_stats)
    'context',      // Debug contexts (context_start, context_run)
]);
