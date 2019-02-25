<?php
namespace Rollbar\Wordpress\Tests;

/**
 * Class BaseTestCase
 *
 * @package Rollbar\Wordpress\Tests
 */
abstract class BaseTestCase extends \WP_UnitTestCase {
    
    function getAccessToken()
    {
        return $_ENV['ROLLBAR_TEST_TOKEN'];
    }
    
    function getEnvironment()
    {
        return "testing";
    }
    
}