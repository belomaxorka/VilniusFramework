<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;

/**
 * Make Controller Command
 * 
 * Создать новый контроллер
 */
class MakeControllerCommand extends Command
{
    protected string $signature = 'make:controller';
    protected string $description = 'Create a new controller class';

    public function handle(): int
    {
        $name = $this->argument(0);

        if (!$name) {
            $this->error('Controller name is required.');
            $this->line('Usage: php vilnius make:controller UserController');
            return 1;
        }

        // Добавляем Controller в конец, если не указано
        if (!str_ends_with($name, 'Controller')) {
            $name .= 'Controller';
        }

        $path = ROOT . '/app/Controllers';

        // Создаем директорию, если её нет
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $filePath = "{$path}/{$name}.php";

        // Проверяем, не существует ли уже такой файл
        if (file_exists($filePath)) {
            $this->error("Controller already exists: {$name}");
            return 1;
        }

        // Проверяем флаг --resource
        $isResource = $this->option('resource') || $this->option('r');

        // Генерируем контент
        $stub = $isResource ? $this->getResourceStub($name) : $this->getStub($name);

        // Записываем файл
        file_put_contents($filePath, $stub);

        $this->success("Controller created successfully!");
        $this->line("  app/Controllers/{$name}.php");

        if ($isResource) {
            $this->newLine();
            $this->info("Resource controller created with methods:");
            $this->line("  • index()   - Display a listing");
            $this->line("  • create()  - Show create form");
            $this->line("  • store()   - Store new resource");
            $this->line("  • show()    - Display resource");
            $this->line("  • edit()    - Show edit form");
            $this->line("  • update()  - Update resource");
            $this->line("  • destroy() - Delete resource");
        }

        return 0;
    }

    /**
     * Получить stub для обычного контроллера
     */
    private function getStub(string $name): string
    {
        return <<<PHP
<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Response;

class {$name} extends Controller
{
    /**
     * Handle the request
     */
    public function index(): Response
    {
        return \$this->view('welcome', [
            'title' => '{$name}',
        ]);
    }
}

PHP;
    }

    /**
     * Получить stub для resource контроллера
     */
    private function getResourceStub(string $name): string
    {
        return <<<PHP
<?php declare(strict_types=1);

namespace App\Controllers;

use Core\Response;
use Core\Request;

class {$name} extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(): Response
    {
        return \$this->view('index');
    }

    /**
     * Show the form for creating a new resource
     */
    public function create(): Response
    {
        return \$this->view('create');
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request \$request): Response
    {
        // Validate and store
        
        return Response::redirectTo('/');
    }

    /**
     * Display the specified resource
     */
    public function show(int \$id): Response
    {
        return \$this->view('show', [
            'id' => \$id,
        ]);
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit(int \$id): Response
    {
        return \$this->view('edit', [
            'id' => \$id,
        ]);
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request \$request, int \$id): Response
    {
        // Validate and update
        
        return Response::redirectTo('/');
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy(int \$id): Response
    {
        // Delete resource
        
        return Response::redirectTo('/');
    }
}

PHP;
    }
}

