<?php declare(strict_types=1);

namespace samsonphp\core\loader\module;

use samsonframework\resource\ResourceMap;
use samsonphp\event\Event;

/**
 * Class ComposerModuleManager implementation
 *
 * @package samsonphp\core\loader\module
 */
class ComposerModuleManager implements ModuleManagerInterface
{
    /** Composer precontainer id */
    const PRE_CONTAINER_ID = 'samsonframework_precontainer';

    /** Composer compressable module id */
    const COMPRESSABLE_MODULE_ID = 'samsonphp_package_compressable';

    /** @var array List of modules */
    protected $composerModules = [];

    /** @var string Application path */
    protected $appPath;

    /** @var string Vendor dir */
    protected $vendorDir;

    /** @var array Collection of interface => [classes]*/
    public $implements = [];
    /** @var array Collection of class => class */
    protected $extends = [];

    /**
     * ComposerModuleManager constructor.
     *
     * @param string $path
     * @param string $vendorDir
     */
    public function __construct(string $path, string $vendorDir)
    {
        $this->appPath = $path;
        $this->vendorDir = $vendorDir;

        // TODO Move this somewhere
        Event::fire('core.composer.create', [
            &$this->composerModules,
            $this->appPath, [
                'vendorsList' => ['samsonphp/', 'samsonos/', 'samsoncms/', 'samsonjavascript/'],
                'ignoreKey' => 'samson_module_ignore',
                'includeKey' => 'samson_module_include'
            ]
        ]);
    }

    /** {@inheritdoc} */
    public function getRegisteredModules() : array
    {
        $list = [];
        // Get all modules by their names
        foreach (array_keys($this->composerModules) as $moduleName) {
            $list[$moduleName] = $this->getModule($moduleName);
        }
        return $list;
    }

    /** {@inheritdoc} */
    public function getModule(string $moduleName) : Module
    {
        // Check if module exists
        if (!array_key_exists($moduleName, $this->composerModules)) {
            throw new \Exception(sprintf('Module with name "%s" not found', $moduleName));
        }

        $composerModule = $this->composerModules[$moduleName];
        $modulePath = $this->appPath . $this->vendorDir . $moduleName;

        // Convert resource map to module type
        $resourceMap = ResourceMap::get($modulePath);

        // Get all module classes
        $classes = [];
        foreach ($resourceMap->classData as $classData) {
            $classes[$classData['path']] = $classData['className'];
            if (array_key_exists('implements', $classData)) {
                foreach ($classData['implements'] as $interfaceName) {
                    $this->implements[$interfaceName] = $classData['className'];
                }
            }
            if (array_key_exists('extends', $classData) && isset($classData['extends']{0})) {
                $this->extends[$classData['extends']] = $classData['className'];
            }
        }

        foreach ($this->implements as $interface => $class) {
            if (array_key_exists($class, $this->extends)) {
                $this->implements[$interface] = $this->extends[$class];
            }
        }

        // Create new module
        $module = new Module($moduleName, $modulePath, $classes);
        $module->composerParameters = $composerModule;
        if (isset($resourceMap->module[0])) {
            $module->className = $resourceMap->module[0];
            $module->pathName = $resourceMap->module[1];
        }
        $module->isVirtualModule = !array_key_exists(0, $resourceMap->module) &&
            array_key_exists(self::COMPRESSABLE_MODULE_ID, $composerModule) &&
            (bool)$composerModule[self::COMPRESSABLE_MODULE_ID] === true;

        return $module;
    }
}
