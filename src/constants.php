<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 05.03.14 at 12:25
 */
namespace samson\core;

/**  Отметка времени начала работы фреймворка */
define('__SAMSON_T_STARTED__', microtime(TRUE));

/** Constant for storing flag that current PHP version is really old */
define('__SAMSON_PHP_OLD', version_compare(PHP_VERSION, '5.3.0', '<'));

/** If no specific vendor path is defined */
if (!defined('__SAMSON_VENDOR_PATH')) {
    define('__SAMSON_VENDOR_PATH', '../vendor/');
}

/** If not specified development vendor path */
if (!defined('__SAMSON_DEV_VENDOR_PATH')) {
    /**
     * Use standard relative path to add ability of using local module version
     * for their quick development
     */
    define('__SAMSON_DEV_VENDOR_PATH', '../../vendor/');
}

/** SamsonPHP copyright */
if (!defined('__SAMSON_COPYRIGHT')) {
    define('__SAMSON_COPYRIGHT', '<!-- This site is working under SamsonPHP framework http:://samsonphp.com  -->');
}

/** Совместимость с PHP 5 */
if(!defined('__DIR__')) define('__DIR__', dirname(__FILE__));

/** Получим путь к фреймфорку SamsonPHP */
define('__SAMSON_PATH__', __DIR__.'/');

/** Получим текущий каталог веб-приложения */
define('__SAMSON_CWD__', str_ireplace('\\', '/', getcwd().'/' ) );

/** Объявим константу для раздели пути в namespace */
define('__NS_SEPARATOR__', '\\');

// Fix for apache mod_vhost_alias
if (strlen(__SAMSON_CWD__) - strlen($_SERVER['DOCUMENT_ROOT']) > 5) {
    $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
}

/** Получим путь к текущему веб-приложению относительно корня сайта */
if(!defined('__SAMSON_BASE__')) {
    define('__SAMSON_BASE__', str_ireplace( $_SERVER['DOCUMENT_ROOT'], '', __SAMSON_CWD__ ) );
}

/** Flag that this script runs from remote app */
define('__SAMSON_REMOTE_APP', __SAMSON_BASE__ !== '/');

/** Get HTTP protocol **/
define('__SAMSON_PROTOCOL', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://" );

/** Default path to web-application cache folder */
if (!defined('__SAMSON_CACHE_PATH')) {
    define('__SAMSON_CACHE_PATH', 'cache');
}

/** Default path to web-application tests folder */
if (!defined('__SAMSON_TEST_PATH')) {
    define('__SAMSON_TEST_PATH', 'tests');
}

/** Default path to web-application code root */
if (!defined('__SAMSON_APP_PATH')) {
    define('__SAMSON_APP_PATH', 'app');
}

/** Default path to tests folder */
if (!defined('__SAMSON_CONFIG_PATH')) {
    define('__SAMSON_CONFIG_PATH', __SAMSON_APP_PATH.'/config/');
}

/** Default path to web-application functional module controllers root */
if (!defined('__SAMSON_CONTROLLER_PATH')) {
    define('__SAMSON_CONTROLLER_PATH', __SAMSON_APP_PATH.'/controller/');
}

/** Default path to web-application module models root */
if (!defined('__SAMSON_MODEL_PATH')) {
    define('__SAMSON_MODEL_PATH', __SAMSON_APP_PATH.'/model/');
}

/** Default path to web-application module views root */
if (!defined('__SAMSON_VIEW_PATH')) {
    define('__SAMSON_VIEW_PATH', __SAMSON_APP_PATH.'/view/');
}

/** Default path to web-application default template view */
if (!defined('__SAMSON_DEFAULT_TEMPLATE')) {
    define('__SAMSON_DEFAULT_TEMPLATE', __SAMSON_VIEW_PATH.'index.php');
}

/** Максимальное время выполнения скрипта */
define( '__SAMSON_MAX_EXECUTION__', 120 );

/** Действие контроллера выполнено успешно */
define( 'A_SUCCESS', TRUE );

/** Действие контроллера НЕ выполнено */
define( 'A_FAILED', FALSE );

/** Путь к файлу с глобальными данными модуля */
define( '__SAMSON_GLOBAL_FILE', 'global.php' );