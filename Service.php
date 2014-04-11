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
        // If this is not a Service(base class) instance creation
        $parentClass = strtolower(get_parent_class($currentObject));
        if(strtolower(__CLASS__) != $parentClass) {
            // Get parent class instance
            $parentInstance = & self::$_factory[$parentClass];
            if(isset($parentInstance)) {
                // Iterate all current ancestor object fields
                foreach(get_object_vars($parentInstance) as $field => $value) {
                    // If ancestor has field set and it is not set in base instance
                    if (isset($parentInstance->$field) && !isset($baseObject->$field)) {
                        // Set ancestors value
                        $baseObject->$field = $parentInstance->$field;
                    }
                }
            }
        }
    }

	/** Конструктор */
	public function __construct( $path = NULL )
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
		parent::__construct( $path );
	}

	/** Обработчик десериализации объекта */
	public function __wakeup(){	parent::__wakeup();	self::$_factory[ strtolower(get_class($this)) ] = $this;  }
}