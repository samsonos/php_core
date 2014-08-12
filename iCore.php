<?php
namespace samson\core;

/**
 * Интерфейс для ядра системы
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 */
interface iCore
{		
	/* Rendering models */
	/** Standard algorithm for view rendering */
	const RENDER_STANDART = 1;
	/** View rendering algorithm from array of view paths */
	const RENDER_ARRAY = 2;
	/** View rendering algorithm from array of view variables */
	const RENDER_VARIABLE = 3;
	
	/**
	 * Module resource manager, working gathering all resources from
	 * specified path and arranging them by file extension
	 *  
	 * @param string 	$path 	Path to scan for module resources
	 * @param array 	$ls		Returnable array of gathered resources
	 */
	public function resources( & $path, & $ls = array(), & $files = null );
	
	/**
	 * Прорисовать файл представления
	 * 
	 * @param string 	$view_path Путь к файлу представления
	 * @param array 	$view_data Коллекция данных для передачи в представление
	 * @return string Обработанное прорисованное представление
	 */
	public function render( $view_path, $view_data = array() );
	
	/**
	 * Add template render function to render stack 
	 * @param mixed $render_handler Pointer to render function
	 * @param number $position Position in render stack to place render function
	 * @return array If nothing passed returns current render handler stack
	 */
	public function renderer( $render_handler = null, $position = null );
	
	/**
	 * Установить/Получить флаг ассинхронности вывода ответа системы
	 * 
	 * @param boolean $async Флаг ассинхронности вывода ответа системы
	 * @return boolean Значение флага ассинхронности вывода ответа системы
	 */
	public function async( $async = NULL );
	
	/**
	 * Установить/Получить путь к основному шаблону системы
	 * 
	 * @param string $template Путь к основному шаблону системы
	 * @return iCore/string Указатель на ядро системы для цепирования / Путь к основному шаблону системы
	 */
	public function template( $template = NULL );
	
	/**
	 * Установить относительный путь к файлам и ресурсам данного Веб-приложения
	 * 
	 * @param string $path Относительный путь к Веб-приложению
	 */
	public function path( $path = NULL );
	
	/**
	 * Установить/Получить текущий активный модуль системы
	 *
	 * @param mixed $module Указатель на модуль для установки
	 * @return iModule Текущий модуль системы до момента вызова данного метода
	 */
	public function & active( iModule & $module = NULL );
	
	
	/**
	 * Получить модуль из стека загруженых модулей системы
	 * по его имени. Если ничего не передано то возвращается
	 * текущий модуль системы.
	 * 
	 * @param string $module Имя модуля
	 * @return iModule Модуль системы 
	 */
	public function & module( & $module = NULL );	
	
	/**
	 * Load module into the framework core
	 *
	 * @param string 	$path	Path to module for loading	 
	 * @return iCore Self reference for chaining
	 */
	public function load( $path = NULL );

	/**
	 * Unload module from framework core 
	 * 
	 * @param string $id Module identifier
	 */
	public function unload( $id );
	
	/**
	 * Обработчик ошибки e404 
	 * Если в метод не переданы аргументы то выполняется текущий обработчик
	 * иначе устанвливается указатель на внешний
	 * 
	 * @param string $callable Указатель на внешний обработчик ошибки
	 */
	public function e404( $callable = null );
	
	/**
	 * Запустить ядро системы
	 * 
	 * После вызова этого метода выполняется прорисвка
	 * всего приложения
	 * 
	 * @param string $default Имя модуля по умолчанию
	 */
	public function start( $default ); 
}