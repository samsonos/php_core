<?php
namespace samson\core;

/**
 * Класс для работы c URL
 * @package Samson
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 1.0
 */
class URL implements iURL
{	
	/**
	 * Дополнительный параметр в URL
	 * Используется для "вложенных" сайтов, таких
	 * которые находятся не в корневой папке сайта
	 * 
	 * @var string
	 */ 
	private $base = '/';	
		
	/**
	 * Значение последнего аргумента из URL 
	 * @var string
	 */
	public $last = '';
	
	/**
	 * Коллекция параметров переданных в URL
	 * @var array
	 */ 
	public $parameters = array();
	
	/**
	 * Текущий модуль
	 * @var string
	 */ 
	public $module = NULL;
	
	/**
	 * Текущий метод контроллера
	 * @var string
	 */ 
	public $method = NULL;
	
	/**
	 * Текстовое представление пути URL
	 * @var string
	 */
	public $text;
	
	/**
	 * Конструктор класса
	 */
	public function __construct(){ $this->parse();}
	
	/**	 
	 * @see iURL::module()
	 * @deprecated Используйте поле класса $module
	 */
	public function module(){ return $this->module; }
	
	/**	 
	 * @see iURL::method()
	 * @deprecated Используйте поле класса $method
	 */
	public function method(){ return $this->method;	}
	
	/**	 
	 * @see iURL::last()
	 * @deprecated Используйте поле класса $last
	 */
	public function last(){ return $this->last;	}
	
	/**	 
	 * @see iURL::text()
	 * @deprecated Используйте поле класса $text
	 */
	public function text(){	return $this->text;	}
	
	/**	 
	 * @see iURL::parameters()
	 * @deprecated Используйте поле класса $parameters
	 */
	public function parameters(){return $this->parameters;}

    /**
     * Check if current module from URL identifier matches passed module identifier and output on success
     * @param string    $modules Module name to match
     * @param string    $output String for output
     * @return boolean True if current module from URL identifier matches passed module identifier
     */
    public function is($modules, $output = '')
    {
        // If module is not array - make it
        if (!is_array($modules)) {
            $modules = array($modules);
        }

        // Iterate passed modules
        foreach ($modules as $module) {
            // If URL module or current system module matches passed module name
            if (($this->module == $module) || (s()->module()->id == $module)) {
                echo $output;

                return true;
            }
        }

        return false;
    }
	
	/** @see iURL::bookmark() */
	public function bookmark( $name = NULL, $return = false )
	{	
		// Указатель на стек URL-маршрутов
		$stack = & $_SESSION[ self::S_BOOKMARK_KEY ];
		
		// Если передан флаг получения закладки и закладка существует - верненм её URL-маршрут
		if( $return && isset( $stack[ $name ] ) ) return unserialize( $stack[ $name ] );

		// Иначе сохраним отметку в закладках
		if( !$return ) $stack[ $name ] = serialize($this); 
	}
	
	/** @see iURL::history() */
	public function history( $number = 0 )
	{		
		// Указатель на стек URL-маршрутов
		$stack = & $_SESSION[ self::S_PREVIOUS_KEY ];		
		
		// Если в сессии есть данные минимум о прошлом URL-маршруте - вернем его
		if( isset( $stack[ $number ] ) ) return unserialize( $stack[ $number ] );		
		// Что бы избежать ошибок вернем самого себя
		else return $this;
	}
	
	/** @see iURL::base() */
	public function base( $url_base = NULL ) 
	{	
		// Если нужно установить новую базу 
		if( isset( $url_base ) )
		{
			// Установим новую "базовую" url			
			$this->base = $url_base;
			
			// Выполним разбор url
			$this->parse();
		}
		
		// Вернуть путь
		return $this->base;
	}	
	
	/**	 
	 * @see iURL::redirect()
	 */
	public function redirect( $url = NULL )
	{
		// Сформируем полный путь для переадресации
		$full_url = $this->base.locale_path().$url;

		// Перейдем к форме авторизации		
		header('Location: '.$full_url );
			
		// Добавим клиентскон перенаправление в случаи не срабатывания серверного
		echo '<script type="text/javascript">window.location.href="' . $full_url . '";</script>';
	}	
	
	/**	 
	 * @see iURL::build()
	 */
	public function build( $url = '' )
	{			
		// Получим все аргументы функции начиная с 2-го
        $args = func_get_args();
		$args = array_slice( $args, 1 );
		
		// Получим все сущности переданные в URL, разбив их в массив по символу "/",
		// чистим полученный массив от пустых элементов,		
		$url_params = explode( '/', $url );

		// Очистим элементы массива
		for ($i = 0; $i < sizeof($url_params); $i++) if( !isset($url_params[$i]{0}) ) unset($url_params[$i]);
		
		// Получим количество аргументов полученных в виде параметров
		$args_count = sizeof( $args );				

		// Получим указатель на текущий модуль системы
		$m = & s()->active();
		
		// Пробижимся по переданным аргументам и попытаемся доставть переменные из модуля
		// и если такая переменная задана то подставим её значение, иначе оставим просто строку		
		for ($i = 0; $i < $args_count; $i++) 
		{
			// Получим строковое представление аргумента функции
			$arg = $args[ $i ];			
			
			// Попытаемся получить переменную с указанным именем в текущем модуле 			
			// Если переменная модуля не существует тогда используем строковое представление аргумента
			// добавим "разпознанное" значение аргумента в коллекцию параметров URL
			$url_params[] = isset( $m[$arg] ) && !is_object($m[$arg])? $m->$arg : $arg;			 	
		}		
 		
		// Вернем полный URL-путь относительно текущего хоста и веб-приложения
		// Соберем все отфильтрованные сущности URL использую разделитель "/"
		return 'http://'.$_SERVER['HTTP_HOST'].$this->base.implode( '/', $url_params );
	}
	
	/**
	 * Розпарсить URL
	 */
	private function parse()
	{			
		// Обнулим параметры		
		$this->text = NULL;
		$this->parameters = array();
		$this->method = NULL;
		$this->module = NULL;
		$this->last = NULL;		
		
		// Розпарсим URL 
		$url = parse_url( $_SERVER["REQUEST_URI"] ); 
		
		// Получим только путь из URL
		$url = $url['path'];
		
		// Отрежем путь к текущему веб-приложению из пути и декодируем другие символы
		$url = trim( urldecode( substr( $url, strlen( __SAMSON_BASE__ ))));

		// Установим базовый путь к приложению
		$this->base = __SAMSON_BASE__;

		// Получим массив переданных аргументов маршрута системы из URL
		// Отфильтруем не пустые элементы, не переживая что мы упустим поряд их следования
		// так номера элементов в массиве сохраняются
		$url_args = explode( '/', $url );		

		// Clear last element if it's empty string 
		$lidx = sizeof( $url_args ) - 1;		
		if( !isset($url_args[ $lidx ]{0}) ) unset( $url_args[ $lidx ] );
	
		// Try to find locale change as url argument
		$key = SamsonLocale::parseURL( $url_args );

		//trace( $url_args, true );

		// Переберем все аргументы и маршрута системы
		foreach ( $url_args as $position => $value ) 
		{
			// Получим аргумент из URL
			$arg = filter_var( $value, FILTER_DEFAULT );
			
			// Определим тип аргумента
			switch( $position )
			{
				// 1-й аргумент это всегда имя модуля
				case 0	: $this->module = $arg;  break;
				// 2-й аргумент это всегда имя метода модуля
				case 1	: $this->method = $arg;  break;
				// Все остальные аргументы это параметры вызываемого метода
				default	:
					// Если в значение аргумента есть запятые - это массив
					//if( strpos( $arg, ',' ) !== FALSE ) $arg = explode( ',', $arg );
			
				// Добавим аргумент как параметр
				$this->parameters[] = $arg;
			}
		}
		
		// Сохраним "чистый" текст URL
		$this->text = $url;
		
		// Получим последний аргумент из URL				
		$this->last = isset($position) ? $url_args[ $position ] : '';
		
		// If we have only one parameter and it is empty - remove it
		//if( sizeof($this->parameters) == 1 && !isset($this->parameters[0]{1})) unset($this->parameters[0]);		
		
		// Если не создан массив прошлых URL - маршрутов, создадим его
		if( !isset($_SESSION[ self::S_PREVIOUS_KEY ]) ) $_SESSION[ self::S_PREVIOUS_KEY ] = array();

		// Если не создан массив прошлых URL - маршрутов, создадим его
		if( !isset($_SESSION[ self::S_BOOKMARK_KEY ]) ) $_SESSION[ self::S_BOOKMARK_KEY ] = array();

		// Фильтруем аяксовые запросы
		if($_SERVER['HTTP_ACCEPT'] != '*/*')
		{
			// Запишем в сессию объект URL для хранения параметров предыдущего маршрута		 
			array_unshift( $_SESSION[ self::S_PREVIOUS_KEY ], serialize($this) );	
	
			// Если стек разросься обрежим его
			if( sizeof($_SESSION[ self::S_PREVIOUS_KEY ]) > self::S_PREVIOUS_SIZE )
			{
				$_SESSION[ self::S_PREVIOUS_KEY ] = array_slice( $_SESSION[ self::S_PREVIOUS_KEY ], 0, self::S_PREVIOUS_SIZE );
			}
		}
	}
}