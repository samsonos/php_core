<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>
 * on 28.11.13 at 15:59
 */

namespace samson\core\tests;

use PHPUnit_Framework_TestCase;

/**
 * SamsonPHP Core testing
 * @package samson\core\tests
 * @author Vitaly Iegorov <egorov@samsonos.com>
 */
class CoreTest extends PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
		s()->load();

        //$this->assertEquals($c, $a + $b);
    }
}