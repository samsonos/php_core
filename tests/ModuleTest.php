<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 12.01.16
 * Time: 14:33
 */
namespace samsonphp\core\tests;

use samsonphp\core\Core;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    /** @var Core */
    protected $core;

    public function setUp()
    {
        $this->core = new Core();
    }
}
