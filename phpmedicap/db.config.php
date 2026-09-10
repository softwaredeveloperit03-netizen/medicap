<?php
/**
 * Database and deploy settings — local XAMPP vs aurenyxgmp.com (Medicap plant 1126).
 * Upload this file with db.php and db1.php before going live.
 */
$dbProfiles = array(
    'local' => array(
        'servername' => 'localhost',
        'username'   => 'root',
        'password'   => '',
        'dbname'     => 'paperlessgmp_cyclone',
        'company_name' => 'GMP Software Pvt Ltd',
        'address'      => 'Location: Pune',
    ),
    'server' => array(
        'servername' => 'localhost',
        // aurenyxgmp.com cPanel account (see statusDB.php on same host)
        'username'   => 'aurenyxgmp_admin',
        'password'   => 'Cppl@1979',
        // cPanel full DB name = aurenyxgmp_ + name shown in phpMyAdmin
        'dbname'     => 'aurenyxgmp_medicap',
        'company_name' => 'Medicap Laboratories',
        'address'      => '30 Worcester Rd, Etobicoke, ON M9W 5X2, Canada',
        'public_base_url' => 'https://aurenyxgmp.com/php/phpdevlop/phpmedicap',
        'logos_base_url'  => 'https://aurenyxgmp.com/php/phpdevlop/gmptotal/logos',
    ),
);

/** plant_id => database name on production server */
$dbPlantMap = array(
    '1126' => 'aurenyxgmp_medicap',
);

function cyclone_detect_db_profile() {
    $profileFile = __DIR__ . '/medicap.db.profile';
    if (is_readable($profileFile)) {
        $forced = strtolower(trim((string) file_get_contents($profileFile)));
        if ($forced === 'server' || $forced === 'local') {
            return $forced;
        }
    }

    $host = isset($_SERVER['SERVER_NAME']) ? strtolower($_SERVER['SERVER_NAME']) : 'localhost';

    if (isset($_GET['db_mode']) && $_GET['db_mode'] === 'server') {
        return 'server';
    }
    if (isset($_GET['db_mode']) && $_GET['db_mode'] === 'local') {
        return 'local';
    }

    if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, '192.168.') === 0) {
        return 'local';
    }

    if ($host === 'aurenyxgmp.com' || strpos($host, 'aurenyxgmp.com') !== false) {
        return 'server';
    }

    return 'server';
}

function cyclone_get_db_config() {
    global $dbProfiles;
    $profile = cyclone_detect_db_profile();
    if (!isset($dbProfiles[$profile])) {
        $profile = 'local';
    }
    $cfg = $dbProfiles[$profile];
    $cfg['profile'] = $profile;
    return $cfg;
}

function cyclone_resolve_dbname($plantId) {
    global $dbPlantMap;
    $profile = cyclone_detect_db_profile();
    $pid = trim((string) $plantId);

    if ($profile === 'server' && $pid !== '' && isset($dbPlantMap[$pid])) {
        return $dbPlantMap[$pid];
    }

    $cfg = cyclone_get_db_config();
    return $cfg['dbname'];
}

function cyclone_get_app_settings() {
    $cfg = cyclone_get_db_config();

    if ($cfg['profile'] === 'server') {
        $logosBase = isset($cfg['logos_base_url']) ? rtrim($cfg['logos_base_url'], '/') : '';
        return array(
            'company_name' => $cfg['company_name'],
            'address'      => $cfg['address'],
            'logo_url'     => $logosBase !== '' ? $logosBase . '/gmp.png' : '',
            'logos_base_url' => $logosBase,
            'public_base_url' => isset($cfg['public_base_url']) ? rtrim($cfg['public_base_url'], '/') : '',
        );
    }

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    return array(
        'company_name' => $cfg['company_name'],
        'address'      => $cfg['address'],
        'logo_url'     => $scheme . '://' . $host . '/gmptotal/upload/user/gmp.png',
        'logos_base_url' => $scheme . '://' . $host . '/gmptotal/logos',
        'public_base_url' => $scheme . '://' . $host . '/cyclone/backend/php/phpDevelopCyclone',
    );
}
