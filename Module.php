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
	
	/** Путь к подключаемому модулю */
	protected $path = '';
	
	/** Имя модуля под которым модуль загружен в ядро системы */
	protected $core_id = '';
	
	/** Настоящее имя модуля, идентифицируещее его физическую структуру */
	protected $id = '';
	
	/** Автор модуля */
	protected $author = 'SamsonOS';
	
	/** Версия модуля */
	protected $version = '0.0.1';
	
	/** Путь к представлению по умолчанию */
	protected $view_path = '';
	
	/** Код HTML представление модуля */
	protected $view_html = '';
	
	/** Коллекция параметров для представления */
	protected $data = array();	
	
	
	/**	@see iModule::title() */
	public function title( $title = NULL ){ return $this->set( 'title', $title ); }
	
	/**	@see iModule::core_id() */
	public function core_id(){ return $this->core_id; }
	
	/**	@see iModule::id() */
	public function id(){ return $this->id; }	
	
	/** @see iModule::set() */
	public function set( $field, $value = NULL ){ $this->__set( $field, $value ); return $this;	}
	
	/** @see iModuleViewable::toView() */
	public function toView( $prefix = NULL, array $restricted = array() )
	{
		return array_merge( $this->data, get_object_vars( $this ) );
	}
	
	/** @see iModule::path() */
	public function path( $value = NULL )
	{		
		// Если передан параметр - установим его
		if( func_num_args() ){ $this->path = $value; return $this; }		
		// Вернем относительный путь к файлам модуля
		else return $this->path;
	}
	
	/** @see iModule::html() */
	public function html( $value = NULL )
	{
		// Если передан параметр то установим его
		if( func_num_args() ){ $this->view_html = $value; return $this; }
		// Вернем значение текущего представления модели
		else return $this->view_html;
	}
	
	/** @see iModule::view() */
	public function view( $value )
	{		
		// Доставим расширение файла если его нет
		if( strpos( $value, '.php' ) === FALSE ) $value .= '.php';
			
		// Подставим путь к представлениям модуля
		$this->view_path = __SAMSON_VIEW_PATH.'/'.$value;
			
		// Продолжим цепирование
		return $this;
	}
	
	/**	@see iModule::output() */
	public function output( $view_path, array $data = null )
	{			
		// Установим HTML представление модуля для вывода
		$out = $this->view_html;
		
		// Если передан путь для вывода представления
		if( isset( $view_path ) && isset( $view_path{0}) )
		{		
			// Доставим расширение файла если его нет
			if( strpos( $view_path, '.php' ) === FALSE ) $view_path .= '.php';
			
			// Временно изменим текущий модуль системы
			$old = s()->active( $this );
			
			// Если переданы дополнительные данные для представления - добавим их
			if( isset( $data ) ) $this->data = array_merge( $this->data, $data );			
			
			// Прорисуем представление модуля
			$out .= output( $this->path.$view_path, $this->data, 'VIEW' );
			
			// Вернем на место текущий модуль системы
			s()->active( $old );
			
			// Очистим само представление
			$this->view_html = '';
			
			// Очистим контекст представления модуля
			$this->data = array();			
		}
		
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
		if( $method_name == $this->id.'__HANDLER' ) $parameters = array_merge( array( url()->method() ), $parameters );	
		
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
	
	/** @see iModule::duplicate() */
	public function & duplicate( $id, $class_name = null )
	{ 		
		// Получим класс текущего модуля
		$class = isset( $class_name ) ? $class_name : get_class( $this );
		
		// Создадим новый модуль этого класса
		$m = new $class( $id, $this->path ); 
		
		// Установим ему идентификатор оригинала для доступа к файлам
		$m->id = $this->id; 
		
		// Вернем созданный дубликат модуль
		return $m; 
	}
	
	/**
	 * Конструктор 
	 * 
	 * @param string 	$id 	Уникальный идентификатор модуля описывающий его "физические" файлы	 
	 * @param string 	$path 	Путь для модулей которые находиться отдельно от веб-приложения и системы
	 * @param array 	$params	Коллекция параметров для модуля
	 */
	public function __construct( $id, $path = NULL, array $params = NULL )
	{		
		// Установим идентификатор модуля
		$this->core_id = $id;				
		
		// Установим идентификатор модуля, если он еще не задан
		$this->id = ! isset( $this->id{0} ) ? $id : $this->id;
		
		//elapsed('Регистрирование модуля системы: '.$this->id.'('.$this->core_id.')');
		
		// Установим путь к "отдаленному" модулю
		$this->path( $path );		
		
		// Установим параметры модуля
		if( isset($params) ) foreach ( $params as $k => $v ) $this->$k = $v;		
		
		// Установим идентификатор модуля в коллекцию перенных модуля
		$this->data['id'] = $this->id;				
		
		// Запишем создаваемый класс в статическую коллекцию
		self::$instances[ $this->core_id ] 	= & $this;
		self::$instances[ $this->id ] 		= & $this;
	}		
	
	/** Обработчик уничтожения объекта */
	public function __destruct()
	{
		//trace('Уничтожение модуля:'.$this->id );
		
		// Очистим коллекцию загруженых модулей
		unset( Module::$instances[ $this->core_id ] );
			
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
		if( is_object( $field ) && in_array( 'samson\core\iModuleViewable', class_implements($field )))
		{					
			// Сформируем регистро не зависимое имя класса для хранения его переменных в модуле
			$class_name = is_string( $value ) ? $value : ''.mb_strtolower( basename(str_replace('\\', '/', get_class($field))), 'UTF-8' );
					
			// Добавим ссылку на сам объект в представление
			$this->data[ $class_name ] = & $field;
			
			// Объединим текущую коллекцию параметров представления модуля с полями класса
			$this->data = array_merge( $this->data, $field->toView( $class_name.'_' ) );
		}		
		// Если вместо имени переменной передан массив - присоединим его к данным представления
		else if( is_array( $field ) ) $this->data = array_merge( $this->data, $field );
		// Если передана обычная переменная, установим значение переменной представления
		// Сделаем имя переменной представления регистро-независимой
		else $this->data[ $field ] = $value;		
	}
	
	/** Обработчик сериализации объекта */
	public function __sleep(){	return array( 'core_id', 'id', 'view_path', 'view_html', 'data', 'path' );	}
	/** Обработчик десериализации объекта */
	public function __wakeup(){ self::$instances[ $this->core_id ] = & $this; }
	
	/** Группа методов для доступа к аттрибутам в виде массива */
	public function offsetSet( $offset, $value ){ $this->__set( $offset, $value ); }
	public function offsetGet( $offset )		{ return $this->__get( $offset ); }
	public function offsetUnset( $offset )		{ $this->data[ $offset ] = ''; }
	public function offsetExists( $offset )		{ return isset($this->data[ $offset ]); }
}