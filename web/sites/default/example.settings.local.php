<?php

assert_options(ASSERT_ACTIVE, TRUE);
\Drupal\Component\Assertion\Handle::register();
if (!empty($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER'])) {
    $databases['default']['default'] = [
        'database' => 'ttv',
        'username' => 'root',
        'password' => '123456',
        'prefix' => '',
        'host' => '0.0.0.0',
        'port' => '8122',
        'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
        'driver' => 'mysql',
    ];
}
else {
    $databases['default']['default'] = [
        'database' => 'ttv',
        'username' => 'root',
        'password' => '123456',
        'prefix' => '',
        'host' => 'ttv_db',
        'port' => '3306',
        'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
        'driver' => 'mysql',
        'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
    ];
}
$settings['config_sync_directory'] = '../config/sync';
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/local.development.services.yml';
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$config['system.logging']['error_level'] = 'verbose';
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = 'sites/private/files';
ini_set('opcache.enable', '0');
//ini_set('post_max_size', '256M');
//ini_set('upload_max_filesize', '155M');
$settings['file_temp_path'] = '/tmp';
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
$settings['rebuild_access'] = TRUE;
ini_set('post_max_size', '256M');
ini_set('upload_max_filesize', '155M');