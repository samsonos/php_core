<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 31.07.16 at 23:41
 */
namespace samsonphp\core;

/**
 * SamsonPHP module interface
 * @package samsonphp\core
 */
interface ModuleInterface
{
    /**
     * Module preparation stage
     */
    public function prepare();

    /**
     * Module initialization stage
     */
    public function init();
}
