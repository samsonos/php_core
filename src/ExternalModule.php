<?php
namespace samson\core;

/**
 * SamsonPHP external module
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 0.1
 */
class ExternalModule extends Module implements iExternalModule
{		
	/** Correct name for composer generator */
	const COMPOSER_VENDOR = 'samsonos';
	
	/** 
	 * Pointer to parent module 
	 * @var \samson\core\Module
	 * @see \samson\core\Module
	 */
	public $parent = NULL;

	/** Virtual module identifier */
	protected $vid = null;	
	
	/** Коллекция связанных модулей с текущим */
	protected $requirements = array();
		
	/**
	 * Constructor
	 * @param string 	$path 		Path to module location
	 * @param string 	$vid		Virtual module identifier
	 * @param array 	$resources	Module resources list 
	 */
	public function  __construct($path, $vid = null, $resources = NULL )
	{
		// Module identifier not specified - set it to NameSpace\Classname
		if (!isset($this->id{0}) && !isset($vid)) {
            // Generate identifier from module class
            $this->id = AutoLoader::oldClassName(get_class($this));
        } else if (!isset($this->id{0}) && isset($vid)) { // If identifier is passed and no id is set
            // Set passed vid as module identifier
            $this->id = $vid;
        }

        // TODO: Do we steel need virtual id?
        $this->vid = $this->id;

        //[PHPCOMPRESSOR(remove,start)]
        // Subscribe to an config ready core event
        Event::subscribe('core.started', array($this, 'init'));
        //[PHPCOMPRESSOR(remove,end)]

      /*  // Subscribe to an module loaded core event
        Event::subscribe('core.module_loaded', array($this, 'prepare'));*/
		
		// Call parent constructor
		parent::__construct($this->id, $path, $resources );
	}
	
	/** @see \samson\core\iExternalModule::copy() */
	public function & copy()
	{
		// Get current class name
		$classname = get_class( $this );
		
		// Generate unique virtual id for copy
		$id = $this->id.'_'.rand( 0, 99999999 );
		
		// Create copy instance
		$o = new $classname( $this->path, $id );	
		$o->views = & $this->views;	
		$o->parent = & $this->parent;
		$o->controllers = & $this->controllers;
		
		return $o;
	}
	
	
	/** Обработчик сериализации объекта */
	public function __sleep()
	{
		// Remove all unnessesary fields from serialization
		return array_diff( array_keys( get_object_vars( $this )), array( 'view_path', 'view_html', 'view_data' ));
	}

    /** Deserialization logic */
    public function __wakeup()
    {
        parent::__wakeup();

        // Subscribe to an config ready core event
        Event::subscribe('core.started', array($this, 'init'));
    }
	
	/** @see Module::duplicate() */
	public function & duplicate( $id, $class_name = null )
	{
		// Вызовем родительский метод
		$m = parent::duplicate( $id, $class_name );
	
		// Привяжем родительский модуль к дубликату
		$m->parent = & $this->parent;		
	
		// Вернем дубликат
		return $m;
	}	
	
	/**
	 * Перегружаем стандартное поведение выполнения действия контроллера
	 * Если текущий модуль наследует другой <code>ModuleConnector</code>
	 * то тогда сначала выполняется действие контроллера в данном модуле,
	 * а потом в его родителе. Это дает возможность выполнять наследование
	 * контроллеров модулей.
	 *
	 * @see iModule::action()
	 */
	public function action( $method_name = NULL )
	{
		// Выполним стандартное действие
		$result = parent::action( $method_name );
	
		// Если мы не смогли выполнить действие для текущего модуля
		// и задан родительский модуль
		if( $result === A_FAILED && isset($this->parent) )
		{
			// Выполним действие для родительского модуля
			return $this->parent->action( $method_name );
		}
	
		// Веренем результат выполнения действия
		return $result;
	}
	
	/**
	 * Перегружаем стандартное поведение вывода представления модуля
	 * Если текущий модуль наследует другой <code>ModuleConnector</code>
	 * то тогда сначала выполняется проверка существования требуемого представление
	 * в данном модуле, а потом в его родителе. Это дает возможность выполнять наследование
	 * представлений модулей.
	 *
	 * @see Module::output()
	 */
	public function output( $view_path = null )
	{	
		//elapsed($this->id.'('.$this->uid.')-'.$this->view_path);
		// Если этот класс не прямой наследник класса "Подключаемого Модуля"
		if( isset( $this->parent ) )
		{		
			// Find full path to view file
			$_view_path = $this->findView( $view_path );			
			
			// Если требуемое представление НЕ существует в текущем модуле -
			// выполним вывод представления для родительского модуля
			if( !isset($_view_path{0})  )					
			{			
				// Merge view data for parent module
				$this->parent->view_data = array_merge( $this->parent->view_data, $this->view_data );
				
				// Switch parent view context
				$this->parent->data = & $this->data;
				
				// Call parent module rendering
				return $this->parent->output( isset($view_path) ? $view_path : $this->view_path );
			}
		}
	
		// Call regular rendering
		return parent::output( $view_path );
	}
	
	/**	@see iExternalModule::prepare() */
	public function prepare()
	{
		// Вернем результат проверки модуля
		return TRUE;
	}
	
	/**	@see iExternalModule::init() */
	public function init( array $params = array() )
	{
		// Установим переданные параметры
		$this->set( $params );
	
		return TRUE;
	}
}