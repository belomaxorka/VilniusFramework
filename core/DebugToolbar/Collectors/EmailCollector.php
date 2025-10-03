<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\Emailer;

/**
 * Email Collector
 * 
 * Собирает информацию об отправленных email
 */
class EmailCollector extends AbstractCollector
{
    public function getName(): string
    {
        return 'emails';
    }

    public function getTitle(): string
    {
        return 'Emails';
    }

    public function collect(): array
    {
        $emails = Emailer::getSentEmails();
        $stats = Emailer::getStats();

        return [
            'emails' => $emails,
            'stats' => $stats,
            'count' => count($emails),
        ];
    }

    public function getIcon(): string
    {
        return '✉️';
    }

    public function getBadge(): ?string
    {
        $count = count(Emailer::getSentEmails());
        return $count > 0 ? (string)$count : null;
    }

    public function getPanelContent(): string
    {
        $data = $this->collect();
        $emails = $data['emails'];
        $stats = $data['stats'];

        if (empty($emails)) {
            return '<p class="text-gray-500 p-4">No emails sent</p>';
        }

        $html = '<div class="p-4">';
        
        // Stats
        $html .= '<div class="mb-4 grid grid-cols-4 gap-4">';
        $html .= '<div class="bg-blue-50 p-3 rounded">';
        $html .= '<div class="text-xs text-gray-600">Total</div>';
        $html .= '<div class="text-xl font-bold">' . $stats['total'] . '</div>';
        $html .= '</div>';
        $html .= '<div class="bg-green-50 p-3 rounded">';
        $html .= '<div class="text-xs text-gray-600">Successful</div>';
        $html .= '<div class="text-xl font-bold text-green-600">' . $stats['successful'] . '</div>';
        $html .= '</div>';
        $html .= '<div class="bg-red-50 p-3 rounded">';
        $html .= '<div class="text-xs text-gray-600">Failed</div>';
        $html .= '<div class="text-xl font-bold text-red-600">' . $stats['failed'] . '</div>';
        $html .= '</div>';
        $html .= '<div class="bg-purple-50 p-3 rounded">';
        $html .= '<div class="text-xs text-gray-600">Total Time</div>';
        $html .= '<div class="text-xl font-bold">' . number_format($stats['total_time'] * 1000, 2) . ' ms</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Email list
        $html .= '<div class="space-y-2">';
        foreach ($emails as $email) {
            $statusClass = $email['success'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            $statusText = $email['success'] ? 'Sent' : 'Failed';

            $html .= '<div class="border rounded p-3">';
            $html .= '<div class="flex items-center justify-between mb-2">';
            $html .= '<span class="font-medium">' . htmlspecialchars($email['subject']) . '</span>';
            $html .= '<span class="px-2 py-1 text-xs rounded ' . $statusClass . '">' . $statusText . '</span>';
            $html .= '</div>';
            $html .= '<div class="text-sm text-gray-600">';
            $html .= '<div>To: ' . htmlspecialchars(implode(', ', $email['to'])) . '</div>';
            $html .= '<div>Driver: ' . htmlspecialchars($email['driver']) . '</div>';
            $html .= '<div>Time: ' . number_format($email['time'] * 1000, 2) . ' ms</div>';
            
            if (!$email['success'] && isset($email['error'])) {
                $html .= '<div class="text-red-600 mt-1">Error: ' . htmlspecialchars($email['error']) . '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}

