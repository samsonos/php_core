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
require('RenderableObject.php');

use samson\core\Core;
use samsonframework\resource\ResourceMap;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    /** @var Module */
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

        $this->assertEquals('<h1>Test</h1>', $this->module->view('index')->output());
        $this->assertNotEquals('<h1>Test</h1>', $this->module->view('path/inner/NotFoundIndex')->output());
    }

    public function testHtml()
    {
        $test = '<h1>Test</h1>';
        $this->assertEquals($test, $this->module->html($test)->output());
        // Render empty
        $this->assertEquals('', $this->module->html('')->output());
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

        $this->setExpectedException('\samsonphp\core\exception\ControllerActionNotFound');
        $this->module->render('notFoundAction', $param);
    }

    public function testArrayAccess()
    {
        $this->module->view('path/inner/index');
        $this->module['testVar'] = 'testVar';

        $this->assertEquals('testVar', $this->module['testVar']);
        $this->assertArrayHasKey('testVar', $this->module);

        unset($this->module['testVar']);

        $this->assertArrayNotHasKey('testVar', $this->module);
    }

    public function testViewSetting()
    {
        $var = 'testvar';
        $this->module->testVar($var);
        $this->assertEquals($var, $this->module['testVar']);

        $this->module->testArray(array('1', 'testArrayKey' => '2', '3'));
        $this->assertArrayHasKey('testArray', $this->module);
        $this->assertEquals(3, sizeof($this->module['testArray']));
        $this->assertArrayHasKey('testArrayKey', $this->module);

        $this->module->testObject(new RenderableObject());
        $this->assertArrayHasKey('testObject', $this->module);
        $this->assertArrayHasKey('testObject_testVar', $this->module);
    }

    public function testSerialization()
    {
        $serialized = serialize($this->module);
        $unserialized = unserialize($serialized);

        $this->assertEquals($this->module->path(), $unserialized->path());
        $this->assertEquals($this->module->id(), $unserialized->id());
    }

    public function testDifferent()
    {
        $this->setExpectedException('\samsonphp\core\exception\ViewVariableNotFound');
        $this->module->titlesadasd;

        $title = 'TESTTITLE';
        $this->module->title($title);
        $this->assertEquals($title, $this->module['title']);

        $this->module->title($title);
        $this->assertEquals($title, $this->module['title']);

        $this->module->testVar2('module');
        $this->assertArrayHasKey('testVar2', $this->module->toView('module'));

        unset($this->module);
    }
}
