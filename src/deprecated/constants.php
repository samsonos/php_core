<?php
/** @deprecated Действие контроллера выполнено успешно */
define( 'A_SUCCESS', TRUE );

/** @deprecated Действие контроллера НЕ выполнено */
define( 'A_FAILED', FALSE );

/** @deprecated Путь к файлу с глобальными данными модуля */
define( '__SAMSON_GLOBAL_FILE', 'global.php' );

/** @deprecated Flag that this script runs from remote app */
define('__SAMSON_REMOTE_APP', __SAMSON_BASE__ !== '/');

/** @deprecated Define path to SamsonPHP framework */
define('__SAMSON_PATH__', __DIR__.'/../');

/** @deprecated Default path to install/update bash scripts folder */
if (!defined('__SAMSON_CONTRIB_PATH')) {
    define('__SAMSON_CONTRIB_PATH', 'contrib/');
}