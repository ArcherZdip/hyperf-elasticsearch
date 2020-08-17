<?php
declare(strict_types=1);

return [
    'driver'      => 'default',
    // 是否软删除
    'soft_delete' => false,

    'prefix' => '',

    'default' => [
        'client'           => [
            'hosts' => [
                env('SCOUT_HYPERF_ELASTIC_HOST', '127.0.0.1:9200'),
            ],
        ],
        // 最大连接数
        'max_connections'  => 500,
        // 批量处理的块数量
        'chunk'            => [
            'searchable'   => 500,
            'unsearchable' => 500,
        ],
        // 是否自动更新 mapping
        'update_mapping'   => env('SCOUT_HYPERF_ELASTIC_UPDATE_MAPPING', true),
        // 索引方式
        'indexer'          => env('SCOUT_HYPERF_ELASTIC_INDEXER', 'single'),
        // 可用选：false (默认)、true 、wait_for
        'document_refresh' => env('SCOUT_HYPERF_ELASTIC_DOCUMENT_REFRESH', false),
    ],
];