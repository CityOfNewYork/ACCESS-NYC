<?php
/**
 * WP Bitly Administration
 *
 * @package     WPBitly
 * @subpackage  WPBitly/administration
 * @author    Temerity Studios <info@temeritystudios.com>
 * @license   GPL-2.0+
 * @link      http://wordpress.org/plugins/wp-bitly
 */

/**
 * Class WPBitly_Admin
 * This handles everything we do on the dashboard side.
 *
 * @since 2.0
 */
class WPBitly_Admin
{

    /**
     * @var $_instance An instance of ones own instance
     */
    protected static $_instance = null;


    /**
     * This creates and returns a single instance of WPBitly_Admin
     *
     * @since   2.0
     * @static
     * @uses    WPBitly_Admin::defineHooks() To set up any necessary WordPress hooks.
     * @return  WPBitly_Admin
     */
    public static function getIn()
    {

        if (!isset(self::$_instance) && !(self::$_instance instanceof WPBitly_Admin)) {
            self::$_instance = new self;
            self::$_instance->defineHooks();
        }

        return self::$_instance;
    }


    /**
     * Hook any necessary WordPress actions or filters that we'll be needing for the admin.
     *
     * @since   2.0
     * @uses    wpbitly()
     */
    public function defineHooks()
    {

        $wpbitly = wpbitly();

        add_action('init', array($this, 'checkForAuthorization'));
        add_action('init', array($this, 'regenerateLinks'));

        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_print_styles', array($this, 'enqueueStyles'));
        add_action('admin_print_scripts', array($this, 'enqueueScripts'));

        if (!$wpbitly->isAuthorized()) {
            add_action('admin_notices', array($this, 'displaySettingsNotice'));
        }


        $post_types = $wpbitly->getOption('post_types');

        if (is_array($post_types)) {
            foreach ($post_types as $post_type) {
                add_action('add_meta_boxes_' . $post_type, array($this, 'addMetaboxes'));
            }
        }

    }


    /**
     * Load administrative stylesheets
     *
     * @since  2.4.1
     */
    public function enqueueStyles()
    {

        $screen = get_current_screen();
        if ('options-writing' == $screen->base || 'post' == $screen->base) {
            wp_enqueue_style('wpbitly-admin', WPBITLY_URL . '/dist/css/admin.min.css');
            wp_enqueue_style('chartist', WPBITLY_URL . '/dist/vendor/chartist/chartist.min.css');
        }

    }


    /**
     * Load the Chartist scripts for our edit post screen
     *
     * @since  2.5.0
     */
    public function enqueueScripts()
    {

        $screen = get_current_screen();
        if ('post' == $screen->base) {
            wp_enqueue_script('chartist', WPBITLY_URL . '/dist/vendor/chartist/chartist.min.js');
        }

    }


    /**
     * Display a simple and unobtrusive notice on the plugins page after activation (and
     * up until the plugin is authorized with Bitly).
     *
     * @since   2.0
     */
    public function displaySettingsNotice()
    {

        $wpbitly = wpbitly();
        $screen = get_current_screen();

        if ($screen->base != 'plugins' || $wpbitly->isAuthorized()) {
            return;
        }

        $prologue = __('WP Bitly is almost ready!', 'wp-bitly');
        $link = sprintf('<a href="%s">', admin_url('options-writing.php')) . __('settings page', 'wp-bitly') . '</a>';
        $epilogue = sprintf(__('Please visit the %s to configure WP Bitly', 'wp-bitly'), $link);

        $message = apply_filters('wpbitly_setup_notice', sprintf('<div id="message" class="updated"><p>%s %s</p></div>', $prologue, $epilogue));

        echo $message;

    }


    /**
     * Checks for authorization information from Bitly, alternatively disconnects the current authorization
     * by deleting the token.
     *
     * @since 2.4.1
     */
    public function checkForAuthorization()
    {

        $wpbitly = wpbitly();
        $auth = $wpbitly->isAuthorized();

        if (!$auth && isset($_GET['access_token']) && isset($_GET['login'])) {

            $token = $_GET['access_token'];
            $login = $_GET['login'];

            wpbitly_debug_log(array('Referer' => $_SERVER['HTTP_REFERER'], 'Query String' => $_SERVER['QUERY_STRING']), 'Authorizing Env');
            wpbitly_debug_log(array('access_token' => $token, 'login' => $login, 'Escaped access_token' => esc_attr($token)), 'Authorizing');

            $wpbitly->setOption('oauth_token', $token);
            $wpbitly->setOption('oauth_login', $login);

            $wpbitly->authorize(true);

            add_action('admin_notices', array($this, 'authorizationSuccessfulNotice'));

        }

        if ($auth && isset($_GET['disconnect']) && 'bitly' == $_GET['disconnect']) {

            wpbitly_debug_log('', 'Disconnecting');
            $wpbitly->setOption('oauth_token', '');
            $wpbitly->setOption('oauth_login', '');

            $wpbitly->authorize(false);
        }

    }


    /**
     * Displays a notice at the top of the screen after a successful authorization
     *
     * @since 2.4.1
     */
    public function authorizationSuccessfulNotice()
    {
        $wpbitly = wpbitly();
        $token = $_GET['access_token'];

        if ($wpbitly->isAuthorized()) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Success!', 'wp-bitly') . '</strong> ' . __('WP Bitly is authorized, and you can start generating shortlinks!', 'wp-bitly') . '<br>';
            echo sprintf('Your access token is: <code>%s</code>', $token) . '</p></div>';
        }
    }


    /**
     * Verifies the shortlink attached to the current post with Bitly, and regenerates the link upon failure.
     *
     * @uses wpbitly_generate_shortlink()
     * @since 2.5.0
     */
    public function regenerateLinks()
    {

        if (isset($_GET['wpbr']) && isset($_GET['post'])) {

            $wpbitly = wpbitly();

            if (!$wpbitly->isAuthorized() || !is_numeric($_GET['post'])) {
                return false;
            }

            $post_id = (int)$_GET['post'];
            wpbitly_generate_shortlink($post_id, 1);

            add_action('admin_notices', array($this, 'regenerateSuccessfulNotice'));

        }

    }

    /**
     * Displays a notice at the top of the screen after a successful shortlink regeneration
     *
     * @since 2.5.0
     */
    public function regenerateSuccessfulNotice()
    {
        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Success!', 'wp-bitly') . '</strong> ' . __('The shortlink for this post has been regenerated.', 'wp-bitly') . '</p></div>';
    }


    /**
     * Add our options array to the WordPress whitelist, append them to the existing Writing
     * options page, and handle all the callbacks.
     * TODO: Let's separate this into its own class for future expansion. WPBitly_Admin should handle registering hooks only.
     *
     * @since   2.0
     */
    public function registerSettings()
    {

        register_setting('writing', 'wpbitly-options', array($this, 'validateSettings'));

        add_settings_section('wpbitly_settings', 'WP Bitly Shortlinks', '_f_settings_section', 'writing');
        function _f_settings_section()
        {
            $url = 'https://bitly.com/a/sign_up';
            echo '<p>' . sprintf(__('You will need a Bitly account to use this plugin. If you do not already have one, sign up <a href="%s">here</a>.', 'wp-bitly'), $url) . '</p>';
        }


        add_settings_field('authorize', '<label for="authorize">' . __('Connect with Bitly', 'wpbitly') . '</label>', '_f_settings_field_authorize', 'writing', 'wpbitly_settings');
        function _f_settings_field_authorize()
        {

            $wpbitly = wpbitly();
            $auth = $wpbitly->isAuthorized();
            $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

            if ($auth) {

                $url = add_query_arg('disconnect', 'bitly', strtok($request_uri, '?'));

                $output = sprintf('<a href="%s" class="button button-danger confirm-disconnect">%s</a>', $url, __('Disconnect', 'wp-bitly'));
                $output .= '<script>jQuery(function(n){n(".confirm-disconnect").click(function(){return window.confirm("Are you sure you want to disconnect your Bitly account?")})});</script>';

            } else {
                $redirect = strtok(home_url($request_uri), '?');

                $url = WPBITLY_TEMERITY_API . '?path=bitly&action=auth&state=' . urlencode($redirect);
                $image = WPBITLY_URL . '/dist/images/b_logo.png';

                $output = sprintf('<a href="%s" class="btn"><span class="btn-content">%s</span><span class="icon"><img src="%s"></span></a>', $url, __('Authorize', 'wp-bitly'), $image);

            }

            echo $output;

        }


        add_settings_field('oauth_token', '<label for="oauth_token">' . __('Bitly OAuth Token', 'wpbitly') . '</label>', '_f_settings_field_oauth', 'writing', 'wpbitly_settings');
        function _f_settings_field_oauth()
        {

            $wpbitly = wpbitly();

            $auth_css = $wpbitly->isAuthorized() ? '' : ' style="border-color: #c00; background-color: #ffecec;" ';
            $output = '<input type="text" size="40" name="wpbitly-options[oauth_token]" value="' . esc_attr($wpbitly->getOption('oauth_token')) . '"' . $auth_css . '>';
            $output .= '<p class="description">' . __('This field should auto-populate after using the authorization button above.', 'wp-bitly') . '<br>';
            $output .= __('If this field remains empty, please disconnect and attempt to authorize again.', 'wp-bitly') . '</p>';

            echo $output;

        }


        add_settings_field('post_types', '<label for="post_types">' . __('Post Types', 'wp-bitly') . '</label>', '_f_settings_field_post_types', 'writing', 'wpbitly_settings');
        function _f_settings_field_post_types()
        {

            $wpbitly = wpbitly();

            $post_types = apply_filters('wpbitly_allowed_post_types', get_post_types(array('public' => true)));
            $output = '<fieldset><legend class="screen-reader-text"><span>Post Types</span></legend>';

            $current_post_types = $wpbitly->getOption('post_types');
            foreach ($post_types as $label) {
                $output .= '<label for "' . $label . '>' . '<input type="checkbox" name="wpbitly-options[post_types][]" value="' . $label . '" ' . checked(in_array($label, $current_post_types), true,
                        false) . '>' . $label . '</label><br>';
            }

            $output .= '<p class="description">' . __('Shortlinks will automatically be generated for the selected post types.', 'wp-bitly') . '</p>';
            $output .= '</fieldset>';

            echo $output;

        }


        add_settings_field('debug', '<label for="debug">' . __('Debug WP Bitly', 'wp-bitly') . '</label>', '_f_settings_field_debug', 'writing', 'wpbitly_settings');
        function _f_settings_field_debug()
        {

            $wpbitly = wpbitly();
            $url = 'https://wordpress.org/support/plugin/wp-bitly';

            $output = '<fieldset>';
            $output .= '<legend class="screen-reader-text"><span>' . __('Debug WP Bitly', 'wp-bitly') . '</span></legend>';
            $output .= '<label title="debug"><input type="checkbox" id="debug" name="wpbitly-options[debug]" value="1" ' . checked($wpbitly->getOption('debug'), 1, 0) . '><span> ' . __("Let's debug!",
                    'wpbitly') . '</span></label><br>';
            $output .= '<p class="description">';
            $output .= sprintf(__("If you're having issues generating shortlinks, turn this on and create a thread in the <a href=\"%s\">support forums</a>.", 'wp-bitly'), $url);
            $output .= '</p></fieldset>';

            echo $output;

        }

    }


    /**
     * Validate user settings.
     *
     * @since   2.0
     * @param   array $input WordPress sanitized data array
     * @return  array           WP Bitly sanitized data
     */
    public function validateSettings($input)
    {

        $input['debug'] = ('1' == $input['debug']) ? true : false;

        if (!isset($input['post_types'])) {
            $input['post_types'] = array();
        } else {
            $post_types = apply_filters('wpbitly_allowed_post_types', get_post_types(array('public' => true)));

            foreach ($input['post_types'] as $key => $pt) {
                if (!in_array($pt, $post_types)) {
                    unset($input['post_types'][$key]);
                }
            }

        }

        return $input;

    }


    /**
     * Add the Link Administration and Statistics metabox to any post with a shortlink.
     * TODO: Separate this from the WP_Bitly_Admin
     *
     * @since   2.0
     * @param   object $post WordPress $post object
     */
    public function addMetaboxes($post)
    {

        $shortlink = get_post_meta($post->ID, '_wpbitly', true);

        if (!$shortlink) {
            return;
        }

        add_meta_box('wpbitly-meta', __('WP Bitly', 'wp-bitly'), array(
            $this,
            'displayMetabox'
        ), $post->post_type, 'side', 'default', array($shortlink));
    }


    /**
     * Handles the display of the metabox. Currently uses the Chartist library for displaying the past 7 days of
     * link activity. Other potential information includes referrers and encoders. Eventually this information
     * might open in a larger modal to display more accurately.
     *
     * @since   2.0
     * @param   object $post WordPress $post object
     * @param   array $args The post shortlink
     */
    public function displayMetabox($post, $args)
    {

        function _ceiling($number, $significance = 1)
        {
            return (is_numeric($number) && is_numeric($significance)) ? (ceil($number / $significance) * $significance) : false;
        }

        $wpbitly = wpbitly();
        $shortlink = $args['args'][0];
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''; // used in the display partial


        // Retrieve lifetime total
        $url = sprintf(wpbitly_api('link/clicks'), $wpbitly->getOption('oauth_token'), $shortlink);
        $response = wpbitly_get($url);

        if (is_array($response)) {
            $totalclicks = $response['data']['link_clicks'];
        }


        // Retrieve last 7 days of click information (starts at current date and runs back)
        $url = sprintf(wpbitly_api('link/clicks') . '&units=7&rollup=false', $wpbitly->getOption('oauth_token'), $shortlink);
        $response = wpbitly_get($url);

        if (is_array($response)) {
            $clicks = $response['data']['link_clicks'];
        }

        // Build strings for use in Chartist
        $labels_arr = array();
        $data_arr = array();

        foreach (array_reverse($clicks) as $click) {
            $labels_arr[] = date('m/d', $click['dt']);
            $data_arr[] = $click['clicks'];
        }

        $highest_clicks = max($data_arr);

        $labels_js = '"' . implode('","', $labels_arr) . '"';
        $data_js = implode(',', $data_arr);

        if ($highest_clicks < 10) {
            $max = 10;
        } else {
            $max = _ceiling($highest_clicks, str_pad('100', strlen((string)$highest_clicks), '0'));
        }

        // If the current highest clicks is less than 50, _ceiling will round up to 100. Better to not exceed 50.
        // TODO: Should this round 2020 to 2500 instead of 3000? 110 to 150 instead of 200? Etc.
        $p = ($highest_clicks / $max) * 100;
        if ($p < 50) {
            $max = $max / 2;
        }


        echo '<label class="screen-reader-text">' . __('WP Bitly Statistics &amp; Administration', 'wp-bitly') . '</label>';

        if (isset($totalclicks) && isset($clicks)) {

            echo '<div class="wpbitly-clicks">';
            echo '<p>' . __('Clicks Today', 'wp-bitly') . ' <span>' . number_format($clicks[0]['clicks']) . '</span></p>';
            echo '<p>' . __('Clicks Over Time', 'wp-bitly') . ' <span>' . number_format($totalclicks) . '</span></p>';
            echo '</div>';

            require(WPBITLY_DIR . '/includes/partials/metabox-display.php');


        } else {
            echo '<p class="error">' . __('There was a problem retrieving information about your link. There may be no statistics yet.', 'wp-bitly') . '</p>';
        }

    }

}

// TODO: This doesn't need to be a singleton.
WPBitly_Admin::getIn();
