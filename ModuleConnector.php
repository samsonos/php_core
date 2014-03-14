<?php
namespace samson\core;

/**
 * Модуль системы c расширенными возможностями подключения к фреймворку
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @author Nikita Kotenko <nick.w2r@gmail.com>
 * @version 0.3
 */
class ModuleConnector extends ExternalModule
{	
	public function __construct()
	{
		e('Module(##) - Class(##) is deprecated use parent class(##) instead', E_SAMSON_CORE_ERROR, array( $this->id, __CLASS__,get_parent_class(__CLASS__)));

        $args = func_get_args();

		parent::__construct($args);
	}
}