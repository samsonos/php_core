<?php
namespace samson\core;

use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;

/**
 * Модуль имеющий единственный свой экземпляр c расширенными возможностями 
 * подключения к фреймворку. 
 * 
 * Удобен для использования во внешних модулях у которых всего один внешний главный
 * класс и это позволяет всю его логику внести в Service не создавая 2 отдельных
 * файла и класса.
 *
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com> 
 * @deprecated
 */
class Service extends ExternalModule
{

}
