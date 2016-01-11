<?php
namespace samson\core;

use samsonframework\core\RequestInterface;
use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;
use samsonphp\event\Event;

/**
 * SamsonPHP external module
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @version 0.1
 */
class ExternalModule extends Module implements iExternalModule
{
    /** @var Module Pointer to parent module */
    public $parent = null;

    /** Коллекция связанных модулей с текущим */
    protected $requirements = array();

    /**
     * ExternalModule constructor.
     *
     * @param string $path
     * @param ResourcesInterface $resources
     * @param SystemInterface $system
     */
    public function __construct($path, ResourcesInterface $resources, SystemInterface $system)
    {
        // Module identifier not specified - set it to NameSpace\Classname
        if (!isset($this->id{0})) {
            // Generate identifier from module class
            //$this->id = AutoLoader::oldClassName(get_class($this));
            $this->id = str_replace('/', '',$path);
        }

        // Subscribe to an config ready core event
        Event::subscribe('core.started', array(& $this, 'init'));

        // Call parent constructor
        parent::__construct($this->id, $path, $resources, $system);
    }

    /** @see \samson\core\iExternalModule::copy() */
    public function &copy()
    {
        // Get current class name
        $classname = get_class($this);

        // Create copy instance
        $clone = new $classname($this->path, $this->resourceMap, $this->system);
        $clone->views = &$this->views;
        $clone->parent = &$this->parent;
        $clone->controllers = &$this->controllers;
        $clone->path = $this->path;

        return $clone;
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
     * @param string $methodName Controller action name
     * @return bool|mixed
     */
    public function action($methodName = null)
    {
        // Выполним стандартное действие
        $result = parent::action($methodName);

        // Если мы не смогли выполнить действие для текущего модуля
        // и задан родительский модуль
        if ($result === false && isset($this->parent)) {
            // Выполним действие для родительского модуля
            return $this->parent->action($methodName);
        }

        // Веренем результат выполнения действия
        return $result;
    }

    /**
     * Set current view for rendering.
     *
     * @param string $viewPath Path for view searching
     * @return self Chaining
     */
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
     * @param string $controller Controller action name
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
            $this->parent->render($controller);
        } else { // Call default module behaviour
            parent::render($controller);
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
