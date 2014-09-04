<?php
/**
 * SamsonPHP init script
 * @package SamsonPHP
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 0.1.0
 */

// Константы фреймворка SamsonPHP
require('constants.php');

// Set default timezone
date_default_timezone_set(date_default_timezone_get());

// Set default execution time limit
set_time_limit( __SAMSON_MAX_EXECUTION__ );
ini_set('max_execution_time', __SAMSON_MAX_EXECUTION__);

// Start session
session_start();

// Remove unnecessary files umask
$old_umask = umask(0);

// Include SamsonPHP auto loader
require('AutoLoader.php');

// Load files with functions
require('shortcuts.php');
require('Utils2.php');
require('View.php');
require('deprecated.php');
