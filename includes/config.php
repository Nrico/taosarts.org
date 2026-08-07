<?php
function config_value(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

defined('DB_HOST') || define('DB_HOST', config_value('TAOSARTS_DB_HOST', 'localhost'));
defined('DB_PORT') || define('DB_PORT', config_value('TAOSARTS_DB_PORT', '3306'));
defined('DB_SOCKET') || define('DB_SOCKET', config_value('TAOSARTS_DB_SOCKET', ''));
defined('DB_NAME') || define('DB_NAME', config_value('TAOSARTS_DB_NAME', 'taosarts'));
defined('DB_USER') || define('DB_USER', config_value('TAOSARTS_DB_USER', 'taosarts_user'));
defined('DB_PASS') || define('DB_PASS', config_value('TAOSARTS_DB_PASS', 'change_this_password'));
defined('SITE_NAME') || define('SITE_NAME', config_value('TAOSARTS_SITE_NAME', 'taosarts.org'));
defined('BASE_URL') || define('BASE_URL', rtrim(config_value('TAOSARTS_BASE_URL', 'https://taosarts.org'), '/'));
