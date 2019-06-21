<?php namespace Rollbar\Wordpress;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class UI
{
    public static function setting($args)
    {
        extract($args);
        
        ?>
        <div class="setting-inputs">
        <?php
        
        switch ($type) {
            case self::SETTING_INPUT_TYPE_TEXT:
                self::textInput($name, $value);
                break;
            case self::SETTING_INPUT_TYPE_BOOLEAN:
                self::boolean($name, $value, $display_name);
                break;
            case self::SETTING_INPUT_TYPE_PHP:
                self::phpEditor($name, $value);
                break;
            case self::SETTING_INPUT_TYPE_SELECTBOX:
                self::select($name, $options, $value);
                break;
        }
        
        self::restoreDefault($name, $type, $default);
        
        ?>
        </div>
        <?php
        
        if (!empty($description)) {
            self::description($description);
        }
    }
    
    public static function restoreDefault($setting, $type, $default)
    {
        ?>
        <button
            type="button" 
            class="button button-secondary rollbar_wp_restore_default"
            name="restore-default"
            data-setting="<?php echo $setting; ?>"
            data-setting-input-type="<?php echo $type; ?>">
            Reset
            <input type="hidden" class="default_value" value="<?php echo $default; ?>" />
        </button>
        <?php
    }
    
    public static function description($description)
    {
        ?>
        <p class="description">
            <?php _e($description, 'rollbar-wp'); ?>
        </p>
        <?php
    }
    
    public static function select($name, $options, $selected)
    {
        ?>
        <select name="rollbar_wp[<?php echo $name; ?>]" id="rollbar_wp_<?php echo $name; ?>">
            <?php
            foreach ($options as $option_value => $option_name) {
                ?>
                <option
                    value="<?php echo $option_value ?>"
                    <?php \selected($selected, $option_value); ?>
                ><?php \_e($option_name, 'rollbar-wp'); ?></option>
                <?php
            }
            ?>
        </select>
        <?php
    }
    
    public static function textInput($name, $value)
    {
        ?>
        <input type='text' name='rollbar_wp[<?php echo $name; ?>]' id="rollbar_wp_<?php echo $name; ?>"
               value='<?php echo $value; ?>' style="width: 300px;">
        <?php
    }
    
    public static function phpEditor($name, $value)
    {
        ?>
        <div 
            id="rollbar_wp_<?php echo $name; ?>_editor"
            style="height: 300px;"><?php echo $value; ?></div>
        <script>
            (function() {
                
                window['rollbar_wp']['settings_page']['<?php echo $name; ?>'] = {};
                
                window['rollbar_wp']['settings_page']['<?php echo $name; ?>']['editor'] = editor = ace.edit("rollbar_wp_<?php echo $name; ?>_editor");
                
                editor.setTheme("ace/theme/chrome");
                editor.session.setMode({path:"ace/mode/php", inline:true});
                
            })();
        </script>
        <?php
    }
    
    public static function boolean($name, $value, $display_name = '', $show_display_name = false)
    {
        ?>
        <input type='checkbox' name='rollbar_wp[<?php echo $name; ?>]'
               id="rollbar_wp_<?php echo $name; ?>" <?php \checked($value, true, 1); ?> value='1'/>
        <?php
        if ($show_display_name) {
        ?>
            <label for="rollbar_wp_<?php echo $name; ?>">
                <?php \_e($display_name, 'rollbar-wp'); ?>
            </label>
        <?php
        }
    }
    
    public static function flashMessage()
    {
        if (isset($_SESSION['rollbar_wp_flash_message'])) {
            ?>
            <div class="<?php echo $_SESSION['rollbar_wp_flash_message']['type']; ?> notice is-dismissable">
                <p><?php echo $_SESSION['rollbar_wp_flash_message']['message']; ?></p>
            </div>
            <?php
            unset($_SESSION['rollbar_wp_flash_message']);
        }
    }
    
    public static function pluginMisconfiguredNotice()
    {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php _e( 'Rollbar is misconfigured. Please, fix your configuration here: ', 'rollbar-wp' ); ?>
                <a href="<?php echo admin_url('/options-general.php?page=rollbar_wp'); ?>">here</a>
            </p>
        </div>
        <?php
    }
    
    public static function restoreAllDefaultsButton()
    {
        ?>
        <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
            <input type="hidden" name="action" value="rollbar_wp_restore_defaults" />
            <input 
                type="submit" 
                class="button button-secondary"
                name="restore-all-defaults"
                id="rollbar_wp_restore_all_defaults"
                value="Restore all defaults"
            />
        </form>
        <br />
        <?php
    }
    
    public static function testButton()
    {
        ?>
        <button
            type="button" 
            class="button button-secondary"
            name="test-logging"
            id="rollbar_wp_test_logging">
            Send test message to Rollbar
        </button>
        <?php
    }
    
    public static function status($settings)
    {
        extract($settings);

        self::boolean('php_logging_enabled', $php_logging_enabled, 'Turn on logging with PHP', true);
        ?>
        <div id="rollbar_wp_server_side_access_token_container" class="hidden">
        <h4 style="margin: 15px 0 5px 0;"><?php \_e('Server Side Access Token', 'rollbar-wp'); ?> <small>(post_server_item)</small></h4>
        <?php
        self::textInput('server_side_access_token', $server_side_access_token);
        ?>
            <p>
                <small>
                    <?php \_e('If no access token is provided here, the following will be used:', 'rollbar-wp'); ?>
                    <ol>
                        <li><?php \_e('the <code>ROLLBAR_ACCESS_TOKEN</code> constant usually defined in your <code>wp-config.php</code>'); ?></li>
                        <li><?php \_e('the <code>ROLLBAR_ACCESS_TOKEN</code> server environment variable'); ?></li>
                    </ol>
                </small>
            </p>
        </div>
        <br />
        <?php
        
        self::boolean('js_logging_enabled', $js_logging_enabled, 'Turn on logging with JavaScript (with rollbar.js)', true);
        ?>
        <div id="rollbar_wp_client_side_access_token_container" class="hidden">
        <h4 style="margin: 5px 0;"><?php \_e('Client Side Access Token', 'rollbar-wp'); ?> <small>(post_client_item)</small></h4>
        <?php
        self::textInput('client_side_access_token', $client_side_access_token);
        
        ?>     
        </div>
        <p>
            <small><?php \_e('You can find your access tokens under your project settings: <strong>Project Access Tokens</strong>.', 'rollbar-wp'); ?></small>
        </p>
        <?php
    }
    
    public static function environmentSettingNote()
    {
        $output = 
            '<p><code>WP_ENV</code> environment variable: <code> ' . getenv('WP_ENV') . ' </code></p>' .
            '<p><small><strong>Rollbar for Wordpress honors the WP_ENV environment variable.</strong> ' .
            'If the <code>environment</code> setting is not specified here, it will take ' .
            'precendence over the default value.</strong></small></p>';
        
        return $output;
    }
    
    public static function getSettingType($setting)
    {
        if (!isset(self::$setting_value_types[$setting])) {
            return false;
        }
        
        if (is_array(self::$setting_value_types[$setting])) {
            return self::$setting_value_types[$setting]['type'];
        } else {
            return self::$setting_value_types[$setting];   
        }
    }
    
    public static function getSettingOptions($setting)
    {
        if (!isset(self::$setting_value_types[$setting])) {
            return false;
        }
        
        if (is_array(self::$setting_value_types[$setting])) {
            return self::$setting_value_types[$setting]['options'];
        }
        
        return array();
    }
    
    public static function getIncludedErrnoDescriptions($value)
    {
        switch ($value) {
            case E_ERROR:
                return \__('Fatal run-time errors (E_ERROR) only', 'rollbar-wp');
                break;
            case E_WARNING:
                return \__('Run-time warnings (E_WARNING) and above', 'rollbar-wp');
                break;
            case E_PARSE:
                return \__('Compile-time parse errors (E_PARSE) and above', 'rollbar-wp');
                break;
            case E_NOTICE:
                return \__('Run-time notices (E_NOTICE) and above', 'rollbar-wp');
                break;
            case E_USER_ERROR:
                return \__('User-generated error messages (E_USER_ERROR) and above', 'rollbar-wp');
                break;
            case E_USER_WARNING:
                return \__('User-generated warning messages (E_USER_WARNING) and above', 'rollbar-wp');
                break;
            case E_USER_NOTICE:
                return \__('User-generated notice messages (E_USER_NOTICE) and above', 'rollbar-wp');
                break;
            case E_STRICT:
                return \__('Suggest code changes to ensure forward compatibility (E_STRICT) and above', 'rollbar-wp');
                break;
            case E_DEPRECATED:
                return \__('Warnings about code that will not work in future versions (E_DEPRECATED) and above', 'rollbar-wp');
                break;
            case E_ALL:
                return \__('Absolutely everything (E_ALL)', 'rollbar-wp');
                break;
        }
        
        return null;
    }
    
    public static function settingsOfType($type)
    {
        $settings = array();
        
        foreach (self::$setting_value_types as $setting => $value_type) {
            if ($value_type == $type) {
                $settings []= $setting;
            }
        }
        
        return $settings;   
    }
    
    const SETTING_INPUT_TYPE_TEXT = 'SETTING_INPUT_TYPE_TEXT';
    const SETTING_INPUT_TYPE_TEXTAREA = 'SETTING_INPUT_TYPE_TEXTAREA';
    const SETTING_INPUT_TYPE_PHP = 'SETTING_INPUT_TYPE_PHP';
    const SETTING_INPUT_TYPE_BOOLEAN = 'SETTING_INPUT_TYPE_BOOLEAN';
    const SETTING_INPUT_TYPE_SKIP = 'SETTING_INPUT_TYPE_SKIP';
    const SETTING_INPUT_TYPE_SELECTBOX = 'SETTING_INPUT_TYPE_SELECTBOX';
    
    private static $setting_value_types = array(
        'php_logging_enabled' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'js_logging_enabled' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'access_token' => self::SETTING_INPUT_TYPE_TEXT,
        'agent_log_location' => self::SETTING_INPUT_TYPE_TEXT,
        'allow_exec' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'endpoint' => self::SETTING_INPUT_TYPE_TEXT,
        'base_api_url' => self::SETTING_INPUT_TYPE_SKIP,
        'branch' => self::SETTING_INPUT_TYPE_TEXT,
        'capture_error_stacktraces' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'check_ignore' => self::SETTING_INPUT_TYPE_PHP,
        'code_version' => self::SETTING_INPUT_TYPE_TEXT,
        'custom' => self::SETTING_INPUT_TYPE_PHP,
        'custom_data_method' => self::SETTING_INPUT_TYPE_PHP,
        'custom_truncation' => self::SETTING_INPUT_TYPE_TEXT,
        'enable_utf8_sanitization' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'enabled' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'environment' => self::SETTING_INPUT_TYPE_TEXT,
        'error_sample_rates' => self::SETTING_INPUT_TYPE_PHP,
        'exception_sample_rates' => self::SETTING_INPUT_TYPE_PHP,
        'fluent_host' => self::SETTING_INPUT_TYPE_TEXT,
        'fluent_port' => self::SETTING_INPUT_TYPE_TEXT,
        'fluent_tag' => self::SETTING_INPUT_TYPE_TEXT,
        'handler' => array(
            'type' => self::SETTING_INPUT_TYPE_SELECTBOX,
            'options' => array(
                'blocking' => 'blocking', 
                'agent' => 'agent', 
                'fluent' => 'fluent'
            )
        ),
        'host' => self::SETTING_INPUT_TYPE_TEXT,
        'include_error_code_context' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'include_exception_code_context' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'included_errno' => array(
            'type' => self::SETTING_INPUT_TYPE_SELECTBOX,
            'options' => array(
                E_ERROR, 
                E_WARNING, 
                E_PARSE,
                E_NOTICE,
                E_USER_ERROR,
                E_USER_WARNING,
                E_USER_NOTICE,
                E_STRICT,
                E_DEPRECATED,
                E_ALL
            )
        ),
        'logger' => self::SETTING_INPUT_TYPE_PHP,
        'person' => self::SETTING_INPUT_TYPE_PHP,
        'person_fn' => self::SETTING_INPUT_TYPE_PHP,
        'capture_ip' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'capture_email' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'capture_username' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'root' => self::SETTING_INPUT_TYPE_TEXT,
        'scrub_fields' => self::SETTING_INPUT_TYPE_PHP,
        'scrub_whitelist' => self::SETTING_INPUT_TYPE_PHP,
        'timeout' => self::SETTING_INPUT_TYPE_TEXT,
        'report_suppressed' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'use_error_reporting' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'proxy' => self::SETTING_INPUT_TYPE_TEXT,
        'send_message_trace' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'include_raw_request_body' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'local_vars_dump' => self::SETTING_INPUT_TYPE_BOOLEAN,
        'verbosity' => array(
            'type' => self::SETTING_INPUT_TYPE_SELECTBOX,
            'options' => array(
                \Psr\Log\LogLevel::EMERGENCY => '\Psr\Log\LogLevel::EMERGENCY',
                \Psr\Log\LogLevel::ALERT => '\Psr\Log\LogLevel::ALERT',
                \Psr\Log\LogLevel::CRITICAL => '\Psr\Log\LogLevel::CRITICAL',
                \Psr\Log\LogLevel::ERROR => '\Psr\Log\LogLevel::ERROR',
                \Psr\Log\LogLevel::WARNING => '\Psr\Log\LogLevel::WARNING',
                \Psr\Log\LogLevel::NOTICE => '\Psr\Log\LogLevel::NOTICE',
                \Psr\Log\LogLevel::INFO => '\Psr\Log\LogLevel::INFO',
                \Psr\Log\LogLevel::DEBUG => '\Psr\Log\LogLevel::DEBUG'
            )
        ),
        'enable_must_use_plugin' => self::SETTING_INPUT_TYPE_BOOLEAN,
    );
}