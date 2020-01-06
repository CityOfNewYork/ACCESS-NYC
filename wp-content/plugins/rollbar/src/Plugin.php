<?php
 
namespace Rollbar\Wordpress;

use \Rollbar\Payload\Level as Level;

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class Plugin {
    
    const VERSION = "2.6.1";
    
    private $config;
    
    private static $instance;
    
    private static $pluginOptions = array(
        'enable_must_use_plugin'
    );
    
    private $settings = null;
    
    private function __construct() {
        $this->config = array();
    }
    
    public static function instance() {
        if( !self::$instance ) {
            self::$instance = new Plugin();
            self::$instance->loadTextdomain();
            self::$instance->hooks();
            self::$instance->initSettings();
            self::$instance->initPhpLogging();
        }

        return self::$instance;
    }
    
    public static function load() {
        return Plugin::instance();
    }
    
    public function configure(array $config) {
        $this->config = array_merge($this->config, $config);
        
        if ($logger = \Rollbar\Rollbar::logger()) {
            $logger->configure($this->config);
        }
    }
    
    private function initSettings() {
        Settings::init();
    }
    
    /**
     * Fetch settings provided in Admin -> Tools -> Rollbar
     * 
     * @returns array
     */
    private function fetchSettings() {
        
        $options = $this->settings ?: array();
        
        if ($saved_options = get_option( 'rollbar_wp' )) {
            $options = array_merge($options, $saved_options);
        }
        
        if (!isset($options['environment']) || empty($options['environment'])) {
            
            if ($wpEnv = getenv('WP_ENV')) {
                $options['environment'] = $wpEnv;
            }
            
        }

        if (!isset($options['proxy']) || empty($options['proxy'])) {

            if (defined('WP_PROXY_HOST') && defined('WP_PROXY_PORT')) {
              $options['proxy'] = WP_PROXY_HOST.":". WP_PROXY_PORT;
            }

        }
        
        if (!isset($options['server_side_access_token']) || empty($options['server_side_access_token'])) {
            
            if (defined('ROLLBAR_ACCESS_TOKEN')) {
                
                $options['server_side_access_token'] = ROLLBAR_ACCESS_TOKEN;
                
            } else if ($token = getenv('ROLLBAR_ACCESS_TOKEN')) {
                
                $options['server_side_access_token'] = $token;
                
            }
        }
        
        $settings = array(
            
            'php_logging_enabled' => (!empty($options['php_logging_enabled'])) ? 1 : 0,
            
            'js_logging_enabled' => (!empty($options['js_logging_enabled'])) ? 1 : 0,
            
            'server_side_access_token' => (!empty($options['server_side_access_token'])) ? 
                esc_attr(trim($options['server_side_access_token'])) : 
                '',
                
            'client_side_access_token' => (!empty($options['client_side_access_token'])) ? 
                trim($options['client_side_access_token']) : 
                '',
            
            'logging_level' => (!empty($options['logging_level'])) ? 
                esc_attr(trim($options['logging_level'])) : 
                Settings::DEFAULT_LOGGING_LEVEL
        );
        
        foreach (self::listOptions() as $option) {
            
            // 'access_token' and 'enabled' are different in Wordpress plugin
            // look for 'server_side_access_token' and 'php_logging_enabled' above
            if (in_array($option, array('access_token', 'enabled'))) {
                continue;
            }
            
            if (!isset($options[$option])) {
                $value = $this->getDefaultOption($option);
            } else {
                $value = $options[$option];
            }
            
            $settings[$option] = $value;
                
        }
        
        $this->settings = \apply_filters('rollbar_plugin_settings', $settings);
        
    }
    
    public function setting() {
        $args = func_get_args();
        $setting = $args[0];
        if (isset($args[1])) {
            $value = $args[1];
        }
        
        if (isset($value)) {
            $this->settings[$setting] = $value;
        } else {
            return $this->settings[$setting];
        }
    }

    private function hooks() {
        \add_action('wp_head', array(&$this, 'initJsLogging'));
        \add_action('admin_head', array(&$this, 'initJsLogging'));
        $this->registerTestEndpoint();
    }
    
    private function registerTestEndpoint() {
        \add_action( 'rest_api_init', function () {
            \register_rest_route(
                'rollbar/v1', 
                '/test-php-logging',
                array(
                    'methods' => 'POST',
                    'callback' => '\Rollbar\Wordpress\Plugin::testPhpLogging',
                    'args' => array(
                        'server_side_access_token' => array(
                            'required' => true
                        ),
                        'environment' => array(
                            'required' => true
                        ),
                        'logging_level' => array(
                            'required' => true
                        )
                    )
                )
            );
        });
    }
    
    public static function testPhpLogging(\WP_REST_Request $request) {
        
        $plugin = self::instance();
        
        foreach(self::listOptions() as $option) {
            $plugin->settings[$option] = $request->get_param($option);
        }
        
        $plugin->settings['server_side_access_token'] = $request->get_param('server_side_access_token');
        
        $response = null;
        
        try {
            $plugin->initPhpLogging();
            
            $response = \Rollbar\Rollbar::log(
                Level::INFO,
                "Test message from Rollbar Wordpress plugin using PHP: ".
                "integration with Wordpress successful"
            );
            
        } catch( \Exception $exception ) {
            
            return new \WP_REST_Response(
                array(
                    'message' => $exception->getMessage()
                ),  
                500
            );   
        }
        
        $info = $response->getInfo();
        
        $response = array('code' => $response->getStatus());
        if (is_array($info)) {
            $response = array_merge($response, $info);
        } else {
            $response['message'] = $info;
        }
        
        return new \WP_REST_Response($response, 200);
        
    }

    public function loadTextdomain() {
        \load_plugin_textdomain( 'rollbar', false, dirname( \plugin_basename( __FILE__  ) ) . '/languages/' );
    }
    
    public static function buildIncludedErrno($cutoff)
    {
            
        $levels = array(
            E_ERROR,
            E_WARNING,
            E_PARSE,
            E_NOTICE,
            E_CORE_ERROR,
            E_CORE_WARNING,
            E_COMPILE_ERROR,
            E_COMPILE_WARNING,
            E_USER_ERROR,
            E_USER_WARNING,
            E_USER_NOTICE,
            E_STRICT,
            E_RECOVERABLE_ERROR,
            E_DEPRECATED,
            E_USER_DEPRECATED,
            E_ALL
        );
        
        $included_errno = 0;
        
        foreach ($levels as $level) {
            
            if ($level <= $cutoff) {
                $included_errno |= $level;    
            }
            
        }
        
        return $included_errno;
    }
    
    public function initPhpLogging()
    {
        $this->fetchSettings();

        // Return if logging is not enabled
        if ( $this->settings['php_logging_enabled'] === 0 ) {
            return;
        }
        
        // installs global error and exception handlers
        try {
            
            \Rollbar\Rollbar::init($this->buildPHPConfig());
            
        } catch (\InvalidArgumentException $exception) {
            
            \add_action(
                'admin_notices', 
                array(
                    '\Rollbar\Wordpress\UI', 
                    'pluginMisconfiguredNotice'
                )
            );
            
            global $wp_settings_errors;
	        $wp_settings_errors[] = array(
	                'setting' => 'rollbar-wp',
	                'code'    => 'rollbar-wp',
	                'message' => 'Rollbar PHP: ' . $exception->getMessage(),
	                'type'    => 'error'
	        );
            
        } catch (\Exception $exception) {
            
            global $wp_settings_errors;
	        $wp_settings_errors[] = array(
	                'setting' => 'rollbar-wp',
	                'code'    => 'rollbar-wp',
	                'message' => 'Rollbar PHP: ' . $exception->getMessage(),
	                'type'    => 'error'
	        );
            
        }
        
    }
    
    public function buildPHPConfig()
    {
        $config = $this->settings;
        
        $config['access_token'] = $this->settings['server_side_access_token'];
        $config['included_errno'] = self::buildIncludedErrno($this->settings['logging_level']);
        $config['timeout'] = intval($this->settings['timeout']);
        
        foreach (UI::settingsOfType(UI::SETTING_INPUT_TYPE_PHP) as $setting) {
            
            if (isset($config[$setting])) {
                
                $code = is_string($config[$setting]) ?: 'return ' . var_export($config[$setting], true) . ';';
                
                $config[$setting] = eval($code);
            }
        }
        
        foreach (UI::settingsOfType(UI::SETTING_INPUT_TYPE_BOOLEAN) as $setting) {
            
            if (isset($config[$setting]) && $config[$setting] === 'false') {
                $config[$setting] = false;
            } else if (isset($config[$setting]) && $config[$setting] === 'true') {
                $config[$setting] = true;
            }
        }
        
        return $config;
    }
    
    public function initJsLogging()
    {
        // Return if logging is not enabled
        if ( $this->settings['js_logging_enabled'] === 0 ) {
            return;
        }
    
        // Return if access token is not set
        if ($this->settings['client_side_access_token'] == '') {
            add_action(
                'admin_notices', 
                array(
                    '\Rollbar\Wordpress\UI', 
                    'pluginMisconfiguredNotice'
                )
            );
            return;
        }
        
        $rollbarJs = \Rollbar\RollbarJsHelper::buildJs($this->buildJsConfig());
        
        echo $rollbarJs;
        
    }
    
    public function buildJsConfig()
    {
        $rollbarJsConfig = array(
          'accessToken' => $this->settings['client_side_access_token'],
          'captureUncaught' => true,
          'payload' => array(
            'environment' => $this->settings['environment']
          ),
        );

        $rollbarJsConfig = apply_filters('rollbar_js_config', $rollbarJsConfig);
        
        return $rollbarJsConfig;
    }
    
    public function updateSettings(array $settings)
    {
        $option = get_option('rollbar_wp');
        
        $option = array_merge($option, $settings);
        
        foreach ($settings as $setting => $value) {
            $this->settings[$setting] = $value;
        }
        
        update_option('rollbar_wp', $option);
    }
    
    public function restoreDefaults()
    {
        $settings = array();
        
        foreach (self::listOptions() as $option) {
            $settings[$option] = $this->getDefaultOption($option);
        }
        
        $this->updateSettings($settings);
    }
    
    public function getDefaultOption($setting)
    {
        $spaced = str_replace('_', ' ', $setting);
        $method = lcfirst(str_replace(' ', '', ucwords($spaced)));
        
        // Handle the "branch" exception
        switch($method) {
            case "branch":
                $method = "gitBranch";
                break;
            case "captureIp":
                $method = "captureIP";
                break;
        }
        
        $rollbarDefaults = \Rollbar\Defaults::get();
        $wordpressDefaults = \Rollbar\Wordpress\Defaults::instance();
        
        $value = null;
        
        if (method_exists($wordpressDefaults, $method) && $value === null) {
            try {
                $value = $wordpressDefaults->$method();
            } catch (\Throwable $e) {
                $value = null;
            } catch (\Exception $e) {
                $value = null;
            }
        }
        
        if (method_exists($rollbarDefaults, $method) && $value === null) {
            try {
                $value = $rollbarDefaults->$method();
            } catch (\Throwable $e) {
                $value = null;
            } catch (\Exception $e) {
                $value = null;
            }
        }
        
        return $value;
    }
    
    public static function listOptions()
    {
        return array_merge(
            \Rollbar\Config::listOptions(),
            self::$pluginOptions
        );
    }
    
    public function enableMustUsePlugin()
    {
        $muPluginsDir = plugin_dir_path(__DIR__) . '../../mu-plugins/';
        
        $muPluginFilepath = plugin_dir_path(__DIR__) . 'mu-plugin/rollbar-mu-plugin.php';
        
        if (!file_exists($muPluginsDir) && !mkdir($muPluginsDir, 0700)) {
            throw new \Exception("Can't create the mu-plugins directory: $muPluginsDir");
        }
        
        if (!copy($muPluginFilepath, $muPluginsDir . 'rollbar-mu-plugin.php')) {
            throw new \Exception("Can't copy mu-plugin from $muPluginFilepath to $muPluginsDir");
        }
    }
    
    public function disableMustUsePlugin()
    {
        $file = plugin_dir_path(__DIR__) . '../../mu-plugins/rollbar-mu-plugin.php';
        if (file_exists($file) && !unlink($file)) {
            throw new \Exception("Can't delete the mu-plugin");
        }
    }
}
