<?php
// adapted from http://wordpress.org/extend/plugins/black-studio-wpml-javascript-redirect/
// thanks to Blank Studio - http://www.blackstudio.it/

class WPML_Browser_Redirect {

	/**
	 * @var SitePress
	 */
	private $sitepress;

	public function __construct( $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function init_hooks() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init(){
        if( ! isset( $_GET['redirect_to'] ) &&
            ! is_admin() &&
            ! preg_match( '#wp-login\.php$#', preg_replace("@\?(.*)$@", '', $_SERVER['REQUEST_URI'] ) )
        ) {
	        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        }
    }

    public function enqueue_scripts(){
        // Enqueue javascripts
        wp_register_script('jquery.cookie', ICL_PLUGIN_URL . '/res/js/jquery.cookie.js', array('jquery'), ICL_SITEPRESS_VERSION);
        wp_register_script('wpml-browser-redirect', ICL_PLUGIN_URL . '/res/js/browser-redirect.js', array('jquery', 'jquery.cookie'), ICL_SITEPRESS_VERSION);
            
        $args['skip_missing'] = intval( $this->sitepress->get_setting( 'automatic_redirect' ) == 1 );
        
        // Build multi language urls array
        $languages      = $this->sitepress->get_ls_languages($args);
        $language_urls  = array();
        foreach($languages as $language) {
			if(isset($language['default_locale']) && $language['default_locale']) {
				$language_urls[$language['default_locale']] = $language['url'];
				$language_parts = explode('_', $language['default_locale']);
				if(count($language_parts)>1) {
					foreach($language_parts as $language_part) {
						if(!isset($language_urls[$language_part])) {
							$language_urls[$language_part] = $language['url'];
						}
					}
				}
			}
			$language_urls[$language['language_code']] = $language['url'];
        }
        // Cookie parameters
        $http_host = $_SERVER['HTTP_HOST'] == 'localhost' ? '' : $_SERVER['HTTP_HOST'];
        $cookie = array(
            'name' => '_icl_visitor_lang_js',
            'domain' => (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN? COOKIE_DOMAIN : $http_host),
            'path' => (defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/'), 
            'expiration' => $this->sitepress->get_setting( 'remember_language' ),
        );
        
        // Send params to javascript
        $params = array(
            'pageLanguage'      => defined('ICL_LANGUAGE_CODE')? ICL_LANGUAGE_CODE : get_bloginfo('language'),
            'languageUrls'      => $language_urls,
            'cookie'            => $cookie            
        );
        wp_localize_script('wpml-browser-redirect', 'wpml_browser_redirect_params', $params);        
        wp_enqueue_script('wpml-browser-redirect');
    }
}
