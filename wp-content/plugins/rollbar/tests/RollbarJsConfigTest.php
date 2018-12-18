<?php
namespace Rollbar\Wordpress\Tests;

/**
 * Class RollbarJSConfigTest
 *
 * @package Rollbar\Wordpress\Tests
 */
class RollbarJSConfigTest extends BaseTestCase {

	function testRollbarJsConfig() {
		
		$expected = array(
          'id' => '1',
          'username' => 'test',
          'email' => 'wptest@rollbar.com'
        );
		
		\add_filter( 'rollbar_js_config', function($config) use ($expected) {
			
	        $config['payload']['person'] = $expected;
	        
		    return $config;
			
		});
		
		$plugin = \Rollbar\Wordpress\Plugin::instance();
		$plugin->setting('client_side_access_token', 'XXX');
		$plugin->setting('js_logging_enabled', '1');
		
		$jsConfig = $plugin->buildJsConfig();
		
		$this->assertEquals($expected, $jsConfig['payload']['person']);
	}
	
}
