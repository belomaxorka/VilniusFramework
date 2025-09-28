<?php declare(strict_types=1);

use Core\Database\DatabaseManager;
use Core\Database\Exceptions\QueryException;

beforeEach(function (): void {
    $this->config = [
        'default' => 'test',
        'connections' => [
            'test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ],
    ];
    
    $this->db = new DatabaseManager($this->config);
    $this->connection = $this->db->connection();
    
    // Создаем тестовые таблицы
    $this->connection->exec('
        CREATE TABLE accounts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            balance DECIMAL(10,2) NOT NULL DEFAULT 0.00
        )
    ');
    
    $this->connection->exec('
        CREATE TABLE transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            from_account_id INTEGER,
            to_account_id INTEGER,
            amount DECIMAL(10,2) NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (from_account_id) REFERENCES accounts (id),
            FOREIGN KEY (to_account_id) REFERENCES accounts (id)
        )
    ');
    
    // Вставляем тестовые данные
    $this->connection->exec("
        INSERT INTO accounts (name, balance) VALUES 
        ('Account A', 1000.00),
        ('Account B', 500.00),
        ('Account C', 2000.00)
    ");
});

it('begins transaction successfully', function (): void {
    $result = $this->db->beginTransaction();
    expect($result)->toBeTrue();
    
    // Проверяем, что транзакция активна
    $inTransaction = $this->connection->inTransaction();
    expect($inTransaction)->toBeTrue();
});

it('commits transaction successfully', function (): void {
    $this->db->beginTransaction();
    
    // Выполняем операцию в транзакции
    $this->db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Test Account', 100.00]);
    
    $result = $this->db->commit();
    expect($result)->toBeTrue();
    
    // Проверяем, что транзакция завершена
    $inTransaction = $this->connection->inTransaction();
    expect($inTransaction)->toBeFalse();
    
    // Проверяем, что данные сохранены
    $count = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Test Account"')->fetchColumn();
    expect($count)->toBe(1);
});

it('rolls back transaction successfully', function (): void {
    $this->db->beginTransaction();
    
    // Выполняем операцию в транзакции
    $this->db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Test Account', 100.00]);
    
    $result = $this->db->rollback();
    expect($result)->toBeTrue();
    
    // Проверяем, что транзакция завершена
    $inTransaction = $this->connection->inTransaction();
    expect($inTransaction)->toBeFalse();
    
    // Проверяем, что данные не сохранены
    $count = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Test Account"')->fetchColumn();
    expect($count)->toBe(0);
});

it('executes transaction callback successfully', function (): void {
    $result = $this->db->transaction(function ($db) {
        $db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Test Account', 100.00]);
        return 'success';
    });
    
    expect($result)->toBe('success');
    
    // Проверяем, что данные сохранены
    $count = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Test Account"')->fetchColumn();
    expect($count)->toBe(1);
});

it('rolls back transaction on callback exception', function (): void {
    expect(fn() => $this->db->transaction(function ($db) {
        $db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Test Account', 100.00]);
        throw new Exception('Test exception');
    }))->toThrow(Exception::class, 'Test exception');
    
    // Проверяем, что данные не сохранены
    $count = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Test Account"')->fetchColumn();
    expect($count)->toBe(0);
});

it('handles nested transactions', function (): void {
    $result = $this->db->transaction(function ($db) {
        $db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Outer Account', 100.00]);
        
        $innerResult = $db->transaction(function ($db) {
            $db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Inner Account', 200.00]);
            return 'inner_success';
        });
        
        expect($innerResult)->toBe('inner_success');
        return 'outer_success';
    });
    
    expect($result)->toBe('outer_success');
    
    // Проверяем, что оба аккаунта созданы
    $outerCount = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Outer Account"')->fetchColumn();
    $innerCount = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Inner Account"')->fetchColumn();
    
    expect($outerCount)->toBe(1);
    expect($innerCount)->toBe(1);
});

it('rolls back nested transactions on inner exception', function (): void {
    expect(fn() => $this->db->transaction(function ($db) {
        $db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Outer Account', 100.00]);
        
        $db->transaction(function ($db) {
            $db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Inner Account', 200.00]);
            throw new Exception('Inner exception');
        });
        
        return 'outer_success';
    }))->toThrow(Exception::class, 'Inner exception');
    
    // Проверяем, что ни один аккаунт не создан
    $outerCount = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Outer Account"')->fetchColumn();
    $innerCount = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Inner Account"')->fetchColumn();
    
    expect($outerCount)->toBe(0);
    expect($innerCount)->toBe(0);
});

it('performs money transfer transaction', function (): void {
    $transferAmount = 100.00;
    $fromAccountId = 1; // Account A with 1000.00
    $toAccountId = 2;   // Account B with 500.00
    
    $result = $this->db->transaction(function ($db) use ($transferAmount, $fromAccountId, $toAccountId) {
        // Проверяем баланс отправителя
        $fromBalance = $db->selectOne('SELECT balance FROM accounts WHERE id = ?', [$fromAccountId])['balance'];
        
        if ($fromBalance < $transferAmount) {
            throw new Exception('Insufficient funds');
        }
        
        // Списываем с отправителя
        $db->update('UPDATE accounts SET balance = balance - ? WHERE id = ?', [$transferAmount, $fromAccountId]);
        
        // Зачисляем получателю
        $db->update('UPDATE accounts SET balance = balance + ? WHERE id = ?', [$transferAmount, $toAccountId]);
        
        // Записываем транзакцию
        $db->insert('INSERT INTO transactions (from_account_id, to_account_id, amount, description) VALUES (?, ?, ?, ?)', 
            [$fromAccountId, $toAccountId, $transferAmount, 'Money transfer']);
        
        return 'transfer_completed';
    });
    
    expect($result)->toBe('transfer_completed');
    
    // Проверяем балансы
    $fromBalance = $this->connection->query('SELECT balance FROM accounts WHERE id = 1')->fetchColumn();
    $toBalance = $this->connection->query('SELECT balance FROM accounts WHERE id = 2')->fetchColumn();
    
    expect($fromBalance)->toBe(900.00); // 1000 - 100
    expect($toBalance)->toBe(600.00);   // 500 + 100
    
    // Проверяем запись транзакции
    $transactionCount = $this->connection->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    expect($transactionCount)->toBe(1);
});

it('rolls back money transfer on insufficient funds', function (): void {
    $transferAmount = 2000.00; // Больше чем есть на счете
    $fromAccountId = 1; // Account A with 1000.00
    $toAccountId = 2;   // Account B with 500.00
    
    expect(fn() => $this->db->transaction(function ($db) use ($transferAmount, $fromAccountId, $toAccountId) {
        // Проверяем баланс отправителя
        $fromBalance = $db->selectOne('SELECT balance FROM accounts WHERE id = ?', [$fromAccountId])['balance'];
        
        if ($fromBalance < $transferAmount) {
            throw new Exception('Insufficient funds');
        }
        
        // Этот код не должен выполниться
        $db->update('UPDATE accounts SET balance = balance - ? WHERE id = ?', [$transferAmount, $fromAccountId]);
        $db->update('UPDATE accounts SET balance = balance + ? WHERE id = ?', [$transferAmount, $toAccountId]);
        
        return 'transfer_completed';
    }))->toThrow(Exception::class, 'Insufficient funds');
    
    // Проверяем, что балансы не изменились
    $fromBalance = $this->connection->query('SELECT balance FROM accounts WHERE id = 1')->fetchColumn();
    $toBalance = $this->connection->query('SELECT balance FROM accounts WHERE id = 2')->fetchColumn();
    
    expect($fromBalance)->toBe(1000.00); // Не изменился
    expect($toBalance)->toBe(500.00);    // Не изменился
    
    // Проверяем, что транзакция не записана
    $transactionCount = $this->connection->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    expect($transactionCount)->toBe(0);
});

it('handles multiple operations in transaction', function (): void {
    $result = $this->db->transaction(function ($db) {
        // Создаем новый аккаунт
        $db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['New Account', 0.00]);
        $newAccountId = $db->lastInsertId();
        
        // Пополняем аккаунт
        $db->update('UPDATE accounts SET balance = ? WHERE id = ?', [500.00, $newAccountId]);
        
        // Записываем транзакцию пополнения
        $db->insert('INSERT INTO transactions (to_account_id, amount, description) VALUES (?, ?, ?)', 
            [$newAccountId, 500.00, 'Initial deposit']);
        
        return $newAccountId;
    });
    
    expect($result)->toBe('4'); // ID нового аккаунта
    
    // Проверяем, что аккаунт создан с правильным балансом
    $account = $this->connection->query('SELECT * FROM accounts WHERE id = 4')->fetch(PDO::FETCH_ASSOC);
    expect($account['name'])->toBe('New Account');
    expect($account['balance'])->toBe(500.00);
    
    // Проверяем транзакцию
    $transaction = $this->connection->query('SELECT * FROM transactions WHERE to_account_id = 4')->fetch(PDO::FETCH_ASSOC);
    expect($transaction['amount'])->toBe(500.00);
    expect($transaction['description'])->toBe('Initial deposit');
});

it('handles transaction with query errors', function (): void {
    expect(fn() => $this->db->transaction(function ($db) {
        $db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Test Account', 100.00]);
        
        // Попытка выполнить неверный SQL
        $db->select('INVALID SQL QUERY');
        
        return 'success';
    }))->toThrow(QueryException::class);
    
    // Проверяем, что данные не сохранены
    $count = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Test Account"')->fetchColumn();
    expect($count)->toBe(0);
});

it('handles manual transaction management', function (): void {
    // Начинаем транзакцию
    $beginResult = $this->db->beginTransaction();
    expect($beginResult)->toBeTrue();
    
    // Выполняем операции
    $this->db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Manual Account', 100.00]);
    $this->db->update('UPDATE accounts SET balance = ? WHERE name = ?', [200.00, 'Manual Account']);
    
    // Подтверждаем транзакцию
    $commitResult = $this->db->commit();
    expect($commitResult)->toBeTrue();
    
    // Проверяем результат
    $account = $this->connection->query('SELECT * FROM accounts WHERE name = "Manual Account"')->fetch(PDO::FETCH_ASSOC);
    expect($account['balance'])->toBe(200.00);
});

it('handles manual transaction rollback', function (): void {
    // Начинаем транзакцию
    $this->db->beginTransaction();
    
    // Выполняем операции
    $this->db->insert('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Rollback Account', 100.00]);
    
    // Откатываем транзакцию
    $rollbackResult = $this->db->rollback();
    expect($rollbackResult)->toBeTrue();
    
    // Проверяем, что данные не сохранены
    $count = $this->connection->query('SELECT COUNT(*) FROM accounts WHERE name = "Rollback Account"')->fetchColumn();
    expect($count)->toBe(0);
});

it('prevents double commit', function (): void {
    $this->db->beginTransaction();
    $this->db->commit();
    
    // Попытка повторного commit должна вернуть false
    $result = $this->db->commit();
    expect($result)->toBeFalse();
});

it('prevents double rollback', function (): void {
    $this->db->beginTransaction();
    $this->db->rollback();
    
    // Попытка повторного rollback должна вернуть false
    $result = $this->db->rollback();
    expect($result)->toBeFalse();
});

it('handles commit without begin transaction', function (): void {
    // Попытка commit без начала транзакции должна вернуть false
    $result = $this->db->commit();
    expect($result)->toBeFalse();
});

it('handles rollback without begin transaction', function (): void {
    // Попытка rollback без начала транзакции должна вернуть false
    $result = $this->db->rollback();
    expect($result)->toBeFalse();
});
