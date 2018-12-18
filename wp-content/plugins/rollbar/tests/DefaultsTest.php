<?php
namespace Rollbar\Wordpress\Tests;

use Rollbar\Wordpress\Defaults as Defaults;

/**
 * Class DefaultsTest
 *
 * @package Rollbar\Wordpress\Tests
 */
class DefaultsTest extends BaseTestCase {
    
    private $subject;
    
    public function setUp()
    {
        $this->subject = Defaults::instance();
    }
    
    public function testRoot()
    {
        $this->assertEquals(ABSPATH, $this->subject->root());
    }
    
    public function testEnvironment()
    {
        $this->assertEquals(getenv('WP_ENV'), $this->subject->environment());
    }
    
}