<?php
namespace tests;
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 04.08.14 at 16:42
 */
class EventTest extends \PHPUnit_Framework_TestCase
{
    public static function eventStaticCallback(&$result)
    {
        return $result = 3;
    }

    public function eventDynamicCallback(&$result)
    {
        return $result = 2;
    }

    public function eventReferenceCallback(&$result)
    {
        $result = array('reference' => 'yes!');
    }

    public function testPassingParametersByReference()
    {
        // Subscribe to event
        \samson\core\Event::subscribe('test.susbcribe_reference', array($this, 'eventReferenceCallback'));

        // Fire event
        $result = null;
        \samson\core\Event::fire('test.susbcribe_reference', array(&$result));

        // Perform test
        $this->assertArrayHasKey('reference', $result);
    }

    public function testSubscribeStatic()
    {
        // Subscribe to event
        \samson\core\Event::subscribe('test.susbcribe_static', array('\tests\EventTest', 'eventStaticCallback'));

        // Fire event
        $result = 0;
        \samson\core\Event::fire('test.susbcribe_static', array(&$result));

        // Perform test
        $this->assertEquals(3, $result);
    }

    public function testSubscribeGlobal()
    {
        // Subscribe to event
        \samson\core\Event::subscribe('test.susbcribe_global', 'globalEventCallback');

        // Fire event
        $result = null;
        \samson\core\Event::fire('test.susbcribe_global', array(&$result));

        // Perform test
        $this->assertEquals(1, $result);
    }

    public function testSubscribeDynamic()
    {
        // Subscribe to event
        \samson\core\Event::subscribe('test.susbcribe_dynamic', array($this, 'eventDynamicCallback'));

        // Fire event
        $result = null;
        \samson\core\Event::fire('test.susbcribe_dynamic', array(&$result));

        // Perform test
        $this->assertEquals(2, $result);
    }
}

function globalEventCallback(&$result)
{
    return $result = 1;
}
