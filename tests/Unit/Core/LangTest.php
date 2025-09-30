<?php declare(strict_types=1);

use Core\Lang;
use Core\Config;

beforeEach(function (): void {
    // Reset Lang state before each test
    Lang::reset();

    // Set up test language directory
    if (!defined('LANG_DIR')) {
        define('LANG_DIR', __DIR__ . '/../../../lang');
    }

    // Setup Config for testing
    Config::clear();
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
        Lang::init();

        expect(Lang::getCurrentLang())->toBe('ru');
    });

    it('initializes with auto-detection when default is auto', function (): void {
        Config::set('language.default', 'auto');
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9';

        Lang::init();

        expect(Lang::getCurrentLang())->toBe('ru');
    });

    it('sets fallback language during initialization', function (): void {
        Config::set('language.fallback', 'ru');
        Lang::init();

        expect(Lang::getFallbackLang())->toBe('ru');
    });

    it('falls back to auto-detection when default language is invalid', function (): void {
        Config::set('language.default', 'invalid_lang');
        Lang::init();

        expect(Lang::getCurrentLang())->toBeString();
    });
});

describe('Basic functionality', function () {
    it('sets and gets current language', function (): void {
        Lang::setLang('en');
        expect(Lang::getCurrentLang())->toBe('en');
    });

    it('loads and retrieves simple translations', function (): void {
        Lang::setLang('en');
        expect(Lang::get('hello', ['name' => 'World']))->toBe('Hello, World!');
    });

    it('uses fallback language when key not found in current language', function (): void {
        Lang::setLang('en');
        expect(Lang::get('nonexistent_key'))->toBe('nonexistent_key');
    });

    it('returns key when translation not found', function (): void {
        Lang::setLang('en');
        expect(Lang::get('missing.key'))->toBe('missing.key');
    });

    it('checks if translation exists', function (): void {
        Lang::setLang('en');
        expect(Lang::has('hello'))->toBeTrue();
        expect(Lang::has('nonexistent'))->toBeFalse();
    });
});

describe('Nested translations', function () {
    it('gets nested translation with dot notation', function (): void {
        Lang::setLang('en');
        expect(Lang::get('user.profile.title'))->toBe('User Profile');
        expect(Lang::get('user.profile.edit'))->toBe('Edit Profile');
    });

    it('gets deeply nested translation', function (): void {
        Lang::setLang('en');
        expect(Lang::get('errors.validation.required', ['field' => 'email']))
            ->toBe('The email field is required');
    });

    it('checks existence of nested keys', function (): void {
        Lang::setLang('en');
        expect(Lang::has('user.profile.title'))->toBeTrue();
        expect(Lang::has('user.profile.nonexistent'))->toBeFalse();
        expect(Lang::has('errors.validation.email'))->toBeTrue();
    });

    it('handles nested keys with placeholders', function (): void {
        Lang::setLang('en');
        expect(Lang::get('user.greeting', ['username' => 'John']))
            ->toBe('Hello, John!');
    });
});

describe('Placeholders', function () {
    it('replaces single placeholder', function (): void {
        Lang::setLang('en');
        expect(Lang::get('hello', ['name' => 'Alice']))->toBe('Hello, Alice!');
    });

    it('replaces multiple placeholders', function (): void {
        Lang::setLang('en');
        $result = Lang::get('errors.validation.min', [
            'field' => 'password',
            'min' => '8',
        ]);
        expect($result)->toBe('The password must be at least 8 characters');
    });

    it('works without placeholders when params are empty', function (): void {
        Lang::setLang('en');
        expect(Lang::get('welcome'))->toBe('Welcome to our application!');
    });

    it('leaves unmatched placeholders unchanged', function (): void {
        Lang::setLang('en');
        expect(Lang::get('hello'))->toBe('Hello, :name!');
    });
});

describe('Fallback language', function () {
    it('uses fallback when key missing in current language', function (): void {
        Lang::setLang('ru');
        Lang::setFallbackLang('en');

        // Add a key only to English
        Lang::addMessages('en', ['only_english' => 'English only']);

        expect(Lang::get('only_english'))->toBe('English only');
    });

    it('gets and sets fallback language', function (): void {
        Lang::setFallbackLang('ru');
        expect(Lang::getFallbackLang())->toBe('ru');

        Lang::setFallbackLang('en');
        expect(Lang::getFallbackLang())->toBe('en');
    });
});

describe('Multiple languages', function () {
    it('switches between languages', function (): void {
        Lang::setLang('en');
        expect(Lang::get('hello', ['name' => 'World']))->toBe('Hello, World!');

        Lang::setLang('ru');
        expect(Lang::get('hello', ['name' => 'Мир']))->toBe('Привет, Мир!');
    });

    it('loads multiple languages', function (): void {
        Lang::setLang('en');
        Lang::setLang('ru');

        $loaded = Lang::getLoadedLanguages();
        expect($loaded)->toContain('en');
        expect($loaded)->toContain('ru');
    });

    it('translates nested keys in Russian', function (): void {
        Lang::setLang('ru');
        expect(Lang::get('user.profile.title'))->toBe('Профиль пользователя');
        expect(Lang::get('buttons.submit'))->toBe('Отправить');
    });
});

describe('Language validation', function () {
    it('validates supported languages', function (): void {
        expect(Lang::isValidLanguage('en'))->toBeTrue();
        expect(Lang::isValidLanguage('ru'))->toBeTrue();
    });

    it('rejects unsupported languages', function (): void {
        expect(Lang::isValidLanguage('fr'))->toBeFalse();
        expect(Lang::isValidLanguage('de'))->toBeFalse();
    });

    it('rejects invalid language codes', function (): void {
        expect(Lang::isValidLanguage('eng'))->toBeFalse();
        expect(Lang::isValidLanguage('e'))->toBeFalse();
        expect(Lang::isValidLanguage('123'))->toBeFalse();
        expect(Lang::isValidLanguage('EN'))->toBeFalse(); // uppercase
    });

    it('rejects empty or special characters', function (): void {
        expect(Lang::isValidLanguage(''))->toBeFalse();
        expect(Lang::isValidLanguage('en-US'))->toBeFalse();
        expect(Lang::isValidLanguage('en_US'))->toBeFalse();
    });

    it('validates language when setting with validation flag', function (): void {
        $result = Lang::setLang('fr', true); // French not supported
        expect($result)->toBeFalse();

        $result = Lang::setLang('ru', true); // Russian is supported
        expect($result)->toBeTrue();
        expect(Lang::getCurrentLang())->toBe('ru');
    });
});

describe('State management', function () {
    it('resets language state', function (): void {
        Lang::setLang('ru');
        Lang::setFallbackLang('ru');

        Lang::reset();

        expect(Lang::getCurrentLang())->toBe('en');
        expect(Lang::getFallbackLang())->toBe('en');
        expect(Lang::getLoadedLanguages())->toBe([]);
    });

    it('gets all messages for current language', function (): void {
        Lang::setLang('en');
        $messages = Lang::all();

        expect($messages)->toBeArray();
        expect($messages)->toHaveKey('hello');
        expect($messages)->toHaveKey('user');
    });

    it('adds messages at runtime', function (): void {
        Lang::setLang('en');
        Lang::addMessages('en', [
            'custom' => 'Custom message',
            'another' => 'Another one',
        ]);

        expect(Lang::get('custom'))->toBe('Custom message');
        expect(Lang::get('another'))->toBe('Another one');
    });

    it('overrides existing messages with addMessages', function (): void {
        Lang::setLang('en');
        Lang::addMessages('en', ['hello' => 'Hi, :name!']);

        expect(Lang::get('hello', ['name' => 'Test']))->toBe('Hi, Test!');
    });

    it('gets messages for specific language', function (): void {
        Lang::setLang('en');
        Lang::setLang('ru'); // Load both languages

        $enMessages = Lang::getMessages('en');
        $ruMessages = Lang::getMessages('ru');

        expect($enMessages)->toBeArray();
        expect($ruMessages)->toBeArray();
        expect($enMessages['hello'])->toBe('Hello, :name!');
        expect($ruMessages['hello'])->toBe('Привет, :name!');
    });
});

describe('Supported languages', function () {
    it('gets supported language codes', function (): void {
        $languages = Lang::getSupportedLanguages();

        expect($languages)->toBe(['en', 'ru']);
    });

    it('gets supported languages with names', function (): void {
        $languages = Lang::getSupportedLanguagesWithNames();

        expect($languages)->toBe([
            'en' => 'English',
            'ru' => 'Русский',
        ]);
    });

    it('gets language display name', function (): void {
        expect(Lang::getLanguageName('en'))->toBe('English');
        expect(Lang::getLanguageName('ru'))->toBe('Русский');
    });

    it('returns language code when name not found', function (): void {
        expect(Lang::getLanguageName('fr'))->toBe('fr');
    });
});

describe('Available languages', function () {
    it('gets available languages from directory', function (): void {
        $available = Lang::getAvailableLanguages();

        expect($available)->toBeArray();
        expect($available)->toContain('en');
        expect($available)->toContain('ru');
    });

    it('filters out invalid language codes', function (): void {
        $available = Lang::getAvailableLanguages();

        foreach ($available as $lang) {
            expect($lang)->toMatch('/^[a-z]{2}$/');
        }
    });
});

describe('RTL support', function () {
    it('detects RTL languages', function (): void {
        expect(Lang::isRTL('ar'))->toBeTrue();
        expect(Lang::isRTL('he'))->toBeTrue();
    });

    it('detects non-RTL languages', function (): void {
        expect(Lang::isRTL('en'))->toBeFalse();
        expect(Lang::isRTL('ru'))->toBeFalse();
    });

    it('checks current language for RTL when no parameter provided', function (): void {
        Lang::init();
        Lang::setLang('en', true);

        expect(Lang::isRTL())->toBeFalse();
    });

    it('handles empty RTL languages list', function (): void {
        Config::set('language.rtl_languages', []);

        expect(Lang::isRTL('ar'))->toBeFalse();
    });
});

describe('Edge cases', function () {
    it('handles invalid language codes gracefully', function (): void {
        Lang::setLang('invalid_lang');
        expect(Lang::getCurrentLang())->toBe('invalid_lang');
        expect(Lang::get('any_key'))->toBe('any_key');
    });

    it('handles empty key', function (): void {
        Lang::setLang('en');
        expect(Lang::get(''))->toBe('');
    });

    it('handles non-existent nested path', function (): void {
        Lang::setLang('en');
        expect(Lang::get('user.nonexistent.deeply.nested'))->toBe('user.nonexistent.deeply.nested');
    });

    it('handles has() with empty messages', function (): void {
        Lang::reset();
        expect(Lang::has('any_key'))->toBeFalse();
    });

    it('handles nested value that is not a string', function (): void {
        Lang::setLang('en');
        Lang::addMessages('en', [
            'data' => [
                'items' => ['one', 'two', 'three'],
            ],
        ]);

        // Getting an array should return the key (since we can't return non-string)
        $result = Lang::get('data.items');
        expect($result)->toBe('data.items');
    });

    it('handles missing config values gracefully', function (): void {
        Config::clear();

        Lang::init();

        // Should use defaults
        expect(Lang::getCurrentLang())->toBeString();
    });

    it('handles empty supported languages list', function (): void {
        Config::set('language.supported', []);

        $languages = Lang::getSupportedLanguages();

        expect($languages)->toBe([]);
    });

    it('validates against empty supported list', function (): void {
        Config::set('language.supported', []);

        expect(Lang::isValidLanguage('en'))->toBeFalse();
    });
});

describe('Auto-detection', function () {
    it('detects language from HTTP_ACCEPT_LANGUAGE header', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
        Config::set('language.auto_detect', true);

        Lang::setLang(null); // null triggers auto-detection

        expect(Lang::getCurrentLang())->toBe('ru');
    });

    it('falls back to default when auto-detect disabled', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9';
        Config::set('language.auto_detect', false);

        Lang::setLang(null);

        expect(Lang::getCurrentLang())->toBe('en'); // fallback
    });

    it('uses fallback when browser language not supported', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
        Config::set('language.auto_detect', true);

        Lang::setLang(null);

        // French is not supported, should use fallback
        expect(Lang::getCurrentLang())->toBe('en');
    });

    it('detects and sets correct language from browser via init', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9,en;q=0.8';
        Config::set('language.default', 'auto');
        Config::set('language.auto_detect', true);

        Lang::init();

        expect(Lang::getCurrentLang())->toBe('ru');
    });

    it('uses fallback when browser language unsupported via init', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
        Config::set('language.default', 'auto');
        Config::set('language.auto_detect', true);

        Lang::init();

        expect(Lang::getCurrentLang())->toBe('en');
    });

    it('ignores auto-detection when disabled via init', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9';
        Config::set('language.default', 'en');
        Config::set('language.auto_detect', false);

        Lang::init();

        expect(Lang::getCurrentLang())->toBe('en');
    });
});
