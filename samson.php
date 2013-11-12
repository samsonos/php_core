<?php
/**
 * Пусковой файл SamsonPHP
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 5.0.0
 */

// Константы фреймворка SamsonPHP

/**  Отметка времени начала работы фреймворка */
define('__SAMSON_T_STARTED__', microtime(TRUE));

/** Установим версию фреймворка */
define('__SAMSON_VERSION__', '5.1.1');

/** Совместимость с PHP 5 */
if(!defined('__DIR__')) define( '__DIR__', dirname(__FILE__));
 
/** Получим путь к фреймфорку SamsonPHP */
define( '__SAMSON_PATH__', __DIR__.'/' );

/** Получим текущий каталог веб-приложения */
define('__SAMSON_CWD__', str_ireplace('\\', '/', getcwd().'/' ) );

/** Получим путь к текущему веб-приложению относительно корня сайта */
define('__SAMSON_BASE__', str_ireplace( $_SERVER['DOCUMENT_ROOT'], '', __SAMSON_CWD__ ) );

/** Объявим константу для раздели пути в namespace */
define('__NS_SEPARATOR__', '\\');

/** Flag that this script runs from remote app */
define( '__SAMSON_REMOTE_APP', __SAMSON_CWD__ !== $_SERVER['DOCUMENT_ROOT'].'/' );

/** Путь к папке где находятся кеш системы */
define('__SAMSON_CACHE_PATH','cache');

/** Путь к файлу с глобальными данными модуля */
define( '__SAMSON_GLOBAL_FILE', 'global.php' );

/** Путь к папке где находятся файлы системы */
define('__SAMSON_APP_PATH','app');

/** Путь к папке где находятся контроллеры системы */
define('__SAMSON_CONTOROLLER_PATH', __SAMSON_APP_PATH.'/controller/');

/** Путь к папке где находятся модели системы */
define('__SAMSON_MODEL_PATH', __SAMSON_APP_PATH.'/model/');

/**  Путь к папке где находятся представления системы */
define('__SAMSON_VIEW_PATH', __SAMSON_APP_PATH.'/view/');

/** Путь к файлу с главным шаблоном системы */
define('__SAMSON_DEFAULT_TEMPLATE', __SAMSON_VIEW_PATH.'index.php' );

/** Максимальное время выполнения скрипта */
define( '__SAMSON_MAX_EXECUTION__', 60 );

/** Действие контроллера выполнено успешно */
define( 'A_SUCCESS', TRUE );

/** Действие контроллера НЕ выполнено */
define( 'A_FAILED', FALSE );

// Установим временную локаль
date_default_timezone_set(date_default_timezone_get() );

// Установим ограничение на выполнение скрипта
set_time_limit( __SAMSON_MAX_EXECUTION__ );

// Установим ограничение на выполнение скрипта
ini_set( 'max_execution_time', __SAMSON_MAX_EXECUTION__ );

//echo microtime(TRUE) - __SAMSON_T_STARTED__;

// Начать сессию
session_start();

// Remove unnessesar files umask
$old_umask = umask(0);

//print_R($_SESSION);

//echo microtime(TRUE) - __SAMSON_T_STARTED__;

// Подключение основных файлов фреймворка
require('include.php');

//
// Функции шорткаты(Shortcut) - для красоты и простоты кода системы
//


/**
 * System(Система) - Получить объект для работы с ядром системы
 * @return samson\core\Core Ядро системы
 */
function & s()
{
	// т.к. эта функция вызывается очень часто - создадим статическую переменную
	static $_v; 
	
	// Если переменная не определена - получим единственный экземпляр ядра	
	if( ! isset($_v) )
	{		
		// Если существует отпечаток ядра, загрузим его
		if( isset( $GLOBALS["__CORE_SNAPSHOT"]) ) 
		{			
			$_v = unserialize(base64_decode($GLOBALS["__CORE_SNAPSHOT"]));			
		}
		// Создадим экземпляр
		else $_v = new samson\core\Core();		
	}
		
	// Вернем указатель на ядро системы
	return $_v; 
}

/**
 * Error(Ошибка) - Получить класс для работы с ошибками и отладкой системы
 *
 * @return \samson\core\Error Класс для работы с ошибками и отладкой системы
 */
function & error(){static $_error; return ( $_error = isset($_error) ? $_error : new \samson\core\Error());}

// Создадим экземпляр класса
error();

/**
 * Module(Модуль) - Получить Текущий модуль / Модуль по имени из стека модулей системы
 * @see iCore::module();
 * 
 * @param mixed $module Указатель на модуль системы * 
 * @return \samson\core\Module Текущую / Модель по её имени / или FALSE если модель не найдена
 */
function & m( $module = NULL )
{ 
	// т.к. эта функция вызывается очень часто - создадим статическую переменную
	static $_s; 
	
	// Если переменная не определена - получим единственный экземпляр ядра
	if( !isset($_s)) $_s = & s();
	
	// Вернем указатель на модуль системы
	return $_s->module( $module );	
}

/**
 * Module Out - получить специальный виртуальный модуль ядра системы "LOCAL" для генерации и вывода
 * промежуточных представлений, что бы избежать затирания/изменения контекста текущего модуля
 * системы. Эта функция является шорткатом вызова m('local')
 * 
 * @see m()
 * @deprecated Use just m() thank's to new rendering model
 * @return iModule Указатель на виртуальный модуль "LOCAL" ядра системы
 */
function & mout(){ return m();  }

/**
 * View variable( Переменная представления ) - Вывести значение переменной представления
 * текущего модуля системы в текущий поток вывода.
 * 
 * @see iModule
 * @see iCore::active()
 * 
 * Это дает возможность использовать функцию в представлениях для более компактной записи:
 * <code><?php v('MODULE_VAR')?></code>
 * 
 * Для возравщения значения переменной, без её вывода в поток, необходимо использовать
 * 	Для переменный представления:
 * 		m( MODEL_NAME )->set( VAR_NAME ) либо $VIEW_VAR_NAME
 * 	Для переменных модуля:
 * 		m( MODEL_NAME )->VAR_NAME;
 * 
 * @param string $name 	Имя переменной представления текущего модуля 
 */
function v( $name, $realName = NULL )
{ 
	// Получим указатель на текущий модуль
	$m = & m();
	
	// Если передана ПРАВДА - используем первый параметр как имя
	if( is_bool( $realName ) && ($realName === true)) $realName = '__dm__'.$name;
	else $realName = '__dm__'.$realName;
	
	// Если задано специальное значение - выведем его
	if( isset($realName) && $m->offsetExists( $realName ))echo $m[ $realName ];	
	// Если дополнительный параметр не задан и у текущего модуля задана требуемое
	// поле - выведем его значение в текущий поток вывода
	else if ( $m->offsetExists( $name )) echo $m[ $name ];
	// Otherwise just output 	
	else echo $name;
}

/** 
 * IV(If view variable) - output view variable only if is correctly set for view output * 
 */
function iv( $name, $realName = NULL )
{
	// Cache current module pointer
	$m = & m();
	
	// If view variable is set - echo it
	if ( isvalue( $m, $name) ) echo $m[ $name ];	
}



/**
 * View variable for Input( Переменная представления ) - Вывести значение переменной представления
 * текущего модуля системы в текущий поток вывода c декодирования HTML символов.
 * 
 * Используется для HTML полей ввода 
 * 
 * @see v() 
 * @param string $name 	Имя переменной представления текущего модуля 
 */
function vi( $name ){ $m = & m();  if( $m->offsetExists( $name )) echo htmlentities($m[ $name ], ENT_QUOTES,'UTF-8');}
	
/**
 * Figure out if module view variable value is correctly set for view output
 * 
 * @param iModule	$m		Pointer to module
 * @param string 	$name 	View variable name
 * @param mixed 	$value	Value to compare
 * @return boolean If view variable can be displayed in view
 */
function isvalue( $m, $name, $value = null )
{	
	// If we have module view variable
	if( isset($m[ $name ]) )
	{
		// Get value
		$var = $m[ $name ];
		
		//trace($name.'-'.$var.'-'.gettype( $var ).'-'.$value);
	
		// Get variable type
		switch( gettype( $var ) )
		{
			// If this is boolean and it matches $value
			case 'boolean': return $var === $value; 			
			// If this is number and it matches $value
			case 'integer': return $var === intval($value); 	
			// If this is double and it matches $value
			case 'double':  return $var === doubleval($value); 	
			// If this is not empty array
			case 'array':   return sizeof($var); 			
			// If this is a string and it matches $value or if no $value is set string is not empty
			case 'string':  return  (!isset($value) && isset($var{0})) || 
									(isset($value) && $var === strval($value)) ; 
			// If this is an object consider it as ok
			case 'object': return true;
			// Not supported for now
			case 'NULL':
			case 'unknown type': 
			default: return false;
		}		
	}
}

/**
 * Is Value( Является ли значением) - Проверить является ли переменная представления 
 * указанным значением. Метод проверяет тип переменной и в зависимости от этого проверяет 
 * соответствует ли переменная представления заданному значению:
 *  - проверяется задана ли переменная вообще
 *  - если передана строка то проверяется соответствует ли она заданному значению
 *  - если передано число то проверяется равно ли оно заданому значению 
 *  
 * Все сравнения происходят при преобразовании входного значения в тип переменной
 * представления. 
 * 
 * По умолчанию выполняется сравнение значения переменной представления 
 * с символом '0'. Т.к. это самый частый вариант использования когда необходимо получить значение
 * переменной объекта полученного из БД, у которого все поля это строки, за исключением
 * собственно описанных полей.
 * 
 * @param string 	$name 		Module view variable name
 * @param mixed 	$value 		Value for checking
 * @param string	$success	Value for outputting in case of success  
 * @param string	$failure	Value for outputting in case of failure 
 * @param boolean	$inverse	Value for outputting in case of success
 * @return boolean Соответствует ли указанная переменная представления переданному значению
 */
function isval( $name, $value = null, $success = null, $failure = null, $inverse = false)
{	
	// Pointer to current module
	$m = & m();
	
	// Flag for checking module value
	$ok = isvalue( $m, $name, $value );
	
	// If inversion is on
	if( $inverse ) $ok = ! $ok;
	
	// If we have success value - output it
	if( $ok && isset( $success) ) v($success);
	// If we have failure value - output it
	else if( isset($failure)) v($failure);
	
	return $ok;
}

/**
 * Is Variable exists, also checks:
 *  - if module view variable is not empty array
 *  - if module view variable is not empty string
 *
 * @param string 	$name Имя переменной для проверки
 * @param string	$success	Value for outputting in case of success
 * @param string	$failure	Value for outputting in case of failure    
 * @return boolean True if variable exists
 */
function isv( $name, $success = null, $failure = null ){ return isval($name, null, $success, $failure ); }

/**
 * Is Variable DOES NOT exists, also checks:
 *  - if module view variable is empty array
 *  - if module view variable is empty string
 *
 * @param string 	$name Имя переменной для проверки
 * @param string	$success	Value for outputting in case of success
 * @param string	$failure	Value for outputting in case of failure 
 * @return boolean True if variable exists
 */
function isnv( $name, $success = null, $failure = null ){ return isval( $name, null, $success, $failure, true ); }

/**
 * Is NOT value - checks if module view variable value does not match $value 
 * 
 * @param string 	$name 	 Module view variable name
 * @param mixed 	$value 	 Value for checking
 * @param string	$success Value for outputting in case of success
 * @param string	$failure Value for outputting in case of failure   
 * @return boolean True if value does NOT match
 */
function isnval( $name, $value = null, $success = null, $failure = null ){ return isval($name, $value, $success, $failure, true ); }

/**
 * Echo HTML link tag with text value from module view variable
 *  
 * @param string $name	View variable name
 * @param string $href	Link url
 * @param string $class	CSS class
 * @param string $id	HTML identifier
 * @param string $title	Title tag value
 */
function vhref( $name, $href = null, $class = null, $id = null,  $title = null )
{
	$m = & m();
	
	// If value can be displayed
	if( isvalue( $m, $name ) )
	{		
		echo '<a id="'.$id.'" class="'.$class.'" href="'.$href.'" title="'.$title.'" >'.$m[ $name ].'</a>';
	}
}

/**
 *
 * @param unknown $src
 * @param string $id
 * @param string $class
 * @param string $alt
 * @param string $dummy
 */
function vimg( $src, $id='', $class='', $alt = '', $dummy = null )
{
	// Закешируем ссылку на текущий модуль
	$m = & m();

	// Проверим задана ли указанная переменная представления в текущем модуле
	if( $m->offsetExists( $src )) $src = $m[ $src ];
	//
	elseif( isset($dummy))$src = $dummy;

	// Выведем изображение
	echo '<img src="'.url()->build($src).'" id="'.$id.'" class="'.$class.'" alt="'.$alt.'" title="'.$alt.'">';
}

/**
 * Output view variable as date with formating
 * If view variable exists then output it as date with formatting
 *
 * @param string $name 	 Module view variable name
 * @param string $format Date format string
 */
function vdate( $name, $format = 'd.m.y' )
{
	// Cache current module
	$m = & m();

	// If view variable is set - echo with format
	if ( $m->offsetExists( $name )) echo date( $format, strtotime($m[ $name ]));
	
}

/**
 * Is Module ( Является ли текущий модуль указанным ) - Проверить совпадает ли имя текущего модуля с указанным 
 * 
 * @param string $name Имя требуемого модуля для сравнения с текущим 
 * @return boolean Является ли имя текущего модуля равным переданному
 */
function ism( $name ){ return (m()->id() == $name); };

/**
 * Error(Ошибка) - Зафиксировать ошибку работы системы
 * 
 * @param string 	$error_msg	Текст ошибки
 * @param numeric 	$error_code	Код ошибки
 * @param mixed 	$args		Специальные "жетоны" для вставки в текст ошибки
 * @param mixed 	$ret_val	Value that must be returned by the function
 * @return boolean FALSE для остановки работы функции или условия
 */
function e( $error_msg = '', $error_code = E_USER_NOTICE, $args = NULL, & $ret_val = false )
{	
	// Сохраним указатель на класс в память
	static $_e; 
	
	// Получим ошибку			
	$_e = isset( $_e ) ? $_e : error();
	
	// Если передан только один аргумент то сделаем из него массив для совместимости
	$args = is_array( $args ) ? $args : array( $args );
	
	// "Украсим" сообщение об ошибке используя переданные аргументы, если они есть
	if( isset( $args ) ) $error_msg = debug_parse_markers( $error_msg, $args );
	
	// Вызовем обработчик ошибки, передадим правильный указатель
	error()->handler( $error_code, $error_msg, NULL, NULL, NULL, debug_backtrace() );

	return $ret_val;
}

/**
 * Получить содержание представления.
 *
 * Данный метод выполняет вывод ПРЕДСТАВЛЕНИЯ(Шаблона) с подключением
 * переменных и всей логики ядра системы в отдельный буферезированный поток вывода. Нужно не путать с методом
 * которой подключает обычные PHP файлы, т.к. этот метод отвечает только за вывод представлений.
 *
 * Так же при выводе представления учитываются все установленные пути приложения.
 *
 * @param string $view 			Путь к представлению для вывода
 * @param string $vars 			Коллекция переменных которые будут доступны в выводимом представлении
 * @param string $prefix 		Дополнительный префикс который возможно добавить к именам переменных в представлении
 *
 * @see iCore::import
 *
 * @return string Содержание представления
 */
function output( $view, array $vars = NULL, $prefix = NULL )
{		
	return s()->render($view,$vars);	
}

/**
 * URL(УРЛ) - Получить объект для работы с URL
 * @return samson\core\URL Объект для работы с URL
 */
function & url(){ static $_v; return ( $_v = isset($_v) ? $_v : new \samson\core\URL()); }

/**
 * Построить полный URL с учетом относительного пути и вывести его в текущий поток вывода
 * Функция может принимать любое количество аргументов начиная со второго, и их значения будут
 * переданы в URL пути как параметры.
 *
 * Каждый переданный параметр начиная со 2-го, расценивается как переменная представления модуля
 * и в случаи её отсутствия просто выводится как строка
 *
 * @see URL::build()
 *
 * @return string Полный URL с параметрами
 */
function url_build()
{
	static $_v;

	$_v = isset($_v) ? $_v : url();

	$args = func_get_args();

	return call_user_func_array( array( $_v, 'build' ), $args );
}

/** 
 * Echo builded from module view parameters URL
 * @see url_build 
 */
function url_base(){ $args = func_get_args(); echo call_user_func_array( 'url_build', $args ); }

/**
 * Построить полный URL с учетом относительного пути и текущей локали и вывести его в текущий поток вывода
 * Функция может принимать любое количество аргументов начиная со второго, и их значения будут
 * переданы в URL пути как параметры.
 *
 * Каждый переданный параметр начиная со 2-го, расценивается как переменная представления модуля
 * и в случаи её отсутствия просто выводится как строка
 *
 * @see URL::build()
 *
 * @param string $url Начальный URL-Путь для построения
 * @return string Полный URL с параметрами
 */
function url_locale_base( $url = '' )
{
	static $_v;

	$_v = isset($_v) ? $_v : url();

	$args = func_get_args();
	
	if (locale() != '') $args=array_merge(array(locale()), $args);

	echo call_user_func_array( array( $_v, 'build' ), $args );
}

/**
 * Построить полный URL с учетом относительного пути для текущего модуля и вывести его в текущий поток вывода
 * Функция может принимать любое количество аргументов, и их значения будут переданы в URL пути как параметры.
 *
 * Каждый переданный параметр расценивается как переменная представления модуля и в случаи её отсутствия просто
 * выводится как строка
 *
 * @see URL::build()
 *
 * @return string Полный URL с параметрами
 */
function module_url()
{
	static $_v;

	$_v = isset($_v) ? $_v : url();

	$func_args = func_get_args();

	$args = array_merge( array( $_v->module()), $func_args );

	echo call_user_func_array( array( $_v, 'build' ), $args );
}

/**
 * Установить все доступные локализации для текущего веб-приложения.
 * Локализацию по умолчанию, передавать не нужно, т.к. она уже включена в список
 * и описана в <code>SamsonLocale::DEF</code>
 *
 * Достуные локализации передаются в функцию в виде обычных аргументов функции:
 * Для исключения ошибок рекомендуется передавать константы класса SamsonLocale
 * <code>setlocales( SamsonLocale::UA, SamsonLocale::EN )</code>
 *
 * @see SamsonLocale::set()
 */
function setlocales(){ \samson\core\SamsonLocale::set( func_get_args() ); }

/**
 * Установить/Получить текущую локализацию сайта
 *
 * @see SamsonLocale::current()
 * @param string $locale Значение локали
 * @return string Возвращает текущее значение локали сайта до момента вызова метода
 */
function locale( $locale = NULL ){ return \samson\core\SamsonLocale::current( $locale ); }

/**
 * Check if passed locale alias matches current locale and output in success
 * @param string $locale Locale alias to compare with current locale
 * @param string $output Output string on success
 * @return boolean True if passed locale alias matches current locale
 */
function islocale( $locale, $output = '' ){ if( \samson\core\SamsonLocale::$current_locale == $locale) { echo $output; return true; } return false; }

/** 
 * @param string $l Locale name
 * @return string locale path if current locale is not default locale
 */
function locale_path( $l = null )
{ 
	
	$l = !isset($l) ? locale() : $l;
	 
	return ($l != \samson\core\SamsonLocale::DEF)? $l.'/' : '';
}

/**
 * Сформировать правильное имя класса с использованием namespace, если оно не указано
 * Функция нужна для обратной совместимости с именами классов без NS
 * 
 * @param string $class_name Имя класса для исправления
 * @param string $ns		 Пространство имен которому принадлежит класс
 * @return string Исправленное имя класса
 */
function ns_classname( $class_name, $ns = 'samson\activerecord' )
{	
	// If passed class already exists - do nothing
	if( class_exists($class_name) ) return $class_name;
	// We must build correcyt class name
	else 
	{
		// If core rendering model is NOT array loading
		if( s()->render_mode != samson\core\iCore::RENDER_ARRAY )
		{ 
			return ( strpos($class_name, __NS_SEPARATOR__) !== false ) ? $class_name : $ns.__NS_SEPARATOR__.$class_name;
		}
		// Array loading render model
		else 
		{
			// If classname with namespaces passed - transform it
			if( strpos($class_name, __NS_SEPARATOR__) !== false ) 
			{
				// Get only namespace from classname
				$ns = trim(nsname($class_name));
				
				// Remove starting \ from namespace
				$ns = $ns{0} == '\\' ? substr( $ns, 1) : $ns;
				
				// Transform namespace and class name
				return str_replace('\\', '_', $ns).'_'.classname( $class_name );
			}
			// Add namespace to class name and transform it
			else return str_replace('\\', '_', $ns).'_'.$class_name;
		}
	}
}

/**
 * Tranform classname with namespace to universal form
 * @param string $class_name Classname for transformation
 * @return mixed Transformed classname in universal format
 */
function uni_classname( $class_name )
{
	return trim(str_replace('\\', '_',strtolower($class_name)));	
}

//elapsed('core included');