<?php
namespace samson\core;

/**
 * Модуль имеющий единственный свой экземпляр c расширенными возможностями 
 * подключения к фреймворку. 
 * 
 * Удобен для использования во внешних модулях у которых всего один внешний главный
 * класс и это позволяет всю его логику внести в SingletonModule не создавая 2 отдельных
 * файла и класса.
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @version 0.1
 * @deprecated
 */
class SingletonModule extends Service 
{	
	public function __construct() 
	{
		e('Module(##) - Class(##) is deprecated use parent class(##) instead', E_SAMSON_CORE_ERROR, array( $this->id, __CLASS__,get_parent_class(__CLASS__)));
		
		parent::__construct( func_get_args() );
	}
}