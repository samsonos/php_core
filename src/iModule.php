<?php
namespace samson\core;

/**
 * Интерфейс для модуля системы
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 *
 */
interface iModule
{
	/** Identifier for default view data entry */
	const VD_POINTER_DEF = '';
	
	/** Pointer to plain html entry in view data entry */
	const VD_HTML = '__html__';
	
	/** Default controller name */
	const CTR_BASE = '__base';	
	
	/** Universal controller name */
	const CTR_UNI = '__handler';	
	
	/** Post controller name */
	const CTR_POST = '__post';
	
	/** Put controller name */
	const CTR_PUT = '__put';
	
	/** Delete controller name */
	const CTR_DELETE = '__delete';

    /** Controllers naming conventions */

    /** Procedural controller prefix */
    const PROC_PREFIX = '_';
    /** OOP controller prefix */
    const OBJ_PREFIX = '__';
    /** AJAX controller prefix */
    const ASYNC_PREFIX = 'async_';
	
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
	 * @param string $method_name 	Имя метода контроллера для выполнения
	 * @return integer Результат выполнения метода контроллера
	 */
	public function action( $method_name = NULL );
	
	/**
	 * Установить/Получить HTML представление модуля
	 * 
	 * @param string $value Значение HTML для установки в представление
	 * @return iModule/string Указатель на текущий модуль для цепирования или текущее значение HTML представления 
	 */
	public function html( $value = NULL );
}