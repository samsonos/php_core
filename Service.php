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
	private static $_factory = array();

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

	/** Конструктор */
	public function __construct( $path = NULL )
	{
		// Получим имя класса
		$class = strtolower(get_class( $this ));
			
		// Проверка на Singleton
		if( !isset(self::$_factory[ $class ]) ) self::$_factory[ $class ] = $this;
		else e('Attempt to create another instance of Factory class: ##', E_SAMSON_FATAL_ERROR, $class );
			
		// Вызовем родительский конструктор
		parent::__construct( $path );
	}

	/** Обработчик десериализации объекта */
	public function __wakeup(){	parent::__wakeup();	self::$_factory[ strtolower(get_class($this)) ] = $this;  }
}