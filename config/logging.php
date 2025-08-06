<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [
    'default' => env('LOG_CHANNEL', 'stack'),

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'datadog' => [
            'driver' => 'monolog',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('DATADOG_HOST', 'udp://localhost'),
                'port' => env('DATADOG_PORT', 10518),
            ],
        ],
    ],
];
