<?php

return [
    'name' => 'web_login_codes',
    'columns' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'user_id' => 'VARCHAR(50) NOT NULL',
        'code' => 'VARCHAR(10) NOT NULL',
        'auth_key' => 'VARCHAR(64) NOT NULL',
        'created_at' => 'INT NOT NULL',
        'is_used' => 'TINYINT DEFAULT 0',
    ],
    'indexes' => [
        'idx_user' => ['user_id'],
        'idx_code' => ['code'],
        'idx_auth_key' => ['auth_key'],
    ],
];
