<?php declare(strict_types=1);

namespace Core\DebugToolbar\Collectors;

use Core\DebugToolbar\AbstractCollector;

/**
 * Коллектор информации о подключенных файлах
 */
class FilesCollector extends AbstractCollector
{
    public function __construct()
    {
        $this->priority = 55;
    }

    public function getName(): string
    {
        return 'files';
    }

    public function getTitle(): string
    {
        return 'Files';
    }

    public function getIcon(): string
    {
        return '📁';
    }

    public function collect(): void
    {
        $files = get_included_files();
        $basePath = dirname(__DIR__, 2);

        $this->data = [
            'total_files' => count($files),
            'total_size' => 0,
            'files' => [],
            'by_directory' => [],
        ];

        foreach ($files as $file) {
            $size = file_exists($file) ? filesize($file) : 0;
            $this->data['total_size'] += $size;

            $relativePath = $this->getRelativePath($file, $basePath);
            $directory = dirname($relativePath);

            $this->data['files'][] = [
                'path' => $file,
                'relative_path' => $relativePath,
                'size' => $size,
                'directory' => $directory,
                'extension' => pathinfo($file, PATHINFO_EXTENSION),
            ];

            // Группируем по директориям
            if (!isset($this->data['by_directory'][$directory])) {
                $this->data['by_directory'][$directory] = [
                    'count' => 0,
                    'size' => 0,
                ];
            }

            $this->data['by_directory'][$directory]['count']++;
            $this->data['by_directory'][$directory]['size'] += $size;
        }

        // Сортируем директории по количеству файлов
        uasort($this->data['by_directory'], fn($a, $b) => $b['count'] <=> $a['count']);
    }

    public function getBadge(): ?string
    {
        return (string)$this->data['total_files'];
    }

    public function render(): string
    {
        $html = '<div style="padding: 20px;">';

        // Statistics
        $html .= '<h3 style="margin-top: 0;">📁 Included Files</h3>';
        $html .= $this->renderStatistics();

        // Files by Directory
        $html .= $this->renderByDirectory();

        // All Files
        $html .= $this->renderAllFiles();

        $html .= '</div>';

        return $html;
    }

    public function getHeaderStats(): array
    {
        return [[
            'icon' => '📁',
            'value' => $this->data['total_files'] . ' files (' . $this->formatBytes($this->data['total_size']) . ')',
            'color' => '#607d8b',
        ]];
    }

    /**
     * Рендер статистики
     */
    private function renderStatistics(): string
    {
        $html = '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">';

        // Total Files
        $html .= '<div>';
        $html .= '<div style="color: #757575; font-size: 12px; margin-bottom: 5px;">Total Files</div>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #1976d2;">' . $this->data['total_files'] . '</div>';
        $html .= '</div>';

        // Total Size
        $html .= '<div>';
        $html .= '<div style="color: #757575; font-size: 12px; margin-bottom: 5px;">Total Size</div>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #388e3c;">' . $this->formatBytes($this->data['total_size']) . '</div>';
        $html .= '</div>';

        // Directories
        $html .= '<div>';
        $html .= '<div style="color: #757575; font-size: 12px; margin-bottom: 5px;">Directories</div>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #f57c00;">' . count($this->data['by_directory']) . '</div>';
        $html .= '</div>';

        // Average File Size
        $avgSize = $this->data['total_files'] > 0 ? $this->data['total_size'] / $this->data['total_files'] : 0;
        $html .= '<div>';
        $html .= '<div style="color: #757575; font-size: 12px; margin-bottom: 5px;">Avg File Size</div>';
        $html .= '<div style="font-size: 24px; font-weight: bold; color: #7b1fa2;">' . $this->formatBytes((int)$avgSize) . '</div>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендер файлов по директориям
     */
    private function renderByDirectory(): string
    {
        $tableId = 'files_by_directory';

        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px; cursor: pointer;" ';
        $html .= 'onclick="document.getElementById(\'' . $tableId . '\').style.display = document.getElementById(\'' . $tableId . '\').style.display === \'none\' ? \'table\' : \'none\'"';
        $html .= '>📊 Files by Directory <span style="color: #757575; font-size: 12px;">(' . count($this->data['by_directory']) . ')</span>';
        $html .= ' <span style="font-size: 12px; color: #757575;">[click to toggle]</span>';
        $html .= '</h4>';

        $html .= '<table id="' . $tableId . '" style="width: 100%; border-collapse: collapse; background: white; display: none;">';
        $html .= '<thead>';
        $html .= '<tr style="background: #e3f2fd;">';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Directory</th>';
        $html .= '<th style="padding: 10px; text-align: center; border: 1px solid #ddd; font-weight: bold;">Files</th>';
        $html .= '<th style="padding: 10px; text-align: right; border: 1px solid #ddd; font-weight: bold;">Size</th>';
        $html .= '<th style="padding: 10px; text-align: center; border: 1px solid #ddd; font-weight: bold;">%</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->data['by_directory'] as $directory => $stats) {
            $percentage = $this->data['total_files'] > 0 ? ($stats['count'] / $this->data['total_files']) * 100 : 0;

            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; font-size: 12px;">'
                . htmlspecialchars($directory) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">'
                . $stats['count'] . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-family: monospace;">'
                . $this->formatBytes($stats['size']) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">'
                . number_format($percentage, 1) . '%</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Рендер всех файлов
     */
    private function renderAllFiles(): string
    {
        $tableId = 'all_files';

        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: #1976d2; margin-bottom: 10px; cursor: pointer;" ';
        $html .= 'onclick="document.getElementById(\'' . $tableId . '\').style.display = document.getElementById(\'' . $tableId . '\').style.display === \'none\' ? \'table\' : \'none\'"';
        $html .= '>📄 All Files <span style="color: #757575; font-size: 12px;">(' . $this->data['total_files'] . ')</span>';
        $html .= ' <span style="font-size: 12px; color: #757575;">[click to toggle]</span>';
        $html .= '</h4>';

        $html .= '<div id="' . $tableId . '" style="display: none; max-height: 400px; overflow-y: auto;">';
        $html .= '<table style="width: 100%; border-collapse: collapse; background: white;">';
        $html .= '<thead style="position: sticky; top: 0; background: #e3f2fd;">';
        $html .= '<tr>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">#</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">File</th>';
        $html .= '<th style="padding: 10px; text-align: center; border: 1px solid #ddd; font-weight: bold;">Extension</th>';
        $html .= '<th style="padding: 10px; text-align: right; border: 1px solid #ddd; font-weight: bold;">Size</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->data['files'] as $index => $file) {
            $html .= '<tr style="' . ($index % 2 ? 'background: #f9f9f9;' : '') . '">';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center; color: #757575;">'
                . ($index + 1) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; font-size: 11px;" title="' . htmlspecialchars($file['path']) . '">'
                . htmlspecialchars($file['relative_path']) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: center;">'
                . '<span style="background: #e3f2fd; padding: 2px 8px; border-radius: 3px; font-size: 11px;">'
                . htmlspecialchars($file['extension'])
                . '</span>'
                . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-family: monospace; font-size: 11px;">'
                . $this->formatBytes($file['size']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Получить относительный путь
     */
    private function getRelativePath(string $file, string $basePath): string
    {
        $file = str_replace('\\', '/', $file);
        $basePath = str_replace('\\', '/', $basePath);

        if (str_starts_with($file, $basePath)) {
            return substr($file, strlen($basePath) + 1);
        }

        return $file;
    }
}

