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
    /**
     * Collection of paths ignored by resources collector
     * @deprecated
     */
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

    /**
     * Module paths loaded stack
     * @deprecated
     */
	public $load_path_stack = array();

    /**
     * Modules to be loaded stack
     * @deprecated
     */
	public $load_stack = array();

    /**
     * Modules to be loaded stack
     * @deprecated
     */
	public $load_module_stack = array();

    /** @var  ResourceMap Current web-application resource map */
    public $map;

    /**
     * Collection of loaded modules
     * @var Module[]
     */
	public $module_stack = array();
	
	/** Render handlers stack */
	public $render_stack = array();
	
	/**
	 * Pointer to current active module
	 * @var Module
	 */
    protected $active = null;
	
	/** Flag for outputting layout template, used for asynchronous requests */
	protected $async = FALSE;	
	
	/** Path to main system template */
	protected $template_path = __SAMSON_DEFAULT_TEMPLATE;
	
	/** Path to current web-application */
	protected $system_path = __SAMSON_CWD__;
	
	/** View path modifier for templating */
	protected $view_path = '';

	/** View path loading mode */
	public $render_mode = self::RENDER_STANDART;

    /**
     * @see \samson\core\iCore::resources()
     * @deprecated
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

        /** @var string $controllerPath Path to module controller file */
        $controllerPath = $resourceMap->module[1];

        /** @var string $moduleClass Name of module controller class to load */
        $moduleClass = $resourceMap->module[0];

        //trace($resourceMap, true);

        // Iterate and require all module global files
        foreach($resourceMap->globals as $global) {
            require_once($global);
        }

        // Define default module identifier if it is not passed
        $module_id = isset($module_id) ? $module_id : AutoLoader::oldClassName($moduleClass);

        // Require module controller class into PHP
        if (file_exists($controllerPath)) {
            //elapsed('+ ['.$module_id.'] Including module controller '.$controllerPath);
            require($controllerPath);
        }

        // Iterate all function-style controllers and require them
        foreach ($resourceMap->controllers as $controller) {
            require($controller);
        }

        /** @var \samson\core\ExternalModule $connector Create module controller instance */
        // TODO: Add  Resource map support to modules, move from old array
        $connector = new $moduleClass($path, $module_id, $ls = $resourceMap->toLoadStackFormat());

        // Get module identifier
        $module_id = $connector->id();

        // TODO: Add ability to get configuration from parent classes

        // If module configuration loaded - set module params
        if (isset(Config::$data[ $module_id ])) {
            foreach (Config::$data[ $module_id ] as $k => $v) {
                // Assign only own class properties no view data set anymore
                if (property_exists($moduleClass, $k)) {
                    $connector->$k = $v;
                }/*else {
                    e('## - Cannot assign parameter(##), it is not defined as class(##) property', E_SAMSON_CORE_ERROR, array($id, $k, $class_name));
                }*/
            }
        }

        // Prepare module mechanism
        if ($connector->prepare() === false) {
            e('## - Failed preparing module', E_SAMSON_FATAL_ERROR, $module_id);
        }

        // TODO: Code lower to be removed

        // Get module name space
        $ns = AutoLoader::getOnlyNameSpace($moduleClass);

        // Save module resources
        $this->load_module_stack[$module_id] = $ls;

        // Check for namespace uniqueness
        if( !isset($this->load_stack[ $ns ])) $this->load_stack[ $ns ] = & $ls;
        // Merge another ns location to existing
        else $this->load_stack[ $ns ] = array_merge_recursive ( $this->load_stack[ $ns ], $ls );

        // TODO: Analyze - do we still need this
        // Trying to find parent class for connecting to it to use View/Controller inheritance
        $parent_class = get_parent_class( $connector );
        if ($parent_class !== AutoLoader::className('samson\core\ExternalModule')) {
            // Переберем загруженные в систему модули
            foreach ($this->module_stack as & $m){
                // Если в систему был загружен модуль с родительским классом
                if( get_class($m) == $parent_class ) {
                    $connector->parent = & $m;
                    //elapsed('Parent connection for '.$class_name.'('.$connector->uid.') with '.$parent_class.'('.$m->uid.')');
                }
            }
        }

        //elapsed('Loaded module:'.$connector->id);
		
		// Chaining
		return $this;
	}
	
	
	/** @see \samson\core\iCore::render() */
	public function render( $__view, $__data = array() )
	{
		////elapsed('Start rendering '.$__view);
		
		// Объявить ассоциативный массив переменных в данном контексте
		if( is_array( $__data ))extract( $__data );	
		
		// Начать вывод в буффер
		ob_start();
		
		// Path to another template view, by default we are using default template folder path,
		// for meeting first condition
		$__template_view = $__view;
		
		//trace('$$__template_view'.$__template_view);
		
		// If another template folder defined 
	/*	if( isset($this->view_path{0}) )
		{
			// Modify standart view path with another template 
			$template_view = str_replace( __SAMSON_VIEW_PATH.'/', __SAMSON_VIEW_PATH.'/'.$this->view_path.'/', $__template_view );
		}	*/
			
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

		// Iterating throw render stack, with one way template processing
		foreach ( $this->render_stack as & $renderer )
		{
			// Выполним одностороннюю обработку шаблона
			$html = call_user_func( $renderer, $html, $__data, $this->active );
		}		
		
		////elapsed('End rendering '.$__view);		
		return $html ;
	}
	
	/** @see \samson\core\iCore::renderer() */
	public function renderer( $render_handler = null, $position = null )
	{
		// If nothing passed just return current render stack
		if( !func_num_args() ) return $this->render_stack;
		// If we have an argument, check if its a function
		else if( is_callable( $render_handler ) )
		{
            //TODO: Add position to insert renderer
			// Insert new renderer at the end of the stack
			return array_push( $this->render_stack, $render_handler );
		}

		// Error
		return e('Argument(##) passed for render function not callable', E_SAMSON_CORE_ERROR, $render_handler );
	}
	 
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

	/** @see \samson\core\iCore::e404() */
	public function e404($callable = null)
	{
		// Если передан аргумент функции то установим новый обработчик e404 
		if (func_num_args()) {
            // Subscribe external handler for e404 event
            Event::subscribe('core.e404', $callable);
			
			// Chaining
			return $this;
		}
	}
	
	/**	@see iCore::start() */
	public function start($default)
	{
        // Fire core started event
        Event::fire('core.started');

        // Set main template path
        $this->template($this->template_path);

        /** @var mixed $result External route controller action result */
        $result = A_FAILED;

        // Fire core routing event
        Event::fire('core.routing', array(&$this, &$result, $default));

        // If no one has passed back routing callback
        if (!isset($result) || $result == A_FAILED) {
            // Fire core e404 - routing failed event
            $result = Event::fire('core.e404', array(url()->module, url()->method), true);
        }

		// Response
		$output = '';
	
		// If this is not asynchronous response and controller has been executed
		if (!$this->async && ($result !== A_FAILED)) {
			// Render main template
            $output = $this->render($this->template_path, $this->active->toView());

            // Fire after render event
            Event::fire('core.rendered', array(&$output));
		}
		
		// Output results to client
		echo $output;

        // Fire ended event
        Event::fire('core.ended', array(&$output));
    }

	//[PHPCOMPRESSOR(remove,start)]
	/** Конструктор */
	public function __construct()
	{
		// Get backtrace to define witch script initiated core creation
		$db = debug_backtrace();	
		
		// Get local web application path for backtrace if available
		if (isset($db[1])) {
            // Get correct web-application path
            $this->system_path = normalizepath(pathname($db[1]['file'])).'/';

            // Get web-application resource map
            $this->map = ResourceMap::get($this->system_path);
        }

		// Connect static collection with this dynamic field to avoid duplicates
		$this->module_stack = & Module::$instances;

		// Load samson\core module
        $this->load(__SAMSON_PATH__);

        // Temporary add template worker
        Event::subscribe('core.rendered', array($this, 'generate_template'));

        // Fire core creation event
        Event::fire('core.created', array(&$this));
	}
	//[PHPCOMPRESSOR(remove,end)]

    /**
     * Load system from composer.json
     * @return $this Chaining
     */
    public function composer()
    {
        // Try new project structure
        $path = $this->path().'../composer.json';

        // If this is an old project structure
        if (!file_exists($path)) {
            $path = $this->path().'composer.json';
        }

        // If we have composer configuration file
        if (file_exists($path)) {
            // Read file into object
            $composerObject = json_decode(file_get_contents($path), true);

           //elapsed('Loading from composer.json');
            // If composer has requirements configured
            if (isset($composerObject['require'])) {
                // Iterate requirements
                foreach ($composerObject['require'] as $requirement => $version) {
                    // Ignore core module and work only with samsonos/* modules before they are not PSR- optimized
                    if(($requirement != 'samsonos/php_core') && (strpos($requirement, 'samsonos/') !== false)) {

                       //elapsed('Loading module '.$requirement);

                        // TODO: Make possible to use local modules when developing SamsonCMS - get relative path to main folder
                        // TODO: Make possible to automatically search for local modules firstly and only then default
                        // TODO: Make possible to automatically define depth of web-application to build proper paths to local modules
                        // TODO: Force debug message if module cannot be autoloaded by PSR-* standard

                        // Use default path
                        $path = __SAMSON_VENDOR_PATH.$requirement;

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
                }
            }

            // Load generic local module with all web-application resources
            if ($this->resources($this->system_path, $ls2)) {

                // Create local module and set it as active
                $this->active = new CompressableLocalModule('local', $this->system_path, $ls2);

                // Manually include local module to load stack
                $this->load_stack['local'] = $ls2;
                $this->load_module_stack[ 'local' ] = $ls2;

                // Require local models files
                foreach ($ls2['models'] as $model) {
                    require_once($model);
                }
            }

            // Iterate all old styled controllers
            foreach (File::dir($this->system_path.__SAMSON_CONTROLLER_PATH) as $file) {
                // Operate only with files
                if(is_file($file)) {

                    // Require class into PHP
                    require($file);

                    // Create module connector instance
                    new CompressableLocalModule(basename($file, '.php'), $this->system_path, $this->map->toLoadStackFormat());
                }
            }

        } else { // Signal configuration error
            return e('Project does not have composer.json', E_SAMSON_FATAL_ERROR);
        }

        return $this;
    }

	
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
