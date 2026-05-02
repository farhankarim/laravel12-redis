<?php

return [
    'enabled' => (bool) env('ELASTICSEARCH_ENABLED', true),
    'hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ELASTICSEARCH_HOSTS', 'http://127.0.0.1:9200'))
    ))),
    'users_index' => env('ELASTICSEARCH_USERS_INDEX', 'users'),
];
