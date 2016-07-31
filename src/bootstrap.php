<?php
/**
 * SamsonPHP bootstrap script
 * @package samsonphp/core
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */

/**
 * Do not start SamsonPHP framework if this is script is ran from CLI,
 * because this is usually tests and system commands runs
 */
if (php_sapi_name() !== 'cli') {
    // Set default timezone
    date_default_timezone_set(date_default_timezone_get());

    // Start session
    session_start();
}
