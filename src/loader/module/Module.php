<?php declare(strict_types=1);

namespace samsonphp\core\loader\module;

use samsonframework\container\ContainerConfigurableInterface;

/**
 * Class Module
 *
 * @package samsonphp\core\loader\module
 */
class Module
{
    /** @var string Interface which mark as class configure the module */
    public static $containerConfigurableInterface = ContainerConfigurableInterface::class;

    /** @var string Module name */
    public $name;
    /** @var string Module class name */
    public $className;
    /** @var string Module path name */
    public $pathName;
    /** @var string Module path */
    public $path;
    /** @var array List of classes */
    public $classes = [];
    /** @var bool Is module has custom dependency injection configurator */
    public $isContainerConfigurable = false;
    /** @var string Configure dependency injection class */
    public $containerConfigurableClassName;
    public $composerParameters;

    public function __construct($name, $path, $classes)
    {
        $this->name = $name;
        $this->path = $path;
        $this->classes = $classes;

//        $this->requireClasses();
//        $this->checkIsContainerConfigurable();
    }

    /**
     * Require module classes
     */
    protected function requireClasses()
    {
        foreach ($this->classes as $classPath => $className) {

            $className = '\\' . ltrim($className, '\\');
//            if (preg_match('/dbQuery/', $className)) {
//                trace('sdf');
//            }
            if (file_exists($classPath)
                && !preg_match('/\/api\/generated\//', $className)
                && !preg_match('/activerecord\/dbQuery/', $className)
                && !preg_match('/samson\/activerecord\//', $className)
                && !preg_match('/Field.php$/', $classPath)
                && !preg_match('/Navigation.php$/', $classPath)
                && !preg_match('/TableVirtualCollection.php$/', $classPath)
                && !preg_match('/TableVirtualEntity.php$/', $classPath)
                && !preg_match('/TableVirtualQuery.php$/', $classPath)
                && !class_exists($className)
            ) {
                require_once($classPath);
            }
        }
    }

    /**
     * Check if class configures the container
     */
    public function checkIsContainerConfigurable()
    {
        // Find class with correct interface
        foreach ($this->classes as $classPath => $className) {
            $reflectionClass = new \ReflectionClass($className);
            // If class implements correct interface then it is container configurable
            if (in_array($this::$containerConfigurableInterface, $reflectionClass->getInterfaceNames(), true)) {
                $this->isContainerConfigurable = true;
                $this->containerConfigurableClassName = $className;
            }
        }
    }
}
