<?php

return [
    'db_query' => [
        'enabled' => env('LOG_SQL_ENABLE', true),
        'slow' => 2000,
        'filter' => [
            '/t/',
            '/telescope/',
            '/h/',
            '/horizon/',
        ],
    ],

    'request' => [
        'enabled' => env('LOG_REQUEST_ENABLE', true),
        'filter' => [
            '/t/',
            '/telescope/',
            '/h/',
            '/horizon/',
        ]
    ],

    'tofile' => env('LOG_TO_FILE', true),
    'toes' => env('LOG_TO_ES', false),

    'queue_name' => env('LOG_QUEUE_NAME', 'elog'),

    'channels' => [
        'e_stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'es'],
            'ignore_exceptions' => false,
        ],

        'e_db_query' => [
            'driver' => 'stack',
            'channels' => ['db_query', 'es'],
            'ignore_exceptions' => false,
        ],

        'e_request' => [
            'driver' => 'stack',
            'channels' => ['request', 'es'],
            'ignore_exceptions' => false,
        ],

        'db_query' => [
            'driver' => 'daily',
            'path' => storage_path('logs/db_query.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'request' => [
            'driver' => 'daily',
            'path' => storage_path('logs/request.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        //elasticsearch
        'es' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => \Monolog\Handler\ElasticsearchHandler::class,
            'formatter' => \Xiaozhu\ELog\EsFormat::class,
            'formatter_with' => [
                'index' => 'logs',
                'type' => '_doc',
            ],
            'handler_with' => [
                'client' => \Elasticsearch\ClientBuilder::create()
                    ->setHosts([
                        env('LOG_ES_URL', 'http://elasticsearch') . ':' . env('LOG_ES_PORT', 9200)
                    ])
                    ->build(),
            ],
        ],

    ]

];
