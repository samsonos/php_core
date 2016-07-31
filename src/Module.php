<?php
namespace samsonphp\core;

/**
 * Module
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
abstract class Module
{
    /**
     * Module configuration stage
     * @param ContainerInterface $container
     */
    abstract public function configure(ContainerInterface $container);

    /**
     * Module preparation stage
     */
    public function prepare()
    {

    }

    /**
     * Module initialization stage
     */
    public function init()
    {

    }
}
