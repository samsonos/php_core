<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 12.01.16
 * Time: 14:33
 */
namespace samsonphp\tests;

require('src/constants.php');
require('src/Utils2.php');
require('src/shortcuts.php');
require('src/View.php');

use samson\core\Core;
use samson\core\Module;
use samsonframework\resource\ResourceMap;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    public function testView()
    {
        $path = __DIR__;
        $ts = microtime(true);
        // Build resources map
        $map = ResourceMap::get($path);
        trace(microtime(true) - $ts);
        // Create core
        $core = new Core($map);
        trace(microtime(true) - $ts);
        // Create module
        $module = new Module('test', $path, $map, $core);
        trace(microtime(true) - $ts);
        $output = $module->view('path/inner/index')->output();
        trace(microtime(true) - $ts);

        $this->assertEquals('<h1>Test</h1>', $output);
    }
}
