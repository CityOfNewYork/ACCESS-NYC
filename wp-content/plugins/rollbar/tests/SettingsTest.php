<?php
namespace Rollbar\Wordpress\Tests;

use Rollbar\Payload\Level;

/**
 * Class SettingsTest
 *
 * @package Rollbar\Wordpress\Tests
 */
class SettingsTest extends BaseTestCase {
    
    private $subject;
    
    public function setUp()
    {
        $this->subject = \Rollbar\Wordpress\Settings::instance();
    }
    
    public function testGetDefaultSetting()
    {
        $this->assertEquals('production', $this->subject->settingDefault('environment'));
        $this->assertTrue($this->subject->settingDefault('capture_error_stacktraces'));
    }
    
    /**
     * @dataProvider preUpdateProvider
     */
    public function testPreUpdate($expected, $data)
    {
        $this->assertEquals(
            $expected, 
            \Rollbar\Wordpress\Settings::preUpdate($data)
        );
    }
    
    public function preUpdateProvider()
    {
        return array(
            
            array(
                array(
                ),
                array(
                    'allow_exec' => true,
                    'capture_error_stacktraces' => true,
                    'local_vars_dump' => true,
                    'capture_ip' => true
                )
            ),
            
            array(
                array(
                    'php_logging_enabled' => true,
                    'enabled' => true
                ),
                array(
                    'php_logging_enabled' => true,
                    'allow_exec' => true,
                    'capture_error_stacktraces' => true,
                    'local_vars_dump' => true,
                    'capture_ip' => true
                )
            ),
            
            array(
                array(
                    'allow_exec' => false
                ),
                array(
                    'allow_exec' => false,
                    'capture_error_stacktraces' => true,
                    'local_vars_dump' => true,
                    'capture_ip' => true
                )
            ),
            
            array(
                array(
                    'use_error_reporting' => true
                ),
                array(
                    'use_error_reporting' => true,
                    'allow_exec' => true,
                    'capture_error_stacktraces' => true,
                    'local_vars_dump' => true,
                    'capture_ip' => true
                )
            ),
            
        );
    }
}