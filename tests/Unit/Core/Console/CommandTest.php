<?php declare(strict_types=1);

use Core\Console\Command;
use Core\Console\Input;
use Core\Console\Output;

beforeEach(function () {
    $this->input = new Input(['script.php', 'test']);
    $this->output = new Output();
});

describe('Command Basic Functionality', function () {
    test('command has signature and description', function () {
        $command = new class extends Command {
            protected string $signature = 'test:command';
            protected string $description = 'Test command description';
            
            public function handle(): int
            {
                return 0;
            }
        };
        
        expect($command->getSignature())->toBe('test:command');
        expect($command->getDescription())->toBe('Test command description');
    });
    
    test('command can execute handle method', function () {
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            public bool $executed = false;
            
            public function handle(): int
            {
                $this->executed = true;
                return 0;
            }
        };
        
        expect($command->executed)->toBeFalse();
        
        $command->execute($this->input, $this->output);
        
        expect($command->executed)->toBeTrue();
    });
    
    test('command returns exit code', function () {
        $successCommand = new class extends Command {
            protected string $signature = 'success';
            protected string $description = 'Success';
            
            public function handle(): int
            {
                return 0;
            }
        };
        
        $failCommand = new class extends Command {
            protected string $signature = 'fail';
            protected string $description = 'Fail';
            
            public function handle(): int
            {
                return 1;
            }
        };
        
        expect($successCommand->execute($this->input, $this->output))->toBe(0);
        expect($failCommand->execute($this->input, $this->output))->toBe(1);
    });
});

describe('Command Output Methods', function () {
    test('info method outputs message', function () {
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            
            public function handle(): int
            {
                $this->info('Info message');
                return 0;
            }
        };
        
        ob_start();
        $command->execute($this->input, $this->output);
        $output = ob_get_clean();
        
        expect($output)->toContain('Info message');
    });
    
    test('success method outputs message', function () {
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            
            public function handle(): int
            {
                $this->success('Success message');
                return 0;
            }
        };
        
        ob_start();
        $command->execute($this->input, $this->output);
        $output = ob_get_clean();
        
        expect($output)->toContain('Success message');
    });
    
    test('error method outputs message', function () {
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            
            public function handle(): int
            {
                $this->error('Error message');
                return 0;
            }
        };
        
        ob_start();
        $command->execute($this->input, $this->output);
        $output = ob_get_clean();
        
        expect($output)->toContain('Error message');
    });
    
    test('warning method outputs message', function () {
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            
            public function handle(): int
            {
                $this->warning('Warning message');
                return 0;
            }
        };
        
        ob_start();
        $command->execute($this->input, $this->output);
        $output = ob_get_clean();
        
        expect($output)->toContain('Warning message');
    });
    
    test('line method outputs message', function () {
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            
            public function handle(): int
            {
                $this->line('Plain message');
                return 0;
            }
        };
        
        ob_start();
        $command->execute($this->input, $this->output);
        $output = ob_get_clean();
        
        expect($output)->toContain('Plain message');
    });
    
    test('newLine method outputs empty line', function () {
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            
            public function handle(): int
            {
                $this->line('Line 1');
                $this->newLine();
                $this->line('Line 2');
                return 0;
            }
        };
        
        ob_start();
        $command->execute($this->input, $this->output);
        $output = ob_get_clean();
        
        expect($output)->toContain("Line 1\n\nLine 2");
    });
});

describe('Command Arguments and Options', function () {
    test('command can access arguments', function () {
        $input = new Input(['script.php', 'command', 'arg1', 'arg2']);
        
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            public array $args = [];
            
            public function handle(): int
            {
                $this->args[] = $this->argument(0);
                $this->args[] = $this->argument(1);
                return 0;
            }
        };
        
        $command->execute($input, $this->output);
        
        expect($command->args)->toBe(['arg1', 'arg2']);
    });
    
    test('command can access options', function () {
        $input = new Input(['script.php', 'command', '--force', '--name=John']);
        
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            public array $opts = [];
            
            public function handle(): int
            {
                $this->opts['force'] = $this->option('force');
                $this->opts['name'] = $this->option('name');
                return 0;
            }
        };
        
        $command->execute($input, $this->output);
        
        expect($command->opts['force'])->toBeTrue();
        expect($command->opts['name'])->toBe('John');
    });
    
    test('command returns default for missing argument', function () {
        $input = new Input(['script.php', 'command']);
        
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            public ?string $arg = null;
            
            public function handle(): int
            {
                $this->arg = $this->argument(0, 'default');
                return 0;
            }
        };
        
        $command->execute($input, $this->output);
        
        expect($command->arg)->toBe('default');
    });
    
    test('command returns default for missing option', function () {
        $input = new Input(['script.php', 'command']);
        
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            public ?string $opt = null;
            
            public function handle(): int
            {
                $this->opt = $this->option('missing', 'default');
                return 0;
            }
        };
        
        $command->execute($input, $this->output);
        
        expect($command->opt)->toBe('default');
    });
});

describe('Command Table Output', function () {
    test('table method outputs formatted table', function () {
        $command = new class extends Command {
            protected string $signature = 'test';
            protected string $description = 'Test';
            
            public function handle(): int
            {
                $this->table(
                    ['Name', 'Age'],
                    [
                        ['John', '25'],
                        ['Jane', '30'],
                    ]
                );
                return 0;
            }
        };
        
        ob_start();
        $command->execute($this->input, $this->output);
        $output = ob_get_clean();
        
        expect($output)->toContain('Name');
        expect($output)->toContain('Age');
        expect($output)->toContain('John');
        expect($output)->toContain('Jane');
    });
});

