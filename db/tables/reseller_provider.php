<?php

return [
    'create' => <<<SQL
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        provider_code VARCHAR(50) NOT NULL UNIQUE,
        provider_name VARCHAR(100) NOT NULL,
        api_key VARCHAR(255) NOT NULL,
        api_secret VARCHAR(255) NOT NULL,
        api_base_url VARCHAR(255) NOT NULL DEFAULT 'https://tr.doumanshop.cv/api/v1',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        custom_volume_enabled TINYINT(1) NOT NULL DEFAULT 0,
        min_gb INT NOT NULL DEFAULT 1,
        max_gb INT NOT NULL DEFAULT 100,
        price_per_gb BIGINT NOT NULL DEFAULT 0,
        cached_unlimited_quota INT NOT NULL DEFAULT 0,
        cached_volume_quota FLOAT NOT NULL DEFAULT 0,
        last_sync INT NOT NULL DEFAULT 0
        SQL,
    'columns' => [
        ['provider_code', 'sanayi', "VARCHAR(50) NOT NULL"],
        ['provider_name', 'Sanayi', "VARCHAR(100) NOT NULL"],
        ['api_key', '', "VARCHAR(255) NOT NULL"],
        ['api_secret', '', "VARCHAR(255) NOT NULL"],
        ['api_base_url', 'https://tr.doumanshop.cv/api/v1', "VARCHAR(255) NOT NULL"],
        ['status', 'active', "VARCHAR(20) NOT NULL DEFAULT 'active'"],
        ['custom_volume_enabled', '0', "TINYINT(1) NOT NULL DEFAULT 0"],
        ['min_gb', '1', "INT NOT NULL DEFAULT 1"],
        ['max_gb', '100', "INT NOT NULL DEFAULT 100"],
        ['price_per_gb', '0', "BIGINT NOT NULL DEFAULT 0"],
        ['cached_unlimited_quota', '0', "INT NOT NULL DEFAULT 0"],
        ['cached_volume_quota', '0', "FLOAT NOT NULL DEFAULT 0"],
        ['last_sync', '0', "INT NOT NULL DEFAULT 0"],
    ],
];
