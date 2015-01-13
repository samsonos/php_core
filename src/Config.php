<?php
//[PHPCOMPRESSOR(remove,start)]
namespace samson\core;

/** Constant to define generic stage at web-application production status */
define('CONFIG_ALL', 0);
/** Constant to define development stage at web-application production status */
define('CONFIG_DEV', 1);
/** Constant to define test stage at web-application production status */
define('CONFIG_TEST', 2);
/** Constant to define final stage at web-application production status */
define('CONFIG_PROD', 3);

/**
 * Module configuration
 * SamsonPHP configuration is based on classes, to configure a module
 * in your web application you must create class that exends this class
 * and all of it public field would be parsed and passed to module
 * at core module load stage.
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <egorov@samsonos.com>
 * @deprecated Use \samsonos\config\Entity
 */
class Config extends \samsonphp\config\Entity
{	

}
//[PHPCOMPRESSOR(remove,end)]
