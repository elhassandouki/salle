
<?php

return [
    'default' => [
        'ip' => env('ZKTECO_IP', '192.168.1.201'),
        'port' => env('ZKTECO_PORT', 4370),
        'timeout' => env('ZKTECO_TIMEOUT', 5),
        'model' => env('ZKTECO_MODEL', 'F18'),
    ],
    
    'sync' => [
        'auto' => env('ZKTECO_AUTO_SYNC', true),
        'interval' => env('ZKTECO_SYNC_INTERVAL', 60),
        'new_users' => env('ZKTECO_SYNC_NEW_USERS', true),
        'delete_expired' => env('ZKTECO_DELETE_EXPIRED', true),
    ],
];