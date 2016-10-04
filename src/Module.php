<?php declare(strict_types=1);
namespace samsonphp\core;

use samson\core\File;
use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;
use samsonframework\core\ViewInterface;
use samsonframework\resource\ResourceMap;
use samsonphp\core\deprecated\iModule;
use samsonphp\core\exception\ControllerActionNotFound;
use samsonphp\core\exception\ViewPathNotFound;
use samsonphp\core\exception\ViewVariableNotFound;

/**
 * Модуль системы
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @deprecated Will be merged with ExternalModule
 */
class Module implements \ArrayAccess, iModule
{
    /** Static module instances collection */
    public static $instances = array();

    /** Uniquer identifier to check pointers */
    public $uid;

    /** @var ResourcesInterface Pointer to module resource map */
    public $resourceMap;

    /** @var array TODO: WTF? */
    public $composerParameters = array();

    /** Module views collection */
    protected $views = array();

    /** Module location */
    protected $path = '';

    /** Unique module identifier */
    protected $id;

    /** Path to view for rendering */
    protected $view_path = self::VD_POINTER_DEF;

    /** Pointer to view data entry */
    protected $data = array(self::VD_POINTER_DEF => array(self::VD_HTML => ''));

    /** Collection of data for view rendering, filled with default pointer */
    protected $view_data = array(self::VD_POINTER_DEF => array(self::VD_HTML => ''));

    /** Name of current view context entry */
    protected $view_context = self::VD_POINTER_DEF;

    /** Unique module cache path in local web-application */
    protected $cache_path;

    /** @var SystemInterface Instance for interaction with framework */
    protected $system;

    /**
     * Constructor
     *
     * @param string $id Module unique identifier
     * @param string $path Module location
     * @param ResourcesInterface $resourceMap Pointer to module resource map
     * @param SystemInterface $system
     */
    public function __construct($id, $path, ResourcesInterface $resourceMap, SystemInterface $system)
    {
        // Inject generic module dependencies
        $this->system = $system;

        // Store pointer to module resource map
        // TODO: Should be changed or removed
        $this->resourceMap = $resourceMap = ResourceMap::get($path);
        // Save views list
        $this->views = $resourceMap->views;

        // Set default view context name
        $this->view_context = self::VD_POINTER_DEF;

        // Set up default view data pointer
        $this->data = &$this->view_data[$this->view_context];

        // Set module identifier
        $this->id = $id;

        // Set path to module
        $this->path(realpath($path));

        // Generate unique module identifier
        $this->uid = rand(0, 9999999) . '_' . microtime(true);

        // Add to module identifier to view data stack
        $this->data['id'] = $this->id;

        // Generate unique module cache path in local web-application
        $this->cache_path = __SAMSON_CWD__ . __SAMSON_CACHE_PATH . $this->id . '/';

        // Save ONLY ONE copy of this instance in static instances collection,
        // avoiding rewriting by cloned modules
        !isset(self::$instances[$this->id]) ? self::$instances[$this->id] = &$this : '';

        // Make view path relative to module - remove module path from view path
        $this->views = str_replace($this->path, '', $this->views);

        //elapsed('Registering module: '.$this->id.'('.$path.')' );
    }

    /** @see iModule::path() */
    public function path($value = null)
    {
        // Если передан параметр - установим его
        if (func_num_args()) {
            $this->path = isset($value{0}) ? rtrim(normalizepath($value), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';

            return $this;
        } // Вернем относительный путь к файлам модуля
        else return $this->path;
    }

    /**    @see iModule::title() */
    public function title($title = null)
    {
        return $this->set($title, 'title');
    }

    /** @see iModule::set() */
    public function set($value, $field = null)
    {
        $this->__set($value, $field);

        return $this;
    }

    /**    @see iModule::id() */
    public function id()
    {
        return $this->id;
    }

    /** @see iModuleViewable::toView() */
    public function toView($prefix = null, array $restricted = array())
    {
        // Get all module data variables
        $view_data = array_merge($this->data, get_object_vars($this));

        // Remove plain HTML from view data
        unset($view_data[self::VD_HTML]);

        return $view_data;
    }

    /** @see iModule::html() */
    public function html($value)
    {
        $this->data[self::VD_HTML] = $value;

        return $this;
    }

    public function view($viewPath)
    {
        if (is_a($viewPath, ViewInterface::class)) {
            $this->view_path = $viewPath;
        } elseif (is_string($viewPath)) {
            // Find full path to view file
            $foundViewPath = $this->findView($viewPath);

            // We could not find view
            if ($foundViewPath !== false) {
                // Switch view context to founded module view
                $this->viewContext($foundViewPath);

                // Set current view path
                $this->view_path = $foundViewPath;
            } else {
                throw(new ViewPathNotFound($viewPath));
            }
        }

        // Продолжим цепирование
        return $this;
    }

    /**
     * Find view file by its part in module view resources and return full path to it.
     *
     * @param string $viewPath Part of path to module view file
     * @return string Full path to view file
     */
    public function findView($viewPath)
    {
        // Remove file extension for correct array searching
        $viewPath = str_replace(array('.php', '.vphp'), '', $viewPath);

        // Try to find passed view_path in  resources views collection
        if (sizeof($view = preg_grep('/' . addcslashes($viewPath, '/\\') . '(\.php|\.vphp)/ui', $this->views))) {
            // Sort view paths to get the shortest path
            usort($view, array($this, 'sortStrings'));

            // Set current full view path as last found view
            return end($view);
        }

        return false;
    }

    /**
     * Perform module view context switching
     * @param string $view_path New view context name
     */
    protected function viewContext($view_path)
    {
        // Pointer to NEW view data context
        $new = &$this->view_data[$view_path];

        // Pointer to OLD view data context
        $old = &$this->view_data[$this->view_context];

        // If we are trying to switch to NEW view context
        if ($this->view_context !== $view_path) {
            //elapsed( $this->id.' - Switching view context from '.$this->view_context.' to '.$view_path );

            // Create new entry in view data collection if it does not exists
            if (!isset($this->view_data[$view_path])) {
                // Create new view data record
                $new = array();

                // If current view data context has view data
                if (isset($old)) {
                    //elapsed($old);
                    //elapsed( $this->id.' - Copying previous view context view data '.$this->view_context.' to new view context '.$view_path.'('.sizeof($old).')');

                    // Copy default view context view data to new view context
                    $new = array_merge($new, $old);
                }

                // Clear plain HTML for new view context
                $new[self::VD_HTML] = '';
            }

            // Change view data pointer to appropriate view data entry
            $this->data = &$new;

            // Save current context name
            $this->view_context = $view_path;
        }
        //else elapsed( $this->id.' - NO need to switch view context from '.$this->view_context.' to '.$view_path );
    }

    /**
     * @param null $controller
     *
     * @throws ControllerActionNotFound
     */
    public function render($controller = null)
    {
        // Switch current system active module
        $old = $this->system->active($this);

        // If specific controller action should be run
        if (isset($controller)) {
            /**
             * TODO: This would be removed in next major version, here should be
             * passed real controller method for execution.
             */
            $controller = method_exists($this, iModule::OBJ_PREFIX . $controller)
                ? iModule::OBJ_PREFIX . $controller
                : $controller;

            // Define if this is a procedural controller or OOP
            $callback = array($this, $controller);

            // If this controller action is present
            if (method_exists($this, $controller)) {
                // Get passed arguments
                $parameters = func_get_args();
                // Remove first as its a controller action name
                array_shift($parameters);
                // Perform controller action with passed parameters
                call_user_func_array($callback, $parameters);
            } else {
                throw new ControllerActionNotFound($this->id . '#' . $controller);
            }
        }

        // Output view
        echo $this->output();

        // Restore previous active module
        $this->system->active($old);
    }

    /**    @see iModule::output() */
    public function output()
    {
        // If view path not specified - use current correct view path
        $viewPath = $this->view_path;

        if (is_string($viewPath)) {

            //elapsed('['.$this->id.'] Rendering view context: ['.$viewPath.'] with ['.$renderer->id.']');

            // Switch view context to new module view
            $this->viewContext($viewPath);

            //elapsed($this->id.' - Outputing '.$view_path.'-'.sizeof($this->data));
            //elapsed(array_keys($this->view_data));

            // Get current view context plain HTML
            $out = $this->data[self::VD_HTML];

            // If view path specified
            if (isset($viewPath{0})) {
                // Временно изменим текущий модуль системы
                $old = $this->system->active($this);

                // Прорисуем представление модуля
                $out .= $this->system->render($this->path . $viewPath, $this->data);

                // Вернем на место текущий модуль системы
                $this->system->active($old);
            }

            // Clear currently outputted view context from VCS
            unset($this->view_data[$viewPath]);

            // Get last element from VCS
            end($this->view_data);

            // Get last element from VCS name
            $this->view_context = key($this->view_data);

            // Set internal view data pointer to last VCS entry
            $this->data = &$this->view_data[$this->view_context];

            // Return view path to previous state
            $this->view_path = $this->view_context;

            // Вернем результат прорисовки
            return $out;

        } elseif (is_a($viewPath, ViewInterface::class)) {
            /** @var ViewInterface $viewPath */
            return $viewPath->output();
        }
    }

    /** Magic method for calling un existing object methods */
    public function __call($method, $arguments)
    {
        //elapsed($this->id.' - __Call '.$method);

        // If value is passed - set it
        if (count($arguments)) {
            // If first argument is object or array - pass method name as second parameter
            if (is_object($arguments[0]) || is_array($arguments[0])) {
                $this->__set($arguments[0], $method);
            } else { // Standard logic
                $this->__set($arguments[0], $method);
            }
        }

        // Chaining
        return $this;
    }

    // Магический метод для получения переменных представления модуля

    /** Обработчик сериализации объекта */
    public function __sleep()
    {
        return array('id', 'path', 'data', 'views');
    }

    /** Обработчик десериализации объекта */
    public function __wakeup()
    {
        // Fill global instances
        self::$instances[$this->id] = &$this;

        // Set up default view data pointer
        $this->view_data[self::VD_POINTER_DEF] = $this->data;

        // Set reference to view context entry
        $this->data = &$this->view_data[self::VD_POINTER_DEF];
    }

    // TODO: Переделать обработчик в одинаковый вид для объектов и простых

    /** Группа методов для доступа к аттрибутам в виде массива */
    public function offsetSet($value, $offset)
    {
        $this->__set($offset, $value);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    // Магический метод для установки переменных представления модуля

    public function __get($field)
    {
        // Установим пустышку как значение переменной
        $result = null;

        // Если указанная переменная представления существует - получим её значение
        if (isset($this->data[$field])) {
            $result = &$this->data[$field];
        } else {
            throw(new ViewVariableNotFound($field));
        }

        // Иначе вернем пустышку
        return $result;
    }

    public function __set($value, $field = null)
    {
        if (is_object($field) || is_array($field)) {
            $tempValue = $field;
            $field = $value;
            $value = $tempValue;

            // TODO: Will be added in next major version
            //throw new \Exception('ViewInterface::set($value, $name) has changed, first arg is variable second is name or prefix');
        }

		// This is object
		if (is_object($value) && is_a($value, 'samsonframework\core\RenderInterface')) {
			$this->_setObject($value, $field);
		} elseif (is_array($value)) { // If array is passed
			$this->_setArray($value, $field);
		} else { // Set view variable
            $this->data[$field] = $value;
		}
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /** Sort array by string length */
    protected function sortStrings($a, $b)
    {
        return strlen($b) - strlen($a);
    }

    /**
     * Create unique module cache folder structure in local web-application.
     *
     * @param string $file Path to file relative to module cache location
     * @param boolean $clear Flag to perform generic cache folder clearence
     * @return boolean TRUE if cache file has to be regenerated
     */
    protected function cache_refresh(&$file, $clear = true, $folder = null)
    {
        // Add slash to end
        $folder = isset($folder) ? rtrim($folder, '/') . '/' : '';

        $path = $this->cache_path . $folder;

        // If module cache folder does not exists - create it
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        // Build full path to cached file
        $file = $path . $file;

        // If cached file does not exsits
        if (!file_exists($file)) {
            // If clearence flag set to true - clear all files in module cache directory with same extension
            if ($clear) {
                File::clear($path, pathinfo($file, PATHINFO_EXTENSION));
            }

            // Signal for cache file regeneration
            return true;
        }

        return false;
    }

    /**
     *
     * @param unknown $object
     * @param string $viewprefix
     */
    private function _setObject($object, $viewprefix = null)
    {
        // Generate viewprefix as only lowercase classname without NS if it is not specified
        $class_name = is_string($viewprefix) ? $viewprefix : '' . mb_strtolower(ns_classname(get_class($object)), 'UTF-8');

        // Save object to view data
        $this->data[$class_name] = $object;

        // Add separator
        $class_name .= '_';

        // Generate objects view array data and merge it with view data
        $this->data = array_merge($this->data, $object->toView($class_name));
    }

    /**
     *
     * @param unknown $array
     * @param string $viewprefix
     */
    private function _setArray($array, $viewprefix = null)
    {
        // Save array to view data
        $this->data[$viewprefix] = $array;

        // Add array values to view data
        $this->data = array_merge($this->data, $array);
    }
}
