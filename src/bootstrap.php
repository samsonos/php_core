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
    // Set default timezone
    date_default_timezone_set(date_default_timezone_get());

    // Start session
    session_start();
}
