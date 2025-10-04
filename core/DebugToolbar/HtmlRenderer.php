<?php declare(strict_types=1);

namespace Core\DebugToolbar;

/**
 * HTML рендерер для Debug Toolbar коллекторов
 * 
 * Предоставляет переиспользуемые методы для рендеринга
 * стандартных HTML компонентов.
 */
class HtmlRenderer
{
    /**
     * Рендер пустого состояния
     * 
     * @param string $message Сообщение для отображения
     * @return string HTML
     */
    public static function renderEmptyState(string $message): string
    {
        return '<div style="padding: 20px; text-align: center; color: ' . ColorPalette::GREY . '; font-style: italic;">'
            . htmlspecialchars($message)
            . '</div>';
    }
    
    /**
     * Рендер секции с заголовком и данными
     * 
     * @param string $title Заголовок секции
     * @param array $data Ассоциативный массив данных [ключ => значение]
     * @return string HTML
     */
    public static function renderSection(string $title, array $data): string
    {
        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: ' . ColorPalette::PRIMARY . '; margin-bottom: 10px;">📋 ' . htmlspecialchars($title) . '</h4>';
        $html .= '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';

        foreach ($data as $key => $value) {
            $html .= '<div style="margin-bottom: 8px;">';
            $html .= '<strong>' . htmlspecialchars($key) . ':</strong> ';
            $html .= is_string($value) ? $value : htmlspecialchars(print_r($value, true));
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
    
    /**
     * Рендер таблицы с данными
     * 
     * @param string $title Заголовок таблицы
     * @param array $data Ассоциативный массив данных [ключ => значение]
     * @param bool $collapsible Сворачиваемая ли таблица
     * @param string|null $warningMessage Предупреждающее сообщение (опционально)
     * @return string HTML
     */
    public static function renderDataTable(
        string $title,
        array $data,
        bool $collapsible = false,
        ?string $warningMessage = null
    ): string {
        $tableId = 'table_' . md5($title . random_bytes(8));

        $html = '<div style="margin-bottom: 20px;">';
        $html .= '<h4 style="color: ' . ColorPalette::PRIMARY . '; margin-bottom: 10px; cursor: ' . ($collapsible ? 'pointer' : 'default') . ';" ';

        if ($collapsible) {
            $html .= 'onclick="document.getElementById(\'' . $tableId . '\').style.display = document.getElementById(\'' . $tableId . '\').style.display === \'none\' ? \'table\' : \'none\'"';
        }

        $html .= '>📋 ' . htmlspecialchars($title) . ' <span style="color: ' . ColorPalette::GREY . '; font-size: 12px;">(' . count($data) . ')</span>';

        if ($collapsible) {
            $html .= ' <span style="font-size: 12px; color: ' . ColorPalette::GREY . ';">[click to toggle]</span>';
        }

        $html .= '</h4>';

        // Предупреждение (если есть)
        if ($warningMessage) {
            $html .= '<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-bottom: 10px; color: #856404;">';
            $html .= '⚠️ <strong>Warning:</strong> ' . htmlspecialchars($warningMessage);
            $html .= '</div>';
        }

        $html .= '<table id="' . $tableId . '" style="width: 100%; border-collapse: collapse; background: white; ' . ($collapsible ? 'display: none;' : '') . '">';
        $html .= '<thead>';
        $html .= '<tr style="background: #e3f2fd;">';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Key</th>';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd; font-weight: bold;">Value</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($data as $key => $value) {
            $html .= '<tr>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; vertical-align: top; width: 30%;">'
                . htmlspecialchars((string)$key) . '</td>';
            $html .= '<td style="padding: 8px; border: 1px solid #ddd; font-family: monospace; word-break: break-all;">'
                . htmlspecialchars(self::formatValueForTable($value)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }
    
    /**
     * Рендер badge (значка)
     * 
     * @param string $text Текст badge
     * @param string $color Цвет фона
     * @return string HTML
     */
    public static function renderBadge(string $text, string $color): string
    {
        return '<span style="background: ' . $color . '; color: white; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-size: 11px;">'
            . htmlspecialchars($text) . '</span>';
    }
    
    /**
     * Рендер статистической карточки
     * 
     * @param string $title Заголовок
     * @param string $value Значение
     * @param string $color Цвет акцента
     * @return string HTML
     */
    public static function renderStatCard(string $title, string $value, string $color): string
    {
        $html = '<div style="background: white; padding: 15px; border-radius: 5px; border-left: 4px solid ' . $color . ';">';
        $html .= '<div style="font-size: 12px; color: #666; margin-bottom: 5px;">' . htmlspecialchars($title) . '</div>';
        $html .= '<div style="font-size: 18px; font-weight: bold; color: ' . $color . ';">' . htmlspecialchars($value) . '</div>';
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Рендер прогресс бара
     * 
     * @param float $percent Процент заполнения (0-100)
     * @param string|null $color Цвет (автоматически определяется если null)
     * @param int $height Высота в пикселях
     * @return string HTML
     */
    public static function renderProgressBar(float $percent, ?string $color = null, int $height = 20): string
    {
        if ($color === null) {
            $color = ColorPalette::getThresholdColor($percent, 50, 75);
        }
        
        $html = '<div style="margin-top: 10px; background: ' . ColorPalette::GREY_LIGHT . '; border-radius: 10px; overflow: hidden; height: ' . $height . 'px;">';
        $html .= '<div style="background: ' . $color . '; width: ' . min(100, $percent) . '%; height: 100%;"></div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Рендер сетки со статистикой
     * 
     * @param array $stats Массив статистики ['label' => 'value', ...]
     * @param int $columns Количество колонок
     * @return string HTML
     */
    public static function renderStatsGrid(array $stats, int $columns = 4): string
    {
        $html = '<div style="background: #f5f5f5; padding: 10px; margin-bottom: 10px; border-radius: 4px;">';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; font-size: 12px;">';
        
        foreach ($stats as $label => $value) {
            $html .= '<div><strong>' . htmlspecialchars($label) . ':</strong> ' . (is_string($value) ? $value : htmlspecialchars((string)$value)) . '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Рендер выделенного блока (highlight box)
     * 
     * @param string $content Содержимое
     * @param string $color Цвет границы
     * @param string|null $title Заголовок (опционально)
     * @return string HTML
     */
    public static function renderHighlightBox(string $content, string $color, ?string $title = null): string
    {
        $html = '<div style="background: ' . $color . '20; border-left: 4px solid ' . $color . '; padding: 15px; margin-bottom: 20px; border-radius: 4px;">';
        
        if ($title) {
            $html .= '<h3 style="margin: 0 0 10px 0; color: ' . $color . ';">' . htmlspecialchars($title) . '</h3>';
        }
        
        $html .= '<div>' . $content . '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Форматировать значение для отображения в таблице
     * 
     * @param mixed $value Значение
     * @return string Отформатированная строка
     */
    private static function formatValueForTable(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_null($value)) {
            return 'null';
        }
        
        if (is_object($value)) {
            return get_class($value);
        }
        
        return (string)$value;
    }
}

