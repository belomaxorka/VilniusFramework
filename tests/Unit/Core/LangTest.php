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
        'rtl_languages' => [],
    ]);
});

afterEach(function (): void {
    Lang::reset();
    Config::clear();
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
        
        // Getting an array should return it as-is or handle appropriately
        $result = Lang::get('data.items');
        expect($result)->toBeArray();
    });
});

describe('Auto-detection', function () {
    it('detects language from HTTP_ACCEPT_LANGUAGE header', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7';
        Config::set('language.auto_detect', true);
        
        Lang::setLang(null); // null triggers auto-detection
        
        expect(Lang::getCurrentLang())->toBe('ru');
        
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    });

    it('falls back to default when auto-detect disabled', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9';
        Config::set('language.auto_detect', false);
        
        Lang::setLang(null);
        
        expect(Lang::getCurrentLang())->toBe('en'); // fallback
        
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    });

    it('uses fallback when browser language not supported', function (): void {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
        Config::set('language.auto_detect', true);
        
        Lang::setLang(null);
        
        // French is not supported, should use fallback
        expect(Lang::getCurrentLang())->toBe('en');
        
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    });
});
