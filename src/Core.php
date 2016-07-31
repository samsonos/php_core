<?php
namespace samsonphp\core;

use samsonframework\core\SystemInterface;
use samsonphp\config\Scheme;
use samsonphp\event\Event;

/**
 * Core
 *
 * @package samsonphp/core
 * @author Vitaly Iegorov <egorov@samsonos.com>
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
        // Fire core creation event
        Event::fire('core.created', array(&$this));
    }

    /**
     * Change current system working environment.
     *
     * @param string $environment Environment identifier
     */
    public function environment($environment = Scheme::BASE)
    {
        $this->environment = $environment;

        // Signal core environment change
        Event::signal('core.environment.change', array($environment, &$this));
    }

    /**
     * Start SamsonPHP framework.
     *
     * @param string $default Default module identifier
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
        if ($result === false) {
            // TODO: Needs to change
            // Fire core e404 - routing failed event
            $result = Event::signal('core.e404');
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
