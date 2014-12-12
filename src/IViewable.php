<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 12.12.2014
 * Time: 9:50
 */

namespace samson\core;

/**
 * Generic view rendering interface defining
 * all main view context operations.
 * @package samson\core
 */
interface IViewable
{
    /**
     * Set view variable
     * @param string $key   Variable key\prefix for objects and arrays
     * @param mixed $value Variable value
     * @return IViewable Chaining
     */
    public function set($key, $value);

    /**
     * Set current view for rendering.
     * Method searches for the shortest matching view path by $pathPattern,
     * from loaded views.
     *
     * @param string $pathPattern  Path pattern for view searching
     * @return IViewable Chaining
     */
    public function view($pathPattern);

    /**
     * Render current view.
     * Method uses current view context and outputs rendering
     * result.
     *
     * @return string Rendered view
     */
    public function output();
}