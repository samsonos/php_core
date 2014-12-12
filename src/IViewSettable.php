<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 12.12.2014
 * Time: 11:23
 */

namespace samson\core;

/**
 * Interface for giving an object ability to be passed
 * to IViewable for rendering
 * @package samson\core
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
interface IViewSettable
{
    /**
     * Generate collection of view variables, prefixed if needed, that should be passed to
     * view context.
     *
     * @param string $prefix Prefix to be added to all keys in returned data collection
     * @return array Collection(key => value) of data for view context
     */
    public function toView($prefix = '');
}
