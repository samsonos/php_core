<?php
namespace samson\core;

/**
 * Виды режимов конфигурация работы фреймворка
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 0.1
 */
class ConfigType 
{
	/** Конфигурация по умолчанию*/
	const ALL 			= 0;
	/** Конфигурация для разработки	*/
	const DEVELOPMENT 	= 1;
	/** Конфигурация для тестирования */
	const TEST 			= 2;
	/** Конфигурация для продакшина */
	const PRODUCTION 	= 3;	
}

/**
 * Класс для конфигурации работы фреймворка
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 0.1
 */
class Config
{	
	/**
	 * Текущий режим конфишурации работы фреймворка 
	 * @var ConfigType
	 */
	public static $type = ConfigType::ALL;
	
	/**
	 * Коллекция параметров конфигурации модулей системы
	 * @var array
	 */
	public static $data = array(); 
	
	/**
	 * Выполнить загрузку и инициализацию конфигурации модулей
	 * загруженных в ядро системы
	 * @param ConfigType $type Рабочая конфигурация
	 * @param iCore Указатель на ядро системы для взаимодействия
	 */
	public static function load( $type = NULL )
	{	
		// Установим режим конфигурации модулей
		if( isset( $type ) ) self::$type = $type;
		
		// Получим загруженные в систему классы которые наследуют этот класс
		foreach ( get_declared_classes() as $class ) if( in_array( 'samson\core\Config', class_parents($class)) ) 
		{				
			//trace('Создаем конфигурацию:'.$class);		
			
			// Создадим экземпляр объекта конфигурации модуля	
			$o = new $class();	
		
			// Сохраним в статическую коллекцию загруженную конфигурацию модулей системы
			// в нужном нам режиме работы
			if( $o->__type === self::$type || $o->__type === ConfigType::ALL ) 
			{					
				self::$data[ $o->__module ] = array_merge(
						isset(self::$data[ $o->__module ])? self::$data[ $o->__module ] : array(),	// Предыдущий конфиг если он существовал	 
						array( '__path' => $o->__path ),	// Путь к модулю
						get_object_vars($o)					// Переменные конфигурации модуля
				);		
			}
		}
	}
	
	/**
	 * Завершить процесс инициализации конфигурации системы
	 * Если конфигурация относится к внешнему модулю и к нему указан
	 * путь в ней, то метод автоматически загрузит этот модуль в ядро системы
	 * @param iCore $core Указатель на ядро системы
	 */
	public static function init( iCore & $core = NULL)
	{	
		// TODO: Переделать это нахер
			
		// Переберем загруженные конфигурации модулей
		foreach ( self::$data as $module => $cfg )
		{
			if( isset($cfg['__path']) && isset($cfg['__path']{0}) )
			{
				// Получим путь к модулю относительно системной папки 
				$path = normalizepath(__DIR__.'/'.$cfg['__path']);
						
				// Если нет то попробуем относительно текущей папки
				if( ! file_exists( $path )) $path = normalizepath( getcwd().'/'.$cfg['__path']);
			
				// Загрузим модуль в ядро системы если к нему указан путь
				if( file_exists( $path ) ) $core->load( $path );	
			}		
		}
	}
	
	/**
	 * Тип конфигурации, по умолчанию DEV
	 * @var ConfigType
	 */
	protected $__type = ConfigType::ALL;	
	
	/**
	 * Имя модуля для которого предназначена конфигурация
	 * @var string
	 */
	protected $__module;
	
	/**
	 * Путь к модулю, если указан то будет выполнена попытка загрузить модуль
	 * если он еще не загружен в ядро
	 * @var string
	 */
	protected $__path;	
}