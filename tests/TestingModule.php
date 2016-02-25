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
    public $testVar = '1';

    public function __testAction($param)
    {
        $file = 'test.php';
        $this->cache_refresh($file);
        $this->cache_refresh($file);
        $this->view('path/inner/parametrizedView')->set($param, 'param');
    }
}
