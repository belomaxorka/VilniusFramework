<?php

use Core\Database\Migrations\Migration;
use Core\Database\Schema\Schema;
use Core\Database;

class InitialDemoSetup extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаём таблицу demo_users
        Schema::create('demo_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('avatar')->nullable();
            $table->string('role')->default('user');
            $table->string('status')->default('active');
            $table->integer('posts_count')->default(0);
            $table->timestamps();
        });

        // Заполняем демо-данными
        $db = Database::getInstance();
        
        $users = [
            [
                'name' => 'Александр Петров',
                'email' => 'alex@example.com',
                'avatar' => '👨‍💻',
                'role' => 'admin',
                'status' => 'active',
                'posts_count' => 45,
                'created_at' => date('Y-m-d H:i:s', strtotime('-25 days')),
            ],
            [
                'name' => 'Мария Иванова',
                'email' => 'maria@example.com',
                'avatar' => '👩‍💼',
                'role' => 'moderator',
                'status' => 'active',
                'posts_count' => 32,
                'created_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
            ],
            [
                'name' => 'Дмитрий Сидоров',
                'email' => 'dmitry@example.com',
                'avatar' => '👨‍🎨',
                'role' => 'user',
                'status' => 'active',
                'posts_count' => 18,
                'created_at' => date('Y-m-d H:i:s', strtotime('-15 days')),
            ],
            [
                'name' => 'Елена Смирнова',
                'email' => 'elena@example.com',
                'avatar' => '👩‍🔬',
                'role' => 'user',
                'status' => 'active',
                'posts_count' => 27,
                'created_at' => date('Y-m-d H:i:s', strtotime('-12 days')),
            ],
            [
                'name' => 'Игорь Козлов',
                'email' => 'igor@example.com',
                'avatar' => '👨‍🏫',
                'role' => 'user',
                'status' => 'inactive',
                'posts_count' => 5,
                'created_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
            ],
            [
                'name' => 'Анна Волкова',
                'email' => 'anna@example.com',
                'avatar' => '👩‍⚕️',
                'role' => 'user',
                'status' => 'active',
                'posts_count' => 41,
                'created_at' => date('Y-m-d H:i:s', strtotime('-6 days')),
            ],
            [
                'name' => 'Сергей Новиков',
                'email' => 'sergey@example.com',
                'avatar' => '👨‍✈️',
                'role' => 'moderator',
                'status' => 'active',
                'posts_count' => 23,
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            ],
            [
                'name' => 'Ольга Морозова',
                'email' => 'olga@example.com',
                'avatar' => '👩‍🎤',
                'role' => 'user',
                'status' => 'active',
                'posts_count' => 15,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
        ];

        foreach ($users as $user) {
            $user['updated_at'] = $user['created_at'];
            $db->table('demo_users')->insert($user);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_users');
    }
}

