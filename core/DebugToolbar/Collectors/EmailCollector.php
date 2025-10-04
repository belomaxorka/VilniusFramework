<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;
use Core\DebugToolbar\ColorPalette;
use Core\DebugToolbar\HtmlRenderer;
use Core\Emailer;

/**
 * Email Collector
 * 
 * Собирает информацию об отправленных email
 */
class EmailCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 85;
    }

    public function getName(): string
    {
        return 'emails';
    }

    public function getTitle(): string
    {
        return 'Emails';
    }

    public function getIcon(): string
    {
        return '✉️';
    }

    public function isEnabled(): bool
    {
        return class_exists('\Core\Emailer');
    }

    public function collect(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $emails = Emailer::getSentEmails();
        $stats = Emailer::getStats();

        $this->data = [
            'emails' => $emails,
            'stats' => $stats,
            'count' => count($emails),
        ];
    }

    public function getBadge(): ?string
    {
        return $this->countBadge('emails');
    }

    public function render(): string
    {
        if (empty($this->data['emails'])) {
            return $this->renderEmptyState('No emails sent');
        }

        $emails = $this->data['emails'];
        $stats = $this->data['stats'];

        $html = '<div style="padding: 20px;">';
        $html .= '<h3 style="margin-top: 0;">✉️ Sent Emails</h3>';
        
        // Статистика
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">';
        
        $html .= HtmlRenderer::renderStatCard(
            '📊 Total',
            (string)$stats['total'],
            ColorPalette::INFO
        );
        
        $html .= HtmlRenderer::renderStatCard(
            '✅ Successful',
            (string)$stats['successful'],
            ColorPalette::SUCCESS
        );
        
        $html .= HtmlRenderer::renderStatCard(
            '❌ Failed',
            (string)$stats['failed'],
            ColorPalette::ERROR
        );
        
        $html .= HtmlRenderer::renderStatCard(
            '⏱️ Total Time',
            $this->formatTime($stats['total_time']),
            ColorPalette::SECONDARY
        );
        
        $html .= '</div>';

        // Список писем
        $html .= '<div style="max-height: 400px; overflow-y: auto;">';
        
        foreach ($emails as $index => $email) {
            $isSuccess = $email['success'];
            $bgColor = $isSuccess ? '#e8f5e9' : '#ffebee';
            $borderColor = $isSuccess ? ColorPalette::SUCCESS : ColorPalette::ERROR;

            $html .= '<div style="background: ' . $bgColor . '; border-left: 4px solid ' . $borderColor . '; padding: 15px; margin-bottom: 10px; border-radius: 4px;">';
            
            // Заголовок письма
            $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
            $html .= '<div style="font-weight: bold; font-size: 14px;">' . htmlspecialchars($email['subject']) . '</div>';
            
            // Статус badge
            $statusColor = $isSuccess ? ColorPalette::SUCCESS : ColorPalette::ERROR;
            $statusText = $isSuccess ? 'Sent' : 'Failed';
            $html .= HtmlRenderer::renderBadge($statusText, $statusColor);
            
            $html .= '</div>';
            
            // Детали
            $html .= '<div style="font-size: 12px; color: #666;">';
            $html .= '<div style="margin-bottom: 5px;"><strong>To:</strong> ' . htmlspecialchars(implode(', ', $email['to'])) . '</div>';
            $html .= '<div style="margin-bottom: 5px;"><strong>Driver:</strong> ' . htmlspecialchars($email['driver']) . '</div>';
            $html .= '<div style="margin-bottom: 5px;"><strong>Time:</strong> ' . $this->formatTime($email['time']) . '</div>';
            
            // Ошибка (если есть)
            if (!$isSuccess && isset($email['error'])) {
                $html .= '<div style="margin-top: 8px; padding: 8px; background: white; border-radius: 3px; color: ' . ColorPalette::ERROR . ';">';
                $html .= '<strong>Error:</strong> ' . htmlspecialchars($email['error']);
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        $count = $this->data['count'] ?? 0;
        
        if ($count === 0) {
            return [];
        }

        $stats = $this->data['stats'];
        $failed = $stats['failed'] ?? 0;
        
        // Определяем цвет: если есть ошибки - красный, иначе зелёный
        $color = $failed > 0 ? ColorPalette::ERROR : ColorPalette::SUCCESS;
        $value = $count . ' emails' . ($failed > 0 ? ' (' . $failed . ' failed)' : '');

        return [
            [
                'icon' => '✉️',
                'value' => $value,
                'color' => $color,
            ]
        ];
    }
}

