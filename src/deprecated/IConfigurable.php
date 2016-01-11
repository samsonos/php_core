<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 10.01.2015
 * Time: 15:18
 */
namespace samsonos\core;

use samsonframework\core\ConfigurableInterface;

/**
 * Give object instances ability for custom implementation of its configuration,
 * if class does not implements this interface then generic configure logic will
 * be applied to it.
 * @package samsonos\core
 * @author Vitaly Egorov <egorov@samsonos.com>
 * @deprecated Use samsonframework\core\ConfigurableInterface
 */
interface IConfigurable extends ConfigurableInterface
{

}
