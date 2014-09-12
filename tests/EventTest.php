<?php
namespace tests;
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 04.08.14 at 16:42
 */
class EventTest extends \PHPUnit_Framework_TestCase
{
    /** Static event callback handler */
    public static function eventStaticCallback(&$result)
    {
        return $result = 3;
    }

    /** Dynamic event callback handler */
    public function eventDynamicCallback(&$result)
    {
        return $result = 2;
    }

    /** Reference event callback handler */
    public function eventReferenceCallback(&$result)
    {
        $result = array('reference' => 'yes!');
    }

    /** Test if we can pass parameters by reference to change them in event callback handler */
    public function testPassingParametersByReference()
    {
        // Subscribe to event
        \samson\core\Event::subscribe('test.subscribe_reference', array($this, 'eventReferenceCallback'));

        // Fire event
        $result = null;
        \samson\core\Event::fire('test.subscribe_reference', array(&$result));

        // Perform test
        $this->assertArrayHasKey('reference', $result);
    }

    /** Test static event callback handler */
    public function testSubscribeStatic()
    {
        // Subscribe to event
        \samson\core\Event::subscribe('test.subscribe_static', array('\tests\EventTest', 'eventStaticCallback'));

        // Fire event
        $result = 0;
        \samson\core\Event::fire('test.subscribe_static', array(&$result));

        // Perform test
        $this->assertEquals(3, $result);
    }

    /** Test global event callback handler */
    public function testSubscribeGlobal()
    {
        // Subscribe to event
        \samson\core\Event::subscribe('test.subscribe_global', '\globalEventCallback');

        // Fire event
        $result = null;
        \samson\core\Event::fire('test.subscribe_global', array(&$result));

        // Perform test
        $this->assertEquals(1, $result);
    }

    /** Test dynamic event callback handler */
    public function testSubscribeDynamic()
    {
        // Subscribe to event
        \samson\core\Event::subscribe('test.subscribe_dynamic', array($this, 'eventDynamicCallback'));

        // Fire event
        $result = null;
        \samson\core\Event::fire('test.subscribe_dynamic', array(&$result));

        // Perform test
        $this->assertEquals(2, $result);
    }
}

/** Global event callback handler */
function globalEventCallback(&$result)
{
    return $result = 1;
}
