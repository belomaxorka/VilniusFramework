<?php declare(strict_types=1);

namespace Core\Console\Commands;

use Core\Console\Command;
use Core\Container;
use Core\Database;

/**
 * Database Seed Command
 * 
 * Заполнить базу данных тестовыми данными
 */
class DbSeedCommand extends Command
{
    protected string $signature = 'db:seed';
    protected string $description = 'Seed the database with test data';

    public function handle(): int
    {
        $this->info('Seeding database with test data...');
        $this->newLine();

        try {
            // Получаем Database из контейнера
            $container = Container::getInstance();
            $db = $container->make(Database::class);
            
            // Проверяем, есть ли уже пользователи
            $existingUsers = $db->table('users')->count();
            
            if ($existingUsers > 0) {
                $this->warn("Database already has {$existingUsers} users.");
                $continue = $this->confirm('Do you want to add more test users?', false);
                
                if (!$continue) {
                    $this->info('Seeding cancelled.');
                    return 0;
                }
            }

            // Добавляем тестовых пользователей
            $users = [
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'Bob Johnson',
                    'email' => 'bob@example.com',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'email_verified_at' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'Alice Williams',
                    'email' => 'alice@example.com',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'Charlie Brown',
                    'email' => 'charlie@example.com',
                    'password' => password_hash('password123', PASSWORD_DEFAULT),
                    'email_verified_at' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
            ];

            $seededCount = 0;
            
            foreach ($users as $user) {
                // Проверяем, не существует ли уже пользователь с таким email
                $exists = $db->table('users')
                    ->where('email', $user['email'])
                    ->first();
                
                if ($exists) {
                    $this->line("  " . $this->color('Skipped:', 'yellow') . " {$user['email']} (already exists)");
                    continue;
                }

                $db->table('users')->insert($user);
                $this->line("  " . $this->color('Created:', 'green') . " {$user['name']} ({$user['email']})");
                $seededCount++;
            }

            $this->newLine();
            
            if ($seededCount > 0) {
                $this->success("Database seeded successfully!");
                $this->line("  Created: {$seededCount} users");
            } else {
                $this->info('No new users were created (all already exist).');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Seeding failed: ' . $e->getMessage());
            return 1;
        }
    }
}

