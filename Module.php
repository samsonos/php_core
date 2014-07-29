<?php	
namespace samson\core;

// TODO: Разобраться почему с вызовом m()->render() во вьюхе, и почему не передаются параметры

/**
 * Модуль системы
 * 
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 1.0
 */
class Module implements iModule, \ArrayAccess, iModuleViewable 
{
	/** Static module instances collection */	
	public static $instances = array();	
	
	/** Uniquer identifier to check pointers */
	public $uid;
	
	/** Collection for callable controllers of module */
	protected $controllers = array();
	
	/** Module views collection */
	protected $views = array();
	
	/** Module location */
	protected $path = '';
	
	/** Unique module identifier */
	protected $id = '';
	
	/** Default module version */
	protected $version = '0.0.1';
	
	/** Path to view for rendering */
	protected $view_path = self::VD_POINTER_DEF;
	
	/** Pointer to view data entry */
	protected $data = array( self::VD_POINTER_DEF => array( self::VD_HTML => '' ) );
	
	/** Collection of data for view rendering, filled with default pointer */
	protected $view_data = array( self::VD_POINTER_DEF => array( self::VD_HTML => '' ) );	
	
	/** Name of current view context entry */
	protected $view_context = self::VD_POINTER_DEF;
	
	/** Unique module cache path in local web-application */
	protected $cache_path;
	
	/**
	 * Perform module view context switching 
	 * @param string $view_path New view context name
	 */
	protected function viewContext( $view_path )
	{			
		// Pointer to NEW view data context
		$new = & $this->view_data[ $view_path ];
		
		// Pointer to OLD view data context
		$old = & $this->view_data[ $this->view_context ];
		
		// If we are trying to switch to NEW view context
		if( $this->view_context !== $view_path ) 		
		{
			//elapsed( $this->id.' - Switching view context from '.$this->view_context.' to '.$view_path );		
			
			// Create new entry in view data collection if it does not exists
			if( ! isset( $this->view_data[ $view_path ] ) ) 
			{
				// Create new view data record 
				$new = array();
			
				// If current view data context has view data 
				if( isset( $old )  )
				{
					//elapsed($old);
					//elapsed( $this->id.' - Copying previous view context view data '.$this->view_context.' to new view context '.$view_path.'('.sizeof($old).')');
						
					// Copy default view context view data to new view context
					$new = array_merge( $new, $old );							
				}
				
				// Clear plain HTML for new view context
				$new[ self::VD_HTML ] = '';
			}
			
			// Change view data pointer to appropriate view data entry
			$this->data = & $new;

			// Save current context name
			$this->view_context = $view_path;
		}
		//else elapsed( $this->id.' - NO need to switch view context from '.$this->view_context.' to '.$view_path );	
	}
	
	/** Sort array by string length */
	protected function sortStrings( $a, $b ){ return strlen($b) - strlen($a); }
	
	/**
	 * Find view file by its part in module view resources and return full path to it
	 * @param string $view_path Part of path to module view file
	 * @return string Full path to view file
	 */
	public function findView( $view_path )
	{				
		// Remove file extension for correct array searching
		$view_path = str_replace( array('.php','.vphp'), '', $view_path );		
		
		// Try to find passed view_path in  resources views collection
		if( sizeof($view = preg_grep('/'.addcslashes($view_path,'/\\').'(\.php|\.vphp)/ui', $this->views )) )
		{		
			// Sort view pathes to get the shortest path	
			usort( $view, array( $this, 'sortStrings') );
			
			// Set current full view path as last found view
			return end( $view );
		}		
		else return false;
		{
			//elapsed($this->views);
			//return e('Cannot find ## view ## - file does not exists', E_SAMSON_RENDER_ERROR, array( $this->id, $view_path));
		}
	}
	
	
	/**	@see iModule::title() */
	public function title( $title = NULL )
    {
        return $this->set( 'title', $title );
    }
	
	/**	@see iModule::id() */
	public function id(){
        return $this->id;
    }
	
	/** @see iModule::set() */
	public function set( $field, $value = NULL )
    {
        $this->__set($field, $value);

        return $this;
    }
	
	/** @see iModuleViewable::toView() */
	public function toView( $prefix = NULL, array $restricted = array() )
	{
		// Get all module data variables
		$view_data = array_merge($this->data, get_object_vars($this));

		// Remove plain HTML from view data
		unset($view_data[ self::VD_HTML ]);
		
		return $view_data;
	}
	
	/** @see iModule::path() */
	public function path( $value = NULL )
	{		
		// Если передан параметр - установим его
		if( func_num_args() )
		{ 
			$this->path = normalizepath($value);	
			
			return $this; 
		}		
		// Вернем относительный путь к файлам модуля
		else return $this->path;
	}
	
	/** @see iModule::html() */
	public function html( $value = NULL )
	{
		//elapsed($this->id.' - Setting HTML for '.array_search(  $this->data, $this->view_data ).'('.strlen($value).')');
		
		// Если передан параметр то установим его
		if( func_num_args() ) $this->data[ self::VD_HTML ] = $value;
		// Вернем значение текущего представления модели
		else return $this->data[ 'html' ];
		
		return $this;
	}
	
	/** @see iModule::view() */
	public function view( $view_path )
	{	
		// Find full path to view file
		$view_path = $this->findView( $view_path );
		
		//elapsed($this->id.' - Changing current view to '.$view_path);
			
		// Switch view context to founded module view
		$this->viewContext( $view_path );

		// Set current view path 
		$this->view_path = $view_path;		
			
		// Продолжим цепирование
		return $this;
	}	
	
	/**	@see iModule::output() */
	public function output( $view_path = null )
	{	
		// If view path not specified - use current correct view path
		if( !isset( $view_path ) ) $view_path = $this->view_path;
		// Direct rendering of specific view, not default view data entry
		else if( isset( $view_path{0} ) ) $view_path = $this->findView( $view_path );		

		
		// Switch view context to new module view		
		$this->viewContext( $view_path );		

		//elapsed($this->id.' - Outputing '.$view_path.'-'.sizeof($this->data));
		//elapsed(array_keys($this->view_data));		

		// Get current view context plain HTML
		$out = $this->data[ self::VD_HTML ];
				
		// If view path specified
		if( isset( $view_path {0}) )
		{	
			// Временно изменим текущий модуль системы
			$old = s()->active( $this );			
				
			// Прорисуем представление модуля
			$out .= s()->render( $this->path.$view_path, $this->data );
			
			// Вернем на место текущий модуль системы
			s()->active( $old );	
		}
		// No plain HTML view data is set also
		else if( !isset($out{0}) )
		{ 
			return e('Cannot render view for module ## - No view path or data has been set', E_SAMSON_CORE_ERROR, $this->id );
		}	
		
		// Clear cuurently outputted view context from VCS
		unset($this->view_data[ $view_path ]);
		
		// Get last element from VCS
		end( $this->view_data );
		
		// Get last element from VCS name 
		$this->view_context = key( $this->view_data );
		
		// Set internal view data pointer to last VCS entry
		$this->data = & $this->view_data[ $this->view_context ];
		
		// Return view path to previous state
		$this->view_path = $this->view_context;
				
		// Вернем результат прорисовки
		return $out;
	}	
	
	/**	@see iModule::render() */
	public function render( $controller = NULL )
	{	
		//trace($this->id.'-'.$controller);
		// Временно изменим текущий модуль системы
		$old = & s()->active( $this );
		
		// Если если передан контроллер модуля для выполнения перед его прорисовкой - выполним его
		if( isset( $controller ) ) 
		{					
			// Выполним действие текущего модуля
			$this->action( $controller == '' ? null : $controller );					
		}			
		
		//elapsed( $this->id.' - Rendering '.$this->view_path );
		
		// Прорисуем представление и выведем его в текущий поток вывода
		echo $this->output( $this->view_path );	
		
		// Ввостановим предыдущий текущий модуль контролера
		s()->active( $old );
	}

	/** @see iModule::action() */
	public function action( $method_name = NULL )
	{	
		//trace( array_keys($this->controllers), true );
		
		// Get parameters from URL
		$parameters = url()->parameters;
		
		// NEW ASYNC EVENT CHAIN LOGIC
            // If this is AJAX request - try to call async handlers
            // Added support for SamsonJS special header
            if($_SERVER['HTTP_ACCEPT'] == '*/*' || isset($_SERVER['HTTP_SJSASYNC']) || isset($_POST['SJSASYNC']))
            {
                // Copy parameters
                $arguments = $parameters;
                array_unshift( $arguments, url()->method );
			
			// Response
			$event_result = array();
			
			// Iterate supported methods
			for ($idx = 0; $idx < sizeof($arguments); $idx++)
			{
                $controller_name = self::ASYNC_PREFIX.$arguments[ $idx ];

				// Build async method handler name and try to find method in arguments list
				$callback = & $this->controllers[ $controller_name ];

				// If async controller handler exists
				if( isset( $callback ) )
				{
					// Get function arguments without function name
					$f_args = array_slice($arguments, $idx + 1);
							
					// Remove used cells from array
					$arguments = array_slice($arguments, $idx + 1);

                    // Decrease index as we change arguments size
                    $idx--;
					
					// Perform event and collect event result data
					$_event_result = call_user_func_array( $callback, $f_args );	
					
					// Anyway convert event result to array
					if( !is_array($_event_result) ) $_event_result = array($_event_result);

					// If event successfully completed
					if( !isset($_event_result['status']) || !$_event_result['status'] )
					{
						// Handle event chain fail
						$_event_result['message'] = "\n".'Event failed: '.$controller_name;

                        // Add event result array to results collection
                        $event_result = array_merge( $event_result, $_event_result );

						// Stop event-chain execution
						break;
					}					
					// Add event result array to results collection
					else $event_result = array_merge( $event_result, $_event_result );					
				}
			}
			
			// If at least one event has been executed
			if( sizeof($event_result) )
			{			
				// Set async responce
				s()->async(true);				
				
				// Send success status
				header("HTTP/1.0 200 Ok");
							
				// Encode event result as json object
				echo json_encode( $event_result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
				
				return A_SUCCESS;
			}
		}	
		
		// Get HTTP request type
		$request_type = $_SERVER['REQUEST_METHOD'];
		
		// Controller by name
		$naming = $method_name;
		// Controller by server request type
		$request = !isset($method_name{0}) ? strtolower( $request_type == 'GET' ? self::CTR_BASE : self::OBJ_PREFIX.$request_type) : '';
		// Universal controller
		$universal = self::CTR_UNI;
		
		// Controller selection logic chain
		$controller = (isset( $this->controllers[ $naming ]  ) ? $this->controllers[ $naming ] : 
							(isset( $this->controllers[ $request ] ) ? $this->controllers[ $request ] :  
								(isset( $this->controllers[ $universal ] ) ? $this->controllers[ $universal ] : null)));
			
		// If we selected universal controller - change parameters signature
		if( isset($this->controllers[ $universal ]) && $controller == $this->controllers[ $universal ] )
		{
			// If method is specified - add it to universal controller parameters list
			if(isset(url()->method{0})) array_unshift( $parameters, url()->method );		
		}

        //elapsed('Performing #'.$this->id.' controller action -'.$controller);
		
		// Perform controller action
		$action_result = isset( $controller ) ? call_user_func_array( $controller, $parameters ) : A_FAILED;		
			
		// Stop candidate search
		return !isset( $action_result ) ? A_SUCCESS : $action_result;		
	}		

	/**
	 * Constructor 
	 * 
	 * @param string 	$id 		Module unique identifier	 
	 * @param string 	$path 		Module location
	 * @param array 	$resources	Module resources list 
	 */
	public function __construct($id, $path = NULL, $resources = NULL)
	{
		// Set default view context name
		$this->view_context = self::VD_POINTER_DEF;
		
		// Set up default view data pointer
		$this->data = & $this->view_data[ $this->view_context ];		
		
		// Set module identifier
		$this->id = $id;
		
		// Set path to module
		$this->path( $path );

		// Generate unique module identifier
		$this->uid = rand(0, 9999999).'_'.microtime(true);
		
		// Add to module identifier to view data stack
		$this->data['id'] = $this->id;			
						
		// Save views list
		isset( $resources ) ? $this->views = & $resources['views'] : '';
		
		// Generate unique module cache path in local web-application
		$this->cache_path = __SAMSON_CWD__.__SAMSON_CACHE_PATH.'/'.$this->id.'/';
		
		// Save ONLY ONE copy of this instance in static instances collection,
		// avoiding rewriting by cloned modules
		!isset( self::$instances[ $this->id ] ) ? self::$instances[ $this->id ] = & $this : '';		
			
		// Find all controller actions
		$functions = get_defined_functions();
		foreach ($functions['user'] as $method) {
			// Try to find standard controllers
			switch (strtolower($method)) {
				// Ignore special controllers
				case $this->id.self::CTR_UNI		:
				case $this->id.self::CTR_POST		:
				case $this->id.self::CTR_PUT		:
				case $this->id.self::CTR_DELETE		:
				case $this->id.self::CTR_BASE		:
					break;
					
				// Default controller
				//case $this->id: $this->controllers[ $method ] = $method; break;
					
				// Check if regular controller
				default: if( preg_match('/^'.$this->id.self::PROC_PREFIX.'(?<controller>.+)/i', $method, $matches ) ) 
				{
					$this->controllers[ $matches['controller'] ] = $method;
				}
			}			
		}		
		
		// Iterate class methods
		foreach (get_class_methods($this) as $method) {
			// Try to find standart controllers			
			switch(strtolower($method)) {
				// Ignore special controllers
				case self::CTR_UNI:
				case self::CTR_POST	:
				case self::CTR_PUT:
				case self::CTR_DELETE:
				case self::CTR_BASE	:
					break;
			
				// Ignore magic methods
				case '__call':
				case '__wakeup':
				case '__sleep':
				case '__construct':
				case '__destruct':
				case '__set':
				case '__get':
					break;
			
				// Check if regular controller
				default: if( preg_match('/^'.self::OBJ_PREFIX.'(?<controller>.+)/i', $method, $matches ) ) 
				{
					$this->controllers[ $matches['controller'] ] = array( $this, $method );
				}
			}				
		}					
		
		if( function_exists( $this->id )) $this->controllers[ self::CTR_BASE ] 	= $this->id;
		if( method_exists($this, self::CTR_BASE)) $this->controllers[ self::CTR_BASE ] 	= array( $this, self::CTR_BASE );		
		
		if( function_exists( $this->id.self::CTR_POST )) 	$this->controllers[ self::CTR_POST ] 	= $this->id.self::CTR_POST;
		if( method_exists($this, self::CTR_POST)) 			$this->controllers[ self::CTR_POST ] 	= array( $this, self::CTR_POST );	
		
		if( function_exists( $this->id.self::CTR_PUT )) 	$this->controllers[ self::CTR_PUT ] 	= $this->id.self::CTR_PUT;
		if( method_exists($this, self::CTR_PUT)) 			$this->controllers[ self::CTR_PUT ] 	= array( $this, self::CTR_PUT );		
				
		if( function_exists( $this->id.self::CTR_DELETE )) 	$this->controllers[ self::CTR_DELETE ] 	= $this->id.self::CTR_DELETE;
		if( method_exists($this, self::CTR_DELETE)) 		$this->controllers[ self::CTR_DELETE ] 	= array( $this, self::CTR_DELETE );
		
		if( function_exists( $this->id.self::CTR_UNI )) 	$this->controllers[ self::CTR_UNI ] 	= $this->id.self::CTR_UNI;	
		if( method_exists($this, self::CTR_UNI)) 			$this->controllers[ self::CTR_UNI ] 	= array( $this, self::CTR_UNI );
		
		// Make view path relative to module - remove module path from view path
		$this->views = str_replace( $this->path, '', $this->views );		
			
		//elapsed('Registering module: '.$this->id.'('.$path.')' );
	}		
	
	/** Обработчик уничтожения объекта */
	public function __destruct()
	{
		//trace('Уничтожение модуля:'.$this->id );				
			
		// Очистим коллекцию загруженых модулей
		unset( Module::$instances[ $this->id ] );
	}
	
	// Магический метод для получения переменных представления модуля
	public function __get( $field )
	{		
		// Установим пустышку как значение переменной
		$result = NULL;
		
		// Если указанная переменная представления существует - получим её значение
		if( isset( $this->data[ $field ] ) ) $result = & $this->data[ $field ];
		// Выведем ошибку
		else return e('Ошибка получения данных модуля(##) - Требуемые данные(##) не найдены', E_SAMSON_CORE_ERROR, array( $this->id, $field ));
		
		// Иначе вернем пустышку
		return $result;		
	}

	
	/**
	 * Create unique module cache folder structure in local web-application
	 * @param string 	$file 	Path to file relative to module cache location
	 * @param boolean 	$clear	Flag to perform generic cache folder clearence
	 * @return boolean TRUE if cache file has to be regenerated
	 */
	protected function cache_refresh( & $file, $clear = true )
	{
		// If module cache folder does not exists - create it
		if( !file_exists( $this->cache_path ) ) mkdir( $this->cache_path, 0777, TRUE );
		
		// Build full path to cached file
		$file = $this->cache_path.$file;		
		
		// If cached file does not exsits
		if( file_exists( $file ) ) return false;
		// Needed file does not exists 	
		else 
		{
			// If clearence flag set to true - clear all files in module cache directory with same extension
			if( $clear ) File::clear( $this->cache_path, pathinfo( $file, PATHINFO_EXTENSION ) );			
			
			// Singal for cache file regeneration
			return true;
		}
	}

	// TODO: Переделать обработчик в одинаковый вид для объектов и простых
	
	/**
	 * 
	 * @param unknown $object
	 * @param string $viewprefix
	 */
	private function _setObject( $object, $viewprefix = null )
	{
		// Generate viewprefix as only lowercase classname without NS if it is not specified
		$class_name = is_string( $viewprefix ) ? $viewprefix : ''.mb_strtolower( classname(get_class($object)), 'UTF-8' );
		
		// Save object to view data
		$this->data[ $class_name ] = $object;
		
		// Add separator
		$class_name .= '_';
		
		// Generate objects view array data and merge it with view data
		$this->data = array_merge( $this->data, $object->toView( $class_name ) );
	}
	
	/**
	 * 
	 * @param unknown $array
	 * @param string $viewprefix
	 */
	private function _setArray( $array, $viewprefix = null )
	{
		// Save array to view data
		$this->data[ $viewprefix ] = $array;
		
		// Add array values to view data
		$this->data = array_merge( $this->data, $array );
	}	
	
	// Магический метод для установки переменных представления модуля
	public function __set( $field, $value = NULL )
	{		
		// This is object
		if( is_object( $field ))
		{				
			// If iModuleViewable implementor is passed
			if( in_array( ns_classname('iModuleViewable','samson\core'), class_implements($field )) ) $this->_setObject( $field, $value );			
		}		
		// If array is passed 
		else if( is_array( $field ) ) $this->_setArray( $field, $value );
		// Set view variable
		else $this->data[ $field ] = $value;		
	}
	
	/** Magic method for calling unexisting object methods */
	public function __call( $method, $arguments )
	{
		//elapsed($this->id.' - __Call '.$method);		
	
		// If value is passed - set it
		if( sizeof( $arguments ) )
		{
			// If first argument is object or array - pass method name as second parameter
			if( is_object($arguments[0]) || is_array($arguments[0]) ) $this->__set( $arguments[0], $method );
			// Standard logic
			else  $this->__set( $method, $arguments[0] );
		}
		
		// Chaining
		return $this;
	}
	
	/** Обработчик сериализации объекта */
	public function __sleep(){	return array( 'id', 'path', 'version', 'data', 'controllers', 'views' );	}
	/** Обработчик десериализации объекта */
	public function __wakeup()
	{		
		// Fill global instances  
		self::$instances[ $this->id ] = & $this;

		// Set up default view data pointer
		$this->view_data[ self::VD_POINTER_DEF ] = $this->data;
		
		// Set reference to view context entry
		$this->data = & $this->view_data[ self::VD_POINTER_DEF ];
	}
	
	/** Группа методов для доступа к аттрибутам в виде массива */
	public function offsetSet( $offset, $value ){ $this->__set( $offset, $value ); }
	public function offsetGet( $offset )		{ return $this->__get( $offset ); }
	public function offsetUnset( $offset )		{ $this->data[ $offset ] = ''; }
	public function offsetExists( $offset )		{ return isset($this->data[ $offset ]); }
}