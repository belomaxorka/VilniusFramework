<?php declare(strict_types=1);

/**
 * Правильное использование Dump Server
 * 
 * ВАЖНО: Передавайте ПЕРЕМЕННЫЕ, а не строки!
 */

require_once __DIR__ . '/core/bootstrap.php';

\Core\Core::init();

echo "🔄 Тестируем правильное использование Dump Server...\n\n";

// Проверяем доступность
if (!dump_server_available()) {
    echo "❌ Dump Server не доступен!\n";
    echo "   Запустите: php vilnius dump-server\n";
    exit(1);
}

echo "✅ Dump Server доступен!\n\n";

// ❌ НЕПРАВИЛЬНО - передаём строку "$user"
echo "❌ НЕПРАВИЛЬНО: server_dump('\$user', 'Wrong Way');\n";
server_dump('$user', 'Wrong Way');

sleep(1);

// ✅ ПРАВИЛЬНО - передаём массив
echo "✅ ПРАВИЛЬНО: server_dump(\$user, 'User Data');\n";
$user = [
    'id' => 123,
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'roles' => ['admin', 'editor'],
    'created_at' => date('Y-m-d H:i:s'),
];
server_dump($user, 'User Data');

sleep(1);

// ✅ ПРАВИЛЬНО - передаём объект
echo "✅ ПРАВИЛЬНО: server_dump(\$stdClass, 'Object Data');\n";
$obj = new stdClass();
$obj->id = 456;
$obj->title = 'Test Object';
$obj->active = true;
server_dump($obj, 'Object Data');

sleep(1);

// ✅ ПРАВИЛЬНО - разные типы данных
echo "✅ ПРАВИЛЬНО: Разные типы данных\n";
server_dump(42, 'Integer');
server_dump(3.14, 'Float');
server_dump(true, 'Boolean');
server_dump(['apple', 'banana', 'orange'], 'Array');
server_dump(null, 'Null Value');

sleep(1);

// ✅ ПРАВИЛЬНО - вложенные массивы
echo "✅ ПРАВИЛЬНО: Вложенные структуры\n";
$complexData = [
    'user' => [
        'id' => 1,
        'profile' => [
            'name' => 'Alice',
            'age' => 25,
            'address' => [
                'city' => 'New York',
                'country' => 'USA'
            ]
        ]
    ],
    'posts' => [
        ['id' => 1, 'title' => 'First Post'],
        ['id' => 2, 'title' => 'Second Post'],
    ]
];
server_dump($complexData, 'Complex Data');

echo "\n✅ Все данные отправлены!\n";
echo "📺 Проверьте окно Dump Server\n";
echo "\n💡 Обратите внимание:\n";
echo "   - Теперь показывает правильный путь к файлу\n";
echo "   - Показывает тип данных\n";
echo "   - Форматирует массивы и объекты\n";

