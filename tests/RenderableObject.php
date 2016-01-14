<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 12.01.16
 * Time: 17:12
 */
namespace samsonphp\tests;

use samsonframework\core\RenderInterface;

class RenderableObject implements RenderInterface
{
    public $testVar = '1';

    public function toView($prefix = null, array $restricted = array())
    {
        $result = array();
        foreach (get_object_vars($this) as $k => $v) {
            $result[$prefix . $k] = $v;
        }
        return $result;
    }
}
