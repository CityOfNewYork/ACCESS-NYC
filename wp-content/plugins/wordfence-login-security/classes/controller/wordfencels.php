<?php

namespace WordfenceLS;

use WordfenceLS\Crypto\Model_JWT;
use WordfenceLS\Crypto\Model_Symmetric;
use WordfenceLS\Text\Model_HTML;
use WordfenceLS\View\Model_Tab;
use WordfenceLS\View\Model_Title;

class Controller_WordfenceLS {
	const VERSION_KEY = 'wordfence_ls_version';
	const USERS_PER_PAGE = 25;
	const SHORTCODE_2FA_MANAGEMENT = 'wordfence_2fa_management';
	const WOOCOMMERCE_ENDPOINT = 'wordfence-2fa';

	private $management_assets_registered = false;
	private $management_assets_enqueued = false;
	private $use_core_font_awesome_styles = null;
	
	/**
	 * Returns the singleton Controller_Wordfence2FA.
	 *
	 * @return Controller_WordfenceLS
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_WordfenceLS();
		}
		return $_shared;
	}
	
	public function init() {
		$this->_init_actions();
		Controller_AJAX::shared()->init();
		Controller_Users::shared()->init();
		Controller_Time::shared()->init();
		Controller_Permissions::shared()->init();
	}
	
	protected function _init_actions() {
		register_activation_hook(WORDFENCE_LS_FCPATH, array($this, '_install_plugin'));
		register_deactivation_hook(WORDFENCE_LS_FCPATH, array($this, '_uninstall_plugin'));
		
		$versionInOptions = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, self::VERSION_KEY, false) : get_option(self::VERSION_KEY, false));
		if (!$versionInOptions || version_compare(WORDFENCE_LS_VERSION, $versionInOptions, '>')) { //Either there is no version in options or the version in options is greater and we need to run the upgrade
			$this->_install();
		}
		
		if (!Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ALLOW_XML_RPC)) {
			add_filter('xmlrpc_enabled', array($this, '_block_xml_rpc'));
		}
		
		add_action('admin_init', array($this, '_admin_init'));
		add_action('login_enqueue_scripts', array($this, '_login_enqueue_scripts'));
		add_filter('authenticate', array($this, '_authenticate'), 25, 3);
		add_action('set_logged_in_cookie', array($this, '_set_logged_in_cookie'), 25, 4);
		add_action('wp_login', array($this, '_record_login'), 999, 1);
		add_action('register_post', array($this, '_register_post'), 25, 3);
		add_filter('wp_login_errors', array($this, '_wp_login_errors'), 25, 3);
		if ($this->is_woocommerce_integration_enabled()) {
			$this->init_woocommerce_actions();
		}
		add_action('user_new_form', array($this, '_user_new_form'));
		add_action('user_register', array($this, '_user_register'));
		
		$useSubmenu = WORDFENCE_LS_FROM_CORE;
		if (is_multisite() && !is_network_admin()) {
			$useSubmenu = false;
		}
		
		add_action('admin_menu', array($this, '_admin_menu'), $useSubmenu ? 55 : 10);
		if (is_multisite()) {
			add_action('network_admin_menu', array($this, '_admin_menu'), $useSubmenu ? 55 : 10);
		}
		add_action('admin_enqueue_scripts', array($this, '_admin_enqueue_scripts'));
		
		add_action('show_user_profile', array($this, '_edit_user_profile'), 0); //We can't add it to the password section directly -- priority 0 is as close as we can get
		add_action('edit_user_profile', array($this, '_edit_user_profile'), 0);

		add_action('init', array($this, '_wordpress_init'));
		if ($this->is_shortcode_enabled())
			add_action('wp_enqueue_scripts', array($this, '_handle_shortcode_prerequisites'));
		
		Controller_Permissions::_init_actions();
	}

	public function _wordpress_init() {
		if (!WORDFENCE_LS_FROM_CORE)
			load_plugin_textdomain('wordfence-login-security', false, WORDFENCE_LS_PATH . 'languages');
		if ($this->is_shortcode_enabled())
			add_shortcode(self::SHORTCODE_2FA_MANAGEMENT, array($this, '_handle_user_2fa_management_shortcode'));
	}

	private function init_woocommerce_actions() {
		add_action('woocommerce_before_customer_login_form', array($this, '_woocommerce_login_enqueue_scripts'));
		add_action('woocommerce_before_checkout_form', array($this, '_woocommerce_checkout_login_enqueue_scripts'));
		add_action('wp_loaded', array($this, '_handle_woocommerce_registration'), 10, 0); //Woocommerce uses priority 20

		if ($this->is_woocommerce_account_integration_enabled()) {
			add_filter('woocommerce_account_menu_items', array($this, '_woocommerce_account_menu_items'));
			add_filter('woocommerce_account_wordfence-2fa_endpoint', array($this, '_woocommerce_account_menu_content'));
			add_filter('woocommerce_get_query_vars', array($this, '_woocommerce_get_query_vars'));
			add_action('wp_enqueue_scripts', array($this, '_woocommerce_account_enqueue_assets'));
		}
	}
	
	public function _admin_init() {
		if (WORDFENCE_LS_FROM_CORE) {
			\wfModuleController::shared()->addOptionIndex('wfls-option-enable-2fa-roles', __('Login Security: Enable 2FA for these roles', 'wordfence-login-security'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-allow-remember', __('Login Security: Allow remembering device for 30 days', 'wordfence-login-security'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-require-2fa-xml-rpc', __('Login Security: Require 2FA for XML-RPC call authentication', 'wordfence-login-security'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-disable-xml-rpc', __('Login Security: Disable XML-RPC authentication', 'wordfence-login-security'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-whitelist-2fa', __('Login Security: Allowlisted IP addresses that bypass 2FA and reCAPTCHA', 'wordfence-login-security'));
			\wfModuleController::shared()->addOptionIndex('wfls-option-enable-captcha', __('Login Security: Enable reCAPTCHA on the login and user registration pages', 'wordfence-login-security'));
			
			$title = __('Login Security Options', 'wordfence-login-security');
			$description = __('Login Security options are available on the Login Security options page', 'wordfence-login-security');
			$url = esc_url(network_admin_url('admin.php?page=WFLS#top#settings'));
			$link = __('Login Security Options', 'wordfence-login-security');;
			\wfModuleController::shared()->addOptionBlock(<<<END
<div class="wf-row">
	<div class="wf-col-xs-12">
		<div class="wf-block wf-always-active" data-persistence-key="">
			<div class="wf-block-header">
				<div class="wf-block-header-content">
					<div class="wf-block-title">
						<strong>{$title}</strong>
					</div>
				</div>
			</div>
			<div class="wf-block-content">
				<ul class="wf-block-list">
					<li>
						<ul class="wf-flex-horizontal wf-flex-vertical-xs wf-flex-full-width wf-add-top wf-add-bottom">
							<li>{$description}</li>
							<li class="wf-right wf-left-xs wf-padding-add-top-xs-small">
								<a href="{$url}" class="wf-btn wf-btn-primary wf-btn-callout-subtle" id="wf-login-security-options">{$link}</a>
							</li>
						</ul>
						<input type="hidden" id="wfls-option-enable-2fa-roles">
						<input type="hidden" id="wfls-option-allow-remember">
						<input type="hidden" id="wfls-option-require-2fa-xml-rpc">
						<input type="hidden" id="wfls-option-disable-xml-rpc">
						<input type="hidden" id="wfls-option-whitelist-2fa">
						<input type="hidden" id="wfls-option-enable-captcha">
					</li>
				</ul>
			</div>
		</div>
	</div>
</div> <!-- end ls options -->
END
);
		}

		if (Controller_Permissions::shared()->can_manage_settings()) {
			if ((is_plugin_active('jetpack/jetpack.php') || (is_multisite() && is_plugin_active_for_network('jetpack/jetpack.php'))) && !Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ALLOW_XML_RPC)) {
				if (is_multisite()) {
					add_action('network_admin_notices', array($this, '_jetpack_xml_rpc_notice'));
				}
				else {
					add_action('admin_notices', array($this, '_jetpack_xml_rpc_notice'));
				}
			}

			if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_CAPTCHA_TEST_MODE) && Controller_CAPTCHA::shared()->enabled()) {
				if (is_multisite()) {
					add_action('network_admin_notices', array($this, '_recaptcha_test_notice'));
				}
				else {
					add_action('admin_notices', array($this, '_recaptcha_test_notice'));
				}
			}

			if ($this->has_woocommerce() && !Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION)) {
				if (!Controller_Notices::shared()->is_persistent_notice_dismissed(get_current_user_id(), Controller_Notices::PERSISTENT_NOTICE_WOOCOMMERCE_INTEGRATION)) {
					Controller_Notices::shared()->register_persistent_notice(Controller_Notices::PERSISTENT_NOTICE_WOOCOMMERCE_INTEGRATION);
					add_action(is_multisite() ? 'network_admin_notices' : 'admin_notices', array($this, '_woocommerce_integration_notice'));
				}
			}
		}
	}
	
	/**
	 * Notices
	 */
	
	public function _jetpack_xml_rpc_notice() {
		echo '<div class="notice notice-warning"><p>' . wp_kses(sprintf(__('XML-RPC authentication is disabled. Jetpack is currently active and requires XML-RPC authentication to work correctly. <a href="%s">Manage Settings</a>', 'wordfence-login-security'), esc_url(network_admin_url('admin.php?page=WFLS#top#settings'))), array('a'=>array('href'=>array()))) . '</p></div>';
	}
	
	public function _recaptcha_test_notice() {
		echo '<div class="notice notice-warning"><p>' . wp_kses(sprintf(__('reCAPTCHA test mode is enabled. While enabled, login and registration requests will be checked for their score but will not be blocked if the score is below the minimum score. <a href="%s">Manage Settings</a>', 'wordfence-login-security'), esc_url(network_admin_url('admin.php?page=WFLS#top#settings'))), array('a'=>array('href'=>array()))) . '</p></div>';
	}

	public function _woocommerce_integration_notice() {
?>
		<div id="<?php echo esc_attr(Controller_Notices::PERSISTENT_NOTICE_WOOCOMMERCE_INTEGRATION) ?>" class="notice notice-warning is-dismissible wfls-persistent-notice">
			<p>
				<?php esc_html_e('WooCommerce appears to be installed, but the Wordfence Login Security WooCommerce integration is not currently enabled. Without this feature, WooCommerce forms will not support all functionality provided by Wordfence Login Security, including CAPTCHA for the login page and user registration.', 'wordfence-login-security'); ?>
				<a href="<?php echo esc_attr(esc_url(network_admin_url('admin.php?page=WFLS#top#settings'))) ?>"><?php esc_html_e('Manage Settings', 'wordfence-login-security') ?></a>
			</p>
		</div>
<?php
	}
	
	/**
	 * Installation/Uninstallation
	 */
	
	public function _install_plugin() {
		$this->_install();
	}
	
	public function _uninstall_plugin() {
		Controller_Time::shared()->uninstall();
		Controller_Permissions::shared()->uninstall();
		
		foreach (array(self::VERSION_KEY) as $opt) {
			if (is_multisite() && function_exists('delete_network_option')) {
				delete_network_option(null, $opt);
			}
			delete_option($opt);
		}
		
		if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_DELETE_ON_DEACTIVATION)) {
			Controller_DB::shared()->uninstall();
		}

		$this->purge_rewrite_rules();
	}
	
	protected function _install() {
		static $_runInstallCalled = false;
		if ($_runInstallCalled) { return; }
		$_runInstallCalled = true;
		
		if (function_exists('ignore_user_abort') && is_callable('ignore_user_abort')) {
			@ignore_user_abort(true);
		}
		
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		
		$previousVersion = ((is_multisite() && function_exists('get_network_option')) ? get_network_option(null, self::VERSION_KEY, '0.0.0') : get_option(self::VERSION_KEY, '0.0.0'));
		if (is_multisite() && function_exists('update_network_option')) {
			update_network_option(null, self::VERSION_KEY, WORDFENCE_LS_VERSION); //In case we have a fatal error we don't want to keep running install.	
		}
		else {
			update_option(self::VERSION_KEY, WORDFENCE_LS_VERSION); //In case we have a fatal error we don't want to keep running install.
		}
		
		Controller_DB::shared()->install();
		Controller_Settings::shared()->set_defaults();
		
		if (\WordfenceLS\Controller_Time::time() > Controller_Settings::shared()->get_int(Controller_Settings::OPTION_LAST_SECRET_REFRESH) + 180 * 86400) {
			Model_Crypto::refresh_secrets();
		}
		
		Controller_Time::shared()->install();
		Controller_Permissions::shared()->install();

		$this->purge_rewrite_rules();
	}

	private function purge_rewrite_rules() {
		// This is usually done internally in WP_Rewrite::flush_rules, but is followed there by WP_Rewrite::wp_rewrite_rules which repopulates it. This should cause it to be repopulated on the next request.
		update_option('rewrite_rules', '');
	}

	/**
	 * In most cases, this will be done internally by WooCommerce since we are using the woocommerce_get_query_vars filter, but when toggling the option on our settings page we must still do this manually
	 */
	private function register_rewrite_endpoints() {
		add_rewrite_endpoint(self::WOOCOMMERCE_ENDPOINT, $this->is_woocommerce_account_integration_enabled() ? EP_PAGES : EP_NONE);
	}

	public function refresh_rewrite_rules() {
		$this->register_rewrite_endpoints();
		flush_rewrite_rules();
	}
	
	public function _block_xml_rpc() {
		/**
		 * Fires just prior to blocking an XML-RPC request. After firing this action hook the XML-RPC request is blocked.
		 *
		 * @param int $source The source code of the block.
		 */
		do_action('wfls_xml_rpc_blocked', 2);
		return false;
	}

	private function has_woocommerce() {
		return class_exists('woocommerce');
	}

	private function is_woocommerce_integration_enabled() {
		return Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION);
	}

	private function is_woocommerce_account_integration_enabled() {
		return $this->is_woocommerce_integration_enabled() && Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION);
	}

	private function is_shortcode_enabled() {
		return Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_SHORTCODE);
	}

	public function _woocommerce_login_enqueue_scripts() {
		wp_enqueue_style('dashicons');
		$this->_login_enqueue_scripts();
	}

	public function _woocommerce_checkout_login_enqueue_scripts() {
		/**
		 * This is the same check used in WooCommerce to determine whether or not to display the checkout login form
		 * @see templates/checkout/form-login.php in WooCommerce
		 */
		if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
			return;
		}
		$this->_woocommerce_login_enqueue_scripts();
	}
	
	/**
	 * Login Page
	 */	
	public function _login_enqueue_scripts() {
		$useCAPTCHA = Controller_CAPTCHA::shared()->enabled();
		if ($useCAPTCHA) {
			wp_enqueue_script('wordfence-ls-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . urlencode(Controller_Settings::shared()->get(Controller_Settings::OPTION_RECAPTCHA_SITE_KEY)));
		}
		
		if ($useCAPTCHA || Controller_Users::shared()->any_2fa_active()) {
			$this->validate_email_verification_token(null, $verification);
			
			Model_Script::create('wordfence-ls-login', Model_Asset::js('login.js'), array('jquery'), WORDFENCE_LS_VERSION)
				->withTranslations(array(
					'Message to Support' => __('Message to Support', 'wordfence-login-security'),
					'Send' => __('Send', 'wordfence-login-security'),
					'An error was encountered while trying to send the message. Please try again.' => __('An error was encountered while trying to send the message. Please try again.', 'wordfence-login-security'),
					'<strong>ERROR</strong>: An error was encountered while trying to send the message. Please try again.' => wp_kses(__('<strong>ERROR</strong>: An error was encountered while trying to send the message. Please try again.', 'wordfence-login-security'), array('strong' => array())),
					'Login failed with status code 403. Please contact the site administrator.' => __('Login failed with status code 403. Please contact the site administrator.', 'wordfence-login-security'),
					'<strong>ERROR</strong>: Login failed with status code 403. Please contact the site administrator.' => wp_kses(__('<strong>ERROR</strong>: Login failed with status code 403. Please contact the site administrator.', 'wordfence-login-security'), array('strong' => array())),
					'Login failed with status code 503. Please contact the site administrator.' => __('Login failed with status code 503. Please contact the site administrator.', 'wordfence-login-security'),
					'<strong>ERROR</strong>: Login failed with status code 503. Please contact the site administrator.' => wp_kses(__('<strong>ERROR</strong>: Login failed with status code 503. Please contact the site administrator.', 'wordfence-login-security'), array('strong' => array())),
					'Wordfence 2FA Code' => __('Wordfence 2FA Code', 'wordfence-login-security'),
					'Remember for 30 days' => __('Remember for 30 days', 'wordfence-login-security'),
					'Log In' => __('Log In', 'wordfence-login-security'),
					'<strong>ERROR</strong>: An error was encountered while trying to authenticate. Please try again.' => wp_kses(__('<strong>ERROR</strong>: An error was encountered while trying to authenticate. Please try again.', 'wordfence-login-security'), array('strong' => array())),
					'The Wordfence 2FA Code can be found within the authenticator app you used when first activating two-factor authentication. You may also use one of your recovery codes.' => __('The Wordfence 2FA Code can be found within the authenticator app you used when first activating two-factor authentication. You may also use one of your recovery codes.', 'wordfence-login-security')
				))
				->setTranslationObjectName('WFLS_LOGIN_TRANSLATIONS')
				->enqueue();
			wp_enqueue_style('wordfence-ls-login', Model_Asset::css('login.css'), array(), WORDFENCE_LS_VERSION);
			wp_localize_script('wordfence-ls-login', 'WFLSVars', array(
				'ajaxurl' => Utility_URL::relative_admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('wp-ajax'),
				'recaptchasitekey' => Controller_Settings::shared()->get(Controller_Settings::OPTION_RECAPTCHA_SITE_KEY),
				'useCAPTCHA' => $useCAPTCHA,
				'allowremember' => Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_REMEMBER_DEVICE_ENABLED),
				'verification' => $verification,
			));
		}
	}

	private function get_2fa_management_script_data() {
		return array(
			'WFLSVars' => array(
				'ajaxurl' => Utility_URL::relative_admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('wp-ajax'),
				'modalTemplate' => Model_View::create('common/modal-prompt', array('title' => '${title}', 'message' => '${message}', 'primaryButton' => array('id' => 'wfls-generic-modal-close', 'label' => __('Close', 'wordfence-login-security'), 'link' => '#')))->render(),
				'modalNoButtonsTemplate' => Model_View::create('common/modal-prompt', array('title' => '${title}', 'message' => '${message}'))->render(),
				'tokenInvalidTemplate' => Model_View::create('common/modal-prompt', array('title' => '${title}', 'message' => '${message}', 'primaryButton' => array('id' => 'wfls-token-invalid-modal-reload', 'label' => __('Reload', 'wordfence-login-security'), 'link' => '#')))->render(),
				'modalHTMLTemplate' => Model_View::create('common/modal-prompt', array('title' => '${title}', 'message' => '{{html message}}', 'primaryButton' => array('id' => 'wfls-generic-modal-close', 'label' => __('Close', 'wordfence-login-security'), 'link' => '#')))->render()
			)
		);
	}

	public function should_use_core_font_awesome_styles() {
		if ($this->use_core_font_awesome_styles === null) {
			$this->use_core_font_awesome_styles = wp_style_is('wordfence-font-awesome-style');
		}
		return $this->use_core_font_awesome_styles;
	}

	private function get_2fa_management_assets($embedded = false) {
		$assets = array(
			Model_Script::create('wordfence-ls-jquery.qrcode', Model_Asset::js('jquery.qrcode.min.js'), array('jquery'), WORDFENCE_LS_VERSION),
			Model_Script::create('wordfence-ls-jquery.tmpl', Model_Asset::js('jquery.tmpl.min.js'), array('jquery'), WORDFENCE_LS_VERSION),
			Model_Script::create('wordfence-ls-jquery.colorbox', Model_Asset::js('jquery.colorbox.min.js'), array('jquery'), WORDFENCE_LS_VERSION)
		);
		if (Controller_Permissions::shared()->can_manage_settings()) { 
			$assets[] = Model_Style::create('wordfence-ls-jquery-ui-css', Model_Asset::css('jquery-ui.min.css'), array(), WORDFENCE_LS_VERSION);
			$assets[] = Model_Style::create('wordfence-ls-jquery-ui-css.structure', Model_Asset::css('jquery-ui.structure.min.css'), array(), WORDFENCE_LS_VERSION);
			$assets[] = Model_Style::create('wordfence-ls-jquery-ui-css.theme', Model_Asset::css('jquery-ui.theme.min.css'), array(), WORDFENCE_LS_VERSION);
		}
		$assets[] = Model_Script::create('wordfence-ls-admin', Model_Asset::js('admin.js'), array('jquery'), WORDFENCE_LS_VERSION)
			->withTranslation('You have unsaved changes to your options. If you leave this page, those changes will be lost.', __('You have unsaved changes to your options. If you leave this page, those changes will be lost.', 'wordfence-login-security'))
			->setTranslationObjectName('WFLS_ADMIN_TRANSLATIONS');
		$registered = array(
			Model_Script::create('chart-js', Model_Asset::js('chart.umd.js'), array('jquery'), '4.2.1')->setRegistered(),
			Model_Script::create('wordfence-select2-js', Model_Asset::js('wfselect2.min.js'), array('jquery'), WORDFENCE_LS_VERSION)->setRegistered(),
			Model_Style::create('wordfence-select2-css', Model_Asset::css('wfselect2.min.css'), array(), WORDFENCE_LS_VERSION)->setRegistered()
		);
		if (!WORDFENCE_LS_FROM_CORE && !$this->management_assets_registered) {
			foreach ($registered as $asset)
				$asset->register();
			$this->management_assets_registered = true;
		}
		$assets = array_merge($assets, $registered);
		$assets[] = Model_Style::create('wordfence-ls-admin', Model_Asset::css('admin.css'), array(), WORDFENCE_LS_VERSION);
		$assets[] = Model_Style::create('wordfence-ls-colorbox', Model_Asset::css('colorbox.css'), array(), WORDFENCE_LS_VERSION);
		$assets[] = Model_Style::create('wordfence-ls-ionicons', Model_Asset::css('ionicons.css'), array(), WORDFENCE_LS_VERSION);
		if ($embedded) {
			$assets[] = Model_Style::create('dashicons');
			$assets[] = Model_Style::create('wordfence-ls-embedded', Model_Asset::css('embedded.css'), array(), WORDFENCE_LS_VERSION);
		}
		if (!$this->should_use_core_font_awesome_styles()) {
			$assets[] = Model_Style::create('wordfence-ls-font-awesome', Model_Asset::css('font-awesome.css'), array(), WORDFENCE_LS_VERSION);
		}
		return $assets;
	}

	private function enqueue_2fa_management_assets($embedded = false) {
		if ($this->management_assets_enqueued)
			return;
		foreach ($this->get_2fa_management_assets($embedded) as $asset)
			$asset->enqueue();
		foreach ($this->get_2fa_management_script_data() as $key => $data)
			wp_localize_script('wordfence-ls-admin', $key, $data);
		$this->management_assets_enqueued = true;
	}

	/**
	 * Admin Pages
	 */
	public function _admin_enqueue_scripts($hookSuffix) {
		if (isset($_GET['page']) && $_GET['page'] == 'WFLS') {
			$this->enqueue_2fa_management_assets();
		}
		else {
			wp_enqueue_style('wordfence-ls-admin-global', Model_Asset::css('admin-global.css'), array(), WORDFENCE_LS_VERSION);
		}
		
		if (Controller_Notices::shared()->has_notice(wp_get_current_user()) || in_array($hookSuffix, array('user-edit.php', 'user-new.php', 'profile.php'))) {
			wp_enqueue_script('wordfence-ls-admin-global', Model_Asset::js('admin-global.js'), array('jquery'), WORDFENCE_LS_VERSION);
			
			wp_localize_script('wordfence-ls-admin-global', 'GWFLSVars', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('wp-ajax'),
			));
		}

	}
	
	public function _edit_user_profile($user) {
		if ($user->ID == get_current_user_id() || !current_user_can(Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS)) {
			$manageURL = admin_url('admin.php?page=WFLS');
		}
		else {
			$manageURL = admin_url('admin.php?page=WFLS&user=' . ((int) $user->ID));
		}
		
		if (is_multisite() && is_super_admin()) {
			if ($user->ID == get_current_user_id()) {
				$manageURL = network_admin_url('admin.php?page=WFLS');
			}
			else {
				$manageURL = network_admin_url('admin.php?page=WFLS&user=' . ((int) $user->ID));
			}
		}
		$userAllowed2fa = Controller_Users::shared()->can_activate_2fa($user);
		$viewerIsUser = $user->ID == get_current_user_id();
		$viewerCanManage2fa = current_user_can(Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS);
		$requires2fa = Controller_Users::shared()->requires_2fa($user, $inGracePeriod, $requiredAt);
		$has2fa = Controller_Users::shared()->has_2fa_active($user);
		$lockedOut = $requires2fa && !$has2fa;
		$hasGracePeriod = Controller_Settings::shared()->get_user_2fa_grace_period() > 0;
		if ($userAllowed2fa && ($viewerIsUser || $viewerCanManage2fa)):
?>
		<h2 id="wfls-user-settings"><?php esc_html_e('Wordfence Login Security', 'wordfence-login-security'); ?></h2>
		<table class="form-table">
			<tr id="wordfence-ls">
				<th><label for="wordfence-ls-btn"><?php esc_html_e('2FA Status', 'wordfence-login-security'); ?></label></th>
				<td>
					<?php if ($userAllowed2fa): ?>
						<p>
							<strong><?php echo $lockedOut ? esc_html__('Locked Out', 'wordfence-login-security') : ($has2fa ? esc_html__('Active', 'wordfence-login-security') :  esc_html__('Inactive', 'wordfence-login-security')); ?>:</strong>
							<?php echo $lockedOut ?
								($viewerIsUser ? esc_html__('Two-factor authentication is required for your account, but has not been configured.', 'wordfence-login-security') : esc_html__('Two-factor authentication is required for this account, but has not been configured.', 'wordfence-login-security'))
								: ($has2fa ? esc_html__('Wordfence 2FA is active.', 'wordfence-login-security') :  esc_html__('Wordfence 2FA is inactive.', 'wordfence-login-security')); ?>
							<a href="<?php echo Controller_Support::esc_supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More', 'wordfence-login-security'); ?></a>
						</p>
						<?php if (!$has2fa && $inGracePeriod): ?>
							<p><strong><?php echo sprintf($viewerIsUser ?
								esc_html__('Two-factor authentication must be activated for your account prior to %s to avoid losing access.', 'wordfence-login-security')
								: esc_html__('Two-factor authentication must be activated for this account prior to %s.', 'wordfence-login-security')
								, Controller_Time::format_local_time('F j, Y g:i A', $requiredAt)) ?></strong></p>
						<?php endif ?>
						<?php if ($has2fa || $viewerIsUser): ?><p><a href="<?php echo esc_url($manageURL); ?>" class="button"><?php echo (Controller_Users::shared()->has_2fa_active($user) ? esc_html__('Manage 2FA', 'wordfence-login-security') :  esc_html__('Activate 2FA', 'wordfence-login-security')); ?></a></p><?php endif ?>
					<?php endif ?>
					<?php if ($viewerCanManage2fa): ?>
						<?php if (!$userAllowed2fa): ?>
							<p><strong><?php esc_html_e('Disabled', 'wordfence-login-security'); ?>:</strong> <?php esc_html_e('Two-factor authentication is not currently enabled for this account type. To enable it, visit the Wordfence 2FA Settings page.', 'wordfence-login-security'); ?> <a href="#"><?php esc_html_e('Learn More', 'wordfence-login-security'); ?></a></p>
						<?php endif ?>
						<?php if ($lockedOut): ?>
							<?php echo Model_View::create(
								'common/reset-grace-period',
								array(
									'user' => $user,
									'gracePeriod' => $inGracePeriod
								))->render() ?>
						<?php elseif ($inGracePeriod && Controller_Users::shared()->has_revokable_grace_period($user)): ?>
							<?php echo Model_View::create(
								'common/revoke-grace-period',
								array(
									'user' => $user
								))->render() ?>
						<?php endif ?>
						<p>
							<a href="<?php echo esc_url(is_multisite() ? network_admin_url('admin.php?page=WFLS#top#settings') : admin_url('admin.php?page=WFLS#top#settings')); ?>" class="button"><?php esc_html_e('Manage 2FA Settings', 'wordfence-login-security'); ?></a>
						</p>
					<?php endif ?>
				</td>
			</tr>
		</table>
<?php
		endif;
	}
	
	/**
	 * Authentication
	 */

	private function _is_woocommerce_login() {
		if (!$this->has_woocommerce())
			return false;
		$nonceValue = '';
		foreach (array('woocommerce-login-nonce', '_wpnonce') as $key) {
			if (array_key_exists($key, $_REQUEST)) {
				$nonceValue = $_REQUEST[$key];
				break;
			}
		}

		return ( isset( $_POST['login'], $_POST['username'], $_POST['password'] ) && is_string($nonceValue) && wp_verify_nonce( $nonceValue, 'woocommerce-login' ) );
	}
	
	public function _authenticate($user, $username, $password) {
		if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST && !Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_XMLRPC_ENABLED)) { //XML-RPC call and we're not enforcing 2FA on it
			return $user;
		}
		
		if (Controller_Whitelist::shared()->is_whitelisted(Model_Request::current()->ip())) { //Whitelisted, so we're not enforcing 2FA
			return $user;
		}

		$isLogin = !(defined('WORDFENCE_LS_AUTHENTICATION_CHECK') && WORDFENCE_LS_AUTHENTICATION_CHECK); //Checking for the purpose of prompting for 2FA, don't enforce it here
		$isCombinedCheck = (defined('WORDFENCE_LS_CHECKING_COMBINED') && WORDFENCE_LS_CHECKING_COMBINED);
		$combinedTwoFactor = false;

		/*
		 * If we don't have a valid $user at this point, it means the $username/$password combo is invalid. We'll check
		 * to see if the user has provided a combined password in the format `<password><code>`, populating $user from
		 * that if so.
		 */
		if (!defined('WORDFENCE_LS_CHECKING_COMBINED') && (!isset($_POST['wfls-token']) || !is_string($_POST['wfls-token'])) && (!is_object($user) || !($user instanceof \WP_User))) {
			//Compatibility with WF legacy 2FA
			$combinedTOTPRegex = '/((?:[0-9]{3}\s*){2})$/i';
			$combinedRecoveryRegex = '/((?:[a-f0-9]{4}\s*){4})$/i';
			if ($this->legacy_2fa_active()) {
				$combinedTOTPRegex = '/(?<! wf)((?:[0-9]{3}\s*){2})$/i';
				$combinedRecoveryRegex = '/(?<! wf)((?:[a-f0-9]{4}\s*){4})$/i';
			}

			if (preg_match($combinedTOTPRegex, $password, $matches)) { //Possible TOTP code
				if (strlen($password) > strlen($matches[1])) {
					$revisedPassword = substr($password, 0, strlen($password) - strlen($matches[1]));
					$code = $matches[1];
				}
			}
			else if (preg_match($combinedRecoveryRegex, $password, $matches)) { //Possible recovery code
				if (strlen($password) > strlen($matches[1])) {
					$revisedPassword = substr($password, 0, strlen($password) - strlen($matches[1]));
					$code = $matches[1];
				}
			}

			if (isset($revisedPassword)) {
				define('WORDFENCE_LS_CHECKING_COMBINED', true); //Avoid recursing into this block
				if (!defined('WORDFENCE_LS_AUTHENTICATION_CHECK')) { define('WORDFENCE_LS_AUTHENTICATION_CHECK', true); }
				$revisedUser = wp_authenticate($username, $revisedPassword);
				if (is_object($revisedUser) && ($revisedUser instanceof \WP_User) && Controller_TOTP::shared()->validate_2fa($revisedUser, $code, $isLogin)) {
					define('WORDFENCE_LS_COMBINED_IS_VALID', true); //This will cause the front-end to skip the 2FA prompt
					$user = $revisedUser;
					$combinedTwoFactor = true;
				}
			}
		}
		
		/*
		 * CAPTCHA Check
		 * 
		 * It will be enforced so long as:
		 * 
		 * 1. It's enabled and keys are set.
		 * 2. This is not an XML-RPC request. An XML-RPC request is de facto an automated request, so a CAPTCHA makes
		 *    no sense.
		 * 3. A filter does not override it. This is to allow plugins with REST endpoints that handle authentication
		 *    themselves to opt out of the requirement.
		 * 4. The user is not providing a combined credentials + 2FA authentication login request.
		 * 5. The request is not a WooCommerce login while WC integration is disabled
		 */
		if (!$combinedTwoFactor && !$isCombinedCheck && !empty($username) && (!$this->_is_woocommerce_login() || Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION))) { //Login attempt, not just a wp-login.php page load

			$requireCAPTCHA = Controller_CAPTCHA::shared()->is_captcha_required();
			$performVerification = false;
			
			$token = Controller_CAPTCHA::shared()->get_token();
			if ($requireCAPTCHA && empty($token) && !Controller_CAPTCHA::shared()->test_mode()) { //No CAPTCHA token means forced additional verification (if neither 2FA nor test mode are active)
				$performVerification = true;
			}
			
			if (is_object($user) && $user instanceof \WP_User && $this->validate_email_verification_token($user)) { //Skip the CAPTCHA check if the email address was verified
				$requireCAPTCHA = false;
				$performVerification = false;
				
				//Reset token rate limit
				$identifier = sprintf('wfls-captcha-%d', $user->ID);
				$tokenBucket = new Model_TokenBucket('rate:' . $identifier, 3, 1 / (WORDFENCE_LS_EMAIL_VALIDITY_DURATION_MINUTES * Model_TokenBucket::MINUTE)); //Maximum of three requests, refilling at a rate of one per token expiration period
				$tokenBucket->reset();
			}
			
			$score = false;
			if ($requireCAPTCHA && !$performVerification) {
				$expired = false;
				if (is_object($user) && $user instanceof \WP_User) {
					$score = Controller_Users::shared()->cached_captcha_score($token, $user, $expired);
				}
				
				if ($score === false) {
					if ($expired) {
						return new \WP_Error('wfls_captcha_expired', wp_kses(__('<strong>CAPTCHA EXPIRED</strong>: The CAPTCHA verification for this login attempt has expired. Please try again.', 'wordfence-login-security'), array('strong'=>array())));
					}
					
					$score = Controller_CAPTCHA::shared()->score($token);
					
					if ($score !== false && is_object($user) && $user instanceof \WP_User) {
						Controller_Users::shared()->cache_captcha_score($token, $score, $user);
						Controller_Users::shared()->record_captcha_score($user, $score);
					}
				}
				
				if ($score === false && !Controller_CAPTCHA::shared()->test_mode()) { //An invalid token will require additional verification (if test mode is not active)
					$performVerification = true;
				}
			}
			
			if ($requireCAPTCHA) {
				if ($performVerification || !Controller_CAPTCHA::shared()->is_human($score)) {
					if (is_object($user) && $user instanceof \WP_User) {
						$identifier = sprintf('wfls-captcha-%d', $user->ID);
						$tokenBucket = new Model_TokenBucket('rate:' . $identifier, 3, 1 / (WORDFENCE_LS_EMAIL_VALIDITY_DURATION_MINUTES * Model_TokenBucket::MINUTE)); //Maximum of three requests, refilling at a rate of one per token expiration period
						if ($tokenBucket->consume(1)) {
							if ($this->has_woocommerce() && array_key_exists('woocommerce-login-nonce', $_POST)) {
								$loginUrl = get_permalink(get_option('woocommerce_myaccount_page_id'));
							}
							else {
								$loginUrl = wp_login_url();
							}
							$verificationUrl = add_query_arg(
								array(
									'wfls-email-verification' => rawurlencode(Controller_Users::shared()->generate_verification_token($user))
								),
								$loginUrl
							);
							$view = new Model_View('email/login-verification', array(
								'siteName' => get_bloginfo('name', 'raw'),
								'verificationURL' => $verificationUrl,
								'ip' => Model_Request::current()->ip(),
								'canEnable2FA' => Controller_Users::shared()->can_activate_2fa($user),
							));
							wp_mail($user->user_email, __('Login Verification Required', 'wordfence-login-security'), $view->render(), "Content-Type: text/html");
						}
					}

					Utility_Sleep::sleep(Model_Crypto::random_int(0, 2000) / 1000);
					return new \WP_Error('wfls_captcha_verify', wp_kses(__('<strong>VERIFICATION REQUIRED</strong>: Additional verification is required for login. If there is a valid account for the provided login credentials, please check the email address associated with it for a verification link to continue logging in.', 'wordfence-login-security'), array('strong' => array())));
				}
			}
		}

		if (!$combinedTwoFactor) {
			if ($isLogin && $user instanceof \WP_User) {
				if (Controller_Users::shared()->has_2fa_active($user)) {
					if (Controller_Users::shared()->has_remembered_2fa($user)) {
						return $user;
					}
					elseif (array_key_exists('wfls-token', $_POST)) {
						if (is_string($_POST['wfls-token']) && Controller_TOTP::shared()->validate_2fa($user, $_POST['wfls-token'])) {
							return $user;
						}
						else {
							return new \WP_Error('wfls_twofactor_failed', wp_kses(__('<strong>CODE INVALID</strong>: The 2FA code provided is either expired or invalid. Please try again.', 'wordfence-login-security'), array('strong'=>array())));
						}
					}
				}
				$in2faGracePeriod = false;
				$time2faRequired = null;
				if (Controller_Users::shared()->has_2fa_active($user)) {
					$legacy2FAActive = Controller_WordfenceLS::shared()->legacy_2fa_active();
					if ($legacy2FAActive) {
						return new \WP_Error('wfls_twofactor_required', wp_kses(__('<strong>CODE REQUIRED</strong>: Please enter your 2FA code immediately after your password in the same field.', 'wordfence-login-security'), array('strong'=>array())));
					}
					return new \WP_Error('wfls_twofactor_required', wp_kses(__('<strong>CODE REQUIRED</strong>: Please provide your 2FA code when prompted.', 'wordfence-login-security'), array('strong'=>array())));
				}
				else if (Controller_Users::shared()->requires_2fa($user, $in2faGracePeriod, $time2faRequired)) {
					return new \WP_Error('wfls_twofactor_blocked', wp_kses(__('<strong>LOGIN BLOCKED</strong>: 2FA is required to be active on your account. Please contact the site administrator.', 'wordfence-login-security'), array('strong'=>array())));
				}
				else if ($in2faGracePeriod) {
					Controller_Notices::shared()->add_notice(Model_Notice::SEVERITY_CRITICAL, new Model_HTML(wp_kses(sprintf(__('You do not currently have two-factor authentication active on your account, which will be required beginning %s. <a href="%s">Configure 2FA</a>', 'wordfence-login-security'), Controller_Time::format_local_time('F j, Y g:i A', $time2faRequired), esc_url((is_multisite() && is_super_admin($user->ID)) ? network_admin_url('admin.php?page=WFLS') : admin_url('admin.php?page=WFLS'))), array('a'=>array('href'=>array())))), 'wfls-will-be-required', $user);
				}
			}

		}

		return $user;
	}
	
	public function _set_logged_in_cookie($logged_in_cookie, $expire, $expiration, $user_id) {
		$user = new \WP_User($user_id);
		if (Controller_Users::shared()->has_2fa_active($user) && isset($_POST['wfls-remember-device']) && $_POST['wfls-remember-device']) {
			Controller_Users::shared()->remember_2fa($user);
		}
		delete_user_meta($user_id, 'wfls-captcha-nonce');
	}
	
	public function _record_login($user_login/*, $user -- we'd like to use the second parameter instead, but too many plugins call this hook and only provide one of the two required parameters*/) {
		$user = get_user_by('login', $user_login);
		if (is_object($user) && $user instanceof \WP_User && $user->exists()) {
			update_user_meta($user->ID, 'wfls-last-login', Controller_Time::time());
		}
	}
	
	public function _register_post($sanitized_user_login, $user_email, $errors) {
		if (!empty($sanitized_user_login)) {
			$captchaResult = $this->process_registration_captcha_with_hooks();
			if ($captchaResult !== true) {
				$errors->add($captchaResult['category'], $captchaResult['message']);
			}
		}
	}

	private function validate_email_verification_token($user = null, &$token = null) {
		$token = isset($_REQUEST['wfls-email-verification']) ? $_REQUEST['wfls-email-verification'] : null;
		if (empty($token))
			return null;
		return is_string($token) && Controller_Users::shared()->validate_verification_token($token, $user);
	}

	/**
	 * @param \WP_Error $errors
	 * @param string $redirect_to
	 * @return \WP_Error
	 */
	public function _wp_login_errors($errors, $redirect_to) {
		$has_errors = (method_exists($errors, 'has_errors') ? $errors->has_errors() : !empty($errors->errors)); //has_errors was added in WP 5.1
		$emailVerificationTokenValid = $this->validate_email_verification_token();
		if (!$has_errors && $emailVerificationTokenValid !== null) {
			if ($emailVerificationTokenValid) {
				$errors->add('wfls_email_verified', esc_html__('Email verification succeeded. Please continue logging in.', 'wordfence-login-security'), 'message');
			}
			else {
				$errors->add('wfls_email_not_verified', esc_html__('Email verification invalid or expired. Please try again.', 'wordfence-login-security'), 'message');
			}
		}
		return $errors;
	}
	
	public function legacy_2fa_active() {
		$wfLegacy2FAActive = false;
		if (class_exists('wfConfig') && \wfConfig::get('isPaid')) {
			$twoFactorUsers = \wfConfig::get_ser('twoFactorUsers', array());
			if (is_array($twoFactorUsers) && count($twoFactorUsers) > 0) {
				foreach ($twoFactorUsers as $t) {
					if ($t[3] == 'activated') {
						$testUser = get_user_by('ID', $t[0]);
						if (is_object($testUser) && $testUser instanceof \WP_User && \wfUtils::isAdmin($testUser)) {
							$wfLegacy2FAActive = true;
							break;
						}
					}
				}
			}
			
			if ($wfLegacy2FAActive && class_exists('wfCredentialsController') && method_exists('wfCredentialsController', 'useLegacy2FA') && !\wfCredentialsController::useLegacy2FA()) {
				$wfLegacy2FAActive = false;
			}
		}
		return $wfLegacy2FAActive;
	}
	
	/**
	 * Menu
	 */
	
	public function _admin_menu() {
		$user = wp_get_current_user();
		if (Controller_Notices::shared()->has_notice($user)) {
			Controller_Users::shared()->requires_2fa($user, $gracePeriod);
			if (!$gracePeriod) {
				Controller_Notices::shared()->remove_notice(false, 'wfls-will-be-required', $user);
			}
		}
		
		Controller_Notices::shared()->enqueue_notices();
		
		$useSubmenu = WORDFENCE_LS_FROM_CORE && current_user_can('activate_plugins');
		if (is_multisite() && !is_network_admin()) {
			$useSubmenu = false;
			
			if (is_super_admin()) {
				return;
			}
		}
		
		if ($useSubmenu) {
			add_submenu_page('Wordfence', __('Login Security', 'wordfence-login-security'), __('Login Security', 'wordfence-login-security'), Controller_Permissions::CAP_ACTIVATE_2FA_SELF, 'WFLS', array($this, '_menu'));
		}
		else {
			add_menu_page(__('Login Security', 'wordfence-login-security'), __('Login Security', 'wordfence-login-security'), Controller_Permissions::CAP_ACTIVATE_2FA_SELF, 'WFLS', array($this, '_menu'), Model_Asset::img('menu.svg'));
		}
	}
	
	public function _menu() {
		$user = wp_get_current_user();
		$administrator = false;
		$canEditUsers = false;
		if (Controller_Permissions::shared()->can_manage_settings($user)) {
			$administrator = true;
		}
		
		if (user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_OTHERS)) {
			$canEditUsers = true;
			if (isset($_GET['user'])) {
				$user = new \WP_User((int) $_GET['user']);
				if (!$user->exists()) {
					$user = wp_get_current_user();
				}
			}
		}

		$sections = array();

		if (isset($_GET['role']) && $canEditUsers) {
			$roleKey = $_GET['role'];
			$roles = new \WP_Roles();
			$role = $roles->get_role($roleKey);
			$roleTitle = $roleKey === 'super-admin' ? __('Super Administrator', 'wordfence-login-security') : $roles->role_names[$roleKey];
			$requiredAt = Controller_Settings::shared()->get_required_2fa_role_activation_time($roleKey);
			$states = array(
				'grace_period' => array(
					'title' => __('Grace Period', 'wordfence-login-security'),
					'gracePeriod' => true
				),
				'locked_out' => array(
					'title' => __('Locked Out', 'wordfence-login-security'),
					'gracePeriod' => false
				)
			);
			foreach ($states as $key => $state) {
				$pageKey = "page_$key";
				$page = isset($_GET[$pageKey]) ? max((int) $_GET[$pageKey], 1) : 1;
				$title = $state['title'];
				$lastPage = true;
				if ($requiredAt === false)
					$users = array();
				else
					$users = Controller_Users::shared()->get_inactive_2fa_users($roleKey, $state['gracePeriod'], $page, self::USERS_PER_PAGE, $lastPage);
				$sections[] = array(
					'tab' => new Model_Tab($key, $key, $title, $title),
					'title' => new Model_Title($key, sprintf(__('Users without 2FA active (%s)', 'wordfence-login-security'), $title) . ' - ' . $roleTitle),
					'content' => new Model_View('page/role', array(
						'role' => $role,
						'roleTitle' => $roleTitle,
						'stateTitle' => $title,
						'requiredAt' => $requiredAt,
						'state' => $state,
						'users' => $users,
						'page' => $page,
						'lastPage' => $lastPage,
						'pageKey' => $pageKey,
						'stateKey' => $key
					)),
				);
			}
		}
		else {	
			$sections[] = array(
				'tab' => new Model_Tab('manage', 'manage', __('Two-Factor Authentication', 'wordfence-login-security'), __('Two-Factor Authentication', 'wordfence-login-security')),
				'title' => new Model_Title('manage', __('Two-Factor Authentication', 'wordfence-login-security'), Controller_Support::supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA), new Model_HTML(wp_kses(__('Learn more<span class="wfls-hidden-xs"> about Two-Factor Authentication</span>', 'wordfence-login-security'), array('span'=>array('class'=>array()))))),
				'content' => new Model_View('page/manage', array(
					'user' => $user,
					'canEditUsers' => $canEditUsers,
				)),
			);
			
			if ($administrator) {
				$sections[] = array(
					'tab' => new Model_Tab('settings', 'settings', __('Settings', 'wordfence-login-security'), __('Settings', 'wordfence-login-security')),
					'title' => new Model_Title('settings', __('Login Security Settings', 'wordfence-login-security'), Controller_Support::supportURL(Controller_Support::ITEM_MODULE_LOGIN_SECURITY), new Model_HTML(wp_kses(__('Learn more<span class="wfls-hidden-xs"> about Login Security</span>', 'wordfence-login-security'), array('span'=>array('class'=>array()))))),
					'content' => new Model_View('page/settings', array(
						'hasWoocommerce' => $this->has_woocommerce()
					)),
				);
			}
		}
		
		$view = new Model_View('page/page', array(
			'sections' => $sections,
		));
		echo $view->render();
	}

	private function process_registration_captcha() {
		if (Controller_Whitelist::shared()->is_whitelisted(Model_Request::current()->ip())) { //Whitelisted, so we're not enforcing 2FA
			return true;
		}

		$captchaController = Controller_CAPTCHA::shared();
		$requireCaptcha = $captchaController->is_captcha_required();
		$token = $captchaController->get_token();

		if ($requireCaptcha) {
			if ($token === null && !$captchaController->test_mode()) {
				return array(
					'message' => wp_kses(__('<strong>REGISTRATION ATTEMPT BLOCKED</strong>: This site requires a security token created when the page loads for all registration attempts. Please ensure JavaScript is enabled and try again.', 'wordfence-login-security'), array('strong'=>array())),
					'category' => 'wfls_captcha_required'
				);
			}
			$score = $captchaController->score($token);
			if ($score === false && !$captchaController->test_mode()) {
				return array(
					'message' => wp_kses(__('<strong>REGISTRATION ATTEMPT BLOCKED</strong>: The security token for the login attempt was invalid or expired. Please reload the page and try again.', 'wordfence-login-security'), array('strong'=>array())),
					'category' => 'wfls_captcha_required'
				);
			}
			Controller_Users::shared()->record_captcha_score(null, $score);
			if (!$captchaController->is_human($score)) {
				$encryptedIP = Model_Symmetric::encrypt(Model_Request::current()->ip());
				$encryptedScore = Model_Symmetric::encrypt($score);
				$result = array(
					'category' => 'wfls_registration_blocked'
				);
				if ($encryptedIP && $encryptedScore && filter_var(get_site_option('admin_email'), FILTER_VALIDATE_EMAIL)) {
					$jwt = new Model_JWT(array('ip' => $encryptedIP, 'score' => $encryptedScore), Controller_Time::time() + 600);
					$result['message'] = wp_kses(sprintf(__('<strong>REGISTRATION BLOCKED</strong>: The registration request was blocked because it was flagged as spam. Please try again or <a href="#" class="wfls-registration-captcha-contact" data-token="%s">contact the site owner</a> for help.', 'wordfence-login-security'), esc_attr((string)$jwt)), array('strong'=>array(), 'a'=>array('href'=>array(), 'class'=>array(), 'data-token'=>array())));
				}
				else {
					$result['message'] = wp_kses(__('<strong>REGISTRATION BLOCKED</strong>: The registration request was blocked because it was flagged as spam. Please try again or contact the site owner for help.', 'wordfence-login-security'), array('strong'=>array()));
				}
				return $result;
			}
		}
		return true;
	}

	/**
	 * @param int $endpointType the type of endpoint being processed
	 *	The default value of 1 corresponds to a regular login
	 *	@see wordfence::wfsnEndpointType()
	 */
	private function process_registration_captcha_with_hooks($endpointType = 1) {
		$result = $this->process_registration_captcha();
		if ($result !== true) {
			if ($result['category'] === 'wfls_registration_blocked') {
				/**
				 * Fires just prior to blocking user registration due to a failed CAPTCHA. After firing this action hook
				 * the registration attempt is blocked.
				 *
				 * @param int $source The source code of the block.
				 */
				do_action('wfls_registration_blocked', $endpointType);

				/**
				 * Filters the message to show if registration is blocked due to a captcha rejection.
				 *
				 * @since 1.0.0
				 *
				 * @param string $message The message to display, HTML allowed.
				 */
				$result['message'] = apply_filters('wfls_registration_blocked_message', $result['message']);
			}
		}
		return $result;
	}

	private function disable_woocommerce_registration($message) {
		if ($this->has_woocommerce()) {
			remove_action('wp_loaded', array('WC_Form_Handler', 'process_registration'), 20);
			wc_add_notice($message, 'error');
		}
	}

	public function _handle_woocommerce_registration() {
		if ($this->has_woocommerce() && isset($_POST['register'], $_POST['email']) && (isset($_POST['_wpnonce']) || isset($_POST['woocommerce-register-nonce']))) {
			$captchaResult = $this->process_registration_captcha_with_hooks();
			if ($captchaResult !== true) {
				$this->disable_woocommerce_registration($captchaResult['message']);
			}
		}
	}

	public function _user_new_form() {
		if (Controller_Settings::shared()->get_user_2fa_grace_period())
			echo Model_View::create('user/grace-period-toggle', array())->render();
	}

	public function _user_register($newUserId) {
		$creator = wp_get_current_user();
		if (!Controller_Permissions::shared()->can_manage_settings($creator) || $creator->ID == $newUserId)
			return;
		if (isset($_POST['wfls-grace-period-toggle']))
			Controller_Users::shared()->allow_grace_period($newUserId); 
	}

	public function _woocommerce_account_menu_items($items) {
		if ($this->can_user_activate_2fa_self()) {
			$endpointId = self::WOOCOMMERCE_ENDPOINT;
			$label = __('Wordfence 2FA', 'wordfence-login-security');
			if (!Utility_Array::insertAfter($items, 'edit-account', $endpointId, $label)) {
				$items[$endpointId] = $label;
			}
		}
		return $items;
	}

	public function _woocommerce_get_query_vars($query_vars) {
		$query_vars[self::WOOCOMMERCE_ENDPOINT] = self::WOOCOMMERCE_ENDPOINT;
		return $query_vars;
	}

	private function can_user_activate_2fa_self($user = null) {
		if ($user === null)
			$user = wp_get_current_user();
		return user_can($user, Controller_Permissions::CAP_ACTIVATE_2FA_SELF);
	}

	private function render_embedded_user_2fa_management_interface($stacked = null) {
		$user = wp_get_current_user();
		$stacked = $stacked === null ? Controller_Settings::shared()->should_stack_ui_columns() : $stacked;
		if ($this->can_user_activate_2fa_self($user)) {
			$assets = $this->management_assets_enqueued ? array() : $this->get_2fa_management_assets(true);
			$scriptData = $this->management_assets_enqueued ? array() : $this->get_2fa_management_script_data();
			return Model_View::create(
				'page/manage-embedded',
				array(
					'user' => $user,
					'stacked' => $stacked,
					'assets' => $assets,
					'scriptData' => $scriptData
				)
			)->render();
		}
		else {
			return Model_View::create('page/permission-denied')->render();
		}
	}

	public function _woocommerce_account_menu_content() {
		echo $this->render_embedded_user_2fa_management_interface();
	}

	private function does_current_page_include_shortcode($shortcode) {
		global $post;
		return $post instanceof \WP_Post && has_shortcode($post->post_content, $shortcode);
	}

	public function _woocommerce_account_enqueue_assets() {
		if (!$this->has_woocommerce())
			return;
		if ($this->does_current_page_include_shortcode('woocommerce_my_account')) {
			wp_enqueue_style('wordfence-ls-woocommerce-account-styles', Model_Asset::css('woocommerce-account.css'), array(), WORDFENCE_LS_VERSION);
			$this->enqueue_2fa_management_assets(true);
		}
	}

	public function _handle_user_2fa_management_shortcode($attributes, $content = null, $shortcode = null) {
		$shortcode = $shortcode === null ? self::SHORTCODE_2FA_MANAGEMENT : $shortcode;
		$attributes = shortcode_atts(
			array(
				'stacked' => Controller_Settings::shared()->should_stack_ui_columns() ? 'true' : 'false'
			),
			$attributes,
			$shortcode
		);
		$stacked = filter_var($attributes['stacked'], FILTER_VALIDATE_BOOLEAN);
		return $this->render_embedded_user_2fa_management_interface($stacked);
	}

	public function _handle_shortcode_prerequisites() {
		if ($this->does_current_page_include_shortcode(self::SHORTCODE_2FA_MANAGEMENT)) {
			if (!is_user_logged_in())
				auth_redirect();
			$this->enqueue_2fa_management_assets(true);
		}
	}

}