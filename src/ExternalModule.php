<?php
namespace samson\core;
use samsonframework\core\RequestInterface;
use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;

/**
 * SamsonPHP external module
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
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

    /** Коллекция связанных модулей с текущим */
    protected $requirements = array();

    /**
     * ExternalModule constructor.
     *
     * @param string $path Path to module
     * @param ResourceMap $resources
     * @param Core $system Framework instance
     * @param Url $request Request instance
     */
    public function  __construct($path, ResourcesInterface $resources, SystemInterface $system, RequestInterface $request)
    {
        // Inject generic module dependencies
        $this->system = $system;
        $this->request = $request;

        // Module identifier not specified - set it to NameSpace\Classname
        if (!isset($this->id{0}) && !isset($identifier)) {
            // Generate identifier from module class
            $this->id = AutoLoader::oldClassName(get_class($this));
        }

        // Subscribe to an config ready core event
        \samsonphp\event\Event::subscribe('core.started', array(&$this, 'init'));

        // Call parent constructor
        parent::__construct($this->id, $path, $resources);
    }

    /** @see \samson\core\iExternalModule::copy() */
    public function &copy()
    {
        // Get current class name
        $classname = get_class($this);

        // Generate unique virtual id for copy
        $id = $this->id;

        // Create copy instance
        $o = new $classname($this->path, $id, $this->resourceMap);
        $o->views = &$this->views;
        $o->parent = &$this->parent;
        $o->controllers = &$this->controllers;
        $o->path = $this->path;

        return $o;
    }

    /** Обработчик сериализации объекта */
    public function __sleep()
    {
        // Remove all unnecessary fields from serialization
        return array_diff(array_keys(get_object_vars($this)), array('view_path', 'view_html', 'view_data'));
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
    public function action($methodName = NULL)
    {
        // Выполним стандартное действие
        $result = parent::action($methodName);

        // Если мы не смогли выполнить действие для текущего модуля
        // и задан родительский модуль
        if ($result === A_FAILED && isset($this->parent)) {
            // Выполним действие для родительского модуля
            return $this->parent->action($methodName);
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
                e('[##] Cannot find view "##"', E_SAMSON_FATAL_ERROR, array($this->id, $viewPath));
            }

            return $this;
        }
    }

    /**
     * Overloading default module rendering behaviour
     * as it is used in templates and views using m()->render()
     * without specifying concrete module or passing a variable.
     *
     * @see parent::render()
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

    /**
     * Module preparation handler.
     * This function is triggered after module instance is being created.
     *
     * @return bool Preparation result
     */
    public function prepare()
    {
        return true;
    }

    /**
     * Module initialization.
     * This function is triggered when system has started. So here
     * we have all modules already prepared and loaded.
     *
     * @param array $params Collection of module initialization parameters
     * @return bool Initialization result
     */
    public function init(array $params = array())
    {
        $this->set($params);

        return true;
    }
}
