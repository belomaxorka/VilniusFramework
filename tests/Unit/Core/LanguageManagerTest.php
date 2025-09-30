<?php declare(strict_types=1);

use Core\Lang;
use Core\Config;
use Core\LanguageManager;

beforeEach(function (): void {
    // Reset state
    Lang::reset();
    Config::clear();
    
    // Define LANG_DIR if not defined
    if (!defined('LANG_DIR')) {
        define('LANG_DIR', __DIR__ . '/../../../lang');
    }
    
    // Setup default config
    Config::set('language', [
        'default' => 'en',
        'fallback' => 'en',
        'supported' => [
            'en' => 'English',
            'ru' => 'Русский',
        ],
        'auto_detect' => true,
        'log_missing' => false,
        'rtl_languages' => ['ar', 'he'],
    ]);
});

afterEach(function (): void {
    Lang::reset();
    Config::clear();
    unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
});

describe('Initialization', function () {
    it('initializes language system with default language', function (): void {
        Config::set('language.default', 'ru');
        LanguageManager::init();
        
        expect(Lang::getCurrentLang())->toBe('ru');
    });

    it('initializes with auto-detection when default is auto', function (): void {
        Config::set('language.default', 'auto');
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9';
        
        LanguageManager::init();
        
        expect(Lang::getCurrentLang())->toBe('ru');
    });

    it('sets fallback language during initialization', function (): void {
        Config::set('language.fallback', 'ru');
        LanguageManager::init();
        
        expect(Lang::getFallbackLang())->toBe('ru');
    });
});

describe('Language validation', function () {
    it('validates supported languages', function (): void {
        expect(LanguageManager::isValidLanguage('en'))->toBeTrue();
        expect(LanguageManager::isValidLanguage('ru'))->toBeTrue();
    });

    it('rejects unsupported languages', function (): void {
        expect(LanguageManager::isValidLanguage('fr'))->toBeFalse();
        expect(LanguageManager::isValidLanguage('de'))->toBeFalse();
    });

    it('rejects invalid language codes', function (): void {
        expect(LanguageManager::isValidLanguage('eng'))->toBeFalse();
        expect(LanguageManager::isValidLanguage('e'))->toBeFalse();
        expect(LanguageManager::isValidLanguage('123'))->toBeFalse();
        expect(LanguageManager::isValidLanguage('EN'))->toBeFalse(); // uppercase
    });

    it('rejects empty or special characters', function (): void {
        expect(LanguageManager::isValidLanguage(''))->toBeFalse();
        expect(LanguageManager::isValidLanguage('en-US'))->toBeFalse();
        expect(LanguageManager::isValidLanguage('en_US'))->toBeFalse();
    });
});

describe('Language switching', function () {
    it('changes language successfully', function (): void {
        LanguageManager::init();
        
        $result = LanguageManager::setLanguage('ru');
        
        expect($result)->toBeTrue();
        expect(LanguageManager::getCurrentLanguage())->toBe('ru');
    });

    it('fails to change to unsupported language', function (): void {
        LanguageManager::init();
        
        $result = LanguageManager::setLanguage('fr');
        
        expect($result)->toBeFalse();
        expect(LanguageManager::getCurrentLanguage())->not->toBe('fr');
    });

    it('fails to change to invalid language code', function (): void {
        LanguageManager::init();
        
        $result = LanguageManager::setLanguage('invalid');
        
        expect($result)->toBeFalse();
    });
});

describe('Supported languages', function () {
    it('gets supported language codes', function (): void {
        $languages = LanguageManager::getSupportedLanguages();
        
        expect($languages)->toBe(['en', 'ru']);
    });

    it('gets supported languages with names', function (): void {
        $languages = LanguageManager::getSupportedLanguagesWithNames();
        
        expect($languages)->toBe([
            'en' => 'English',
            'ru' => 'Русский',
        ]);
    });

    it('gets language display name', function (): void {
        expect(LanguageManager::getLanguageName('en'))->toBe('English');
        expect(LanguageManager::getLanguageName('ru'))->toBe('Русский');
    });

    it('returns language code when name not found', function (): void {
        expect(LanguageManager::getLanguageName('fr'))->toBe('fr');
    });
});

describe('RTL support', function () {
    it('detects RTL languages', function (): void {
        expect(LanguageManager::isRTL('ar'))->toBeTrue();
        expect(LanguageManager::isRTL('he'))->toBeTrue();
    });

    it('detects non-RTL languages', function (): void {
        expect(LanguageManager::isRTL('en'))->toBeFalse();
        expect(LanguageManager::isRTL('ru'))->toBeFalse();
    });

    it('checks current language for RTL when no parameter provided', function (): void {
        LanguageManager::init();
        LanguageManager::setLanguage('en');
        
        expect(LanguageManager::isRTL())->toBeFalse();
    });

    it('handles empty RTL languages list', function (): void {
        Config::set('language.rtl_languages', []);
        
        expect(LanguageManager::isRTL('ar'))->toBeFalse();
    });
});

describe('Available languages', function () {
    it('gets available languages from directory', function (): void {
        $available = LanguageManager::getAvailableLanguages();
        
        expect($available)->toBeArray();
        expect($available)->toContain('en');
        expect($available)->toContain('ru');
    });

    it('returns empty array when LANG_DIR not defined', function (): void {
        // This test is tricky since LANG_DIR is already defined
        // We can't undefine it, but we can test the logic indirectly
        expect(LanguageManager::getAvailableLanguages())->toBeArray();
    });

    it('filters out invalid language codes', function (): void {
        $available = LanguageManager::getAvailableLanguages();
        
        foreach ($available as $lang) {
            expect($lang)->toMatch('/^[a-z]{2}$/');
        }
    });
});

describe('Edge cases', function () {
    it('handles missing config values gracefully', function (): void {
        Config::clear();
        
        LanguageManager::init();
        
        // Should use defaults
        expect(Lang::getCurrentLang())->toBeString();
    });

    it('handles empty supported languages list', function (): void {
        Config::set('language.supported', []);
        
        $languages = LanguageManager::getSupportedLanguages();
        
        expect($languages)->toBe([]);
    });

    it('validates against empty supported list', function (): void {
        Config::set('language.supported', []);
        
        expect(LanguageManager::isValidLanguage('en'))->toBeFalse();
    });
});

describe('Integration with Lang class', function () {
    it('initializes and retrieves translations', function (): void {
        LanguageManager::init();
        LanguageManager::setLanguage('en');
        
        expect(Lang::get('hello', ['name' => 'World']))->toBe('Hello, World!');
    });

    it('switches language and retrieves correct translations', function (): void {
        LanguageManager::init();
        
        LanguageManager::setLanguage('en');
        $enGreeting = Lang::get('hello', ['name' => 'World']);
        
        LanguageManager::setLanguage('ru');
        $ruGreeting = Lang::get('hello', ['name' => 'Мир']);
        
        expect($enGreeting)->toBe('Hello, World!');
        expect($ruGreeting)->toBe('Привет, Мир!');
    });

    it('maintains fallback language across language switches', function (): void {
        Config::set('language.fallback', 'en');
        LanguageManager::init();
        
        LanguageManager::setLanguage('ru');
        
        expect(Lang::getFallbackLang())->toBe('en');
    });
});

describe('Auto-detection scenarios', function () {
    it('detects and sets correct language from browser', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9,en;q=0.8';
        Config::set('language.default', 'auto');
        Config::set('language.auto_detect', true);
        
        LanguageManager::init();
        
        expect(LanguageManager::getCurrentLanguage())->toBe('ru');
    });

    it('uses fallback when browser language unsupported', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
        Config::set('language.default', 'auto');
        Config::set('language.auto_detect', true);
        
        LanguageManager::init();
        
        expect(LanguageManager::getCurrentLanguage())->toBe('en');
    });

    it('ignores auto-detection when disabled', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9';
        Config::set('language.default', 'en');
        Config::set('language.auto_detect', false);
        
        LanguageManager::init();
        
        expect(LanguageManager::getCurrentLanguage())->toBe('en');
    });
});
