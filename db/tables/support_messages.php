<?php

return [
    'name' => 'support_messages',
    'columns' => [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'user_id' => 'VARCHAR(50) NOT NULL',
        'sender' => "ENUM('user', 'admin') NOT NULL",
        'admin_id' => 'VARCHAR(50) DEFAULT NULL',
        'message' => 'TEXT NOT NULL',
        'media_url' => 'VARCHAR(500) DEFAULT NULL',
        'created_at' => 'INT NOT NULL',
        'is_read' => 'TINYINT DEFAULT 0',
    ],
    'indexes' => [
        'idx_user' => ['user_id'],
        'idx_created' => ['created_at'],
        'idx_is_read' => ['is_read'],
    ],
];
