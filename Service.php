<?php
namespace samson\core;

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
	/** Factory instances */
	protected static $_factory = array();

    /** Ancestor field that must be ignored */
    protected static $ancestorIgnoreData = array('vid'=>'', 'uid'=>'', 'cache_path'=>'');

    /** Flag for gathering all ancestor data on instance creation */
    public $gatherAncestorData = true;

	/**
	 * @param $class Classname for getting instance
	 * @return SingletonModule
	*/
	public static function & getInstance( $class )
	{
		// Получим класс из которого был вызван метод
		$class = strtolower( isset( $class ) ? $class : e('Classname not specified', E_SAMSON_FATAL_ERROR));
	
		// Check if service exists
		if( !isset(self::$_factory[ $class ])) e('Service ## does not exists', E_SAMSON_FATAL_ERROR, $class );
		// Return service instance
		else return self::$_factory[ $class ];
	}

    /**
     *
     * @param $baseObject
     * @param $currentObject
     */
    protected static function gatherAncestorsData(& $baseObject, & $currentObject)
    {
        //trace('Gathering ancestor data for service: "'.get_class($currentObject).'"');
        // If this is not a Service(base class) instance creation
        $parentClass = strtolower(get_parent_class($currentObject));
        if(strtolower(__CLASS__) != $parentClass) {
            // Get parent class instance
            $parentInstance = & self::$_factory[$parentClass];
            if(isset($parentInstance)) {
                //trace('-Using parent class "'.$parentClass.'"');
                // Iterate all current ancestor object fields except ignored
                foreach (array_diff_key(get_object_vars($parentInstance), self::$ancestorIgnoreData) as $field => $value) {
                    // If ancestor has field set and it is not set in base instance
                    if (isset($parentInstance->$field) && !isset($baseObject->$field)) {
                         $baseObject->$field = $parentInstance->$field;
                    }
                }

                // Merger service requirements
                if (isset($parentInstance->requirements)) {
                    $baseObject->requirements = array_unique(array_merge($baseObject->requirements, $parentInstance->requirements));
                }

                // Set parent service
                $baseObject->parent = & $parentInstance;

                // Go deeper in recursion
                self::gatherAncestorsData($baseObject, $parentInstance);
            }
        }
    }

	/** Конструктор */
	public function __construct($path = NULL)
	{
		// Получим имя класса
		$class = strtolower(get_class( $this ));
			
		// Проверка на Singleton
		if (!isset(self::$_factory[ $class ])) {
            self::$_factory[ $class ] = $this;

            // If service is configured to gather ancestors data
            if ($this->gatherAncestorData) {
                $this->gatherAncestorsData($this, $this);
            }
        }
		else e('Attempt to create another instance of Factory class: ##', E_SAMSON_FATAL_ERROR, $class );
			
		// Вызовем родительский конструктор
		parent::__construct($path);
	}

	/** Обработчик десериализации объекта */
	public function __wakeup(){	parent::__wakeup();	self::$_factory[ strtolower(get_class($this)) ] = $this;  }
}