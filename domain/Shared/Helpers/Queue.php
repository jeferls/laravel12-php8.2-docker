<?php

namespace Domain\Shared\Helpers;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use ReflectionException;
use ReflectionMethod;

class Queue
{
    /**
     * Undocumented function
     *
     * @return void
     */
    public static function processMessage(AMQPMessage $message, int $delay = 0, string $workerID = '')
    {
        $messageBody = unserialize($message->getBody());
        RequestID::setID($messageBody['__RID']);
        $class = $messageBody['__class'];
        $function = $messageBody['__function'];
        $args = $messageBody['__args'];
        $publishedAt = $messageBody['__publishedAt'] ?? null;
        $messageID = $messageBody['__messageID'] ?? null;

        Log::debug(__CLASS__ . '.' . __FUNCTION__, [
            '__class' => $class,
            '__function' => $function,
            '__args' => $args,
            '__publishedAt' => $publishedAt,
            '__messageID' => $messageID,
            'workerID' => $workerID,
            'message_properties' => $message->get_properties(),
        ]);

        if ($publishedAt && $delay) {
            $processAt = $publishedAt->addSeconds($delay);
            $now = Carbon::now();

            if ($processAt->isAfter($now)) {
                Log::debug('Queue.processMessage -> Delaying Message', [
                    'processAt' => $processAt,
                    'publishedAt' => $publishedAt,
                    'now' => $now,
                ]);

                return '__delay__';
            }
        }

        try {
            $reflection = new ReflectionMethod($class, $function);
        } catch (ReflectionException $re) {
            Log::warning(
                __CLASS__ . '.' . __FUNCTION__ . ": \"{$re->getMessage()}\"",
                [
                    'messageBody' => $messageBody,
                    'class' => $class,
                    'function' => $function,
                ]
            );

            return false;
        }

        if ($reflection->isStatic()) {
            return $reflection->invoke(null, ...$args);
        }

        return $reflection->invoke(new $class, ...$args);
    }

    public static function fakeFanout(array $queues, string $function, ...$args): array
    {
        Log::debug(__CLASS__ . '.' . __FUNCTION__, ['queues' => $queues, 'function' => $function, 'args' => $args]);
        $res = [];
        foreach ($queues as $queue => $class) {
            Log::debug(__CLASS__ . '.' . __FUNCTION__ . ' -> publishing', ['queue' => $queue, 'class' => $class, 'function' => $function, 'args' => $args]);

            $res[$queue] = self::publish($queue, $class, $function, ...$args);
        }

        return $res;
    }

    public static function publish(string $queue, string $class, string $function, ...$args): bool
    {

        if (SupressQueue::has($queue)) {
            Log::debug('Queue.publish', ['queue_supress' => $queue]);

            return false;
        }

        $publishedAt = Carbon::now();
        $messageID = Str::uuid();

        Log::debug(__CLASS__ . '.' . __FUNCTION__, [
            '__class' => $class,
            '__function' => $function,
            '__args' => $args,
            '__publishedAt' => $publishedAt,
            '__messageID' => $messageID,
            'queue' => $queue,
        ]);
        $message = self::parseMessage([
            '__RID' => RequestID::getID(),
            '__class' => $class,
            '__function' => $function,
            '__args' => $args,
            '__publishedAt' => $publishedAt,
            '__messageID' => $messageID,
        ]);
        if (strtoupper(config('queue.mode')) !== 'ASYNC') {
            self::processMessage($message);

            return true;
        }
        self::directPublish($queue, $message);

        return true;
    }

    public static function directPublish(string $queue, AMQPMessage $message): bool
    {
        if (! self::boot() || ! self::$channel) {
            return false;
        }
        self::declareQueue($queue);
        self::$channel->basic_publish($message, '', $queue);
        self::$dirty = true;
        self::$dirtyCount++;

        return true;
    }

    public static function consume(string $queue, Closure $closure)
    {
        if (! self::boot() || ! self::$channel) {
            return;
        }
        self::declareQueue($queue);
        self::$channel->basic_consume($queue, '', false, false, false, false, function ($message) use ($closure) {
            $closure($message);
        });
        while (self::$channel->is_consuming()) {
            self::$channel->wait();
        }
    }

    public static function commit()
    {
        if (! self::boot()) {
            return;
        }
        if (self::$dirty && self::$channel) {
            self::$channel->publish_batch();
            self::$dirty = false;
            self::$dirtyCount = 0;
        }
    }

    private static function parseMessage(array $message): AMQPMessage
    {
        return new AMQPMessage(serialize($message), [
            'content-type' => 'application/php-serialized',
            'delivery_mode' => 2,
        ]);
    }

    private static function declareQueue(string $queue): void
    {
        if (self::$currentQueue === $queue) {
            return;
        }
        $returnedDeclare = self::$channel->queue_declare($queue, false, true, false, false);
        self::$consumerCounts[$returnedDeclare[0] ?? ''] = $returnedDeclare[2] ?? 0;
        self::$currentQueue = $queue;
    }

    public static function getConsumerCount(string $queue): int
    {
        if (! self::boot() || ! self::$channel) {
            return 0;
        }
        self::declareQueue($queue);

        return self::$consumerCounts[$queue] ?? 0;
    }

    public static function setQos(int $prefetchSize, int $prefetchCount, bool $aGlobal)
    {
        if (! self::boot() || ! self::$channel) {
            return;
        }
        self::$channel->basic_qos($prefetchSize, $prefetchCount, $aGlobal);
    }

    private static function openChannel(): void
    {
        if (! self::$connection) {
            throw new \RuntimeException('No connection available');
        }
        self::$channel = self::$connection->channel();
    }

    private static function connect(): void
    {
        if (strtoupper(env('AMQP_MODE')) === 'SSL') {
            self::$connection = new AMQPSSLConnection(
                env('AMQP_HOST'),
                env('AMQP_PORT'),
                env('AMQP_USERNAME'),
                env('AMQP_PASSWORD'),
                '/',
                ['verify_peer' => false]
            );
        } else {
            self::$connection = new AMQPStreamConnection(
                env('AMQP_HOST'),
                env('AMQP_PORT'),
                env('AMQP_USERNAME'),
                env('AMQP_PASSWORD'),
                '/',
                false,
                'AMQPLAIN',
                null,
                'en_US',
                10,
                10,
                null,
                true,
                0,
                0,
                null
            );
        }
    }

    private static function boot(): bool
    {
        if (self::$isBooted) {
            return true;
        }

        $retryDelaySeconds = 1;
        $maxDelaySeconds = 30;

        while (true) {
            try {
                self::connect();
                self::openChannel();
                self::$isBooted = true;
                Log::info('Queue -> conexão estabelecida com sucesso com o RabbitMQ');
                
                return true;
            } catch (\Throwable $th) {
                Log::error('Queue -> failed to boot, retrying...', [
                    'code' => $th->getCode(),
                    'message' => $th->getMessage(),
                    'retry_in_seconds' => $retryDelaySeconds,
                ]);

                self::closeConnection();

                sleep($retryDelaySeconds);

                // Exponential backoff with cap
                $retryDelaySeconds = min($retryDelaySeconds * 2, $maxDelaySeconds);
            }
        }
    }

    private static function closeChannel()
    {
        try {
            if (self::$channel) {
                self::$channel->close();
                self::$channel = null;
            }
        } catch (\Throwable $th) {
            Log::error('Queue -> failed to close channel');
        }
    }

    private static function closeConnection()
    {
        try {
            if (self::$connection) {
                self::$connection->close();
                self::$connection = null;
            }
        } catch (\Throwable $th) {
            Log::error('Queue -> failed to close connection');
        }
    }

    public static function shutdown()
    {
        if (! self::$isBooted) {
            return;
        }
        self::commit();
        self::closeChannel();
        self::closeConnection();
        self::$isBooted = false;
    }

    private static $queue;

    private static $connection;

    private static $channel;

    private static $currentQueue;

    private static $isBooted = false;

    private static $dirty = false;

    private static $dirtyCount = 0;

    private static $consumerCounts = [];
}
