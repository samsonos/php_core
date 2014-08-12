<?php
namespace samson\core;

/**
 * Интерфейс определяющий возможность создавания
 * своего представления для отображения в представлении модуля 
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 0.1
 */
interface iModuleViewable
{
	/**
	 * Сгенерировать представление данных текущего объекта
	 * для отображения и использования в представлении модуля
	 * 
	 * @param string 	$prefix 	Специальный префикс для подставления в имена полей передаваемых в представление
	 * @param array 	$restricted Коллекция имен полей класса которые необходимо обязательно игнорировать при генерации  
	 * 								данных для представления	  
	 * @return array Коллекция данных для представление модуля
	 */
	function toView( $prefix = NULL, array $restricted = array() );
}