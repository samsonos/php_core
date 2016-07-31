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
class Core implements CoreInterface
{
    /** @var string Current system environment */
    protected $environment;

    /** @var Module[] Loaded modules collection */
    protected $modules;

    /**
     * Core constructor
     */
    public function __construct()
    {
        // Fire core creation event
        Event::fire(self::E_CREATED, [&$this]);
    }

    /**
     * Change current system working environment.
     *
     * @param string $environment Environment identifier
     *
     * @return $this Chaining
     */
    public function environment($environment = Scheme::BASE)
    {
        $this->environment = $environment;

        // Signal core environment change
        Event::fire(self::E_ENVIRONMENT, [&$this, $environment]);

        return $this;
    }

    /**
     * Load module.
     *
     * @param ModuleInterface $instance Module ancestor for loading
     *
     * @return $this Chaining
     */
    public function load($instance, $alias = null)
    {
        // Store module instance by alias or class name
        $this->modules[$alias ?: get_class($instance)] = $instance;

        return $this;
    }
}
