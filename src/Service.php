<?php
namespace samson\core;

use samson\core\AutoLoader;
use samsonframework\core\RequestInterface;
use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;

/**
 * Модуль имеющий единственный свой экземпляр c расширенными возможностями 
 * подключения к фреймворку. 
 * 
 * Удобен для использования во внешних модулях у которых всего один внешний главный
 * класс и это позволяет всю его логику внести в Service не создавая 2 отдельных
 * файла и класса.
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 0.1
 */
class Service extends ExternalModule
{
    /** @var Service[] Factory instances */
    protected static $factory = array();

    /** Ancestor field that must be ignored */
    protected static $ancestorIgnoreData = array('vid'=>'', 'uid'=>'', 'cache_path'=>'');

    /** Flag for gathering all ancestor data on instance creation */
    public $gatherAncestorData = true;

    /**
     * Get unique service instance. If service has not yet been created
     * new instance will be created.
     *
     * @param string $className Class name for getting service instance
     * @return Service Service instance
    */
    public static function &getInstance($className)
    {
        // Check service class existence
        if (class_exists($className)) {
            // Convert class name to avoid misspelling
            $class = self::getName($className);

            // Check if service exists
            if (isset(self::$factory[ $class ])) {
                // Return service instance
                return self::$factory[ $class ];
            } else { // Create service instance
                $service = new $className('');
                return $service;
            }
        } else {
            return e('Service class [##] does not exists', E_SAMSON_FATAL_ERROR, $className);
        }
    }

    /**
     * Generic method for setting fields to current service from its parents.
     * Method recursively copies all unset fields of $baseObject from defined
     * field values of its parent classes
     * @param mixed $baseObject     Object fo set undefined fields from parents
     * @param mixed $currentObject  Current object for recursion to find parent fields
     */
    protected static function gatherAncestorsData(& $baseObject, & $currentObject)
    {
        // If this is not a Service(base class) instance creation
        $parentClass = self::getName(get_parent_class($currentObject));
        // This instance is not Service
        if (self::getName(__CLASS__) != $parentClass) {
            // Get parent class instance
            $parentInstance = & self::$factory[$parentClass];
            if (isset($parentInstance)) {
                // Get all parent instance fields except ignored
                $fields = array_diff_key(get_object_vars($parentInstance), self::$ancestorIgnoreData);

                // Iterate all current ancestor object fields except ignored
                foreach ($fields as $field => $value) {
                    // If ancestor has field set and it is not set in base instance
                    if (isset($parentInstance->$field) && !isset($baseObject->$field)) {
                         $baseObject->$field = $parentInstance->$field;
                    }
                }

                // Set parent service
                $baseObject->parent = & $parentInstance;

                // Go deeper in recursion
                self::gatherAncestorsData($baseObject, $parentInstance);
            }
        }
    }

    /**
     * Convert class name to correct service name
     * @var string $class Class name to convert
     * @return string Get correct service name
     */
    public static function getName($class)
    {
        // Generate correct service name
        return AutoLoader::oldClassName($class);
    }

    /**
     * ExternalModule constructor.
     *
     * @param string $path Path to module
     * @param ResourcesInterface $resources
     * @param SystemInterface $system Framework instance
     * @param RequestInterface $request Request instance
     */
    public function  __construct($path, ResourcesInterface $resources, SystemInterface $system, RequestInterface $request)
    {
        // Получим имя класса
        $class = self::getName(get_class($this));

        // Search instance
        if (!isset(self::$factory[$class])) {
            self::$factory[ $class ] = $this;

            // If service is configured to gather ancestors data
            if ($this->gatherAncestorData) {
                $this->gatherAncestorsData($this, $this);
            }
        } else {
            e('Attempt to create another instance of Factory class: ##', E_SAMSON_FATAL_ERROR, $class);
        }

        // Вызовем родительский конструктор
        parent::__construct($path, $resources, $system, $request);
    }

    /** Deserialization */
    public function __wakeup()
    {
        parent::__wakeup();

        self::$factory[self::getName(get_class($this))] = $this;
    }
}
