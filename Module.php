<?php	
namespace samson\core;

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
	
	/**
	 * Module core
	 * @var Core
	 */
	protected $core;
	
	/** Module views collection */
	protected $views;
	
	/** Module location */
	protected $path = '';
	
	/** Unique module identifier */
	protected $id = '';
	
	/** Default module author */
	protected $author = 'Vitaly Iegorov <egorov@samsonos.com>';
	
	/** Default module version */
	protected $version = '0.0.1';
	
	/** Path to view for rendering, also key to $view_data entry */
	protected $view_path = self::VD_POINTER_DEF;
	
	/** Pointer to view data enty */
	protected $data = array();	
	
	/** Collection of data for view rendering, filled with default pointer */
	protected $view_data = array( self::VD_POINTER_DEF => array( 'html' => '' ) );	
	
	
	/**	@see iModule::title() */
	public function title( $title = NULL ){ return $this->set( 'title', $title ); }
	
	/**	@see iModule::id() */
	public function id(){ return $this->id; }	
	
	/** @see iModule::set() */
	public function set( $field, $value = NULL ){ $this->__set( $field, $value ); return $this;	}
	
	/** @see iModuleViewable::toView() */
	public function toView( $prefix = NULL, array $restricted = array() )
	{
		// Get all module data variables
		$view_data = array_merge( $this->data, get_object_vars( $this ) );

		// Remove plain HTML from view data
		unset( $view_data[ self::VD_HTML ] );
		
		return $view_data;
	}
	
	/** @see iModule::path() */
	public function path( $value = NULL )
	{		
		// Если передан параметр - установим его
		if( func_num_args() ){ $this->path = normalizepath($value); return $this; }		
		// Вернем относительный путь к файлам модуля
		else return $this->path;
	}
	
	/** @see iModule::html() */
	public function html( $value = NULL )
	{
		// Если передан параметр то установим его
		if( func_num_args() ) $this->data[ self::VD_HTML ] = $value;
		// Вернем значение текущего представления модели
		else return $this->data[ 'html' ];
		
		return $this;
	}
	
	/** @see iModule::view() */
	public function view( $view_path )
	{		
		// Доставим расширение файла если его нет
		if( strpos( $view_path, '.php' ) === FALSE ) $view_path .= '.php';
			
		// Build path to default view folder
		$view_path = __SAMSON_VIEW_PATH.$view_path;	

		// Create new entry in view data collection
		if( !isset( $this->view_data[ $view_path ] ) ) 
		{				
			// If view data pointer is set to default view data entry			
			if( $this->data === $this->view_data[ self::VD_POINTER_DEF ] )
			{
				// Pointer first view entry to it
				$this->view_data[ $view_path ] = array_merge( array(), $this->view_data[ self::VD_POINTER_DEF ] );				
			}
			// Create new view data entry
			else $this->view_data[ $view_path ] = array(); 						
			
			//elapsed($this->core_id.' - Changing VD_POINTER to '.$view_path.' with '.sizeof($this->view_data[ self::VD_POINTER_DEF ]).' params' );
		}
		
		// Set current view path
		$this->view_path = $view_path;
		
		// Change view data pointer to appropriate view data entry
		$this->data = & $this->view_data[ $this->view_path ];
			
		// Продолжим цепирование
		return $this;
	}	
	
	/**	@see iModule::output() */
	public function output( $view_path = null )
	{					
		// If view path not specified - use current correct view path
		if( !isset( $view_path ) ) $view_path = $this->view_path;
		// Direct rendering of specific view, not default view data entry
		else if( $view_path !== self::VD_POINTER_DEF )
		{					
			// Add extension if nessesary
			if( strpos( $view_path, '.php' ) === false ) $view_path .= '.php';
			
			// If no default view path was specified
			if( strpos( $view_path, __SAMSON_VIEW_PATH ) === false ) $view_path = __SAMSON_VIEW_PATH.$view_path;			
							
			// Copy current view data stack to new entry for direct output
			if( isset($this->view_data[ $this->view_path ] ) )
			{
				$this->view_data[ $view_path ] = array_merge( array(), $this->view_data[ $this->view_path ] );
			} 
			// Create new view data entry
			else $this->view_data[ $view_path ] = array();
				
			// When direct outputting don't copy plain html			
			unset($this->view_data[ $view_path ][ self::VD_HTML ]);		
				
			// Change view data pointer to appropriate view data entry
			$this->data = & $this->view_data[ $view_path ];	
		}		

		// Output data
		$out = isset($this->data[ self::VD_HTML ]) ? $this->data[ self::VD_HTML ] : '';		
		
		// If view path specified
		if( isset( $view_path {0}) && ($view_path != __SAMSON_VIEW_PATH.'/.php'))
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
			
		// If we have only one element - it is a default enty - delete it
		if( sizeof($this->view_data) == 1 ) $this->view_data[ self::VD_POINTER_DEF ] = array();	
		// Delete current view data entry 
		else unset( $this->view_data[ $view_path ] );
		
		// Switch internal array pointer to last element
		end( $this->view_data );
		
		// Get previous view path in view data stack
		$this->view_path = key( $this->view_data );
		
		// Change view data pointer
		$this->data = & $this->view_data[ $this->view_path ];	
		
		// Вернем результат прорисовки
		return $out;
	}	
	
	/**	@see iModule::render() */
	public function render( $controller = NULL )
	{					
		// Если если передан контроллер модуля для выполнения перед его прорисовкой - выполним его
		if( isset( $controller ) ) 
		{			
			// Временно изменим текущий модуль системы
			$old = & s()->active( $this );
			
			// Выполним действие текущего модуля
			$this->action( $controller == '' ? null : $controller );	

			// Ввостановим предыдущий текущий модуль контролера
			s()->active( $old );				
		}			
		
		//trace('rendering '.$this->id.'('.$this->view_path.')');
		
		// Прорисуем представление и выведем его в текущий поток вывода
		echo $this->output( $this->view_path );	
	}

	/** @see iModule::action() */
	public function action( $method_name = NULL )
	{			
		// If no controller action name is specified 
		if( ! isset( $method_name ) )
		{
			// Try to guess controller action name by server request type
			switch( $_SERVER['REQUEST_METHOD'] )
			{
				case 'POST'		: $method_name = self::CTR_POST; 		break;
				case 'PUT'		: $method_name = self::CTR_PUT; 		break;
				case 'DELETE'	: $method_name = self::CTR_DELETE; 		break;
				default			: $method_name = self::CTR_BASE;	 				
			}
			
			// Copy default controller action name
			$o_method_name = $method_name;
		}	
		// Append object controller action name prefix 
		else $o_method_name = '__'.$method_name;

		// Get parameters from URL
		$parameters = url()->parameters();
		
		// If module object has controller action defined - try object approach
		if( method_exists( $this, $o_method_name ) ) $method_name = array( $this, $o_method_name );		
		// Now module object controller action method found - try function approach
		else 
		{
			// Build function controller action name		
			$method_name = $this->id.(isset( $method_name{0} ) && $method_name != self::CTR_BASE ? '_'.$method_name : '');		
		
			// If we did not found controller action try universal controller action
			if( ! function_exists( $method_name ))
			{
				// Modify parameters list for universal controller action
				$parameters = array_merge( array( url()->method ), $parameters );
				
				// Set universal controller action
				$method_name = $this->id.self::CTR_UNI;
			}		
			
			// No appropriate controller action found for this module		
			if( ! function_exists( $method_name )) return A_FAILED;
		
			// TODO: wait for getHint() method for removing parameter name dependency and use just hint type
			
			// Pass specific system parameters to controller action handler
			$f = new \ReflectionFunction( $method_name );
			// Iterate params and match needed parameters by name
			foreach ( $f->getParameters() as $n => $p) 
			{				
				// If system core needed
				if( $p->name == '_core' || $p->name == 's' )  $var = s();
				// If current module needed
				else if( $p->name == '_module' || $p->name == 'm' ) $var = $this;
				// Parameter does not match
				else continue;
				
				// If we here that something matched - insert neede parameter in parameter array
				array_splice( $parameters, $n, 0, array($var) );
			}		
		}		
		
		// Run module action method
		$action_result = call_user_func_array( $method_name, $parameters );
		
		// Вернем результат выполнения метода контроллера
		return ! isset( $action_result ) ? A_SUCCESS : $action_result;		
	}		

	/**
	 * Конструктор 
	 * 
	 * @param string 	$id 	Уникальный идентификатор модуля описывающий его "физические" файлы	 
	 * @param string 	$path 	Путь для модулей которые находиться отдельно от веб-приложения и системы
	 */
	public function __construct( $id, $path = NULL )
	{		
		// Set up default view data pointer
		$this->view_data[ self::VD_POINTER_DEF ] = & $this->data;		
		
		// Set module identifier
		$this->id = $id;
		
		// Set path to module
		$this->path( $path );						
		
		// Add to module identifier to view data stack
		$this->data['id'] = $this->id;	
	
		// Save ONLY ONE copy of this instance in static instances collection,
		// avoiding rewriting by cloned modules		
		if( !isset( self::$instances[ $this->id ]) ) self::$instances[ $this->id ] = & $this;
		
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
	
	// Магический метод для установки переменных представления модуля
	public function __set( $field, $value = NULL )
	{		
		// Если передан класс который поддерживает представление для модуля
		if( is_object( $field ) && in_array( ns_classname('iModuleViewable','samson\core'), class_implements($field )))
		{					
			// Сформируем регистро не зависимое имя класса для хранения его переменных в модуле
			$class_name = is_string( $value ) ? $value : ''.mb_strtolower( classname(get_class($field)), 'UTF-8' );
				
			// Объединим текущую коллекцию параметров представления модуля с полями класса
			$this->data = array_merge( $this->data, $field->toView( $class_name.'_' ) );
		}		
		// Если вместо имени переменной передан массив - присоединим его к данным представления
		else if( is_array( $field ) ) $this->data = array_merge( $this->data, $field );
		// Если передана обычная переменная, установим значение переменной представления
		// Сделаем имя переменной представления регистро-независимой
		else  $this->data[ $field ] = $value;		
	}
	
	/** Magic method for calling unexisting object methods */
	public function __call( $method, $arguments )
	{
		// If value is passed - set it
		if( isset( $arguments[0] ) )$this->data[ $method ] = $arguments[0];
		
		// Chaining
		return $this;
	}
	
	/** Обработчик сериализации объекта */
	public function __sleep(){	return array( 'id', 'path', 'author', 'version', 'data' );	}
	/** Обработчик десериализации объекта */
	public function __wakeup()
	{
		// Fill global instances  
		self::$instances[ $this->id ] = & $this;

		// Set up default view data pointer
		$this->view_data[ self::VD_POINTER_DEF ] = & $this->data;
	}
	
	/** Группа методов для доступа к аттрибутам в виде массива */
	public function offsetSet( $offset, $value ){ $this->__set( $offset, $value ); }
	public function offsetGet( $offset )		{ return $this->__get( $offset ); }
	public function offsetUnset( $offset )		{ $this->data[ $offset ] = ''; }
	public function offsetExists( $offset )		{ return isset($this->data[ $offset ]); }
}