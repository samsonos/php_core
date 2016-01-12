<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 12.01.16
 * Time: 16:08
 */
namespace samsonphp\tests;

use samson\core\Module;

/**
 * Class TestingModule
 * @package samsonphp\tests
 */
class TestingModule extends Module
{
    public function __testAction($param = null)
    {
        $this->view('path/inner/index');
    }
}
