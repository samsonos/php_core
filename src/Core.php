<?php
namespace samsonphp\core;

use samsonframework\core\SystemInterface;
use samsonphp\config\Scheme;
use samsonphp\core\exception\CannotLoadModule;
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
    protected $modules = [];

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
        // Fire core environment change
        Event::fire(self::E_ENVIRONMENT, [&$this, &$environment]);

        $this->environment = $environment;

        return $this;
    }

    /**
     * Load module.
     *
     * @param ModuleInterface $instance Module ancestor for loading
     *
     * @param string|null $alias Module alias
     *
     * @return $this Chaining
     *
     * @throws CannotLoadModule
     */
    public function load($instance, $alias = null)
    {
        // If no alias is passed - use fully qualified class name
        $alias = $alias ?: get_class($instance);

        // Check for duplicating aliases
        if (array_key_exists($alias, $this->modules)) {
            throw new CannotLoadModule($alias.' - alias already in use');
        }

        // Fire core before module loading
        Event::fire(self::E_BEFORE_LOADED, [&$this, &$instance, &$alias]);

        // Store module instance by alias or class name
        $this->modules[$alias] = $instance;

        // Fire core before module loading
        Event::fire(self::E_AFTER_LOADED, [&$this, &$instance, &$alias]);

        return $this;
    }

    public function handle()
    {

    }
}
