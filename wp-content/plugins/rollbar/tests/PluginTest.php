<?php
namespace Rollbar\Wordpress\Tests;

use Rollbar\Payload\Level;

/**
 * Class PluginTest
 *
 * @package Rollbar\Wordpress\Tests
 */
class PluginTest extends BaseTestCase {
    
    private $subject;
    
    public function setUp()
    {
        $this->subject = \Rollbar\Wordpress\Plugin::instance();
    }
    
    /**
     * @dataProvider loggingLevelTestDataProvider
     */
    public function testLoggingLevel(
        $loggingLevel, 
        $errorLevel, 
        $errorMsg, 
        $shouldIgnore
    )
    {
        
        $plugin = $this->subject;
        
        $plugin->setting('php_logging_enabled', 1);
        $plugin->setting(
            'logging_level',
            \Rollbar\Wordpress\Plugin::buildIncludedErrno($loggingLevel)
        );
        $plugin->setting('server_side_access_token', $this->getAccessToken());
        $plugin->setting('environment', $this->getEnvironment());
        
        $plugin->initPhpLogging();
        
        $logger = \Rollbar\Rollbar::logger();
        $dataBuilder = $logger->getDataBuilder();
        
        $errorWrapper = $dataBuilder->generateErrorWrapper(
            $errorLevel, $errorMsg, "", ""
        );
        
        $response = $logger->log(Level::ERROR, $errorWrapper);
        
        if ($shouldIgnore) {
            $this->assertEquals("Ignored", $response->getInfo());
        } else {
            $this->assertNotEquals("Ignored", $response->getInfo());
        }
        
    }
    
    public function loggingLevelTestDataProvider()
    {
        return array(
                array(
                    E_ERROR, // Plugin logging level
                    E_WARNING, // Triggered error code
                    "This error should get ignored.",
                    true // Expected 'Ignored' ?
                ),
                array( // Should get reported to Rollbar
                    E_WARNING, // Plugin logging level
                    E_WARNING, // Triggered error code
                    "This E_WARNING triggered with logging level E_WARNING should get reported.",
                    false // Expected 'Ignored' ?
                ),
                array( // Should get reported to Rollbar
                    E_WARNING, // Plugin logging level
                    E_ERROR, // Triggered error code
                    "This E_ERROR triggered with logging level E_WARNING should get reported.",
                    false // Expected 'Ignored' ?
                ),
                array( // Should get reported to Rollbar
                    E_ALL, // Plugin logging level
                    E_ERROR, // Triggered error code
                    "This E_ERROR triggered with logging level E_ALL should get reported.",
                    false // Expected 'Ignored' ?
                ),
            );
    }
    
    public function testbuildIncludedErrno()
    {
        $expected = (E_ERROR | E_WARNING);
        
        $result = \Rollbar\Wordpress\Plugin::buildIncludedErrno(E_WARNING);
        
        $this->assertEquals((E_ERROR | E_WARNING), $result);
        
        $result = \Rollbar\Wordpress\Plugin::buildIncludedErrno(E_NOTICE);
        
        $this->assertNotEquals((E_ERROR | E_WARNING), $result);
        
        $result = \Rollbar\Wordpress\Plugin::buildIncludedErrno(E_NOTICE);
        
        $this->assertEquals((E_ERROR | E_WARNING | E_PARSE | E_NOTICE), $result);
    }
    
    public function testConfigure()
    {
        $expected = 'testConfigure';
        
        $plugin = $this->subject;
        
        $plugin->setting('php_logging_enabled', 1);
        $plugin->setting(
            'logging_level',
            \Rollbar\Wordpress\Plugin::buildIncludedErrno(E_WARNING)
        );
        $plugin->setting('server_side_access_token', $this->getAccessToken());
        $plugin->setting('environment', $expected);
        $plugin->configure(array('environment' => $expected));
        
        
        $plugin->initPhpLogging();
        
        
        $dataBuilder = \Rollbar\Rollbar::logger()->getDataBuilder();
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        
        $this->assertEquals($expected, $output->getEnvironment());
        
        
        $expected = "testConfigure2";
        
        $plugin->configure(array('environment' => $expected));
        $dataBuilder = \Rollbar\Rollbar::logger()->getDataBuilder();
        
        $output = $dataBuilder->makeData(Level::ERROR, "testing", array());
        
        $this->assertEquals($expected, $output->getEnvironment());
    }
    
    public function testGetDefaultOption()
    {
        $this->assertEquals("production", $this->subject->getDefaultOption('environment'));
        
        putenv("WP_ENV=phpunit_test");
        
        $this->assertEquals("phpunit_test", $this->subject->getDefaultOption('environment'));
        
        putenv("WP_ENV");
    }
    
}