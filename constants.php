<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 05.03.14 at 12:25
 */
namespace samson\core;

/**  Отметка времени начала работы фреймворка */
define('__SAMSON_T_STARTED__', microtime(TRUE));

/** Установим версию фреймворка */
define('__SAMSON_VERSION__', '5.1.1');

/** Совместимость с PHP 5 */
if(!defined('__DIR__')) define( '__DIR__', dirname(__FILE__));

/** Получим путь к фреймфорку SamsonPHP */
define( '__SAMSON_PATH__', __DIR__.'/' );

/** Получим текущий каталог веб-приложения */
define('__SAMSON_CWD__', str_ireplace('\\', '/', getcwd().'/' ) );

/** Получим путь к текущему веб-приложению относительно корня сайта */
define('__SAMSON_BASE__', str_ireplace( $_SERVER['DOCUMENT_ROOT'], '', __SAMSON_CWD__ ) );

/** Объявим константу для раздели пути в namespace */
define('__NS_SEPARATOR__', '\\');

/** Flag that this script runs from remote app */
define( '__SAMSON_REMOTE_APP', __SAMSON_CWD__ !== $_SERVER['DOCUMENT_ROOT'].'/' );

/** Default path to cache folder */
define('__SAMSON_CACHE_PATH','cache');

/** Default path to tests folder */
define('__SAMSON_TEST_PATH','tests');

/** Путь к файлу с глобальными данными модуля */
define( '__SAMSON_GLOBAL_FILE', 'global.php' );

/** Путь к папке где находятся файлы системы */
define('__SAMSON_APP_PATH','app');

/** Путь к папке где находятся контроллеры системы */
define('__SAMSON_CONTOROLLER_PATH', __SAMSON_APP_PATH.'/controller/');

/** Путь к папке где находятся модели системы */
define('__SAMSON_MODEL_PATH', __SAMSON_APP_PATH.'/model/');

/**  Путь к папке где находятся представления системы */
define('__SAMSON_VIEW_PATH', __SAMSON_APP_PATH.'/view/');

/** Путь к файлу с главным шаблоном системы */
define('__SAMSON_DEFAULT_TEMPLATE', __SAMSON_VIEW_PATH.'index.php' );

/** Максимальное время выполнения скрипта */
define( '__SAMSON_MAX_EXECUTION__', 60 );

/** Действие контроллера выполнено успешно */
define( 'A_SUCCESS', TRUE );

/** Действие контроллера НЕ выполнено */
define( 'A_FAILED', FALSE );