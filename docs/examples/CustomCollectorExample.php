<?php declare(strict_types=1);

/**
 * Пример создания и использования кастомного коллектора для Debug Toolbar
 * 
 * Этот пример показывает, как создать коллектор для отслеживания HTTP запросов
 */

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

/**
 * Коллектор HTTP запросов
 * Отслеживает все внешние HTTP запросы (API, webhooks и т.д.)
 */
class HttpCollector extends AbstractCollector
{
    private static array $requests = [];

    public function __construct()
    {
        $this->priority = 38; // Между Queries (30) и Timers (40)
    }

    public function getName(): string
    {
        return 'http';
    }

    public function getTitle(): string
    {
        return 'HTTP';
    }

    public function getIcon(): string
    {
        return '🌐';
    }

    public function collect(): void
    {
        $this->data = [
            'requests' => self::$requests,
            'stats' => $this->calculateStats(),
        ];
    }

    public function getBadge(): ?string
    {
        $count = count(self::$requests);
        return $count > 0 ? (string)$count : null;
    }

    public function render(): string
    {
        if (empty(self::$requests)) {
            return '<div style="padding: 20px; text-align: center; color: #757575;">No HTTP requests made</div>';
        }

        $stats = $this->data['stats'];

        $html = '<div style="padding: 10px;">';
        
        // Статистика
        $html .= '<div style="background: #f5f5f5; padding: 10px; margin-bottom: 10px; border-radius: 4px;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; font-size: 12px;">';
        $html .= '<div><strong>Total:</strong> ' . $stats['total'] . '</div>';
        $html .= '<div><strong>Success:</strong> <span style="color: #66bb6a;">' . $stats['success'] . '</span></div>';
        $html .= '<div><strong>Failed:</strong> <span style="color: #ef5350;">' . $stats['failed'] . '</span></div>';
        $html .= '<div><strong>Total Time:</strong> ' . $this->formatTime($stats['total_time']) . '</div>';
        $html .= '<div><strong>Avg Time:</strong> ' . $this->formatTime($stats['avg_time']) . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Список запросов
        $html .= '<div style="max-height: 350px; overflow-y: auto;">';
        foreach (self::$requests as $index => $req) {
            $isSuccess = $req['status_code'] >= 200 && $req['status_code'] < 300;
            $borderColor = $isSuccess ? '#66bb6a' : '#ef5350';
            
            $html .= '<div style="background: white; border-left: 4px solid ' . $borderColor . '; padding: 8px; margin-bottom: 6px; border-radius: 4px; font-size: 12px;">';
            
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">';
            $html .= '<div>';
            $html .= '<strong style="color: ' . $borderColor . ';">' . strtoupper($req['method']) . '</strong> ';
            $html .= '<code style="background: #f5f5f5; padding: 2px 4px; border-radius: 2px;">' . htmlspecialchars($req['url']) . '</code>';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '<div style="display: flex; justify-content: space-between; font-size: 11px; color: #757575;">';
            $html .= '<span>Status: <strong style="color: ' . $borderColor . ';">' . $req['status_code'] . '</strong></span>';
            $html .= '<span>Time: ' . $this->formatTime($req['time']) . '</span>';
            $html .= '</div>';
            
            if (!empty($req['error'])) {
                $html .= '<div style="margin-top: 4px; font-size: 11px; color: #ef5350;">Error: ' . htmlspecialchars($req['error']) . '</div>';
            }
            
            $html .= '</div>';
        }
        $html .= '</div>';
        
        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        $count = count(self::$requests);
        if ($count === 0) {
            return [];
        }

        $stats = $this->data['stats'] ?? $this->calculateStats();
        $color = $stats['failed'] > 0 ? '#ef5350' : '#66bb6a';
        
        return [[
            'icon' => '🌐',
            'value' => $count . ' HTTP' . ($stats['failed'] > 0 ? ' (' . $stats['failed'] . ' failed)' : ''),
            'color' => $color,
        ]];
    }

    /**
     * Логировать HTTP запрос
     */
    public static function logRequest(
        string $method,
        string $url,
        int $statusCode,
        float $time,
        ?string $error = null
    ): void {
        self::$requests[] = [
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'time' => $time,
            'error' => $error,
            'timestamp' => microtime(true),
        ];
    }

    private function calculateStats(): array
    {
        $total = count(self::$requests);
        $success = 0;
        $failed = 0;
        $totalTime = 0;

        foreach (self::$requests as $req) {
            if ($req['status_code'] >= 200 && $req['status_code'] < 300) {
                $success++;
            } else {
                $failed++;
            }
            $totalTime += $req['time'];
        }

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'total_time' => $totalTime,
            'avg_time' => $total > 0 ? $totalTime / $total : 0,
        ];
    }
}

// ============================================================================
// ИСПОЛЬЗОВАНИЕ
// ============================================================================

/*

// 1. Регистрируем коллектор (в bootstrap.php или public/index.php)

use Core\DebugToolbar;
use Core\DebugToolbar\Collectors\HttpCollector;

DebugToolbar::addCollector(new HttpCollector());


// 2. Создаем HTTP клиент с интеграцией

class HttpClient
{
    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, [], $headers);
    }

    public function post(string $url, array $data = [], array $headers = []): array
    {
        return $this->request('POST', $url, $data, $headers);
    }

    private function request(string $method, string $url, array $data = [], array $headers = []): array
    {
        $start = microtime(true);
        $error = null;
        $statusCode = 0;

        try {
            // Выполняем реальный HTTP запрос
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            if ($method === 'POST' && !empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
            }
            
            curl_close($ch);
            
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $statusCode = 0;
        }
        
        $time = (microtime(true) - $start) * 1000;
        
        // Логируем в коллектор
        HttpCollector::logRequest($method, $url, $statusCode, $time, $error);
        
        return [
            'status_code' => $statusCode,
            'body' => $response ?? null,
            'error' => $error,
        ];
    }
}


// 3. Используем в приложении

$client = new HttpClient();

// GET запрос
$response = $client->get('https://api.example.com/users');

// POST запрос
$response = $client->post('https://api.example.com/users', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Webhook
$client->post('https://hooks.slack.com/services/xxx', [
    'text' => 'Deploy completed!'
]);


// 4. Все запросы автоматически появятся в Debug Toolbar!
//    🌐 HTTP вкладка покажет:
//    - Все HTTP запросы
//    - Методы (GET, POST, etc)
//    - URL
//    - Status codes
//    - Время выполнения
//    - Ошибки
//    - Статистику (total, success, failed, avg time)

*/
