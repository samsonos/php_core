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
require('TestingModule.php');

use samson\core\Core;
use samsonframework\resource\ResourceMap;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    protected $module;

    public function setUp()
    {
        $path = __DIR__;

        // Build resources map
        $map = ResourceMap::get($path);
        // Create core
        $core = new Core($map);
        // Create module
        $this->module = new TestingModule('test', $path, $map, $core);
    }

    public function testView()
    {
        $this->setExpectedException('\samsonphp\core\exception\ViewPathNotFound');

        $this->assertEquals('<h1>Test</h1>', $this->module->view('path/inner/index')->output());
        $this->assertNotEquals('<h1>Test</h1>', $this->module->view('path/inner/NotFoundIndex')->output());
    }

    public function testHtml()
    {
        $test = '<h1>Test</h1>';
        $this->assertEquals($test, $this->module->html($test)->output());
    }

    public function testRender()
    {
        $param = 'tset';
        $test = '<h1>Test' . $param . '</h1>';

        ob_start();
        $this->module->render('testAction', $param);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($test, $output);
    }

    public function testArrayAccess()
    {
        $this->module['testVar'] = 'testVar';

        $this->assertEquals('testVar', $this->module['testVar']);
        $this->assertArrayHasKey('testVar', $this->module);
    }
}
