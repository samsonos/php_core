<?php
namespace samson\core;

use samsonframework\core\RenderInterface;

/**
 * Интерфейс для модуля системы
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 *
 */
interface iModule extends RenderInterface
{
	/** Identifier for default view data entry */
	const VD_POINTER_DEF = '';
	
	/** Pointer to plain html entry in view data entry */
	const VD_HTML = '__html__';

	/** Controller action cache name marker */
	const CTR_CACHE = 'cache_';
	
	/** Default controller name */
	const CTR_BASE = '__base';
	const CTR_CACHE_BASE = '__cache_base';
	
	/** Universal controller name */
	const CTR_UNI = '__handler';
	const CTR_CACHE_UNI = '__cache_handler';
	
	/** Post controller name */
	const CTR_POST = '__post';
	const CTR_CACHE_POST = '__cache_post';
	
	/** Put controller name */
	const CTR_PUT = '__put';
	const CTR_CACHE_PUT = '__cache_put';
	
	/** Delete controller name */
	const CTR_DELETE = '__delete';
	const CTR_CACHE_DELETE = '__delete';

    /** Controllers naming conventions */

    /** Procedural controller prefix */
    const PROC_PREFIX = '_';
    /** OOP controller prefix */
    const OBJ_PREFIX = '__';
    /** AJAX controller prefix */
    const ASYNC_PREFIX = 'async_';
	/** CACHE controller prefix */
	const CACHE_PREFIX = 'cache_';
	
	/**
	 * Установить заголовок страницы
	 * @param string $title Значение для заголовка страницы
	 */
	public function title( $title = NULL );
	
	/**
	 * Получить реальный идентификатор модуля
	 * @return string Реальный идентификатор модуля
	 */
	public function id();
	
	/**
	 * Установить/Получить относительный путь к файлам данного модуля
	 *
	 * @param string $value Значение пути к файлам модуля для установки
	 * @return iModule Указатель на самого себя для "ЦЕПИРОВАНИЯ"
	 */
	public function path( $value = NULL );
	
	/**
	 * Выполнить прорисовку данного модуля
	 * Если передан метод контроллера модуля, попытаемся его выпонить перед прорисовкой модуля 
	 * 
	 * @param string $controller Метод контроллера для выполнения
	 */
	public function render( $controller = NULL );
	
	/**
	 * Выполнить метод контроллера, варианты выполнения:
	 * 	- Если ничего не передано то выполним базовый контроллер модуля
	 * 		<code>ИМЯ_МОДУЛЯ( $p1, $p2, ... )</code> 
	 * 	- Если базовый контроллер не найден то выполняется попытка вызова универсального контроллера
	 * 		<code>ИМЯ_МОДУЛЯ__HANDLER( $p1, $p2, ... )</code> 
	 * 	- Если передан специальный идентификатор типа контроллера то выполняется попытка его вызова 
	 * 		<code>ИМЯ_МОДУЛЯ__[POST,PUT,DELETE]( $p1, $p2, ... )</code>
	 *
	 * @param string $methodName 	Имя метода контроллера для выполнения
	 * @return integer Результат выполнения метода контроллера
	 */
	public function action($methodName = NULL );
	
	/**
	 * Установить/Получить HTML представление модуля
	 * 
	 * @param string $value Значение HTML для установки в представление
	 * @return iModule/string Указатель на текущий модуль для цепирования или текущее значение HTML представления 
	 */
	public function html( $value = NULL );
}