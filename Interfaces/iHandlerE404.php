<?php
namespace samson\core;

/**
 * Специальный интерфейс для определения системного обработчика 404 ошибки
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 */
interface iHandlerE404
{
	/**
	 * Обработчик 404 ошибки
	 */
	function handler_e404();	
}
?>