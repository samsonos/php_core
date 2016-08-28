<?php declare(strict_types=1);
namespace samson\core;

use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;
use samsonframework\core\ViewInterface;
use samsonframework\core\PreparableInterface;
use samsonphp\core\Module;
use samsonphp\event\Event;

/**
 * SamsonPHP external module
 *
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class ExternalModule extends Module implements PreparableInterface
{
    /** @var Module Pointer to parent module */
    public $parent = null;

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
            $this->id = strtolower(str_replace(__NS_SEPARATOR__, '_', get_class($this)));
        }

        // Generate unique module cache path in local web-application
        $this->cache_path = __SAMSON_CWD__ . __SAMSON_CACHE_PATH . $this->id . '/';

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
     * Set current view for rendering.
     *
     * @param string $viewPath Path for view searching
     * @return self Chaining
     */
    public function view($viewPath)
    {
        if (is_a($viewPath, ViewInterface::class)) {
            $this->view_path = $viewPath;
            return $this;
            // Old string approach - will be deprecated soon
        } elseif (is_string($viewPath)) {
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

                return $this;
            }
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
        if (isset($this->parent) && $this->view_path == false /*&& !method_exists($this, $controller)*/) {
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

    public function setId($id)
    {
        $this->id = $id;
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
