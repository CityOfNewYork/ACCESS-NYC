<?php
namespace Rollbar\Wordpress;

use Michelf\Markdown;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Settings
{
    const DEFAULT_LOGGING_LEVEL = E_ERROR;
    
    private static $instance;
    
    private $plugin;

    private function __construct() {
        $this->plugin = \Rollbar\Wordpress\Plugin::instance();
    }
    
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public static function init() {
        $instance = self::instance();
        \add_action('admin_menu', array(&$instance, 'addAdminMenu'));
        \add_action('admin_init', array(&$instance, 'addSettings'));
        \add_action('admin_enqueue_scripts', function($hook) {
            
            if ($hook != 'settings_page_rollbar_wp') {
                return;
            }
            
            \wp_register_script( 
                'RollbarWordpressSettings.js', 
                \plugin_dir_url(__FILE__)."../public/js/RollbarWordpressSettings.js",
                array("jquery"),
                Plugin::VERSION
            );
            
            \wp_localize_script(
                'RollbarWordpressSettings.js', 
                'RollbarWordpress', 
                array(
                    'plugin_url' => \plugin_dir_url(__FILE__) . "../",
                )
            );
            
            \wp_enqueue_script(
                "RollbarWordpressSettings.js",
                \plugin_dir_url(__FILE__)."../public/js/RollbarWordpressSettings.js", 
                array("jquery"),
                Plugin::VERSION
            );
            
            \wp_register_script( 
                'AceEditor', 
                \plugin_dir_url(__FILE__)."../public/js/ace-builds/src-min-noconflict/ace.js",
                array('jquery'),
                Plugin::VERSION
            );
            
            \wp_localize_script(
                'AceEditor', 
                'AceEditorLocalized', 
                array(
                    'plugin_url' => \plugin_dir_url(__FILE__) . "../",
                )
            );
            
            \wp_enqueue_script(
                "AceEditor",
                \plugin_dir_url(__FILE__)."../public/js/ace-builds/src-min-noconflict/ace.js", 
                array("jquery"),
                Plugin::VERSION
            );  
            
            \wp_register_style(
                'RollbarWordpressSettings',
                \plugin_dir_url(__FILE__)."../public/css/RollbarWordpressSettings.css",
                false, 
                Plugin::VERSION
            );
            \wp_enqueue_style('RollbarWordpressSettings');
        });
        
        \add_action('init', array(get_called_class(), 'registerSession'));

        \add_action('admin_post_rollbar_wp_restore_defaults', array(get_called_class(), 'restoreDefaultsAction'));
        
        \add_action('pre_update_option_rollbar_wp', array(get_called_class(), 'preUpdate'));
    }
    
    public static function registerSession()
    {
        if( !session_id() ) {
            session_start();
        }
    }

    function addAdminMenu()
    {
        add_submenu_page(
            'options-general.php',
            'Rollbar',
            'Rollbar',
            'manage_options',
            'rollbar_wp',
            array(&$this, 'optionsPage')
        );
    }

    function addSettings()
    {
        \register_setting(
            'rollbar_wp', 
            'rollbar_wp'
        );

        // SECTION: General
        \add_settings_section(
            'rollbar_wp_general',
            false,
            false,
            'rollbar_wp'
        );

        // On/off & tokens
        \add_settings_field(
            'rollbar_wp_status',
            __('Status', 'rollbar'),
            array('\Rollbar\Wordpress\UI', 'status'),
            'rollbar_wp',
            'rollbar_wp_general',
            array(
                'php_logging_enabled' => (!empty($this->plugin->setting('php_logging_enabled'))) ? 1 : 0,
                'server_side_access_token' => $this->plugin->setting('server_side_access_token'),
                'js_logging_enabled' => (!empty($this->plugin->setting('js_logging_enabled'))) ? 1 : 0,
                'client_side_access_token' => $this->plugin->setting('client_side_access_token')
            )
        );

        $envDescription = $this->parseSettingDescription('environment');
        $envDescription .= UI::environmentSettingNote();
        $this->addSetting(
            'environment',
            'rollbar_wp_general',
            array(
                'description' => $envDescription
            )
        );
        
        $included_errno_options = UI::getSettingOptions('included_errno');
        $human_friendly_errno_options = array();
        foreach ($included_errno_options as $included_errno) {
            $human_friendly_errno_options[$included_errno] = UI::getIncludedErrnoDescriptions($included_errno);
        }
        
        $this->addSetting(
            'logging_level', 
            'rollbar_wp_general', 
            array(
                'type' => UI::SETTING_INPUT_TYPE_SELECTBOX,
                'options' => $human_friendly_errno_options,
                'default' => E_ERROR
            )
        );
        
        // SECTION: Advanced
        \add_settings_section(
            'rollbar_wp_advanced',
            null,
            array(&$this, 'advancedSectionHeader'),
            'rollbar_wp'
        );
        
        $options = \Rollbar\Wordpress\Plugin::listOptions();
        $skip = array(
            'access_token', 'environment', 'enabled', 'included_errno',
            'base_api_url', 'enable_must_use_plugin'
        );
        
        foreach ($options as $option) {
            if (in_array($option, $skip)) {
                continue;
            }
            
            // TODO: https://github.com/rollbar/rollbar-php-wordpress/issues/41
            if (UI::getSettingType($option) == UI::SETTING_INPUT_TYPE_PHP) {
                continue;
            }
            
            $this->addSetting($option, 'rollbar_wp_advanced');
        }
        
        $this->addSetting(
            'enable_must_use_plugin', 
            'rollbar_wp_advanced',
            array(
                'type' => UI::getSettingType('enable_must_use_plugin'),
                'default' => \Rollbar\Wordpress\Defaults::instance()->enableMustUsePlugin(),
                'description' => __('Allows Rollbar plugin to be loaded as early ' .
                                    'as possible as a Must-Use plugin. Activating / ' .
                                    'deactivating the plugin in the plugins admin panel ' .
                                    'won\'t have an effect as long as this option in enabled.', 'rollbar'),
                'display_name' => __('Enable as a Must-Use plugin', 'rollbar')
            )
        );
    }
    
    private function addSetting($setting, $section, array $overrides = array())
    {
        $type = isset($overrides['type']) ? 
            $overrides['type'] : 
            UI::getSettingType($setting);
            
        $options = isset($overrides['options']) ? 
            $overrides['options'] : 
            UI::getSettingOptions($setting);
            
        if ($type === false || $options === false) {
            return;
        }
        
        $display_name = isset($overrides['display_name']) ? 
            $overrides['display_name'] : 
            ucfirst(str_replace("_", " ", $setting));
        
        if (isset($overrides['description'])) {
            $description = $overrides['description'];
        } else {
            $description = $this->parseSettingDescription($setting);
        }
            
        $default = isset($overrides['default']) ? 
            $overrides['default'] : 
            $this->settingDefault($setting);
            
        $value = $this->setting($setting);
        
        \add_settings_field(
            'rollbar_wp_' . $setting,
            __($display_name, 'rollbar'),
            array('Rollbar\Wordpress\UI', 'setting'),
            'rollbar_wp',
            $section,
            array(
                'label_for' => 'rollbar_wp_' . $setting,
                'name' => $setting,
                'display_name' => $display_name,
                'value' => $value,
                'description' => $description,
                'type' => $type,
                'options' => $options,
                'default' => $default
            )
        );
    }
    
    private function setting($setting)
    {
        return $this->settingToString($this->plugin->setting($setting));
    }
    
    private function settingToString($value)
    {
        if (is_string($value)) {
            $value = esc_attr(trim($value));
        } else if (is_array($value)) {
            $value = var_export($value, true);
        }
        
        return $value;
    }
    
    public function settingDefault($setting)
    {
        return $this->settingToString($this->plugin->getDefaultOption($setting));
    }
    
    public function advancedSectionHeader()
    {
        $output = '';
        
        $output .=  "<h3 class='hover-pointer' id='rollbar_settings_advanced_header'>" .
                    "   <span id='rollbar_settings_advanced_toggle'>►</span> " .
                    "   Advanced" .
                    "</h3>";
        
        $output .=  "<div id='rollbar_settings_advanced' style='display:none;'>";
        
        echo $output;
    }

    function optionsPage()
    {
        
        UI::flashMessage();
        
        ?>
        <form action='options.php' method='post'>

            <h2 class="rollbar-header">
                <img class="logo" alt="Rollbar" src="//cdn.rollbar.com/static/img/rollbar-icon-white.svg?ts=1548370449v8" width="auto" height="24">
                Rollbar for WordPress
            </h2>

            <?php
            \settings_fields('rollbar_wp');
            \do_settings_sections('rollbar_wp');
            ?>
            </div>
            
            <?php
            \submit_button();
            ?>

        </form>
        <?php
        
        UI::restoreAllDefaultsButton();
        UI::testButton();
    }
    
    private function parseSettingDescription($option)
    {
        $readme = file_get_contents(__DIR__ . '/../vendor/rollbar/rollbar/README.md');
        
        $option_pos = stripos($readme, '<dt>' . $option);

        $desc = '';
        
        if ($option_pos !== false) {
        
            $desc_pos = stripos($readme, '<dd>', $option_pos) + strlen('<dd>');
            
            $desc_close = stripos($readme, '</dd>', $desc_pos);
            
            $desc = substr($readme, $desc_pos, $desc_close - $desc_pos);
            
        }
        
        $desc = str_replace('```php', '```', $desc);
        $desc = Markdown::defaultTransform($desc);
        $desc = str_replace('```', '', $desc);
        
        return $desc;
    }
    
    public static function restoreDefaultsAction()
    {
        \Rollbar\Wordpress\Plugin::instance()->restoreDefaults();
        
        self::flashRedirect(
            "updated", 
            __("Default Rollbar settings restored.", "rollbar")
        );
    }
    
    public static function flashRedirect($type, $message)
    {
        self::flashMessage($type, $message);
        
        wp_redirect(admin_url('/options-general.php?page=rollbar_wp'));
    }
    
    public static function flashMessage($type, $message)
    {
        $_SESSION['rollbar_wp_flash_message'] = array(
            "type" => $type,
            "message" => $message
        );
    }
    
    public static function preUpdate($settings)
    {
        
        // Empty checkboxes don't get propagated into the $_POST at all. Fill out
        // missing boolean settings with default values.
        foreach (UI::settingsOfType(UI::SETTING_INPUT_TYPE_BOOLEAN) as $setting) {
            
            if (!isset($settings[$setting])) {
                $settings[$setting] = false;
            }
            
        }
        
        $settings['enabled'] = isset($settings['php_logging_enabled']) && $settings['php_logging_enabled'];
    
        if (isset($settings['enable_must_use_plugin']) && $settings['enable_must_use_plugin']) {
            try {
                Plugin::instance()->enableMustUsePlugin();
            } catch (\Exception $exception) {
                self::flashMessage('error', 'Failed enabling the Must-Use plugin.');
                $settings['enable_must_use_plugin'] = false;
            }
        } else {
            try {
                Plugin::instance()->disableMustUsePlugin();
            } catch (\Exception $exception) {
                self::flashMessage('error', 'Failed disabling the Must-Use plugin.');
                $settings['enable_must_use_plugin'] = true;
            }
        }
        
        // Don't store default values in the database. This is so that future updates
        // to default values in PHP SDK don't get stored in users databases.
        foreach ($settings as $setting_name => $setting_value) {
            if ($setting_value == Plugin::instance()->getDefaultOption($setting_name)) {
                unset($settings[$setting_name]);
            }
        }
        
        return $settings;
    }
}

?>
