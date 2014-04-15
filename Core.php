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
    /** Collection of paths ignored by resources collector */
    public static $resourceIgnorePath = array(
        '.git',
        '.svn',
        '.settings',
        '.idea',
        'vendor',
        'upload',
        __SAMSON_CACHE_PATH,
        __SAMSON_TEST_PATH,
    );

	/** Module paths loaded stack */
	public $load_path_stack = array();

	/** Modules to be loaded stack */
	public $load_stack = array();
	
	/** Modules to be loaded stack */
	public $load_module_stack = array();

    /**
     * Collection of loaded modules
     * @var Module[]
     */
	public $module_stack = array();
	
	/** Render handlers stack */
	public $render_stack = array();
	
	/** Pointer to external E404 error handler */
	protected $e404 = null;
	
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
	
	/** Collection of performance benchmarks for analyzing */
	public $benchmarks = array();
	
	/** View path loading mode */
	public $render_mode = self::RENDER_STANDART;
	
	/**  
	 * Automatic class loader based on lazy loading from load_stack
	 * based on class namespace data
	 */
	private function __autoload($class)
	{
		//[PHPCOMPRESSOR(remove,start)]
		$this->benchmark( __FUNCTION__, func_get_args() );		
		//[PHPCOMPRESSOR(remove,end)]
		
		// Get just class name without ns
		$class_name = classname( $class );
		// Get just ns without class name 
		$ns = nsname( $class );
		
		// System module
		if( $ns == 'samson\core' ) return require ( __SAMSON_PATH__.$class_name.'.php' );
		// Other modules
		else 
		{
			// If we have loaded path with this namespace
			if( isset( $this->load_stack[ $ns ] ) )	$ls = & $this->load_stack[ $ns ];
			// Get last path scanned from load_path_stack for getting php files list,
			// as we can be sure this condition is met on loading another php file in iCore::load()
			else
			{ 
				end( $this->load_path_stack );
				$ls = & $this->load_path_stack[ key( $this->load_path_stack ) ];
			}
			
			// If we have php files in this entry to search for needed class
            foreach (array('php','models') as $key) {
                // Pointer to module files list
                $fileSource = & $ls[$key];
                if( isset($fileSource) && sizeof($fileSource) )
                {
                    // Simple method - trying to find class by classname
                    if( $files = preg_grep( '/\/'.$class_name.'\.php/i', $fileSource))
                    {
                        // Проверим на однозначность
                        if( sizeof($files) > 1 ) return e('Cannot autoload class(##), too many files matched ##', E_SAMSON_CORE_ERROR, array($class,$files) );

                        // Require lastarray element
                        return require_once( end($files) );
                    }
                }
            }

            // We have not found class in current module, try using PSR-* loader
            $composerPath = 'vendor/'.classnameToComposer($class).'/'.classname($class).'.php';

            // Try to load path from composer
            if(file_exists($composerPath)) {
                return require_once($composerPath);
            }

            return '';
		}
	}


    /**
     * Put benchmark record for performance analyzing
     * @param string $function  Function name
     * @param array $args       Function arguments
     * @param string $class     Classname who called benchmark
     */
	public function benchmark( $function = __FUNCTION__, $args = array(), $class = __CLASS__ )
	{		
		$this->benchmarks[] = array( 
				microtime(true)-__SAMSON_T_STARTED__, 	// Time elapsed from start
				$class.'::'.$function, 		            // Function class::name
				$args, 									// Function arguments
				memory_get_usage(true) 					// Memmory
		);		
	}
	
	
	/** @see \samson\core\iCore::resources() */
	public function resources( & $path, & $ls = array(), & $files = null )
	{	
		//[PHPCOMPRESSOR(remove,start)]
		$this->benchmark( __FUNCTION__, func_get_args() );
		//[PHPCOMPRESSOR(remove,end)]
		
		$path = normalizepath( $path.'/' );		
		
		//trace('Collecting resources from '.$path);
		
		// Check for module location
		if( !file_exists( $path ) ) return e( 'loading module from ## - path doesn\'t exists',E_SAMSON_CORE_ERROR,$path, $this );
				
		// If this module is not queued for loading
		if( ! isset( $this->load_path_stack[ $path ] ) )
		{
			// Save this path entry
			$this->load_path_stack[ $path ] = '';				
						
			// Collection for gathering all resources located at module path, grouped by extension
			$ls['resources'] = array();
			$ls['controllers'] = array();
			$ls['models'] = array();
			$ls['views'] = array();
			$ls['php'] = array();
				
			// Make pointer for pithiness
			$resources = & $ls['resources'];
			
			// Recursively scan module folders for resources if they not passed
			$files = ! isset( $files ) ? File::dir($path, null, '', $files, NULL, 0, self::$resourceIgnorePath) : $files;
		
			// Iterate module files
			foreach ( $files as $resource )
			{
				// No cache folders
				if( strpos( $resource, '/'.__SAMSON_CACHE_PATH.'/') === false )
				{
					// Get extension as resource type
					$rt = pathinfo( $resource, PATHINFO_EXTENSION );
						
					// Check if resource type array cell created
					if( !isset( $resources[ $rt ] ) ) $resources[ $rt ] = array();
						
					// Save resource file path to appropriate collection and fix possible slash issues to *nix format
					$resources[ $rt ][] = $resource;				
				}
			}		
				
			// If module contains PHP resources - lets distribute them to specific collections
			if( isset( $resources[ 'php' ] )) foreach ( $resources[ 'php' ] as $php )
			{				
				// Controllers
				if( strpos( $php, __SAMSON_CONTOROLLER_PATH ) ) $ls['controllers'][] = $php;				
				// Models
				else if ( strpos( $php, __SAMSON_MODEL_PATH ) ) $ls['models'][] = $php;
				// Views and other php files will load only when needed
				else if ( strpos( $php, __SAMSON_VIEW_PATH )) $ls['views'][] = $php;
				// Regular php file
				else $ls['php'][] = $php;
			}
			
			// New way for storing views with extension vphp - enables free module folder structure 
			if( isset( $resources[ 'vphp' ] )) foreach ( $resources[ 'vphp' ] as $php ) $ls['views'][] = $php;
			
			// Save path resources data
			$this->load_path_stack[ $path ] = & $ls;
			
			// New data
			return true;
		}
		
		// Cached data
		return false;
	}

	/** @see \samson\core\iCore::load() */
	public function load( $path = NULL, $module_id = NULL )
	{	
		//[PHPCOMPRESSOR(remove,start)]
		$this->benchmark( __FUNCTION__, func_get_args() );		
		//[PHPCOMPRESSOR(remove,end)]
		
		//elapsed('Start loading from '.$path);
		
		// If we han't scanned resources at this path
		if( $this->resources( $path, $ls ) )
		{					
			//elapsed('   -- Gathered resources from '.$path);
			
			// Let's fix collection of loaded classes 
			$classes = get_declared_classes();	

			// Controllers, models and global files must be required immediately
			// because they can consist of just functions, no lazy load available
			if(file_exists($path.__SAMSON_GLOBAL_FILE))	require_once($path.__SAMSON_GLOBAL_FILE);
			foreach ($ls['controllers'] as $php) require_once($php);
			foreach ($ls['models'] as $php) require_once($php);
			
			//elapsed('   -- Icluded models/controllers/globals from '.$path);
			
			// Iterate only php files
			foreach ( $ls['php'] as $php )
			{						
				// We must require regular files and wait to find iModule class ancestor declaration
				require_once( $php );			

				//elapsed('   -- Icluded '.$php.' from '.$path);
					
				// If we have new class declared after requiring
				$n_classes = get_declared_classes();
				if( sizeof($new_classes = array_diff( $n_classes, $classes )))
				{
					// Save new loaded classes list
					$classes = $n_classes;
				
					// Iterate new declared classes
					foreach ( $new_classes as $class_name )
					{
						// If this is ExternalModule ancestor
						if( in_array( ns_classname('ExternalModule','samson\core'), class_parents( $class_name )))
						{	
							//elapsed('   -- Found iModule ancestor '.$class_name.'('.$ns.') in '.$path);

                            // Create object
                            /** @var \samson\core\ExternalModule $connector */
                            $connector = new $class_name( $path, $module_id, $ls );
							$id = $connector->id();
							
							// Save namespace module data to load stack
							// If no namespace specified consider classname as namespace
							$ns = pathname( strtolower($class_name) );							
						
							// Save module resources
							$this->load_module_stack[ $id ] = $ls;
							
							// Check for namespace uniqueness 
							if( !isset($this->load_stack[ $ns ])) $this->load_stack[ $ns ] = & $ls;
							// Merge another ns location to existing
							else $this->load_stack[ $ns ] = array_merge_recursive ( $this->load_stack[ $ns ], $ls );
							
							//else e('Found duplicate ns(##) for class(##) ', E_SAMSON_CORE_ERROR, array( $ns, $class_name)); 
											
							//elapsed('   -- Created instance of '.$class_name.'('.$id.') in '.$path);
							
							// Module check
							if( !isset($id{0})) e('Module from ## does not have identifier', E_SAMSON_CORE_ERROR, $path );
							if( $ns != strtolower($ns)) e('Module ## has incorrect namespace ## - it must be lowercase', E_SAMSON_CORE_ERROR, array($id,$ns) );
							if( !isset($ns{0}) ) e('Module ## has no namespace', E_SAMSON_CORE_ERROR,  $id, $ns );
											
							// If module configuration loaded - set module params
							if( isset( Config::$data[ $id ] ) ) foreach ( Config::$data[ $id ] as $k => $v) 
							{
								// Assisgn only own class properties no view data set anymore
								if( property_exists( $class_name, $k ))	$connector->$k = $v;
								//else e('## - Cannot assign parameter(##), it is not defined as class(##) property', E_SAMSON_CORE_ERROR, array($id, $k, $class_name));								
							}
							
							//elapsed('   -- Configured '.$class_name.' in '.$path);
							
							// Prepare module mechanism
							if( $connector->prepare() === false ) e('## - Failed preparing module', E_SAMSON_FATAL_ERROR, $id);
													
							//elapsed('   -- Prepared '.$class_name.' in '.$path);
									
							// Trying to find parent class for connecting to it to use View/Controller inheritance
							$parent_class = get_parent_class( $connector );
							if( $parent_class !== ns_classname('ExternalModule','samson\core') )
							{
								// Переберем загруженные в систему модули
								foreach ( $this->module_stack as & $m )
								{
									// Если в систему был загружен модуль с родительским классом
									if( get_class($m) == $parent_class ) 
									{										
										$connector->parent = & $m;
										//elapsed('Parent connection for '.$class_name.'('.$connector->uid.') with '.$parent_class.'('.$m->uid.')');
									}
								}
							}
				
							// End top loop - we have found what we needed
							break 2;
						}
					}
				}
			}		
			//elapsed('End loading module from '.$path);				
		}
		// Another try to load same path
		else e('Path ## has all ready been loaded', E_SAMSON_CORE_ERROR, $path );
		
		// Chaining
		return $this;
	}
	
	
	/** @see \samson\core\iCore::render() */
	public function render( $__view, $__data = array() )
	{		
		//[PHPCOMPRESSOR(remove,start)]
		$this->benchmark( __FUNCTION__, func_get_args() );
		//[PHPCOMPRESSOR(remove,end)]
		
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
			// Modify standart view path with another template
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
		if( func_num_args() )  
		{	
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
	public function & active( iModule & $module = NULL )
	{
		// Сохраним старый текущий модуль		
		$old = & $this->active;
		
		// Если передано значение модуля для установки как текущий - проверим и установим его
		if( isset( $module ) ) $this->active = & $module;

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
	
	public function generate_template( $template_html )
	{
		// Добавим путь к ресурсам для браузера
		$head_html = "\n".'<base href="'.url()->base().'">';
		// Добавим отметку времени для JavaScript
		$head_html .= "\n".'<script type="text/javascript">var __SAMSONPHP_STARTED = new Date().getTime();</script>';		
		
		// Добавим поддержку HTML для старых IE
		$head_html .= "\n".'<!--[if lt IE 9]>'; 
		$head_html .= "\n".'<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>';
		$head_html .= "\n".'<![endif]-->';
			
		// Выполним вставку главного тега <base> от которого зависят все ссылки документа
		// также подставим МЕТА-теги для текущего модуля и сгенерированный минифицированный CSS
		$template_html = str_ireplace( '<head>', '<head>'.$head_html, $template_html );
		
		// Так сказать - копирайт =)
		$body_html = '<!-- Сайт разработан компанией SamsonOS -->';
		$body_html .= '<!-- PHP фреймфорк SamsonPHP http:://samsonos.com  -->';
		$body_html .= '<!-- Нравится PHP/HTML, хочешь узнать много нового и зарабатывать на этом деньги? Пиши info@samsonos.com -->';
		
		// Вставим указатель JavaScript ресурсы в конец HTML документа
		$template_html = str_ireplace( '</html>', '</html>'.$body_html, $template_html );
		
		return $template_html;
	}
	
	/** @see \samson\core\iCore::e404() */
	public function e404( $callable = null )
	{
		// Если передан аргумент функции то установим новый обработчик e404 
		if( func_num_args() ) 
		{
			// Set e404 handler		
			$this->e404 = $callable;
			
			// Продолжим цепирование
			return $this;
		}
		// Проверим если задан обработчик e404
		else if( is_callable( $this->e404 ) )
		{
			// Вызовем обработчик
			$result = call_user_func( $this->e404, url()->module, url()->method );
			
			// Если метод ничего не вернул - считаем что все ок!
			return isset( $result ) ? $result : A_SUCCESS;		
		}
		// Стандартное поведение
		else 
		{
			//elapsed('e404');
			// Установим HTTP заголовок что такой страницы нет
			header('HTTP/1.0 404 Not Found');
			
			// Установим представление
			echo'<h1>Запрашиваемая страница не найдена</h1>';

			// Вернем успешный статус выполнения функции
			return A_FAILED;
		}	
	}
	
	/**	@see iCore::start() */
	public function start( $default )
	{	
		// Parse URL
		url();
		
		//[PHPCOMPRESSOR(remove,start)]
		// TODO: Again module load not corresponds to local and system load functionality	
		
		// Search for remote web applications if this is local web application
		$files = File::dir( $this->system_path, null, '', $files, NULL, 0, self::$resourceIgnorePath );
		foreach ( preg_grep('/\.htaccess/iu', $files) as $web_app_path )
		{
			// If path not to local web application
			$web_app_path = pathname( $web_app_path ).'/';
			if( $web_app_path != $this->system_path )
			{
				// Create copy of files collection
				$_files = $files;
				$files = array();
					
				// Save only local web application files
				foreach ( $_files as $resource ) if( strpos( $resource, $web_app_path ) === false ) $files[] = $resource;
			}
		}
		
		// Инициализируем локальные модуль
		if( $this->resources( $this->system_path, $ls2, $files ))
		{
			// Create local module and set it as active
			$this->active = new CompressableLocalModule( 'local', $this->system_path, $ls2 );
				
			// Set main template path
			$this->template( $this->template_path );
				
			// Manually include local module to load stack
			$this->load_stack['local'] = $ls2;
			$this->load_module_stack[ 'local' ] = $ls2;
				
			// Require local controllers
			foreach ( $ls2['controllers'] as $controler )
			{
				require( $controler );
		
				// Get local module name
				$local_module = strtolower(basename( $controler, '.php' ));
					
				// Create new local compressable module
				new CompressableLocalModule( $local_module, $this->system_path, $ls2 );
			}
				
			// Require local models
			foreach ( $ls2['models'] as $model ) require_once( $model );
		}

		$this->benchmark( __FUNCTION__, func_get_args() );		

		// Проинициализируем оставшиеся конфигурации и подключим внешние модули по ним
		Config::init( $this );					
		//[PHPCOMPRESSOR(remove,end)]		
		
		// Проинициализируем НЕ ЛОКАЛЬНЫЕ модули
		foreach ($this->module_stack as $id => $module )
		{
            /** @var $module Module */

			// Только внешние модули и их оригиналы
			if( method_exists( $module, 'init') && $module->id() == $id )
			{			
				////elapsed('Start - Initializing module: '.$id);
				$module->init();
				////elapsed('End - Initializing module: '.$id);
			}
		}	
		
		////elapsed('End initing modules');
	
		// Send success status
		header("HTTP/1.0 200 Ok");
		
		// Результат выполнения действия модуля
		$module_loaded = A_FAILED;

		// Получим идентификатор модуля из URL и сделаем идентификатор модуля регистро-независимым 
		$module_name = mb_strtolower( url()->module, 'UTF-8');
				
		// Если не задано имя модуля, установим модуль по умолчанию
		if( ! isset( $module_name{0} ) ) $module_name = $default;	
		
		//elapsed('Trying to get '.$module_name.' controller');
		
		// Если модуль был успешно загружен и находится в стеке модулей ядра
		if( isset( $this->module_stack[ $module_name ] ) )
		{	
			//elapsed('Preforming '.$module_name.'::'.url()->method().' controller action');

            /**
             * Set found module as current
             * @var $active Module
             */
			$this->active = & $this->module_stack[ $module_name ];

            /**
             * Try to perform controller action
             * @var $module_loaded integer
             */
            $module_loaded = $this->active->action( url()->method );
		}
	
		// Если мы не выполнили ни одного контроллера, обработаем ошибку 404
		if( $module_loaded === A_FAILED ) $module_loaded = $this->e404();		
	
		////elapsed('Start outputing');
		
		// Сюда соберем весь вывод системы
		$template_html = '';		
	
		// Если вывод разрешен - выведем шаблон на экран
		if( ! $this->async && ($module_loaded !== A_FAILED) )
		{					
			// Прорисуем главный шаблон
			$template_html = $this->render( $this->template_path, $this->active->toView() );
			
			// Подготовим HTML код для заполнения шапки шаблона
			$head_html = '';

			//[PHPCOMPRESSOR(remove,start)]		
			// Сгенерируем необходимые элементы в HTML шаблоне
			$template_html = $this->generate_template( $template_html, '','');
			//[PHPCOMPRESSOR(remove,end)]
			
			// Добавим специальную системную комманду для инициализации фреймворка в JavaScript
			$head_html .= '
			<script type="text/javascript">			
			if(typeof SamsonPHP != "undefined"){
				SamsonPHP._uri = "'.url()->text.'";
				SamsonPHP._moduleID = "'.$this->active->id().'";
				SamsonPHP._url_base = "'.url()->base().'";
				SamsonPHP._locale = "'.locale().'";
			}
			</script>';			
			
			// Insert what we have generated
			$template_html = str_ireplace( '</head>', $head_html."\n".'</head>', $template_html );
		}
		
		// Выведем все что мы на генерили
		echo $template_html;			
		
		////elapsed('End routing');
	}		

	//[PHPCOMPRESSOR(remove,start)]
	/** Конструктор */
	public function __construct()
	{
		//elapsed('Constructor');		
		$this->benchmark( __FUNCTION__, func_get_args() );			
		
		// Get backtrace to define witch scipt initiated core creation
		$db = debug_backtrace();	
		
		// Get local web application path for backtrace if available
		if( isset($db[1]) ) $this->system_path = normalizepath( pathname($db[1]['file']) ).'/';	
				 		
		// Установим обработчик автоматической загрузки классов
		spl_autoload_register( array( $this, '__autoload'));								
		
		// Свяжем коллекцию загруженных модулей в систему со статической коллекцией
		// Созданных экземпляров класса типа "Модуль"
		$this->module_stack = & Module::$instances;		
		
		// TODO: Сделать полноценную загрузку core и local через load

		// Load samson\core module
		new System( __SAMSON_PATH__ );

		// Manually include system module to load stack
		$path = __SAMSON_PATH__;
		if( $this->resources( $path, $ls ))
		{
			$this->load_stack['core'] = & $ls;
			// Save module resources
			$this->load_module_stack[ 'core' ] = & $ls;
		}
			
		// Выполним инициализацию конфигурации модулей загруженных в ядро
		Config::load();
	}
	//[PHPCOMPRESSOR(remove,end)]

    /**
     * Load system from composer.json
     * @return $this Chaining
     */
    public function composer()
    {
        // If we have composer configuration file
        if (file_exists('composer.json')) {
            // Read file into object
            $composerObject = json_decode(file_get_contents('composer.json'), true);

            // If composer has requirements configured
            if (isset($composerObject['require'])) {
                // Iterate requirements
                foreach ($composerObject['require'] as $requirement => $version) {
                    if($requirement != 'samsonos/core') {

                        // Build path to module
                        $path = __SAMSON_VENDOR_PATH.$requirement;

                        // If path with underscores does not exists
                        if (!file_exists($path)) {
                            // Try path without underscore
                            $path = str_replace('_', '/', $path);
                            if (!file_exists($path)) {
                                e('Cannot load module: "'.$requirement.'" - Path not found');
                                continue;
                            }
                        }

                        // Load module
                        $this->load($path);
                    }
                }
            }
        }

        return $this;
    }

	
	/** Магический метод для десериализации объекта */
	public function __wakeup(){	$this->active = & $this->module_stack['local']; }
	
	/** Магический метод для сериализации объекта */
	public function __sleep(){  return array( 'module_stack', 'e404', 'render_mode', 'view_path' ); }
}