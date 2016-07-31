<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 31.07.16 at 17:32
 */
namespace samsonphp\core;

/**
 * SamsonPHP core interface.
 * @package samsonphp\core
 */
interface CoreInterface
{
    /** Event identifier for core creation */
    const E_CREATED = 10000;
    /** Event identifier before core module loading */
    const E_BEFORE_LOADED = 10001;
    /** Event identifier after core module loading */
    const E_AFTER_LOADED = 10002;
    /** Event identifier after core module loading */
    const E_ENVIRONMENT = 100003;
}
