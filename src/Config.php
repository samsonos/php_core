<?php
namespace samson\core;

/** Constant to define generic stage at web-application production status */
define('CONFIG_ALL', 0);
/** Constant to define development stage at web-application production status */
define('CONFIG_DEV', 1);
/** Constant to define test stage at web-application production status */
define('CONFIG_TEST', 2);
/** Constant to define final stage at web-application production status */
define('CONFIG_PROD', 3);

/**
 * Module configuration
 * SamsonPHP configuration is based on classes, to configure a module
 * in your web application you must create class that exends this class
 * and all of it public field would be parsed and passed to module
 * at core module load stage.
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @deprecated Use \samsonos\config\Entity
 */
class Config extends \samsonos\config\Entity
{	
	/** @var integer Current web-application development stage*/
	public static $type = CONFIG_ALL;
	
	/**
	 * Коллекция параметров конфигурации модулей системы
	 * @var array
	 */
	public static $data = array();

    /**
     * Assign configuration data to a module object
     *
     * @param string $moduleID Module identifier
     * @param mixed $module Pointer to a module instance to be configured
     * @param array $configuration Collection of key => value configuration parameters
     */
    public static function implement($moduleID, & $module, & $configuration = null)
    {
        // If external configuration is not passed
        if (!isset($configuration)) {
            // Pointer to module configuration
            $configuration = & self::$data[$moduleID];
        }

        // If module configuration loaded - set module params
        if (isset($configuration)) {
            // Iterate all configuration fields
            foreach ($configuration as $k => $v) {
                // Assign only own class properties no view data set anymore
                if (property_exists(get_class($module), $k)) {
                    $module->$k = $v;
                }
            }
        }
    }

    /**
     * Load configuration classes and store their data
     *
     * @param \samson\core\Core|\samson\core\iCore $core Pointer to core instance
     * @param integer                              $type Web-application development stage identifier
     */
	public static function load(\samson\core\iCore & $core, $type = NULL)
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
		foreach (get_declared_classes() as $class ) {
            if( in_array( 'samson\core\Config', class_parents($class))) {
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
                } else if($o->__type === CONFIG_ALL) {
                    // If this is generic module configuration
                    $config = array_merge(
                        get_object_vars($o),                    // Get all configuration parameters
                        isset($config) ? $config : array(),     // Merge with previous configuration
                        array( '__path' => $o->__path )         // Add path parameter from configuration
                    );
                }
            }
        }
	}
	
	/**
	 * Завершить процесс инициализации конфигурации системы
	 * Если конфигурация относится к внешнему модулю и к нему указан
	 * путь в ней, то метод автоматически загрузит этот модуль в ядро системы
	 * @param iCore $core Указатель на ядро системы
	 */
	public static function init(\samson\core\iCore & $core = NULL)
	{
        // Load configuration data if it is not loaded
		if (sizeof(self::$data) === 0) {
            self::load($core);
        }
			
		// Переберем загруженные конфигурации модулей
		foreach (self::$data as $module => $cfg) {
			if (isset($cfg['__path']) && isset($cfg['__path']{0})) {
				// Получим путь к модулю относительно системной папки 
				$path = normalizepath(__DIR__.'/'.$cfg['__path']);
						
				// Если нет то попробуем относительно текущей папки
				if (!file_exists($path)) {
                    $path = normalizepath( getcwd().'/'.$cfg['__path']);
                }
			
				// Загрузим модуль в ядро системы если к нему указан путь
				if (file_exists($path)) {
                    $core->load($path);
                }
			}		
		}
	}
	
	/** @var integer Configuration web-application development stage identifier */
	protected $__type = CONFIG_ALL;
	
	/** @var string	Special field to specify module identifier to what this configuration is */
	protected $__module;
	
	/** @var string	Path to module location if it was not loaded already */
	protected $__path;	
}

//[PHPCOMPRESSOR(remove,start)]
// Subscribe to core started event to load all possible module configurations
//\samson\core\Event::subscribe('core.created', array('\samson\core\Config', 'load'));

// Subscribe to core started event to load all possible module configurations
//\samson\core\Event::subscribe('core.routing', array('\samson\core\Config', 'init'));

// Subscribe to core module loaded core event
//\samson\core\Event::subscribe('core.module_loaded', array('\samson\core\Config', 'implement'));
//[PHPCOMPRESSOR(remove,end)]
