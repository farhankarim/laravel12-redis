<?php

return [
    'company_prefix' => env('CHEQUEBOOK_COMPANY_PREFIX', 'qr'),
    'batch_size' => (int) env('CHEQUEBOOK_BATCH_SIZE', 500),
    'max_batch_size' => (int) env('CHEQUEBOOK_MAX_BATCH_SIZE', 50000),
    'queue' => env('CHEQUEBOOK_QUEUE', 'chequebook-imports'),
    'aes_key' => env('CHEQUEBOOK_AES_KEY', env('APP_KEY', '')),
];
