<?php 
namespace samson\core;

/**
 * Ядро фреймворка SamsonPHP
 * 
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 */
final class Core implements iCore
{		
	/** Module pathes loaded stack */
	protected $load_path_stack = array();
	
	/** Scanned module resources */
	public $load_resources = array();
	
	/** Modules to be loaded stack */
	public $load_stack = array();
	
	/** Стек загруженных в ядро модулей */
	public $module_stack = array();
	
	/** Render handlers stack */
	public $render_stack = array();
	
	/** Указатель а обработчик 404 ошибки */
	protected $e404 = null;
	
	/**
	 * Текущий активный модуль с которым работает ядро
	 * @var Module
	 */
	protected $active = null;
	
	/** Flag for outputting layout template, used for asynchronous requests */
	protected $async = FALSE;	
	
	/** Главный шаблон системы */
	protected $template_path = __SAMSON_DEFAULT_TEMPLATE;
	
	/** Путь к текущемуу Веб-приложению */
	protected $system_path = '';
	
	/** Модификатор пути к представлениям, для шаблонизации представлений */
	protected $view_path = '';

	/** Данные о загружаемом в данный момент модуле */
	protected $loaded_module = array();
	
	/** Режим работы с представлениями */
	public $render_mode = self::RENDER_STANDART;
	
	/**  
	 * Automatic class loader based onlazy loading from load_stack
	 * based on class namespace data
	 */
	private function __autoload2 ( $class )
	{			
		// Get just class name without ns
		$class_name = classname( $class );
		// Get just ns without class name 
		$ns = nsname( $class );	
		
		// System module
		if( $ns == 'samson\core' ) return require( __SAMSON_PATH__.$class_name.'.php');
		// Other modules
		else 
		{
			// If we have loaded path with this namespace
			if( isset( $this->load_stack[ $ns ] ) ) $ls = & $this->load_stack[ $ns ];
			// Try to get last path scanned from load_path_stack
			else
			{ 
				end( $this->load_path_stack );
				$ls = & $this->load_path_stack[ key( $this->load_path_stack ) ];
			}	
				
			// If we have php files in this entry to search for
			if( isset($ls['php']) && sizeof($ls['php']) )
			{
				// Simple method - trying to find class by classname
				if( $files = preg_grep( '/\/'.$class_name.'\.php/', $ls['php']))
				{
					// Проверим на однозначность
					if( sizeof($files) > 1 ) return e('Cannot autoload class(##), too many files matched ##', E_SAMSON_CORE_ERROR, array($class,$files) );
					
					// Require lastarray element
					return require( end($files) );
				}
			}		 
			
			e('Cannot autoload class(##), class not found', E_SAMSON_CORE_ERROR, $class );
		}
	}
	
	public function resources( & $path, & $ls = array() )
	{
		$path = normalizepath( $path.'/' );		
		
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
				
			// Recursively scan module folders for resources
			foreach (File::dir( $path ) as $resource )
			{
				// No cache folders
				if( strpos( $resource, '/'.__SAMSON_CACHE_PATH.'/') === false )
				{
					// Get extension as resource type
					$rt = pathinfo( $resource, PATHINFO_EXTENSION );
						
					// Check if resource type array cell created
					if( !isset( $resources[ $rt ] ) ) $resources[ $rt ] = array();
						
					// Save resource file path to appropriate collection and fix possible slash issues to *nix format
					$resources[ $rt ][] = normalizepath( $resource );
				}
			}
		
			// Combine module resources with global resources collection
			$this->load_resources = array_merge_recursive( $this->load_resources, $resources );
			
			// If module contains PHP resources - lets distribute them to specific collections
			if( isset( $resources[ 'php' ] )) foreach ( $resources[ 'php' ] as $php )
			{				
				if( strpos( $php, __SAMSON_CONTOROLLER_PATH ) ) $ls['controllers'][] = $php;				
				else if ( strpos( $php, __SAMSON_MODEL_PATH ) ) $ls['models'][] = $php;
				// Views and other php files will load only when needed
				else if ( strpos( $php, __SAMSON_VIEW_PATH ) ) 	$ls['views'][] = $php;
				// Regular php file
				else $ls['php'][] = $php;
			}
			
			// Save path resources data
			$this->load_path_stack[ $path ] = & $ls;
			
			// New data
			return true;
		}
		
		// Cached data
		return false;
	}
	
	/** @see \samson\core\iCore::load2() */
	public function load2( $path = NULL )
	{	
		//elapsed('Start loading from '.$path);
		
		// If we han't scanned resources at this path
		if( $this->resources( $path, $ls ) )
		{					
			//elapsed('   -- Gathered resources from '.$path);
			
			// Let's fix collection of loaded classes 
			$classes = get_declared_classes();	

			// Controllers, models and global files must be required immediately
			// because they can consist of just functions, no lazy load available
			if(file_exists($path.__SAMSON_GLOBAL_FILE))	require($path.__SAMSON_GLOBAL_FILE);		
			foreach ($ls['controllers'] as $php) require($php);
			foreach ($ls['models'] as $php) require($php);
			
			//elapsed('   -- Icluded models/controllers/globals from '.$path);
			
			// Iterate only php files
			foreach ( $ls['php'] as $php) 
			{	
				// We must require regular files and wait to find iModule class ancestor declaration
				require_once( $php );
				
				//elapsed('   -- Icluded '.$php.' from '.$path);
					
				// If we have new class declared after requiring
				if( sizeof($new_classes = array_diff( get_declared_classes(), $classes )))
				{
					// Save new loaded classes list
					$classes = get_declared_classes();
				
					// Iterate new declared classes
					foreach ( $new_classes as $class_name )
					{
						// If this is ExternalModule ancestor
						if( in_array( ns_classname('ExternalModule','samson\core'), class_parents( $class_name )))
						{							
							//elapsed('   -- Found iModule ancestor '.$class_name.' in '.$path);
							
							// Save namespace module data to load stack
							$ns = pathname( $class_name );				
							$this->load_stack[ $ns ] = & $ls;
				
							// Create object
							$connector = new $class_name( $path );						
								
							$id = $connector->id();
							$ls['classname'] = $class_name;
							$ls['namespace'] = $ns;			

							//elapsed('   -- Created instance of '.$class_name.' in '.$path);
							
							// Module check
							if( !isset($id{0})) e('Module from ## has doens not have identifier', E_SAMSON_CORE_ERROR, $path );
							//if( $ns != strtolower($ns)) e('Module ## has incorrect namespace ## - it must be lowercase', E_SAMSON_CORE_ERROR, array($id,$ns) );
							//if( !isset($ns{0}) ) e('Module ## has no namespace', E_SAMSON_CORE_ERROR,  $id, $ns );
											
							// If module configuration loaded - set module params
							if( isset( Config::$data[ $id ] ) ) foreach ( Config::$data[ $id ] as $k => $v) 
							{
								// Assisgn only own class properties no view data set anymore
								if( property_exists( $class_name, $k ))	$connector->$k = $v;
								//else e('## - Cannot assign parameter(##), it is not defined as class(##) property', E_SAMSON_CORE_ERROR, array($id, $k, $class_name));								
							}
							
							//elapsed('   -- Configured '.$class_name.' in '.$path);
							
							// Prepare module mechanism
							if( !$connector->prepare())
							{
								e('## - Failed preparing module', E_SAMSON_FATAL_ERROR, $id);
							}
							
							//elapsed('   -- Prepared '.$class_name.' in '.$path);
									
							// Trying to find parent class for connecting to it to use View/Controller inheritance
							$parent_class = get_parent_class( $connector );
							if( $parent_class !== ns_classname('ExternalModule','samson\core') )
							{
								// Переберем загруженные в систему модули
								foreach ( $this->module_stack as & $m )
								{
									// Если в систему был загружен модуль с родительским классом
									if( get_class($m) == $parent_class ) $connector->parent = & $m;
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
	public function render( $__view, array $__data = array() )
	{		
		////elapsed('Start rendering '.$__view);
		
		// Объявить ассоциативный массив переменных в данном контексте
		extract( $__data );	
		
		// Начать вывод в буффер
		ob_start();
		
		// Path to another template view, by default we are using default template folder path,
		// for meeting first condition
		$__template_view = $__view;
		
		// If another template folder defined 
		if( isset($this->view_path{0}) )
		{
			// Modify standart view path with another template 
			$template_view = str_replace( __SAMSON_VIEW_PATH.'/', __SAMSON_VIEW_PATH.'/'.$this->view_path.'/', $template_view );
		}
		
		// Depending on core view rendering model
		switch ( $this->render_mode )
		{
			// Standart algorithm for view rendering
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
			array_push( $this->render_stack, $render_handler ); 
		}
		// Error
		else return e('Argument(##) passed for render function not callable', E_SAMSON_CORE_ERROR, $render_handler );
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
		else return $this->template_path;
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
			
			// Установим путь к локальному модулю
			$this->module_stack['local']->path( $this->system_path );
			
			// Выполним инициализацию ядра
			$this->init();
			
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
		if( !isset($_module) && isset( $this->active ) ) $ret_val = $this->active;	
		// Если уже передан какой-то модуль - просто вернем его
		else if( is_object( $_module ) ) $ret_val = $_module;
		// Если передано имя модуля	
		else
		{
			// Получим регистро не зависимое имя модуля 
			$_module = mb_strtolower( $_module, 'UTF-8' );			
			
			// Если передано имя модуля то попытаемся его найти в стеке модулей
			if( isset( $this->module_stack[ $_module ] ) ) $ret_val = $this->module_stack[ $_module ];			
		}		
		
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
			// Очистим коллекцию загруженых модулей
			unset( $this->module_stack[ $_id ] );			
		}		
	}

	/** @see iCore::load() */
	public function load( $id, $path = NULL, $params = array() )
	{		
		return $this->load2( $path );
		
		//elapsed('----------------------');
		//elapsed('Start loading module '.$id);
		
		// Получим регистро не зависимый идентификатор модуля
		$ci_id = mb_strtolower( $id, 'UTF-8' );	
		
		// Если в систему загружена нужная конфигурация для загружаемого модуля - получим её
		if( isset( Config::$data[ $ci_id ] ) ) $params = array_merge( Config::$data[ $ci_id ], $params );
		
		//elapsed(' --Config loading finished');
				
		// Если мы еще не загрузили модуль в ядро
		if( ! isset( $this->module_stack[ $ci_id ] ) )
		{		
			// Если не указан путь к модулю - считаем что он локальный
			if( ($path === 'local') || (!isset( $path )) )
			{						
				// Создадим локальный модуль
				$module = new CompressableLocalModule( $ci_id, $this->system_path, $params  );				
				
				// Сохраним путь к файлу модели
				$model_path = $this->system_path.__SAMSON_MODEL_PATH.'/'.$ci_id.'.php';
				
				// Подключим файл модели если он существует
				if( file_exists( $model_path ) ) require $model_path;
				
				// Путь к файлу контроллера модуля
				$controller_path = $this->system_path.__SAMSON_CONTOROLLER_PATH.'/'.$ci_id.'.php';
				
				// Подключим файл контроллера если он существует
				if( file_exists( $controller_path ) ) require $controller_path;
				
				//elapsed(' --Creating local module instance finished');
			}			
			// Путь к модулю задан - считаем его внешний			
			else 
			{	
				// Обязательно добавим в конец пути к модуля слеш для правильности формирования
				// внутенних путей модуля
				if( $path[ strlen($path) - 1 ] != '/' ) $path .= '/';
				
				// Получим аргументы функции как параметры загружаемого модуляы
				$this->loaded_module = array( 
					'id' 		=> $id, 
					'path' 		=>	$path, 
					'params'	=> $params, 
					'files' 	=> FILE::dir( $path, 'php' )
				);		

				//elapsed(' --Getting module structure finished');
				
				// Получим список загруженных классов
				$classes = get_declared_classes();
			
				// Совместимость со старыми модулями
				if( file_exists( $path.'include.php') )
				{
					// Прочитаем старый файл подключения
					$include = file_get_contents($path.'include.php');
					
					// Уберем из него все не нужное
					$include = str_replace( array('<?php','<?','?>'), '', preg_replace('/(require|require_once)\s*[^;]*\;/', '', $include ));
					
					// Загрузим оставшиеся в систему
					eval($include);
				}

				// Переберем все файлы модуля и подключим их
				foreach ( $this->loaded_module['files'] as $file ) 
				{					
					// Пропустим папку представлений
					if( strpos( $file, __SAMSON_VIEW_PATH.'/' )  ) continue;
					
					////elapsed(' --Loading file: '.$file);
					
					// Совместимость со старыми модулями
					if( basename($file) == 'include.php' ) continue;					
					// Просто подключи файл модуля
					else require_once( $file );									
				}		

				//elapsed(' --Include file loading finished');
				
				// Получим список новых загруженных классов			
				foreach ( array_diff( get_declared_classes(), $classes ) as $class ) 
				{
					// Если этот класс является потомком модуля
					if( in_array( ns_classname('ExternalModule','samson\core'), class_parents( $class )))
					{							
						// Создадим экземпляр класса для подключения модуля
						$connector = new $class( $ci_id, $path, $params );
					
						// Получим родительский класс
						$parent_class = get_parent_class( $connector );
		
						// Проверим родительский класс
						if( $parent_class !== ns_classname('ExternalModule','samson\core') )
						{					
							// Переберем загруженные в систему модули
							foreach ( $this->module_stack as & $m )
							{
								// Если в систему был загружен модуль с родительским классом
								if( get_class($m) == $parent_class ) $connector->parent = & $m;
							}
						}

						// Прекратим поиски
						break;
					}
				}

				//elapsed(' --Creating module instance finished');
			}		
		}
		
		//elapsed('end loading module '.$id);
		
		// Продожим "ЦЕПИРОВАНИЕ"
		return $this;
	}		
	
	public function generate_template( $template_html, $css_url, $js_url )
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
		$copyright = '<a style="display:none" target="_blank" href="http://samsonos.com" title="Сайт разработан SamsonOS">Сайт разработан SamsonOS</a>';
		// Вставим указатель JavaScript ресурсы в конец HTML документа
		$template_html = str_ireplace( '</body>', $copyright.'</body>', $template_html );
			
		// Добавим в конец предложения по работе
		$template_html .= '<!-- PHP фреймфорк SamsonPHP http:://samsonos.com  -->';
		$template_html .= '<!-- Нравится PHP/HTML, хочешь узнать много нового и зарабатывать на этом деньги? Пиши info@samsonos.com -->';
		
		return $template_html;
	}
	
	/** @see \samson\core\iCore::e404() */
	public function e404( $callable = null )
	{
		// Если передан аргумент функции то установим новый обработчик e404 
		if( func_num_args() ) 
		{
			// Check e404 handler
			if( !is_callable( $callable ) ) return e('E404 handler is not valid', E_SAMSON_CORE_ERROR ); 
			else $this->e404 = $callable;
			
			// Продолжим цепирование
			return $this;
		}
		// Проверим если задан обработчик e404
		else if( is_callable( $this->e404 ) )
		{
			// Вызовем обработчик
			$result = call_user_func( $this->e404, url()->module(), url()->method() );
			
			// Если метод ничего не вернул - считаем что все ок!
			return isset( $result ) ? $result : A_SUCCESS;		
		}
		// Стандартное поведение
		else 
		{
			// Установим HTTP заголовок что такой страницы нет
			header('HTTP/1.0 404 Not Found');
			
			// Установим представление
			$this->active->html('<h1>Запрашиваемая страница не найдена</h1>')->title('Страница не найдена');

			// Вернем успешный статус выполнения функции
			return A_SUCCESS;
		}	
	}
	
	/**	@see iCore::start() */
	public function start( $default )
	{					
		//elapsed('Start routing');	
		//[PHPCOMPRESSOR(remove,start)]				
		// Проинициализируем оставшиеся конфигурации и подключим внешние модули по ним
		Config::init( $this );					
		//[PHPCOMPRESSOR(remove,end)]
		
		// Проинициализируем НЕ ЛОКАЛЬНЫЕ модули
		foreach ($this->module_stack as $id => $module )
		{		
			// Только внешние модули и их оригиналы
			if( method_exists( $module, 'init') && $module->id() == $id )
			{			
				////elapsed('Start - Initializing module: '.$id);
				$module->init();
				////elapsed('End - Initializing module: '.$id);
			}
		}

		////elapsed('End initing modules');
		
		// Результат выполнения действия модуля
		$module_loaded = A_FAILED;

		// Получим идентификатор модуля из URL и сделаем идентификатор модуля регистро-независимым 
		$module_name = mb_strtolower( url()->module(), 'UTF-8');		
			
		// Если не задано имя модуля, установим модуль по умолчанию
		if( ! isset( $module_name{0} ) ) $module_name = $default;	
		
		//elapsed('Trying to get '.$module_name.' controller');
	
		// Если модуль был успешно загружен и находится в стеке модулей ядра
		if( isset( $this->module_stack[ $module_name ] ) )
		{				
			// Установим требуемый модуль как текущий
			$this->active = & $this->module_stack[ $module_name ];			
			
			// Определим класс текущего моуля
			$module_class = get_class( $this->active );				

			// Попытаемся выполнить действие модуля указанное в URL, переданим тип HTTP запроса
			$module_loaded = $this->active->action( url()->method() );

			//elapsed('Preforming '.$module_name.'::'.url()->method().' controller action');
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
			
			// Создадим МЕТА теги для выводимой страницы			
			if( isset($this->active->keywords) ) 	$head_html .= '<meta name="keywords" content="' . $this->active->keywords . '">';
			if( isset($this->active->description) ) $head_html .= '<meta name="description" content="' . $this->active->description . '">';
				
			//[PHPCOMPRESSOR(remove,start)]		
			// Сгенерируем необходимые элементы в HTML шаблоне
			$template_html = $this->generate_template( $template_html, '','');
			//[PHPCOMPRESSOR(remove,end)]
			
			// Добавим специальную системную комманду для инициализации фреймворка в JavaScript
			$head_html .= '<script type="text/javascript">var __SAMSONPHP_STARTED = new Date().getTime();if(SamsonPHP){SamsonPHP._uri = "'.url()->text().'"; SamsonPHP._moduleID = "'.$this->active->id().'";SamsonPHP._url_base = "'.url()->base().'";SamsonPHP._locale = "'.locale().'";}</script>';			
			
			// Выполним вставку главного тега <base> от которого зависят все ссылки документа
			// также подставим МЕТА-теги для текущего модуля и сгенерированный минифицированный CSS 
			$template_html = str_ireplace( '</head>', $head_html.'</head>', $template_html );				
			
			// Профайлинг PHP
			$template_html .= '<!-- '.profiler().' -->';
			$template_html .= '<!-- Использовано памяти: '.round(memory_get_usage(true)/1000000,1).' МБ -->';			
			if( function_exists('db')) $template_html .= '<!-- '.db()->profiler().' -->';
		}
		
		// Выведем все что мы на генерили
		echo $template_html;	
		
		////elapsed('End routing');
	}
	
	/** Load local resources and modules */
	private function init()
	{			
		// Gather local resources for this path once
		$path = $this->path();
		if( $this->resources( $path, $ls ))
		{			
			// Require local controllers 
			foreach ( $ls['controllers'] as $controler ) 
			{
				require( $controler );
				
				// Get local module name
				$local_module = basename( $controler, '.php' );
					
				// Create new local compressable module
				new CompressableLocalModule( $local_module, $this->system_path );
			}
			
			// Require local models
			foreach ( $ls['models'] as $model ) require( $model );
		}	
	}
	
	//[PHPCOMPRESSOR(remove,start)]	
	/** Обработчик автоматической загрузки классов модулей */
	public function __autoload ( $class )
	{	
		// Получим имя самого класса
		$class_name = basename($class);
		
		// Если мы знаем какой модуль загружается в данный моммент
		if( isset( $this->loaded_module ) && sizeof( $this->loaded_module ) )
		{			
			// Найдем в списке файлов нужный нам по имени класса, так как мы незнаем
			// структуру каталогов модуля поищем класс символьным поиском в списке всех файлов
			// модуля
			if($files = preg_grep( '/\/'.$class_name.'\.php/', $this->loaded_module['files']))
			{				
				// Проверим на однозначность
				if( sizeof($files) > 1 ) return e('Невозможно определить файл для класса: ##', E_SAMSON_CORE_ERROR, $class );
				
				// Получим путь к файлу и подключим нужный файл
				foreach ( $files as $k => $file ) require_once( $file );				
			}			
		}	
		// Загрузка класса ядра системы
		else if( strpos( $class, 'samson\core\\' ) !== false ) require str_replace('samson\core'.__NS_SEPARATOR__, '', $class).'.php';	
	}
	//[PHPCOMPRESSOR(remove,end)]
	
	/** Конструктор */
	public function __construct()
	{			
		//[PHPCOMPRESSOR(remove,start)]
		// Установим обработчик автоматической загрузки классов
		spl_autoload_register( array( $this, '__autoload2'));
		//spl_autoload_register( array( $this, '__autoload'));
		//[PHPCOMPRESSOR(remove,end)]
				
		// Установим полный путь к рабочей директории
		$this->system_path = __SAMSON_CWD__.'/';		
		
		// Свяжем коллекцию загруженных модулей в систему со статической коллекцией
		// Созданных экземпляров класса типа "Модуль"
		$this->module_stack = & Module::$instances;		
		
		//$this->load2( __SAMSON_PATH__ );
	
		// Создадим специальный модуль для PHP
		new PHP();	
		
		// Load samson\core module
		new System( __SAMSON_PATH__ );
		
		// Create local module and set it as active
		$this->active = new CompressableLocalModule( 'local', $this->system_path );			
				
		// Инициализируем локальные модуль
		$this->init();
			
		// Выполним инициализацию конфигурации модулей загруженных в ядро
		Config::load();
	}
	
	/** Магический метод для десериализации объекта */
	public function __wakeup(){	$this->active = & $this->module_stack['local']; }
	
	/** Магический метод для сериализации объекта */
	public function __sleep(){ return array( 'module_stack', 'e404', 'render_mode', /*'render_stack',*/ 'view_path' ); }
}