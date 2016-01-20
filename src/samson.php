<?php
/**
 * SamsonPHP init script
 * @package SamsonPHP
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 0.1.0
 */

/**
 * Do not start SamsonPHP framework if this is script is ran from CLI,
 * because this is usually tests and system commands runs
 */
if (php_sapi_name() !== 'cli') {
    // Константы фреймворка SamsonPHP
    require('constants.php');

    // Set default timezone
    date_default_timezone_set(date_default_timezone_get());

    // Set default execution time limit
    set_time_limit(__SAMSON_MAX_EXECUTION__);
    ini_set('max_execution_time', __SAMSON_MAX_EXECUTION__);

    // Start session
    session_start();

    // Remove unnecessary files umask
    $old_umask = umask(0);

    require('AutoLoader.php');
    require('Utils2.php');
    require('shortcuts.php');
    require('View.php');

    // deprecated
    new \samson\core\Error();
}
