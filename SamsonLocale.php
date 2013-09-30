<?php
namespace samson\core;

/**
 * Класс для поддержки локализации веб-приложения
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @package SamsonPHP 
 * @version 0.1.0
 */
class SamsonLocale
{
	/** Локализация по умолчанию */
	const DEF = '';
	/** Украинская локализация */
	const UA = 'ua';
	/** Английская локализация */
	const EN = 'en';
	
	/**
	 * Коллекция поддерживаемых локализаций
	 * Используется для дополнительного контроля за локализациями
	 * @var array
	 */
	private static $supported = array( SamsonLocale::DEF, SamsonLocale::EN, SamsonLocale::UA );
	
	/**
	 * Текущая локализация веб-приложения
	 * @var string
	 */
	public static $current_locale = '';
	
	/**
	 * Коллекция подключенных локализаций для текущего веб-приложения
	 * Локаль по умолчанию RU, имеет представление пустышку - ''
	 * @var array
	 */
	private static $locales = array('');
	
	/**
	 * Проверить текущей значение установленной локали, и если выставлена
	 * не поддерживаемая локаль - установим локаль по умолчанию
	 */
	public static function check()
	{
		// Проверим значение установленной текущей локализации, если оно не входит в список доступных локализаций
		// установим локализацию по умолчанию
		if( ! in_array( strtolower(self::$current_locale), self::$locales ) ) self::$current_locale = self::DEF;
	}
	
	/**
	 * Установить все доступные локализации для текущего веб-приложения.
 	 * Локализацию по умолчанию, передавать не нужно, т.к. она уже включена в список
 	 * и описана в <code>SamsonLocale::DEF</code>
 	 * 
	 * Функция автоматически проверяет уже выставленное значение локализации веб-приложения
	 * и вслучаи его отсутствия, выставляет локализацию по умолчанию  
	 * 
	 * @param array $available_locales Коллекция с доступными локализациями веб-приложения
	 */
	public static function set( array $available_locales )
	{
		// Переберем локализации
		foreach ( $available_locales as $locale )
		{
			// Добавим в коллекцию доступных локализаций переданные
			if( in_array( strtolower($locale), self::$supported ) ) self::$locales[] = strtolower($locale);
			// Проверим разрешаемые локали
			else die('Устанавливаемая локализация "'.$locale.'" - не предусмотрена в SamsonLocale');		
		}			

		// Проверим значение установленной текущей локализации, если оно не входит в список доступных локализаций
		// установим локализацию по умолчанию
		self::check();
	}
	
	/**
	 * Получить все доступные локализации для текущего веб-приложения
	 */
	public static function get(){ return self::$locales; }
	
	/**
	 * Установить/Получить текущую локализацию веб-приложения
	 * @param string $locale Значение локализации веб-приложения для установки
	 * @return string 	Возвращается значение текущей локализации веб-приложения до момента 
	 * 					вызова данного метода 
	 */
	public static function current( $locale = NULL )
	{
		// Сохраним старое значение локали
		$_locale = self::$current_locale;	
		
		// Если ничего не передано - вернем текущее значение локали 
		if( !isset($locale) ) return $_locale;
		// Нам передано значение локали 
		else 
		{	
			// Только большой регистр
			$locale = strtolower( $locale );
			// Если требуется установть доступную локализацию
			//if( in_array( $locale, self::$locales ) ) self::$current_locale = $locale;
			// Установим локализацию по умолчанию
			//else self::$current_locale = SamsonLocale::DEF;
			self::$current_locale = $locale;
			
			// Запишем текущее значение локализации
			$_SESSION['__SAMSON_LOCALE__'] = self::$current_locale;					
			
			// Вернем текущее значение локали сайта до момента візова метода
			return $_locale;
		}	
	}
	
	public static function find($list)
	{
		//print_r(SamsonLocale::$locales);
		$key = false;
		foreach (self::$locales as $locale)
		{
			$key = array_search($locale, $list);
			if ($key!==false)
			{
				self::current($locale);break;
			}
		}
		return $key; 
	} 
}

// Установим текущую локаль из сессии, если оно там имеется
//if( isset( $_SESSION['__SAMSON_LOCALE__'] ) ) SamsonLocale::$current_locale = strtolower($_SESSION['__SAMSON_LOCALE__']);

/*if ( strpos($_SERVER["REQUEST_URI"], '/locale/')!==FALSE)
{
	SamsonLocale::find();
	// Розпарсим URL 
		$url = parse_url( $_SERVER["REQUEST_URI"] ); 	
		
		// Отрежем путь к текущему веб-приложению из пути и декодируем другие символы
		$url = trim( urldecode( $url['path'] ));	

		// Получим массив переданных аргументов маршрута системы из URL
		// Отфильтруем не пустые элементы, не переживая что мы упустим поряд их следования
		// так номера элементов в массиве сохраняются
		$url_args = explode( '/', $url );	
		
		$key = array_search('locale', $url_args);	
		$search = '/locale';	
		if (isset($url_args[$key+1]))  
		{
			SamsonLocale::current($url_args[$key+1]);
			$search .= '/'.$url_args[$key+1];
		}

		$_SERVER["REQUEST_URI"] = str_replace($search, '', $_SERVER["REQUEST_URI"]);	
}*/