<?php declare(strict_types=1);

use Core\Console\Input;

describe('Input Arguments Parsing', function () {
    test('parses simple arguments', function () {
        $input = new Input(['script.php', 'command', 'arg1', 'arg2']);
        
        expect($input->getArguments())->toBe(['arg1', 'arg2']);
        expect($input->getArgument(0))->toBe('arg1');
        expect($input->getArgument(1))->toBe('arg2');
    });
    
    test('returns null for missing argument', function () {
        $input = new Input(['script.php', 'command']);
        
        expect($input->getArgument(0))->toBeNull();
    });
    
    test('returns default for missing argument', function () {
        $input = new Input(['script.php', 'command']);
        
        expect($input->getArgument(0, 'default'))->toBe('default');
    });
    
    test('hasArgument checks if argument exists', function () {
        $input = new Input(['script.php', 'command', 'arg1']);
        
        expect($input->hasArgument(0))->toBeTrue();
        expect($input->hasArgument(1))->toBeFalse();
    });
});

describe('Input Options Parsing', function () {
    test('parses boolean flags', function () {
        $input = new Input(['script.php', 'command', '--force', '--verbose']);
        
        expect($input->getOption('force'))->toBeTrue();
        expect($input->getOption('verbose'))->toBeTrue();
    });
    
    test('parses short flags', function () {
        $input = new Input(['script.php', 'command', '-f', '-v']);
        
        expect($input->getOption('f'))->toBeTrue();
        expect($input->getOption('v'))->toBeTrue();
    });
    
    test('parses options with values', function () {
        $input = new Input(['script.php', 'command', '--name=John', '--age=25']);
        
        expect($input->getOption('name'))->toBe('John');
        expect($input->getOption('age'))->toBe('25');
    });
    
    test('parses short options with values', function () {
        $input = new Input(['script.php', 'command', '-n=John']);
        
        expect($input->getOption('n'))->toBe('John');
    });
    
    test('returns default for missing option', function () {
        $input = new Input(['script.php', 'command']);
        
        expect($input->getOption('missing', 'default'))->toBe('default');
    });
    
    test('hasOption checks if option exists', function () {
        $input = new Input(['script.php', 'command', '--force']);
        
        expect($input->hasOption('force'))->toBeTrue();
        expect($input->hasOption('missing'))->toBeFalse();
    });
    
    test('getAllOptions returns all parsed options', function () {
        $input = new Input(['script.php', 'command', '--force', '--name=John', '-v']);
        
        $options = $input->getAllOptions();
        
        expect($options)->toHaveKey('force');
        expect($options)->toHaveKey('name');
        expect($options)->toHaveKey('v');
        expect($options['force'])->toBeTrue();
        expect($options['name'])->toBe('John');
    });
});

describe('Input Mixed Arguments and Options', function () {
    test('parses arguments and options together', function () {
        $input = new Input(['script.php', 'command', 'arg1', '--force', 'arg2', '--name=John']);
        
        expect($input->getArgument(0))->toBe('arg1');
        expect($input->getArgument(1))->toBe('arg2');
        expect($input->getOption('force'))->toBeTrue();
        expect($input->getOption('name'))->toBe('John');
    });
    
    test('stops parsing after double dash', function () {
        $input = new Input(['script.php', 'command', '--force', '--', '--not-an-option']);
        
        expect($input->getOption('force'))->toBeTrue();
        expect($input->getArgument(0))->toBe('--not-an-option');
        expect($input->hasOption('not-an-option'))->toBeFalse();
    });
});

describe('Input Edge Cases', function () {
    test('handles empty input', function () {
        $input = new Input(['script.php', 'command']);
        
        expect($input->getArguments())->toBe([]);
        expect($input->getAllOptions())->toBe([]);
    });
    
    test('handles options with empty values', function () {
        $input = new Input(['script.php', 'command', '--name=']);
        
        expect($input->getOption('name'))->toBe('');
    });
    
    test('handles options with special characters', function () {
        $input = new Input(['script.php', 'command', '--path=/home/user', '--url=https://example.com']);
        
        expect($input->getOption('path'))->toBe('/home/user');
        expect($input->getOption('url'))->toBe('https://example.com');
    });
    
    test('handles numeric arguments', function () {
        $input = new Input(['script.php', 'command', '123', '456']);
        
        expect($input->getArgument(0))->toBe('123');
        expect($input->getArgument(1))->toBe('456');
    });
});

describe('Input Replace Method', function () {
    test('replace method updates arguments', function () {
        $input = new Input(['script.php', 'command', 'old-arg']);
        
        expect($input->getArgument(0))->toBe('old-arg');
        
        $input->replace([0 => 'new-arg']);
        
        expect($input->getArgument(0))->toBe('new-arg');
    });
    
    test('replace method adds new arguments', function () {
        $input = new Input(['script.php', 'command']);
        
        $input->replace([0 => 'new-arg1', 1 => 'new-arg2']);
        
        expect($input->getArgument(0))->toBe('new-arg1');
        expect($input->getArgument(1))->toBe('new-arg2');
    });
});

