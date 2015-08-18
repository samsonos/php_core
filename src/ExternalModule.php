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
	/** 
	 * Pointer to parent module 
	 * @var \samson\core\Module
	 * @see \samson\core\Module
	 */
	public $parent = NULL;

	/** @deprecated Virtual module identifier */
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

        // Subscribe to an config ready core event
        \samsonphp\event\Event::subscribe('core.started', array(&$this, 'init'));
		
		// Call parent constructor
		parent::__construct($this->id, $path, $resources );
	}
	
	/** @see \samson\core\iExternalModule::copy() */
	public function & copy()
	{
		// Get current class name
		$classname = get_class( $this );
		
		// Generate unique virtual id for copy
		$id = $this->id;
		
		// Create copy instance
		$o = new $classname( $this->path, $id, $this->resourceMap);	
		$o->views = & $this->views;	
		$o->parent = & $this->parent;
		$o->controllers = & $this->controllers;
        $o->path = $this->path;
		
		return $o;
	}
	
	/** Обработчик сериализации объекта */
	public function __sleep()
	{
		// Remove all unnecessary fields from serialization
		return array_diff( array_keys( get_object_vars( $this )), array( 'view_path', 'view_html', 'view_data' ));
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

    /** @see iModule::view() */
    public function view($viewPath)
    {
        //elapsed('['.$this->id.'] Setting view context: ['.$viewPath.']');
        // Find full path to view file
        $this->view_path = $this->findView($viewPath);

        // If we have not found view in current module but we have parent module
        if (isset($this->parent) && $this->view_path === false) {
            //elapsed('['.$this->id.'] Cannot set view['.$viewPath.'] - passing it to parent['.$this->parent->id.']');
           
            /*
             * Call parent module view setting and return PARENT module to chain
             * actually switching current module in chain
             */
            return $this->parent->view($viewPath);
        } else { // Call default module behaviour
            // Call default module behaviour
            parent::view($this->view_path);

            // If view has not been set at final stage - trigger error
            if ($this->view_path === false) {
                e('[##] Cannot find view "##"', E_SAMSON_FATAL_ERROR, array( $this->id, $viewPath));
            }

            return $this;
        }
    }

    /**
     * Overloading default module rendering behaviour
     * as it is used in templates and views using m()->render()
     * without specifying concrete module or passing a variable
     * @see iModule::render()
     */
    public function render($controller = null)
    {
        // If we have parent module connection and have no view set
        if (isset($this->parent) && $this->view_path == false) {
            // Merge current and parent module view data
            $this->parent->view_data = array_merge($this->parent->view_data, $this->view_data);
            // Set internal view context data pointer
            $this->parent->data = &$this->parent->view_data[$this->parent->view_context];
            // Call parent render
            return $this->parent->render($controller);
        } else { // Call default module behaviour
            return parent::render($controller);
        }
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
