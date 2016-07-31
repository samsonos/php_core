<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 31.07.16 at 23:39
 */
namespace samsonphp\core\tests;

use samsonphp\core\ContainerInterface;
use samsonphp\core\Module;

class TestModule extends Module
{
    /**
     * Module configuration stage
     *
     * @param ContainerInterface $container
     */
    public function configure(ContainerInterface $container)
    {
        // TODO: Implement configure() method.
    }
}
