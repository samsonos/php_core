<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 11.12.2014
 * Time: 11:50
 */

namespace samson\core;

/**
 * Interface for giving ability to class for generic configuration
 * @package samson\core
 * @author Vitaly Egorov <egorov@samsonos.com>
 */
interface IConfigurable
{
    /**
     * Perform instance configuration
     * @param array $params Collection of parameters for configuration
     * @return bool True if class has been successfully configured
     */
    public function configure(array $params = array());
}
