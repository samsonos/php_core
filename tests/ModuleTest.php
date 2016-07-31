<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 12.01.16
 * Time: 14:33
 */
namespace samsonphp\core\tests;

use samsonphp\core\Core;
use samsonphp\event\Event;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    /** @var Core */
    protected $core;
    
    /** @var \ReflectionClass */
    protected $reflection;

    public function setUp()
    {
        $this->core = new Core();
        $this->reflection = new \ReflectionClass($this->core);
    }

    /**
     * Get $object private/protected property value.
     *
     * @param string $property Private/protected property name
     *
     * @param object $object Object instance for getting private/protected property value
     *
     * @return mixed Private/protected property value
     */
    protected function getProperty($property, $object)
    {
        $environmentProperty = $this->reflection->getProperty($property);
        $environmentProperty->setAccessible(true);
        return $environmentProperty->getValue($object);
    }

    public function testEnvironment()
    {
        $environment = 'test';
        $this->core->environment($environment);

        $this->assertEquals($environment, $this->getProperty('environment', $this->core));
    }

    public function testEnvironmentEventChangedValue()
    {
        $changedEnvironment = 'changedTest';
        $environment = 'test';
        Event::subscribe(Core::E_ENVIRONMENT, function(&$core, &$environment) use ($changedEnvironment) {
            $environment = $changedEnvironment;
        });
        $this->core->environment($environment);
        $this->assertEquals($changedEnvironment, $this->getProperty('environment', $this->core));
    }
    
    public function testLoadWithoutAlias()
    {
        $this->core->load(new TestModule());
        $this->assertArrayHasKey(TestModule::class, $this->getProperty('modules', $this->core));
    }

    public function testLoadWithAlias()
    {
        $moduleAlias = 'testModule';
        $this->core->load(new TestModule(), $moduleAlias);
        $this->assertArrayHasKey($moduleAlias, $this->getProperty('modules', $this->core));
    }
}
