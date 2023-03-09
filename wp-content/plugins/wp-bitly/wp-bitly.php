<?php
/**
 * WP Bitly
 * This plugin can be used to generate shortlinks for your websites posts, pages, and custom post types.
 * Extremely lightweight and easy to set up!
 *
 * @package   wp-bitly
 * @author    Temerity Studios <info@temeritystudios.com>
 * @author    Chip Bennett
 * @license   GPL-2.0+
 * @link      http://wordpress.org/plugins/wp-bitly
 * @wordpress-plugin
 *            Plugin Name:       WP Bitly
 *            Plugin URI:        http://wordpress.org/plugins/wp-bitly
 *            Description:       WP Bitly can be used to generate shortlinks for your website posts, pages, and custom post types. Extremely lightweight and easy to set up!
 *            Version:            2.5.2
 *            Author:            <a href="https://temeritystudios.com/">Temerity Studios</a>
 *            Text Domain:       wp-bitly
 *            License:           GPL-2.0+
 *            License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 *            Domain Path:       /languages
 */


if (!defined('WPINC')) {
    die;
}


define('WPBITLY_VERSION', ' 2.5.2');

define('WPBITLY_DIR', WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)));
define('WPBITLY_URL', plugins_url() . '/' . basename(dirname(__FILE__)));

define('WPBITLY_LOG', WPBITLY_DIR . '/log/debug.txt');
define('WPBITLY_ERROR', __('WP Bitly Error: No such option %1$s', 'wp-bitly'));

define('WPBITLY_OPTIONS', 'wpbitly-options');
define('WPBITLY_AUTHORIZED', 'wpbitly-authorized');

define('WPBITLY_BITLY_API', 'https://api-ssl.bitly.com/v3/');
define('WPBITLY_TEMERITY_API', 'https://api.temeritystudios.com/');

/**
 * The primary controller class for everything wonderful that WP Bitly does.
 * We're not sure entirely what that means yet; if you figure it out, please
 * let us know and we'll say something snazzy about it here.
 *
 * @package wp-bitly
 */
final class WPBitly
{

    /**
     * @var $_instance An instance of ones own instance
     */
    private static $_instance;

    /**
     * @var array The WP Bitly configuration is stored in here
     */
    private $_options = array();


    /**
     * Returns a single instance of WPBitly.
     *
     * @since   2.0
     * @static
     * @uses    WPBitly::populateOptions()  To create our options array.
     * @uses    WPBitly::includes_files()   To do something that sounds a lot like what it sounds like.
     * @uses    WPBitly::defineHooks()      To set up any necessary WordPress hooks.
     * @return  WPBitly
     */
    public static function getIn()
    {
        if (null === self::$_instance) {
            self::$_instance = new self;
            self::$_instance->populateOptions();
            self::$_instance->includeFiles();
            self::$_instance->defineHooks();
        }

        return self::$_instance;
    }


    /**
     * Populate WPBitly::$options with the configuration settings.
     *
     * @since 2.0
     */
    public function populateOptions()
    {

        $defaults = apply_filters('wpbitly_default_options', array(
            'version' => WPBITLY_VERSION,
            'oauth_token' => '',
            'oauth_login' => '',
            'post_types' => array('post', 'page'),
            'debug' => false,
        ));

        $this->_options = wp_parse_args(get_option(WPBITLY_OPTIONS), $defaults);

    }


    /**
     * Save all current options to the database
     *
     * @since 2.4.0
     */
    private function _saveOptions()
    {
        update_option('wpbitly-options', $this->_options);
    }

    /**
     * Access to our WPBitly::$_options array.
     *
     * @since 2.2.5
     * @param  $option string The name of the option we need to retrieve
     * @return         mixed  Returns the option
     */
    public function getOption($option)
    {
        if (!isset($this->_options[ $option ])) {
            trigger_error(sprintf(WPBITLY_ERROR, ' <code>' . $option . '</code>'), E_USER_ERROR);
        }

        return $this->_options[ $option ];
    }


    /**
     * Sets a single WPBitly::$_options value on the fly
     *
     * @since 2.2.5
     * @param $option string The name of the option we're setting
     * @param $value  mixed  The value, could be bool, string, array
     */
    public function setOption($option, $value)
    {
        if (!isset($this->_options[ $option ])) {
            trigger_error(sprintf(WPBITLY_ERROR, ' <code>' . $option . '</code>'), E_USER_ERROR);
        }

        $this->_options[ $option ] = $value;
        $this->_saveOptions();
    }


    /**
     * Used to short circuit any shortlink functions if we haven't authenticated to Bitly
     *
     * @since 2.4.0
     * @return bool
     */
    public function isAuthorized()
    {
        return get_option(WPBITLY_AUTHORIZED, false);
    }


    /**
     * @param bool $auth
     */
    public function authorize($auth = true)
    {
        if ($auth != true) {
            $auth = false;
        }

        update_option(WPBITLY_AUTHORIZED, $auth);
    }

    /**
     * So many files! Without this function we'd probably include things
     * in the wrong order or not at all, and wars would erupt across the planet.
     *
     * @since   2.0
     */
    public function includeFiles()
    {
        require_once(WPBITLY_DIR . '/includes/functions.php');
        if (is_admin()) {
            require_once(WPBITLY_DIR . '/includes/class.wp-bitly-admin.php');
        }
    }


    /**
     * Hook any necessary WordPress actions or filters that we'll be needing in order to make
     * the plugin work its magic. This method also registers our super amazing shortcode.
     *
     * @since 2.0
     */
    public function defineHooks()
    {

        add_action('init', array($this, 'loadPluginTextdomain'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'addActionLinks'));
        add_action('admin_bar_menu', 'wp_admin_bar_shortlink_menu', 90);

        //add_action('save_post', 'wpbitly_generate_shortlink');
        add_filter('pre_get_shortlink', 'wpbitly_get_shortlink', 20, 2);

        add_shortcode('wpbitly', 'wpbitly_shortlink');

    }


    /**
     * Add a settings link to the plugins page so people can figure out where we are.
     *
     * @since   2.0
     *
     * @param   $links An array returned by WordPress with our plugin action links
     *
     * @return  array The slightly modified 'rray.
     */
    public function addActionLinks($links)
    {

        return array_merge(array('settings' => '<a href="' . admin_url('options-writing.php') . '">' . __('Settings', 'wp-bitly') . '</a>'), $links);

    }


    /**
     * This would be much easier if we all spoke Esperanto (or Old Norse).
     *
     * @since   2.0
     */
    public function loadPluginTextdomain()
    {

        $languages = apply_filters('wpbitly_languages_dir', WPBITLY_DIR . '/languages/');
        $locale = apply_filters('plugin_locale', get_locale(), 'wp-bitly');
        $mofile = $languages . $locale . '.mo';

        if (file_exists($mofile)) {
            load_textdomain('wp-bitly', $mofile);
        } else {
            load_plugin_textdomain('wp-bitly', false, $languages);
        }

    }

}


/**
 * Call this in place of WPBitly::getIn()
 *
 * @return WPBitly
 */
function wpbitly()
{
    return WPBitly::getIn(); // there.
}

wpbitly();
