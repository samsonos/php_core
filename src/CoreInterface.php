<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 31.07.16 at 17:32
 */
namespace samsonphp\core;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use samsonphp\config\Scheme;
use samsonphp\core\exception\CannotLoadModule;

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

    /**
     * Change current system working environment.
     *
     * @param string $environment Environment identifier
     *
     * @return $this Chaining
     */
    public function environment($environment = Scheme::BASE);

    /**
     * Load module.
     *
     * @param ModuleInterface $instance Module ancestor for loading
     *
     * @param string|null $alias Module alias
     *
     * @return $this Chaining
     *
     * @throws CannotLoadModule On alias duplication
     */
    public function load($instance, $alias = null);

    /**
     * Process request.
     * Method supports PSR middleware approach.
     *
     * @param RequestInterface  $request Request instance
     * @param ResponseInterface $response Response instance
     * @param callable          $next Next callable middleware
     *
     * @return ResponseInterface Processed response instance
     */
    public function process(RequestInterface $request, ResponseInterface $response, callable $next);
}
