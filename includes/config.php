<?php
function config_value(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

define('DB_HOST', config_value('TAOSARTS_DB_HOST', 'localhost'));
define('DB_NAME', config_value('TAOSARTS_DB_NAME', 'taosarts'));
define('DB_USER', config_value('TAOSARTS_DB_USER', 'taosarts_user'));
define('DB_PASS', config_value('TAOSARTS_DB_PASS', 'change_this_password'));
define('SITE_NAME', config_value('TAOSARTS_SITE_NAME', 'taosarts.org'));
define('BASE_URL', rtrim(config_value('TAOSARTS_BASE_URL', 'https://taosarts.org'), '/'));
