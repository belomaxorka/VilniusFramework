<?php declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\LanguageInterface;
use Core\Contracts\ConfigInterface;
use Core\Contracts\HttpInterface;
use Core\Contracts\LoggerInterface;

/**
 * Language Service
 * 
 * Сервис для работы с многоязычностью, переводами и локализацией
 */
class LanguageService implements LanguageInterface
{
    protected array $messages = [];
    protected string $currentLang = 'en';
    protected string $fallbackLang = 'en';

    public function __construct(
        protected ConfigInterface $config,
        protected HttpInterface $http,
        protected LoggerInterface $logger
    ) {}

    /**
     * Инициализировать систему языков из конфигурации
     */
    public function init(): void
    {
        $defaultLang = $this->config->get('language.default', 'en');
        $fallbackLang = $this->config->get('language.fallback', 'en');

        $this->setFallbackLang($fallbackLang);

        // Determine language: use specific or auto-detect
        if ($defaultLang === 'auto') {
            $this->setLang(null); // Auto-detection
        } elseif ($this->isValidLanguage($defaultLang)) {
            $this->setLang($defaultLang);
        } else {
            $this->setLang(null); // Fallback to auto-detection
        }
    }

    /**
     * Установить текущий язык и загрузить переводы
     */
    public function setLang(?string $lang = null, bool $validate = false): bool
    {
        $targetLang = $lang ?? $this->detectUserLang();

        // Validate if requested
        if ($validate && !$this->isValidLanguage($targetLang)) {
            return false;
        }

        $this->currentLang = $targetLang;
        $this->loadMessages($this->currentLang);

        // Preload fallback language
        if ($this->currentLang !== $this->fallbackLang) {
            $this->loadMessages($this->fallbackLang);
        }

        return true;
    }

    /**
     * Определить язык пользователя из HTTP заголовков или default
     */
    protected function detectUserLang(): string
    {
        $autoDetectEnabled = $this->config->get('language.auto_detect', false);
        $acceptLanguages = $this->http->getHeader('Accept-Language');

        if (!$autoDetectEnabled || empty($acceptLanguages)) {
            return $this->fallbackLang;
        }

        $languages = [];

        // Parse Accept-Language header
        preg_match_all(
            '/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?/i',
            $acceptLanguages,
            $matches
        );

        if (empty($matches[1])) {
            return $this->fallbackLang;
        }

        // Extract language codes with quality scores
        foreach ($matches[1] as $i => $lang) {
            $quality = isset($matches[2][$i]) && $matches[2][$i] !== ''
                ? (float)$matches[2][$i]
                : 1.0;
            $langCode = strtolower(substr($lang, 0, 2));

            if (preg_match('/^[a-z]{2}$/', $langCode)) {
                $languages[$langCode] = $quality;
            }
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Find first supported language
        $supportedLanguages = $this->getSupportedLanguages();
        foreach (array_keys($languages) as $lang) {
            if (in_array($lang, $supportedLanguages, true)) {
                return $lang;
            }
        }

        // No supported language found
        return $this->fallbackLang;
    }

    /**
     * Загрузить переводы из файла
     */
    protected function loadMessages(string $lang): void
    {
        if (isset($this->messages[$lang])) {
            return; // already loaded
        }

        if (!preg_match('/^[a-z]{2}$/', $lang)) {
            $this->messages[$lang] = [];
            return;
        }

        $file = LANG_DIR . "/$lang.php";
        if (is_file($file)) {
            $messages = include $file;
            $this->messages[$lang] = is_array($messages) ? $messages : [];
        } else {
            $this->messages[$lang] = [];
        }
    }

    /**
     * Получить перевод по ключу с необязательными плейсхолдерами
     */
    public function get(string $key, array $params = []): string
    {
        $currentValue = $this->getNestedValue($this->messages[$this->currentLang] ?? [], $key);
        $fallbackValue = $this->getNestedValue($this->messages[$this->fallbackLang] ?? [], $key);

        if ($currentValue === null && $fallbackValue === null) {
            $this->logMissingKey($key);
        }

        $message = $currentValue ?? $fallbackValue ?? $key;

        // Ensure we return a string (not array)
        if (!is_string($message)) {
            return $key;
        }

        // Replace placeholders
        if (!empty($params)) {
            $search = array_map(fn($k) => ":$k", array_keys($params));
            $replace = array_values($params);
            $message = str_replace($search, $replace, $message);
        }

        return $message;
    }

    /**
     * Проверить существование ключа перевода
     */
    public function has(string $key): bool
    {
        return $this->getNestedValue($this->messages[$this->currentLang] ?? [], $key) !== null
            || $this->getNestedValue($this->messages[$this->fallbackLang] ?? [], $key) !== null;
    }

    /**
     * Получить все сообщения для текущего языка
     */
    public function all(): array
    {
        return $this->messages[$this->currentLang] ?? [];
    }

    /**
     * Получить код текущего языка
     */
    public function getCurrentLang(): string
    {
        return $this->currentLang;
    }

    /**
     * Получить код резервного языка
     */
    public function getFallbackLang(): string
    {
        return $this->fallbackLang;
    }

    /**
     * Установить резервный язык
     */
    public function setFallbackLang(string $lang): void
    {
        $this->fallbackLang = $lang;
    }

    /**
     * Получить все загруженные языки
     */
    public function getLoadedLanguages(): array
    {
        return array_keys($this->messages);
    }

    /**
     * Получить все сообщения для конкретного языка
     */
    public function getMessages(?string $lang = null): array
    {
        $lang = $lang ?? $this->currentLang;
        return $this->messages[$lang] ?? [];
    }

    /**
     * Добавить или переопределить переводы во время выполнения
     */
    public function addMessages(string $lang, array $messages): void
    {
        if (!isset($this->messages[$lang])) {
            $this->messages[$lang] = [];
        }

        $this->messages[$lang] = array_merge($this->messages[$lang], $messages);
    }

    /**
     * Проверить валидность/поддержку языка
     */
    public function isValidLanguage(string $lang): bool
    {
        if (!preg_match('/^[a-z]{2}$/', $lang)) {
            return false;
        }

        $supportedLanguages = $this->getSupportedLanguages();
        return in_array($lang, $supportedLanguages, true);
    }

    /**
     * Получить поддерживаемые коды языков
     */
    public function getSupportedLanguages(): array
    {
        $supported = $this->config->get('language.supported', ['en' => 'English']);
        return array_keys($supported);
    }

    /**
     * Получить поддерживаемые языки с названиями
     */
    public function getSupportedLanguagesWithNames(): array
    {
        return $this->config->get('language.supported', ['en' => 'English']);
    }

    /**
     * Получить отображаемое название языка
     */
    public function getLanguageName(string $lang): string
    {
        $languages = $this->config->get('language.supported', ['en' => 'English']);
        return $languages[$lang] ?? $lang;
    }

    /**
     * Получить доступные коды языков из директории lang
     */
    public function getAvailableLanguages(): array
    {
        if (!defined('LANG_DIR') || !is_dir(LANG_DIR)) {
            return [];
        }

        $languages = [];
        $files = glob(LANG_DIR . '/*.php');

        if ($files === false) {
            return [];
        }

        foreach ($files as $file) {
            $langCode = basename($file, '.php');
            if (preg_match('/^[a-z]{2}$/', $langCode)) {
                $languages[] = $langCode;
            }
        }

        return $languages;
    }

    /**
     * Проверить является ли язык RTL (справа налево)
     */
    public function isRTL(?string $lang = null): bool
    {
        $lang = $lang ?? $this->currentLang;
        $rtlLanguages = $this->config->get('language.rtl_languages', []);
        return in_array($lang, $rtlLanguages, true);
    }

    /**
     * Сбросить состояние языка (для тестирования)
     */
    public function reset(): void
    {
        $this->messages = [];
        $this->currentLang = 'en';
        $this->fallbackLang = 'en';
    }

    /**
     * Логировать отсутствующий ключ перевода
     */
    protected function logMissingKey(string $key): void
    {
        if (!$this->config->get('language.log_missing', false)) {
            return;
        }

        $message = sprintf(
            'Missing translation key "%s" for language "%s" (fallback: "%s")',
            $key,
            $this->currentLang,
            $this->fallbackLang
        );

        $this->logger->warning($message, ['context' => 'language']);
    }

    /**
     * Получить значение из вложенного массива используя точечную нотацию
     */
    private function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }
}

