<?php
namespace samson\core;

/**
 * Интерфейс определяющий возможность сжатия модуля 
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 0.1
 */
interface iModuleCompressable
{	
	/**
	 * Предобработчик сжатия модуля
	 *
	 * @param object 	$obj 	Объект который выполняет процесс сжатия модуля
	 * @param array 	$code	Соллекция кода модуля которую необходимо заполнить
	 */
	function beforeCompress( & $obj = null, array & $code = null );
	
	/**
	 * Постобработчик сжатия модуля 
	 * 
	 * @param object 	$obj 	Объект который выполняет процесс сжатия модуля
	 * @param array 	$code	Соллекция кода модуля которую необходимо заполнить
	 */
	function afterCompress( & $obj = null, array & $code = null );
}