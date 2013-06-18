<?php
namespace Samson\Core;

/**
 * Универсальный интерфейс для подключения модулей в ядро SamsonPHP
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @author Nikita Kotenko <nick.w2r@gmail.com>
 * @version 0.1
 */
interface iModuleConnector
{	
	/**
	 * Специальный обработчик для выполнения процесса проверки и подготовки
	 * подключаемого в ядро системы модуля. 
	 */
	public function prepare();
	
	/**
	 * Специальный обработчик для финальной инициализации подключаемого в ядро системы модуля. 
	 * Этот метод должен установить специфические параметры для модуля, выполнить создание всех
	 * необходимых классов модуля и их инициализацию.
	 * @param array $params Необязательная коллекция параметров для инициализации модуля 
	 */
	public function init( array $params = array() );
}
?>