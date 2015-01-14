<?php
/*
 * This file is part of the SamsonPHP\Core package.
 * (c) 2013 Vitaly Iegorov <egorov@samsonos.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace samson\core;

/**
 * Core of SamsonPHP
 * 
 * @package SamsonPHP
 * @author 	Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version @version@
 */
class Core implements iCore
{
    /** @deprecated Collection of paths ignored by resources collector */
    public static $resourceIgnorePath = array(
        '.git',
        '.svn',
        '.settings',
        '.idea',
        'vendor',
        'upload',
		'out',
        'i18n',
        __SAMSON_CACHE_PATH,
        __SAMSON_TEST_PATH,
    );

    /** @deprecated Module paths loaded stack */
	public $load_path_stack = array();

    /** @deprecated Modules to be loaded stack */
	public $load_stack = array();

    /** @deprecated Modules to be loaded stack */
	public $load_module_stack = array();

    /** @deprecated Render handlers stack, With new event system we don't need any kind of stack anymore */
    public $render_stack = array();

    /** @var  ResourceMap Current web-application resource map */
    public $map;

    /** @var Module[] Collection of loaded modules */
	public $module_stack = array();
	
	/** @var Module Pointer to current active module */
    protected $active = null;
	
	/** @var bool Flag for outputting layout template, used for asynchronous requests */
	protected $async = FALSE;	
	
	/** @var string Path to main system template */
	protected $template_path = __SAMSON_DEFAULT_TEMPLATE;
	
	/** @var string Path to current web-application */
	public $system_path = __SAMSON_CWD__;
	
	/** @var string View path modifier for templates */
	protected $view_path = '';

	/** @var string View path loading mode */
	public $render_mode = self::RENDER_STANDART;

    /** @var string  composer lock file name */
    public $composerLockFile = 'composer.lock';

    /**
     * Change current system working environment
     * @param string $environment Environment identifier
     * @return self Chaining
     */
    public function environment($environment = \samsonos\config\Scheme::BASE)
    {
        // Signal core environment change
        \samsonphp\event\Event::signal('core.environment.change', array($environment, &$this));

        return $this;
    }

    /**
     * @see \samson\core\iCore::resources()
     * @deprecated Use ResourceMap::find()
     */
	public function resources( & $path, & $ls = array(), & $files = null )
	{
        if (!isset( $this->load_path_stack[$path])) {
            // Get the resource map for this entry point
            $resourceMap = ResourceMap::get($path);
            // Collection for gathering all resources located at module path, grouped by extension
            $ls['resources'] = $resourceMap->resources;
            $ls['controllers'] = $resourceMap->controllers;
            $ls['models'] = $resourceMap->models;
            $ls['views'] = $resourceMap->views;
            $ls['php'] = $resourceMap->php;

            // Save path resources data
            $this->load_path_stack[ $path ] = & $ls;

            return true;
        } else {
            $ls = $this->load_path_stack[ $path ];
        }

        return false;
	}

	/** @see \samson\core\iCore::load() */
	public function load($path = NULL, $module_id = null)
	{
        /** @var ResourceMap $resourceMap Pointer to resource map object */
        $resourceMap = ResourceMap::get($path);

        // Check if we have found SamsonPHP external module class
        if (isset($resourceMap->module[0])) {
            /** @var string $controllerPath Path to module controller file */
            $controllerPath = $resourceMap->module[1];

            /** @var string $moduleClass Name of module controller class to load */
            $moduleClass = $resourceMap->module[0];

            // Define default module identifier if it is not passed
            $module_id = isset($module_id) ? $module_id : AutoLoader::oldClassName($moduleClass);

            // Require module controller class into PHP
            if (file_exists($controllerPath)) {
                //elapsed('+ ['.$module_id.'] Including module controller '.$controllerPath);
                require($controllerPath);
            }

            // TODO: this should be done via composer autoload file field
            // Iterate all function-style controllers and require them
            foreach ($resourceMap->controllers as $controller) {
                require($controller);
            }

            /** @var \samson\core\ExternalModule $connector Create module controller instance */
            // TODO: Add  Resource map support to modules, move from old array
            $connector = new $moduleClass($path, $module_id, $ls = $resourceMap->toLoadStackFormat());

            // Get module identifier
            $module_id = $connector->id();

            // Fire core module load event
            \samsonphp\event\Event::fire('core.module_loaded', array($module_id, &$connector));

            // Signal core module configure event
            \samsonphp\event\Event::signal('core.module.configure', array(&$connector, $module_id));

            // TODO: Think how to decouple this
            // Call module preparation handler
            if (!$connector->prepare()) {
                // Handle module failed preparing
            }

            // TODO: Add ability to get configuration from parent classes
            // TODO: Code lower to be removed, or do we still need this

            // Get module name space
            $ns = AutoLoader::getOnlyNameSpace($moduleClass);

            // Save module resources
            $this->load_module_stack[$module_id] = $ls;

            // Check for namespace uniqueness
            if( !isset($this->load_stack[ $ns ])) $this->load_stack[ $ns ] = & $ls;
            // Merge another ns location to existing
            else $this->load_stack[ $ns ] = array_merge_recursive ( $this->load_stack[ $ns ], $ls );

            // Trying to find parent class for connecting to it to use View/Controller inheritance
            $parent_class = get_parent_class( $connector );
            if ($parent_class !== AutoLoader::className('samson\core\ExternalModule')) {
                // Переберем загруженные в систему модули
                foreach ($this->module_stack as & $m){
                    // Если в систему был загружен модуль с родительским классом
                    if (get_class($m) == $parent_class) {
                        $connector->parent = & $m;
                        //elapsed('Parent connection for '.$moduleClass.'('.$connector->uid.') with '.$parent_class.'('.$m->uid.')');
                    }
                }
            }
        } else {// Signal error
            e('Cannot load module from: "##"', D_SAMSON_DEBUG, $path);
        }
		
		// Chaining
		return $this;
	}
	
	
	/** @see \samson\core\iCore::render() */
	public function render( $__view, $__data = array() )
	{
		////elapsed('Start rendering '.$__view);

        // TODO: Make rendering as external system, to split up these 3 rendering options
		
		// Объявить ассоциативный массив переменных в данном контексте
		if( is_array( $__data ))extract( $__data );	
		
		// Начать вывод в буффер
		ob_start();
		
		// Path to another template view, by default we are using default template folder path,
		// for meeting first condition
		$__template_view = $__view;
			
		if (locale() != SamsonLocale::DEF)
		{
			// Modify standard view path with another template
			$__template_view = str_replace( __SAMSON_VIEW_PATH, __SAMSON_VIEW_PATH.locale().'/', $__template_view );
		}
		
		// Depending on core view rendering model
		switch ( $this->render_mode )
		{
			// Standard algorithm for view rendering
			case self::RENDER_STANDART: 
				// Trying to find another template path, by default it's an default template path
				if( file_exists( $__template_view ) ) include( $__template_view ); 
				// If another template wasn't found - we will use default template path
				else if( file_exists( $__view ) ) include( $__view );
				// Error no template view was found 
				else e('Cannot render view(##,##) - file doesn\'t exists', E_SAMSON_RENDER_ERROR, array( $__view, $this->view_path ) );
			break;			
			
			// View rendering algorithm form array of view pathes 
			case self::RENDER_ARRAY:				
				// Collection of view pathes
				$views = & $GLOBALS['__compressor_files'];
				// Trying to find another template path, by default it's an default template path
				if( isset($views[ $__template_view ]) && file_exists( $views[ $__template_view ] ) ) include( $views[ $__template_view ] );
				// If another template wasn't found - we will use default template path
				else if( isset($views[ $__view ]) && file_exists( $views[ $__view ] ) ) include( $views[ $__view ] );
				// Error no template view was found
				else e('Cannot render view(##,##) - file doesn\'t exists', E_SAMSON_RENDER_ERROR, array( $views[ $__view ], $this->view_path ) );			
			break;
			
			// View rendering algorithm from array of view variables
			case self::RENDER_VARIABLE:
				// Collection of views
				$views = & $GLOBALS['__compressor_files'];
				// Trying to find another template path, by default it's an default template path
				if( isset($views[ $__template_view ])) eval(' ?>'.$views[ $__template_view ].'<?php ');
				// If another template wasn't found - we will use default template path
				else if( isset($views[ $__view ])) eval(' ?>'.$views[ $__view ].'<?php ');
				// Error no template view was found
				else e('Cannot render view(##,##) - view variable not found', E_SAMSON_RENDER_ERROR, array( $__view, $this->view_path ) );			
			break;
		}
		
		// Получим данные из буффера вывода
		$html = ob_get_contents();
		
		// Очистим буффер
		ob_end_clean();

        // Fire core render event
        \samsonphp\event\Event::fire('core.render', array(&$html, &$__data, &$this->active));

		// Iterating throw render stack, with one way template processing
		foreach ( $this->render_stack as & $renderer )
		{
			// Выполним одностороннюю обработку шаблона
			$html = call_user_func( $renderer, $html, $__data, $this->active );
		}		
		
		////elapsed('End rendering '.$__view);		
		return $html ;
	}

    //[PHPCOMPRESSOR(remove,start)]
    /**
     * Generic wrap for Event system subscription
     * @see \samson\core\\samsonphp\event\Event::subscribe()
     *
     * @param string   $key     Event identifier
     * @param callable $handler Event handler
     * @param array    $params  Event parameters
     *
     * @return $this Chaining
     */
    public function subscribe($key, $handler, $params = array())
    {
        \samsonphp\event\Event::subscribe($key, $handler, $params);

        return $this;
    }
    //[PHPCOMPRESSOR(remove,end)]
	 
	/**	@see iCore::async() */
	public function async( $async = NULL )
	{ 
		// Если передан аргумент
		if( func_num_args() ){$this->async = $async; return $this;} 
		// Аргументы не переданы - вернем статус ассинхронности вывода ядра системы
		else return $this->async; 
	}		
		
	/**	@see iCore::template() */
	public function template( $template = NULL )
	{
		// Если передан аргумент
		if( func_num_args() ){ $this->template_path = $this->active->path().$template;	} 

		// Аргументы не переданы - вернем текущий путь к шаблону системы
		return $this->template_path;
	}
	
	/** @see iCore::path() */
	public function path( $path = NULL )
	{ 			
		// Если передан аргумент
		if (func_num_args()) {
			// Сформируем новый относительный путь к главному шаблону системы
			$this->template_path = $path.$this->template_path;  
	
			// Сохраним относительный путь к Веб-приложению
			$this->system_path = $path;	
			
			// Продолжил цепирование
			return $this;
		}
		
		// Вернем текущее значение
		return $this->system_path; 		
	}	
	
	/**	@see iModule::active() */
	public function & active(iModule & $module = null)
	{
		// Сохраним старый текущий модуль		
		$old = & $this->active;
		
		// Если передано значение модуля для установки как текущий - проверим и установим его
		if (isset($module)) {
            $this->active = & $module;
        }

		// Вернем значение текущего модуля  
		return $old;
	}
			
	/**	@see iCore::module() */
	public function & module( & $_module = NULL )
	{		
		$ret_val = null;
		
		// Ничего не передано - вернем текущуй модуль системы
		if( !isset($_module) && isset( $this->active ) ) $ret_val = & $this->active;	
		// Если уже передан какой-то модуль - просто вернем его
		else if( is_object( $_module ) ) $ret_val = & $_module;
		// If module name is passed - try to find it
		else if( is_string( $_module ) && isset( $this->module_stack[ $_module ] ) ) $ret_val = & $this->module_stack[ $_module ];				
		
		//elapsed('Getting module: '.$_module);
		
		// Ничего не получилось вернем ошибку
		if( $ret_val === null ) e('Не возможно получить модуль(##) системы', E_SAMSON_CORE_ERROR, array( $_module ) );
		
		return $ret_val;
	}
	
	/** @see iCore::unload() */
	public function unload( $_id )
	{
		// Если модуль загружен в ядро
		if( isset($this->module_stack[ $_id ]) )
		{	
			// Get module instance
			$m = & $this->module_stack[ $_id ];
			
			// Remove load stack data of this module 
			$ns = nsname( get_class($m) );			
			if( isset( $this->load_stack[ $ns ] )) unset($this->load_stack[ $ns ]);
			
			// Очистим коллекцию загруженых модулей
			unset( $this->module_stack[ $_id ] );			
		}		
	}

    //[PHPCOMPRESSOR(remove,start)]
    /**
     * Insert generic html template tags and data
     * @param $template_html
     * @deprecated Must be moved to a new HTML output object
     * @return mixed Changed HTML template
     */
    public function generate_template( & $template_html )
	{
		// Добавим путь к ресурсам для браузера
		$head_html = "\n".'<base href="'.url()->base().'">';
		// Добавим отметку времени для JavaScript
		$head_html .= "\n".'<script type="text/javascript">var __SAMSONPHP_STARTED = new Date().getTime();</script>';		
		
		// Добавим поддержку HTML для старых IE
		$head_html .= "\n".'<!--[if lt IE 9]>'; 
		$head_html .= "\n".'<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>';
        $head_html .= "\n".'<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>';
		$head_html .= "\n".'<![endif]-->';
			
		// Выполним вставку главного тега <base> от которого зависят все ссылки документа
		// также подставим МЕТА-теги для текущего модуля и сгенерированный минифицированный CSS
		$template_html = str_ireplace( '<head>', '<head>'.$head_html, $template_html );

		// Вставим указатель JavaScript ресурсы в конец HTML документа
		$template_html = str_ireplace( '</html>', '</html>'.__SAMSON_COPYRIGHT, $template_html );
		
		return $template_html;
	}

    /** @see \samson\core\iCore::e404()
     * @deprecated Use Core:subscribe('core.e404', ...)
     */
	public function e404($callable = null)
	{
		// Если передан аргумент функции то установим новый обработчик e404 
		if (func_num_args()) {
            // Subscribe external handler for e404 event
            $this->subscribe('core.e404', $callable);
			
			// Chaining
			return $this;
		}
	}
    //[PHPCOMPRESSOR(remove,end)]
	
	/**	@see iCore::start() */
	public function start($default)
	{
		// TODO: Change ExternalModule::init() signature
        // Fire core started event
        \samsonphp\event\Event::fire('core.started');

        // TODO: Does not see why it should be here
        // Set main template path
        $this->template($this->template_path);

        /** @var mixed $result External route controller action result */
        $result = A_FAILED;

        // Fire core routing event
        \samsonphp\event\Event::signal('core.routing', array(&$this, &$result, $default));

        // If no one has passed back routing callback
        if (!isset($result) || $result == A_FAILED) {
            // Fire core e404 - routing failed event
            $result = \samsonphp\event\Event::signal('core.e404', array(url()->module, url()->method));
        }

		// Response
		$output = '';
	
		// If this is not asynchronous response and controller has been executed
		if (!$this->async && ($result !== A_FAILED)) {

            // Store module data
            $data = $this->active->toView();

			// Render main template
            $output = $this->render($this->template_path, $data);

            // Fire after render event
            \samsonphp\event\Event::fire('core.rendered', array(&$output, &$data, &$this->active));
		}
		
		// Output results to client
		echo $output;

        // Fire ended event
        \samsonphp\event\Event::fire('core.ended', array(&$output));
    }

	//[PHPCOMPRESSOR(remove,start)]
	/** Конструктор */
	public function __construct()
	{
        // Get correct web-application path
        $this->system_path = __SAMSON_CWD__;

        // Get web-application resource map
        $this->map = ResourceMap::get($this->system_path, false, array('src/'));

		// Connect static collection with this dynamic field to avoid duplicates
		$this->module_stack = & Module::$instances;

		// Load samson\core module
        $this->load(__SAMSON_PATH__);

        // Temporary add template worker
        $this->subscribe('core.rendered', array($this, 'generate_template'));

        // Fire core creation event
        \samsonphp\event\Event::fire('core.created', array(&$this));

        // Signal core configure event
        \samsonphp\event\Event::signal('core.configure', array($this->system_path.__SAMSON_CONFIG_PATH));
	}

    /**
     * Load system from composer.json
     * @return $this Chaining
     */
    public function composer()
    {
        $composerModules = array();

	    \samsonphp\event\Event::fire('core.composer.create', array(& $composerModules, $this->system_path));

        // Iterate requirements
        foreach ($composerModules as $requirement => $rating) {
            //elapsed('Loading module '.$requirement);

            // Use default path
            $path = __SAMSON_CWD__.__SAMSON_VENDOR_PATH.$requirement;

            // If path with underscores does not exists
            if (!file_exists($path)) {
                // Try path without underscore
                $path = str_replace('_', '/', $path);
                if (!file_exists($path)) {
                    return e('Cannot load module(from ##): "##" - Path not found', E_SAMSON_FATAL_ERROR, array($path, $requirement));
                }
            }

            // Load module
            $this->load($path);
        }


        // Load local module with all web-application resources
        $localResources = $this->map->toLoadStackFormat();

        // Manually include local module to load stack
        $this->load_stack['local'] = $localResources;
        $this->load_module_stack[ 'local' ] = $localResources;

        // Create local module and set it as active
        $this->active = new CompressableLocalModule('local', $this->system_path, $localResources);

        // Require all local module model files
        foreach ($localResources['models'] as $model) {
            // TODO: Why have to require once?
            require_once($model);
        }

        // Create all local modules
        foreach ($localResources['controllers'] as $controller) {
            // Require class into PHP
            require($controller);

            // Create module connector instance
            new CompressableLocalModule(basename($controller, '.php'), $this->system_path, $localResources);
        }

        return $this;
    }
    //[PHPCOMPRESSOR(remove,end)]

	
	/** Магический метод для десериализации объекта */
	public function __wakeup()
    {
        $this->active = & $this->module_stack['local'];
    }
	
	/** Магический метод для сериализации объекта */
	public function __sleep()
    {
        return array( 'module_stack', 'render_mode', 'view_path' );
    }
}
