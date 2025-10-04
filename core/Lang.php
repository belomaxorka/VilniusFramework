<?php declare(strict_types=1);

namespace Core;

use Core\Facades\Facade;
use Core\Contracts\LanguageInterface;

/**
 * Lang Facade
 * 
 * Статический фасад для LanguageService
 * Все методы делегируются к LanguageInterface через DI контейнер
 * 
 * @method static void init()
 * @method static bool setLang(?string $lang = null, bool $validate = false)
 * @method static string get(string $key, array $params = [])
 * @method static bool has(string $key)
 * @method static array all()
 * @method static string getCurrentLang()
 * @method static string getFallbackLang()
 * @method static void setFallbackLang(string $lang)
 * @method static array getLoadedLanguages()
 * @method static array getMessages(?string $lang = null)
 * @method static void addMessages(string $lang, array $messages)
 * @method static bool isValidLanguage(string $lang)
 * @method static array getSupportedLanguages()
 * @method static array getSupportedLanguagesWithNames()
 * @method static string getLanguageName(string $lang)
 * @method static array getAvailableLanguages()
 * @method static bool isRTL(?string $lang = null)
 * @method static void reset()
 * 
 * @see \Core\Services\LanguageService
 */
class Lang extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LanguageInterface::class;
    }
}
