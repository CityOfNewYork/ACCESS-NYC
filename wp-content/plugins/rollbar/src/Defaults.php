<?php
namespace Rollbar\Wordpress;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Defaults {
    
    private static $instance;
    
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
    
    public function environment()
    {
        return getenv('WP_ENV') ?: null;
    }
    
    public function root()
    {
        return ABSPATH;
    }
    
    public function loggingLevel()
    {
        return E_ERROR;
    }
    
    public function enabled()
    {
        return false;
    }
    
    public function enableMustUsePlugin()
    {
        return false;
    }
}

?>