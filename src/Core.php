<?php
namespace samsonphp\core;

use samsonframework\core\SystemInterface;
use samsonframework\resource\ResourceMap;
use samsonphp\config\Scheme;
use samsonphp\event\Event;

/**
 * Core
 *
 * @package samsonphp/core
 * @author 	Vitaly Iegorov <egorov@samsonos.com>
 */
class Core implements SystemInterface
{
    /** @var string Current system environment */
    protected $environment;

    /**
     * Core constructor
     */
    public function __construct()
    {
        // TODO: Remove as hard dependency - create bridge/etc
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();

        // Fire core creation event
        Event::fire('core.created', array(&$this));

        // Signal core configure event
        Event::signal('core.configure', array($this->system_path . __SAMSON_CONFIG_PATH));
    }

    /**
     * Change current system working environment or receive
     * current system enviroment if no arguments are passed.
     *
     * @param string $environment Environment identifier
     *
     * TODO: Function has two different logics - needs to be changed!
     * @return $this|string Chaining or current system environment
     */
    public function environment($environment = Scheme::BASE)
    {
        if (func_num_args() !== 0) {
            $this->environment = $environment;

            // Signal core environment change
            Event::signal('core.environment.change', array($environment, &$this));
            return $this;
        }

        return $this->environment;
    }

    /**
     * Start SamsonPHP framework.
     *
     * @param string $default Default module identifier
     *
     * @throws ViewPathNotFound
     */
    public function start($default)
    {
        // Fire core started event
        Event::fire('core.started');

        // Security layer
        $securityResult = true;
        // Fire core security event
        Event::fire('core.security', array(&$this, &$securityResult));

        /** @var mixed $result External route controller action result */
        $result = false;

        // If we have passed security application layer
        if ($securityResult) {
            // Fire core routing event - go to routing application layer
            Event::signal('core.routing', array(&$this, &$result, $default));
        }

        // If no one has passed back routing callback
        if (!isset($result) || $result === false) {
            // Fire core e404 - routing failed event
            $result = Event::signal('core.e404', array(url()->module, url()->method));
        }

        // Response
        $output = '';

        // If this is not asynchronous response and controller has been executed
        if ($result !== false) {
            // Fire after render event
            Event::fire('core.rendered', array(&$output));
        }

        // Output results to client
        echo $output;

        // Fire ended event
        Event::fire('core.ended', array(&$output));
    }
}
