<?php
namespace samson\core;

/**
 * Модуль системы поддерживающий сжатие
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 0.1
 * @deprecated use CompressableExternalModule
 */
class CompressableModule extends CompressableExternalModule
{		
	public function __construct()
	{
		e('Module(##) - Class(##) is deprecated use parent class(##) instead', E_SAMSON_CORE_ERROR, array( $this->id, __CLASS__,get_parent_class(__CLASS__)));

        $args = func_get_args();
		parent::__construct($args);
	}
}