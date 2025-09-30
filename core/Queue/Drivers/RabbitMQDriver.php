<?php declare(strict_types=1);

namespace Core\Queue\Drivers;

use Core\Queue\QueueInterface;
use Core\Queue\Job;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Драйвер для работы с RabbitMQ
 * 
 * Требует установки: composer require php-amqplib/php-amqplib
 */
class RabbitMQDriver implements QueueInterface
{
    protected ?AMQPStreamConnection $connection = null;
    protected ?AMQPChannel $channel = null;
    protected string $host;
    protected int $port;
    protected string $user;
    protected string $password;
    protected string $vhost;
    protected array $currentJobs = [];

    public function __construct(
        string $host = 'localhost',
        int $port = 5672,
        string $user = 'guest',
        string $password = 'guest',
        string $vhost = '/'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->vhost = $vhost;
    }

    /**
     * Устанавливает соединение с RabbitMQ
     */
    protected function connect(): void
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            try {
                $this->connection = new AMQPStreamConnection(
                    $this->host,
                    $this->port,
                    $this->user,
                    $this->password,
                    $this->vhost
                );
                $this->channel = $this->connection->channel();
            } catch (\Exception $e) {
                throw new \RuntimeException(
                    "Failed to connect to RabbitMQ: " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
    }

    /**
     * Объявляет очередь в RabbitMQ
     */
    protected function declareQueue(string $queue): void
    {
        $this->connect();
        
        // Объявляем очередь как durable (сохраняется при перезапуске)
        $this->channel->queue_declare(
            $queue,           // queue name
            false,            // passive
            true,             // durable
            false,            // exclusive
            false             // auto_delete
        );
    }

    public function push(Job $job, string $queue = 'default'): string
    {
        $this->declareQueue($queue);
        
        $id = uniqid('rabbitmq_', true);
        $job->setId($id);
        
        $payload = $job->serialize();
        
        $message = new AMQPMessage($payload, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $id,
        ]);
        
        $this->channel->basic_publish($message, '', $queue);
        
        return $id;
    }

    public function pop(string $queue = 'default'): ?Job
    {
        $this->declareQueue($queue);
        
        $message = $this->channel->basic_get($queue);
        
        if ($message === null) {
            return null;
        }
        
        try {
            $job = Job::unserialize($message->body);
            $job->setId($message->getMessageId() ?? uniqid('rabbitmq_', true));
            $job->incrementAttempts();
            
            // Сохраняем ссылку на сообщение для acknowledge
            $this->currentJobs[$job->getId()] = $message;
            
            return $job;
        } catch (\Exception $e) {
            // Если не удалось десериализовать, удаляем сообщение
            $this->channel->basic_ack($message->getDeliveryTag());
            throw $e;
        }
    }

    public function acknowledge(Job $job): void
    {
        $message = $this->currentJobs[$job->getId()] ?? null;
        
        if ($message) {
            $this->channel->basic_ack($message->getDeliveryTag());
            unset($this->currentJobs[$job->getId()]);
        }
    }

    public function release(Job $job, int $delay = 0): void
    {
        $message = $this->currentJobs[$job->getId()] ?? null;
        
        if ($message) {
            // Отклоняем сообщение и возвращаем в очередь
            $this->channel->basic_nack($message->getDeliveryTag(), false, true);
            unset($this->currentJobs[$job->getId()]);
        }
        
        // Примечание: RabbitMQ не поддерживает delay из коробки
        // Для delay нужно использовать плагин delayed-message-exchange
    }

    public function delete(Job $job): void
    {
        $this->acknowledge($job);
    }

    public function size(string $queue = 'default'): int
    {
        $this->declareQueue($queue);
        
        // Получаем информацию об очереди
        [$queueName, $messageCount, $consumerCount] = $this->channel->queue_declare(
            $queue,
            true  // passive mode - только получить информацию
        );
        
        return $messageCount;
    }

    public function clear(string $queue = 'default'): void
    {
        $this->connect();
        $this->channel->queue_purge($queue);
    }

    /**
     * Закрывает соединение
     */
    public function __destruct()
    {
        try {
            if ($this->channel) {
                $this->channel->close();
            }
            if ($this->connection) {
                $this->connection->close();
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки при закрытии
        }
    }
}
