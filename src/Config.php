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
     * Load configuration classes and store their data
     *
     * @param Core       $core
     * @param ConfigType $type Рабочая конфигурация
     */
	public static function load(\samson\core\Core & $core, $type = NULL)
	{
        // Iterate all files in configuration folder
        foreach(\samson\core\File::dir($core->path().__SAMSON_CONFIG_PATH) as $configFile) {
            // Match only files ending with ...Config.php
            if(stripos($configFile, 'Config.php') !== false) {
                // Register configuration class in system
                require_once($configFile);
            }
        }

		// Установим режим конфигурации модулей
		if( isset( $type ) ) self::$type = $type;
		
		// Получим загруженные в систему классы которые наследуют этот класс
		foreach ( get_declared_classes() as $class ) if( in_array( 'samson\core\Config', class_parents($class)) ) 
		{				
			//trace('Создаем конфигурацию:'.$class);		
			
			/** @var Config $o Module configuration object */
			$o = new $class();

            // Pointer to module configuration
            $config = & self::$data[$o->__module];

            // If this module configuration matches current configuration type
            if ($o->__type === self::$type) {
                // Set only this module configuration object
                $config = array_merge(
                    get_object_vars($o),            // Get all configuration parameters
                    array( '__path' => $o->__path ) // Add path parameter from configuration
                );
            } else if($o->__type === ConfigType::ALL) {
                // If this is generic module configuration
                $config = array_merge(
                    get_object_vars($o),                    // Get all configuration parameters
                    isset($config) ? $config : array(),     // Merge with previous configuration
                    array( '__path' => $o->__path )         // Add path parameter from configuration
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
//[PHPCOMPRESSOR(remove,start)]
// Subscribe to core started event to load all possible module configurations
\samson\core\Event::subscribe('core.started', array('\samson\core\Config', 'init'));

// Subscribe to core created event to load and parse all configurations
\samson\core\Event::subscribe('core.created', array('\samson\core\Config', 'load'));
//[PHPCOMPRESSOR(remove,end)]
