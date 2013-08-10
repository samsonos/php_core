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
	/** Коллекция созданных экземпляров модулей системы */	
	public static $instances = array();	
	
	/**
	 * Module core
	 * @var Core
	 */
	protected $core;
	
	/** Путь к подключаемому модулю */
	protected $path = '';
	
	/** Настоящее имя модуля, идентифицируещее его физическую структуру */
	protected $id = '';
	
	/** Автор модуля */
	protected $author = 'SamsonOS';
	
	/** Версия модуля */
	protected $version = '0.0.1';
	
	/** Path to view for rendering, also key to $view_data entry */
	protected $view_path = self::VD_POINTER_DEF;
	
	/** Код HTML представление модуля */
	protected $view_html = '';
	
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
			$this->view_data[ $view_path ] = array_merge( array(), $this->view_data[ $this->view_path ] ); 
				
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
			$this->action( $controller );	

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
		// Если не указан конкретный метод контроллера
		if( ! isset( $method_name ) )
		{
			// Проверим HTTP метод запроса
			switch( $_SERVER['REQUEST_METHOD'] )
			{
				case 'POST'		: $method_name = iModule::POST_CONTROLLER; 	break;
				case 'PUT'		: $method_name = iModule::PUT_CONTROLLER; 	break;
				case 'DELETE'	: $method_name = iModule::DELETE_CONTROLLER; break;
				case 'GET'		:  // Стандартное поведение
			}
		}
		
		// Проверим переданное имя контроллера
		switch( $method_name )
		{
			// Если указан специальный идентификатор использования базового контроллера
			case self::BASE_CONTROLLER		: $method_name = $this->id; break;
			// Если указан специальный идентификатор использования универсального контроллера
			case self::UNI_CONTROLLER		: $method_name = $this->id.'__HANDLER'; break;		
			// Если указан специальный идентификатор использования универсального контроллера
			case self::POST_CONTROLLER		: $method_name = $this->id.'__POST'; break;
			// Если указан специальный идентификатор использования универсального контроллера
			case self::PUT_CONTROLLER		: $method_name = $this->id.'__PUT'; break;
			// Если указан специальный идентификатор использования универсального контроллера
			case self::DELETE_CONTROLLER	: $method_name = $this->id.'__DELETE'; break;
			// Если не найден специальный идентификатор метода контроллера
			default : 
				
				// Сформируем полное имя метода контроллера если оно задано или устанвоим базовый контроллер
				$method_name = isset( $method_name ) ? $this->id.'_'.$method_name : $this->id;			
	
				// Если существует универсальный контроллер - установим его имя
				if( ! function_exists( $method_name ) ) $method_name = $this->id.'__HANDLER';					
		}	

		// Если базовый контроллер или указанный метод контроллера не существует
		if( ! function_exists( $method_name ) ) return A_FAILED;			
		
		// Получим параметры
		$parameters = url()->parameters();

		// Если мы используем универсальный контроллер добавим первым параметром универсального контроллера - имя метода
		if( $method_name == $this->id.'__HANDLER' && isset(url()->method{0}) ) 
		{
			$parameters = array_merge( array( url()->method ), $parameters );	
		}
			
		// TODO: wait for getHint() method for removing parameter name dependency and use just hint type
		
		// Pass specific system parameters to controller action handler
		$f = new \ReflectionFunction($method_name);
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
		
		// Оптимизируем вызов функции call_user_func_array в зависимости от количества параметров
		switch( sizeof( $parameters ) )
		{
			case 0	: $action_result = $method_name(); break;
			case 1	: $action_result = $method_name( $parameters[0] ); break;			
			case 2	: $action_result = $method_name( $parameters[0],$parameters[1] ); break;
			case 3	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2] ); break;
			case 4	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2],$parameters[3] ); break;
			case 5	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2],$parameters[3],$parameters[4] ); break;
			case 6	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2],$parameters[3],$parameters[4],$parameters[5] ); break;
			case 7	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2],$parameters[3],$parameters[4],$parameters[5],$parameters[6] ); break;
			case 8	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2],$parameters[3],$parameters[4],$parameters[5],$parameters[6],$parameters[7] ); break;
			case 9	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2],$parameters[3],$parameters[4],$parameters[5],$parameters[6],$parameters[7],$parameters[8] ); break;
			case 10	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2],$parameters[3],$parameters[4],$parameters[5],$parameters[6],$parameters[7],$parameters[8],$parameters[9] ); break;
			case 11	: $action_result = $method_name( $parameters[0],$parameters[1],$parameters[2],$parameters[3],$parameters[4],$parameters[5],$parameters[6],$parameters[7],$parameters[8],$parameters[9],$parameters[10] ); break;					
			default	: return e('Передано слишком много параметров для контроллера(##)',E_SAMSON_FATAL_ERROR, sizeof( $parameters ) );
		}			
		
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
	
		// Save this instance in static instances collection		
		self::$instances[ $this->id ] = & $this;
		
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