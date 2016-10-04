<?php declare(strict_types=1);

namespace samsonphp\core\loader\module;

/**
 * Interface ModuleManagerInterface
 *
 * @package samsonphp\core\loader\module
 */
interface ModuleManagerInterface
{
    /**
     * Get all registered modules
     *
     * @return Module[] Module collection
     */
    public function getRegisteredModules() : array;

    /**
     * Get module metadata by name
     *
     * @param string $moduleName
     * @return Module
     */
    public function getModule(string $moduleName) : Module;
}
