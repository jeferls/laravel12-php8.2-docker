<?php

namespace Domain\Shared\Helpers;

class SupressQueue
{
    public static function setQueue(string $queue): void
    {
        if (! in_array($queue, self::$queues)) {
            array_push(self::$queues, $queue);
        }
    }

    public static function hasAny(array $queues): bool
    {
        foreach ($queues as $queue) {
            if (self::has($queue)) {
                return true;
            }
        }

        return false;
    }

    public static function has(string $queue): bool
    {
        return in_array($queue, self::getQueues(), true);
    }

    public static function getQueues(): array
    {
        return self::$queues;
    }

    public static function reset(): void
    {
        self::$queues = [];
    }

    protected static array $queues = [];
}
