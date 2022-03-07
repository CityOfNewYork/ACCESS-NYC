<?php

/**
 * Manage settings for the plugin
 *
 * @link       https://watermelonwebworks.com
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */

class Wp_Bitly_Settings {

    /**
     * The options class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_options
     */
    protected $wp_bitly_options;

    /**
     * The api class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_options
     */
    protected $wp_bitly_api;

    /**
     * The auth class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class  $wp_bitly_auth
     */
    protected $wp_bitly_auth;
	
	/**
	 * Initialize 
	 *
	 * @since    2.6.0
	 */
	public function __construct() {
		$this->wp_bitly_options = new Wp_Bitly_Options();
        $this->wp_bitly_auth = new Wp_Bitly_Auth();
        $this->wp_bitly_api = new Wp_Bitly_Api();
	}

	/**
     * Add our options array to the WordPress whitelist, append them to the existing Writing
     * options page, and handle all the callbacks.
     *
     * @since   2.0
     */
    public function register_settings()
    {
        register_setting('writing', 'wpbitly-options', array($this->wp_bitly_options, 'validate_settings'));

        add_settings_section('wpbitly_settings', 'WP Bitly Shortlinks', '_f_settings_section', 'writing');

        function _f_settings_section()
        {
            $url = 'https://bitly.com/a/sign_up';
            echo '<p>' . sprintf(__('You will need a Bitly account to use this plugin. If you do not already have one, sign up <a href="%s">here</a>.', 'wp-bitly'), $url) . '</p>';
        }


        add_settings_field('authorize', '<label for="authorize">' . __('Connect with Bitly', 'wpbitly') . '</label>', '_f_settings_field_authorize', 'writing', 'wpbitly_settings', array($this->wp_bitly_auth));
        function _f_settings_field_authorize(array $args)
        {           
            
            $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

            $wp_bitly_auth = $args[0];
            $disconnect_url = add_query_arg('disconnect', 'bitly', strtok($request_uri, '?'));

            if ($wp_bitly_auth->isAuthorized()) {

                $output = sprintf('<a href="%s" id="disconnect_button" class="button button-danger confirm-disconnect">%s</a>', $disconnect_url, __('Disconnect', 'wp-bitly'));

            } else {

                $param_arr = array(
                    'client_id' => WPBITLY_OAUTH_CLIENT_ID,
                    'redirect_uri' => WPBITLY_OAUTH_REDIRECT_URI,
                );
                $params = urldecode( http_build_query( $param_arr ) );

                $url = WPBITLY_OAUTH_API . '?' . $params;
                $image = WPBITLY_URL . '/admin/images/b_logo.png';

                $output = sprintf('<a href="%s" id="authorization_button" class="btn"><span class="btn-content">%s</span><span class="icon"><img src="%s"></span></a>', $url, __('Authorize', 'wp-bitly'), $image);
                $output .= sprintf('<a href="%s" id="disconnect_button" class="button button-danger confirm-disconnect hidden" style="display:none;">%s</a>', $disconnect_url, __('Disconnect', 'wp-bitly'));
            }

            echo $output;

        }


        add_settings_field('oauth_token', '<label for="oauth_token">' . __('Bitly OAuth Token', 'wpbitly') . '</label>', '_f_settings_field_oauth', 'writing', 'wpbitly_settings', array($this->wp_bitly_auth,$this->wp_bitly_options));
        function _f_settings_field_oauth(array $args)
        {      
            $wp_bitly_auth = $args[0];
            $wp_bitly_options = $args[1];

            $auth_css = $wp_bitly_auth->isAuthorized() ? ' class="authorized" ' : ' class="not_authorized" ';
            $output = '<input type="text" size="40" id="wpbitly_oauth_token" name="wpbitly-options[oauth_token]" value="' . esc_attr($wp_bitly_options->get_option('oauth_token')) . '"' . $auth_css . '>';
            $output .= '<p class="description">' . __('This field should auto-populate after using the authorization button above.', 'wp-bitly') . '<br>';
            $output .= __('If this field remains empty, please disconnect and attempt to authorize again.', 'wp-bitly') . '</p>';

            echo $output;

        }


        add_settings_field('post_types', '<label for="post_types">' . __('Post Types', 'wp-bitly') . '</label>', '_f_settings_field_post_types', 'writing', 'wpbitly_settings', array($this->wp_bitly_options));
        function _f_settings_field_post_types(array $args)
        {
            $post_types = apply_filters('wpbitly_allowed_post_types', get_post_types(array('public' => true)));
            $output = '<fieldset><legend class="screen-reader-text"><span>Post Types</span></legend>';

            $wp_bitly_options = $args[0];

            $current_post_types = $wp_bitly_options->get_option('post_types');
            foreach ($post_types as $label) {
                $output .= '<label for "' . $label . '>' . '<input type="checkbox" name="wpbitly-options[post_types][]" value="' . $label . '" ' . checked(in_array($label, $current_post_types), true,
                        false) . '>' . $label . '</label><br>';
            }

            $output .= '<p class="description">' . __('Shortlinks will automatically be generated for the selected post types.', 'wp-bitly') . '</p>';
            $output .= '</fieldset>';

            echo $output;

        }
        
        add_settings_field('default_org', '<label for="post_types">' . __('Default Organization', 'wp-bitly') . '</label>', '_f_settings_field_default_org', 'writing', 'wpbitly_settings', array($this->wp_bitly_options,$this));
        function _f_settings_field_default_org(array $args)
        {            
            $wp_bitly_options = $args[0];
            $wp_bitly_settings = $args[1];

            
            if(!$wp_bitly_options->get_option('oauth_token')){
                $style = "style = 'display:none;'";       
            }else{
                $style = "";
            }
 
            $output .= "<a id = 'bitly_selections'></a><fieldset class = 'wpbitly_default_org_fieldset' $style ><legend class='screen-reader-text'><span>Default Organization</span></legend>";
            $output .= "<select name='wpbitly-options[default_org]' id='wpbitly_default_org' >";
            $output .= $wp_bitly_settings->get_domain_options($wp_bitly_options->get_option('oauth_token'));
            $output .= "</select>";
            $output .= '</fieldset>';
            echo $output;
        }
        
        add_settings_field('default_group', '<label for="post_types">' . __('Default Group', 'wp-bitly') . '</label>', '_f_settings_field_default_group', 'writing', 'wpbitly_settings', array($this->wp_bitly_options,$this));
        function _f_settings_field_default_group(array $args)
        {            
            $wp_bitly_options = $args[0];
            $wp_bitly_settings = $args[1];
            $current_default_org = $wp_bitly_options->get_option('default_org');
            if(!$wp_bitly_options->get_option('oauth_token')){
                $style = "style = 'display:none;'";       
            }else{
                $style = "";
            }
            $output = "<fieldset class = 'wpbitly_default_org_fieldset' $style ><legend class='screen-reader-text'><span>Default Group</span></legend>";
            $output .= "<select name='wpbitly-options[default_group]' id='wpbitly_default_group' >";
            $output .= $wp_bitly_settings->get_group_options($current_default_org);
            $output .= "</select>";
            $output .= '<p class="description">' . __('If no default group is selected, the default group setting on your Bitly account will be used.', 'wp-bitly') . '</p>';
            $output .= '</fieldset>';
            echo $output;
            
            
        }
        
        add_settings_field('default_domain', '<label for="post_types">' . __('Default Domain', 'wp-bitly') . '</label>', '_f_settings_field_default_domain', 'writing', 'wpbitly_settings', array($this->wp_bitly_options,$this));
        function _f_settings_field_default_domain(array $args)
        {
            $wp_bitly_options = $args[0];
            $wp_bitly_settings = $args[1];
            $current_default_group = $wp_bitly_options->get_option('default_group');
            if(!$wp_bitly_options->get_option('oauth_token')){
                $style = "style = 'display:none;'";       
            }else{
                $style = "";
            }
            $output = "<fieldset class = 'wpbitly_default_org_fieldset' $style ><legend class='screen-reader-text'><span>Default Domain</span></legend>";
            $output .= "<select name='wpbitly-options[default_domain]' id='wpbitly_default_domain' >";
            $output .= $wp_bitly_settings->get_domain_options($current_default_group);
            $output .= "</select>";
            $output .= '<p class="description">' . __('If you do not have any additional domains on your account, the default bit.ly domain will be the only option.', 'wp-bitly') . '</p>';
            $output .= '</fieldset>';
            echo $output;
            
        }
        
        


        add_settings_field('debug', '<label for="debug">' . __('Debug WP Bitly', 'wp-bitly') . '</label>', '_f_settings_field_debug', 'writing', 'wpbitly_settings', array($this->wp_bitly_options));
        function _f_settings_field_debug(array $args)
        {
            $wp_bitly_options = $args[0];
            $url = 'https://wordpress.org/support/plugin/wp-bitly';

            $output = '<fieldset>';
            $output .= '<legend class="screen-reader-text"><span>' . __('Debug WP Bitly', 'wp-bitly') . '</span></legend>';
            $output .= '<label title="debug"><input type="checkbox" id="debug" name="wpbitly-options[debug]" value="1" ' . checked($wp_bitly_options->get_option('debug'), 1, 0) . '><span> ' . __("Let's debug!",
                    'wpbitly') . '</span></label><br>';
            $output .= '<p class="description">';
            $output .= sprintf(__("If you're having issues generating shortlinks, turn this on and create a thread in the <a href=\"%s\">support forums</a>.", 'wp-bitly'), $url);
            $output .= '</p></fieldset>';

            echo $output;

        }

    }
    
        /**
* This function is used to return the org options available for the selected token. 
*
* @since 2.6.0
*
* @see _f_settings_field_default_org
* 
* @param string $current_token The token associated with the the Bitly account
* @return string $output The html options available for the organization selector based on the Bitly token.
*/
    
    public function get_org_options($current_token){

        if(wp_doing_ajax()){
            $token = sanitize_text_field( $_POST['token'] );
        }else{
            $token = $current_token;
        }
        $output = '';
        $current_default_org = $this->wp_bitly_options->get_option('default_org');
        $organization_url = $this->wp_bitly_api->wpbitly_api('organizations');
        $organization_response = $this->wp_bitly_api->wpbitly_get($organization_url,$token);
        
        foreach($organization_response['organizations'] as $org){
            $guid=$org['guid'];
            $name=$org['name'];

            if($guid == $current_default_org){
                $selected = "selected";
            }else{
                $selected = "";
            }
            $output .= "<option value = '$guid' $selected >$name</option>";
        }
        if(wp_doing_ajax()){
            echo $output;
            die();
        }else{
            return $output;
        }
}
    
    
    
    /**
* This function is used to return the group options available for the selected organization id. 
*
* @since 2.6.0
*
* @see _f_settings_field_default_org
* 
* @param string $current_default_org The guid associated with the organization of the Bitly account
* @return string $output The html options available for the group selector based on the Bitly group id
*/
    
    public function get_group_options($current_default_org = ''){

        if(wp_doing_ajax()){
            $org = sanitize_text_field( $_POST['curr_org'] );
            
        }else{
            $org = $current_default_org;
        }
        $output = '';
        $current_default_group = $this->wp_bitly_options->get_option('default_group');
        //first we need to find the organization guid.
        if($org){
            $current_org_guid = $org;
        }else{
            //get the first result for now.
            $organization_url = $this->wp_bitly_api->wpbitly_api('organizations');
            $organization_response = $this->wp_bitly_api->wpbitly_get($organization_url,$this->wp_bitly_options->get_option('oauth_token'));
            $current_org_guid = $organization_response['organizations'][0]['guid'];
        }
        //now with the organization guid, get the group
        $groups_url=sprintf($this->wp_bitly_api->wpbitly_api('groups').'?organization_guid=%1$s',$current_org_guid);
        $response_groups = $this->wp_bitly_api->wpbitly_get($groups_url,$this->wp_bitly_options->get_option('oauth_token'));

        if(count($response_groups['groups'])>1){
            $output .= "<option value=''>- select -</option>";
        }
        
        foreach($response_groups['groups'] as $group){
            $group_guid=$group['guid'];
            $group_name=$group['name'];
            if($current_default_group == $group_guid){
                $selected = 'selected';
            }else{
                $selected = '';
            }
            $output .= "<option value='$group_guid' $selected >$group_name</option>";
        }
        if(wp_doing_ajax()){
            echo $output;
            die();
        }else{
            return $output;
        }
}
    
/**
* This function is used to return the domain options available for the selected group id. This
* defaults to bit.ly unless the group has access to more domains per the Bitly account associated
* with the token.
*
* @since 2.6.0
*
* @see _f_settings_field_default_group
* 
* @param string $current_default_group The group id (aka guid) associated with the Bitly account
* @return string $output The html options available for the domain selector based on the Bitly group id
*/
    
    public function get_domain_options($current_default_group = ''){

        if(wp_doing_ajax()){
            $group_id = sanitize_text_field( $_POST['curr_group'] );
        }else{
            $group_id = $current_default_group;
        }
        $current_default_domain = $this->wp_bitly_options->get_option('default_domain');
        if(!$group_id){
            $output = "<option value=''>bit.ly</option>";
            if(wp_doing_ajax()){
                echo $output;
                die(); 
            }else{
                return $output;
            }
        }
        $group_url=$this->wp_bitly_api->wpbitly_api('groups')."/".$group_id;
        $response_group = $this->wp_bitly_api->wpbitly_get($group_url,$this->wp_bitly_options->get_option('oauth_token'));
        $output = "<option value=''>bit.ly</option>";
        if(count($response_group['bsds'])>0){
            foreach($response_group['bsds'] as $domain){
                if($current_default_domain==$domain){
                    $selected = 'selected';
                }else{
                    $selected = '';
                }
                $output .= "<option value='$domain' $selected >$domain</option>";
            }
            
        }
        if(wp_doing_ajax()){
            echo $output;
            die();
        }else{
            return $output;
        }
        
    }
	

}
