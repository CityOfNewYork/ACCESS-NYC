<?php

namespace WordfenceLS;

use WordfenceLS\Settings\Model_DB;
use WordfenceLS\Settings\Model_WPOptions;

class Controller_Settings {
	//Configurable
	const OPTION_XMLRPC_ENABLED = 'xmlrpc-enabled';
	const OPTION_2FA_WHITELISTED = 'whitelisted';
	const OPTION_IP_SOURCE = 'ip-source';
	const OPTION_IP_TRUSTED_PROXIES = 'ip-trusted-proxies';
	const OPTION_REQUIRE_2FA_ADMIN = 'require-2fa.administrator';
	const OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED = 'require-2fa-grace-period-enabled';
	const OPTION_REQUIRE_2FA_GRACE_PERIOD = 'require-2fa-grace-period';
	const OPTION_REQUIRE_2FA_USER_GRACE_PERIOD = '2fa-user-grace-period';
	const OPTION_REMEMBER_DEVICE_ENABLED = 'remember-device';
	const OPTION_REMEMBER_DEVICE_DURATION = 'remember-device-duration';
	const OPTION_ALLOW_XML_RPC = 'allow-xml-rpc';
	const OPTION_ENABLE_AUTH_CAPTCHA = 'enable-auth-captcha';
	const OPTION_CAPTCHA_TEST_MODE = 'recaptcha-test-mode';
	const OPTION_RECAPTCHA_SITE_KEY = 'recaptcha-site-key';
	const OPTION_RECAPTCHA_SECRET = 'recaptcha-secret';
	const OPTION_RECAPTCHA_THRESHOLD = 'recaptcha-threshold';
	const OPTION_DELETE_ON_DEACTIVATION = 'delete-deactivation';
	const OPTION_PREFIX_REQUIRED_2FA_ROLE = 'required-2fa-role';
	const OPTION_ENABLE_WOOCOMMERCE_INTEGRATION = 'enable-woocommerce-integration';
	const OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION = 'enable-woocommerce-account-integration';
	const OPTION_ENABLE_SHORTCODE = 'enable-shortcode';
	const OPTION_ENABLE_LOGIN_HISTORY_COLUMNS = 'enable-login-history-columns';
	const OPTION_STACK_UI_COLUMNS = 'stack-ui-columns';
	
	//Internal
	const OPTION_GLOBAL_NOTICES = 'global-notices';
	const OPTION_LAST_SECRET_REFRESH = 'last-secret-refresh';
	const OPTION_USE_NTP = 'use-ntp';
	const OPTION_ALLOW_DISABLING_NTP = 'allow-disabling-ntp';
	const OPTION_NTP_FAILURE_COUNT = 'ntp-failure-count';
	const OPTION_NTP_OFFSET = 'ntp-offset';
	const OPTION_SHARED_HASH_SECRET_KEY = 'shared-hash-secret';
	const OPTION_SHARED_SYMMETRIC_SECRET_KEY = 'shared-symmetric-secret';
	const OPTION_DISMISSED_FRESH_INSTALL_MODAL = 'dismissed-fresh-install-modal';
	const OPTION_CAPTCHA_STATS = 'captcha-stats';
	const OPTION_SCHEMA_VERSION = 'schema-version';
	const OPTION_USER_COUNT_QUERY_STATE = 'user-count-query-state';
	const OPTION_DISABLE_TEMPORARY_TABLES = 'disable-temporary-tables';

	const DEFAULT_REQUIRE_2FA_USER_GRACE_PERIOD = 10;
	const MAX_REQUIRE_2FA_USER_GRACE_PERIOD = 99;

	const STATE_2FA_DISABLED = 'disabled';
	const STATE_2FA_OPTIONAL = 'optional';
	const STATE_2FA_REQUIRED = 'required';
	
	protected $_settingsStorage;
	
	/**
	 * Returns the singleton Controller_Settings.
	 *
	 * @return Controller_Settings
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_Settings();
		}
		return $_shared;
	}
	
	public function __construct($settingsStorage = false) {
		if (!$settingsStorage) {
			$settingsStorage = new Model_DB();
		}
		$this->_settingsStorage = $settingsStorage;
		$this->_migrate_admin_2fa_requirements_to_roles();
	}
	
	public function set_defaults() {
		$this->_settingsStorage->set_multiple(array(
			self::OPTION_XMLRPC_ENABLED => array('value' => true, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_2FA_WHITELISTED => array('value' => '', 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false), 
			self::OPTION_IP_SOURCE => array('value' => Model_Request::IP_SOURCE_AUTOMATIC, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_IP_TRUSTED_PROXIES => array('value' => '', 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_REQUIRE_2FA_ADMIN => array('value' => false, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED => array('value' => false, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD => array('value' => self::DEFAULT_REQUIRE_2FA_USER_GRACE_PERIOD, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_GLOBAL_NOTICES => array('value' => '[]', 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_REMEMBER_DEVICE_ENABLED => array('value' => false, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_REMEMBER_DEVICE_DURATION => array('value' => (30 * 86400), 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_ALLOW_XML_RPC => array('value' => true, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_ENABLE_AUTH_CAPTCHA => array('value' => false, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_CAPTCHA_STATS => array('value' => '{"counts":[0,0,0,0,0,0,0,0,0,0,0],"avg":0}', 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_RECAPTCHA_THRESHOLD => array('value' => 0.5, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_LAST_SECRET_REFRESH => array('value' => 0, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_DELETE_ON_DEACTIVATION => array('value' => false, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION => array('value' => false, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION => array('value' => false, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_ENABLE_SHORTCODE => array('value' => false, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS => array('value' => true, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_STACK_UI_COLUMNS => array('value' => true, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_SCHEMA_VERSION => array('value' => 0, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_USER_COUNT_QUERY_STATE => array('value' => 0, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false),
			self::OPTION_DISABLE_TEMPORARY_TABLES => array('value' => 0, 'autoload' => Model_Settings::AUTOLOAD_YES, 'allowOverwrite' => false)
		));
	}
	
	public function set($key, $value, $already_validated = false) {
		return $this->set_multiple(array($key => $value), $already_validated);
	}
	
	public function set_array($key, $value, $already_validated = false) {
		return $this->set_multiple(array($key => json_encode($value)), $already_validated);
	}
	
	public function set_multiple($changes, $already_validated = false) {
		if (!$already_validated && $this->validate_multiple($changes) !== true) {
			return false;
		}
		$changes = $this->clean_multiple($changes);
		$changes = $this->preprocess_multiple($changes);
		$this->_settingsStorage->set_multiple($changes);
		return true;
	}
	
	public function get($key, $default = false) {
		return $this->_settingsStorage->get($key, $default);
	}
	
	public function get_bool($key, $default = false) {
		return $this->_truthy_to_bool($this->get($key, $default));
	}
	
	public function get_int($key, $default = 0) {
		return intval($this->get($key, $default));
	}
	
	public function get_float($key, $default = 0.0) {
		return (float) $this->get($key, $default);
	}
	
	public function get_array($key, $default = array()) {
		$value = $this->get($key, null);
		if (is_string($value)) {
			$value = @json_decode($value, true);
		}
		else {
			$value = null;
		}
		return is_array($value) ? $value : $default;
	}
	
	public function remove($key) {
		$this->_settingsStorage->remove($key);
	}
	
	/**
	 * Validates whether a user-entered setting value is acceptable. Returns true if valid or an error message if not.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return bool|string
	 */
	public function validate($key, $value) {
		switch ($key) {
			//Boolean
			case self::OPTION_XMLRPC_ENABLED:
			case self::OPTION_REQUIRE_2FA_ADMIN:
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED:
			case self::OPTION_REMEMBER_DEVICE_ENABLED:
			case self::OPTION_ALLOW_XML_RPC:
			case self::OPTION_ENABLE_AUTH_CAPTCHA:
			case self::OPTION_CAPTCHA_TEST_MODE:
			case self::OPTION_DISMISSED_FRESH_INSTALL_MODAL:
			case self::OPTION_DELETE_ON_DEACTIVATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION:
			case self::OPTION_ENABLE_SHORTCODE:
			case self::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS:
			case self::OPTION_STACK_UI_COLUMNS:
			case self::OPTION_USER_COUNT_QUERY_STATE:
			case self::OPTION_DISABLE_TEMPORARY_TABLES:
				return true;
				
			//Int
			case self::OPTION_LAST_SECRET_REFRESH:
				return is_numeric($value); //Left using is_numeric to prevent issues with existing values
			case self::OPTION_SCHEMA_VERSION:
				return Utility_Number::isInteger($value, 0);
				
			//Array
			case self::OPTION_GLOBAL_NOTICES:
			case self::OPTION_CAPTCHA_STATS:
				return preg_match('/^\[.*\]$/', $value) || preg_match('/^\{.*\}$/', $value); //Only a rough JSON validation
				
			//Special
			case self::OPTION_IP_TRUSTED_PROXIES:
			case self::OPTION_2FA_WHITELISTED:
				$value = !is_string($value) ? '' : $value;
				$parsed = array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $value)));
				foreach ($parsed as $entry) {
					if (!Controller_Whitelist::shared()->is_valid_range($entry)) {
						return sprintf(__('The IP/range %s is invalid.', 'wordfence-login-security'), esc_html($entry));
					}
				}
				return true;
			case self::OPTION_IP_SOURCE:
				if (!in_array($value, array(Model_Request::IP_SOURCE_AUTOMATIC, Model_Request::IP_SOURCE_REMOTE_ADDR, Model_Request::IP_SOURCE_X_FORWARDED_FOR, Model_Request::IP_SOURCE_X_REAL_IP))) {
					return __('An invalid IP source was provided.', 'wordfence-login-security');
				}
				return true;
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD:
				$gracePeriodEnd = strtotime($value);
				if ($gracePeriodEnd <= \WordfenceLS\Controller_Time::time()) {
					return __('The grace period end time must be in the future.', 'wordfence-login-security');
				}
				return true;
			case self::OPTION_REMEMBER_DEVICE_DURATION:
				return is_numeric($value) && $value > 0;
			case self::OPTION_RECAPTCHA_THRESHOLD:
				return is_numeric($value) && $value > 0 && $value <= 1;
			case self::OPTION_RECAPTCHA_SITE_KEY:
				if (empty($value)) {
					return true;
				}
				
				$response = wp_remote_get('https://www.google.com/recaptcha/api.js?render=' . urlencode($value));
				
				if (!is_wp_error($response)) {
					$status = wp_remote_retrieve_response_code($response);
					if ($status == 200) {
						return true;
					}
					
					$data = wp_remote_retrieve_body($response);
					if (strpos($data, 'grecaptcha') === false) {
						return __('Unable to validate the reCAPTCHA site key. Please check the key and try again.', 'wordfence-login-security');
					}
					return true;
				}
				return sprintf(__('An error was encountered while validating the reCAPTCHA site key: %s', 'wordfence-login-security'), $response->get_error_message());
			case self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD:
				return is_numeric($value) && $value >= 0 && $value <= self::MAX_REQUIRE_2FA_USER_GRACE_PERIOD;
		}
		return true;
	}
	
	public function validate_multiple($values) {
		$errors = array();
		foreach ($values as $key => $value) {
			$status = $this->validate($key, $value); 
			if ($status !== true) {
				$errors[$key] = $status;
			}
		}
		
		if (!empty($errors)) {
			return $errors;
		}
		
		return true;
	}
	
	/**
	 * Cleans and normalizes a setting value for use in saving.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	public function clean($key, $value) {
		switch ($key) {
			//Boolean
			case self::OPTION_XMLRPC_ENABLED:
			case self::OPTION_REQUIRE_2FA_ADMIN:
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED:
			case self::OPTION_REMEMBER_DEVICE_ENABLED:
			case self::OPTION_ALLOW_XML_RPC:
			case self::OPTION_ENABLE_AUTH_CAPTCHA:
			case self::OPTION_CAPTCHA_TEST_MODE:
			case self::OPTION_DISMISSED_FRESH_INSTALL_MODAL:
			case self::OPTION_DELETE_ON_DEACTIVATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION:
			case self::OPTION_ENABLE_WOOCOMMERCE_ACCOUNT_INTEGRATION:
			case self::OPTION_ENABLE_SHORTCODE;
			case self::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS:
			case self::OPTION_STACK_UI_COLUMNS:
			case self::OPTION_USER_COUNT_QUERY_STATE:
			case self::OPTION_DISABLE_TEMPORARY_TABLES:
				return $this->_truthy_to_bool($value);
				
			//Int
			case self::OPTION_REMEMBER_DEVICE_DURATION:
			case self::OPTION_LAST_SECRET_REFRESH:
			case self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD:
			case self::OPTION_SCHEMA_VERSION:
				return (int) $value;
				
			//Float
			case self::OPTION_RECAPTCHA_THRESHOLD:
				return (float) $value;
			
			//Special
			case self::OPTION_IP_TRUSTED_PROXIES:
			case self::OPTION_2FA_WHITELISTED:
				$value = !is_string($value) ? '' : $value;
				$parsed = array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $value)));
				$cleaned = array();
				foreach ($parsed as $item) {
					$cleaned[] = $this->_sanitize_ip_range($item);
				}
				return implode("\n", $cleaned);
			case self::OPTION_REQUIRE_2FA_GRACE_PERIOD:
				$dt = $this->_parse_local_time($value);
				return $dt->format('U');
			case self::OPTION_RECAPTCHA_SITE_KEY:
			case self::OPTION_RECAPTCHA_SECRET:
				return trim($value);
		}
		return $value;
	}
	
	public function clean_multiple($changes) {
		$cleaned = array();
		foreach ($changes as $key => $value) {
			$cleaned[$key] = $this->clean($key, $value);
		}
		return $cleaned;
	}

	private function get_required_2fa_role_key($role) {
		return implode('.', array(self::OPTION_PREFIX_REQUIRED_2FA_ROLE, $role));
	}

	public function get_required_2fa_role_activation_time($role) {
		$time = $this->get_int($this->get_required_2fa_role_key($role), -1);
		if ($time < 0)
			return false;
		return $time;
	}

	public function get_user_2fa_grace_period() {
		return $this->get_int(self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD, self::DEFAULT_REQUIRE_2FA_USER_GRACE_PERIOD);
	}

	/**
	 * Preprocesses the value, returning true if it was saved here (e.g., saved 2fa enabled by assigning a role 
	 * capability) or false if it is to be saved by the backing storage.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param array &$settings the array of settings to process, this function may append additional values from preprocessing
	 * @return bool
	 */
	public function preprocess($key, $value, &$settings) {
		if (preg_match('/^enabled-roles\.(.+)$/', $key, $matches)) { //Enabled roles are stored as capabilities rather than in the settings storage
			$role = $matches[1];
			if ($role === 'super-admin') {
				$roleValid = true;
			}
			else if (in_array($value, array(self::STATE_2FA_OPTIONAL, self::STATE_2FA_REQUIRED))) {
				$roleValid = Controller_Permissions::shared()->allow_2fa_self($role);
			}
			else {
				$roleValid = Controller_Permissions::shared()->disallow_2fa_self($role);
			}
			
			if (!in_array($value, array(self::STATE_2FA_OPTIONAL, self::STATE_2FA_REQUIRED))) {
				$value = self::STATE_2FA_DISABLED;
			}
			
			if ($roleValid) {
				$settings[$this->get_required_2fa_role_key($role)] = ($value === self::STATE_2FA_REQUIRED ? time() : -1);
			}
			
			/**
			 * Fires when 2FA availability/required on a role changes.
			 *
			 * @since 1.1.13
			 *
			 * @param string $role The name of the role.
			 * @param string $state The state of 2FA on the role.
			 */
			do_action('wordfence_ls_changed_2fa_required', $role, $value);
			
			return true;
		}
		
		//Settings that will dispatch actions
		switch ($key) {
			case self::OPTION_XMLRPC_ENABLED:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the XML-RPC 2FA requirement changes.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_xml_rpc_2fa_toggled', $before, $after);
				}
				break;
			case self::OPTION_2FA_WHITELISTED:
				$before = $this->whitelisted_ips();
				$after = explode("\n", $value); //Already cleaned here so just re-split
					
				if ($before != $after) {
					/**
					 * Fires when the whitelist changes.
					 *
					 * @since 1.1.13
					 *
					 * @param string[] $before The previous value.
					 * @param string[] $after The new value.
					 */
					do_action('wordfence_ls_updated_allowed_ips', $before, $after);
				}
				break;
			case self::OPTION_IP_SOURCE:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the IP source changes.
					 *
					 * @since 1.1.13
					 *
					 * @param string $before The previous value.
					 * @param string $after The new value.
					 */
					do_action('wordfence_ls_changed_ip_source', $before, $after);
				}
				break;
			case self::OPTION_IP_TRUSTED_PROXIES:
				$before = $this->trusted_proxies();
				$after = explode("\n", $value); //Already cleaned here so just re-split
				
				if (count($before) == count($after) && empty(array_diff($before, $after))) {
					/**
					 * Fires when the trusted proxy list changes.
					 *
					 * @since 1.1.13
					 *
					 * @param string[] $before The previous value.
					 * @param string[] $after The new value.
					 */
					do_action('wordfence_ls_updated_trusted_proxies', $before, $after);
				}
				break;
			case self::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the grace period changes.
					 *
					 * @since 1.1.13
					 *
					 * @param int $before The previous value.
					 * @param int $after The new value.
					 */
					do_action('wordfence_ls_changed_grace_period', $before, $after);
				}
				break;
			case self::OPTION_ALLOW_XML_RPC:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the XML-RPC is enabled/disabled.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_xml_rpc_enabled_toggled', $before, $after);
				}
				break;
			case self::OPTION_ENABLE_AUTH_CAPTCHA:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the login captcha is enabled/disabled.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_captcha_enabled_toggled', $before, $after);
				}
				break;
			case self::OPTION_RECAPTCHA_THRESHOLD:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when the reCAPTCHA threshold changes.
					 *
					 * @since 1.1.13
					 *
					 * @param float $before The previous value.
					 * @param float $after The new value.
					 */
					do_action('wordfence_ls_captcha_threshold_changed', $before, $after);
				}
				break;
			case self::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when WooCommerce integration is enabled/disabled.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_woocommerce_enabled_toggled', $before, $after);
				}
				break;
			case self::OPTION_CAPTCHA_TEST_MODE:
				$before = $this->get($key);
				$after = $value;
				
				if ($before != $after) {
					/**
					 * Fires when captcha test mode is enabled/disabled.
					 *
					 * @since 1.1.13
					 *
					 * @param bool $before The previous value.
					 * @param bool $after The new value.
					 */
					do_action('wordfence_ls_captcha_test_mode_toggled', $before, $after);
				}
				break;
		}
		
		return false;
	}
	
	public function preprocess_multiple($changes) {
		$remaining = array();
		foreach ($changes as $key => $value) {
			if (!$this->preprocess($key, $value, $remaining)) {
				$remaining[$key] = $value;
			}
		}
		return $remaining;
	}
	
	/**
	 * Convenience
	 */
	
	/**
	 * Returns a cleaned array containing the whitelist entries.
	 * 
	 * @return array
	 */
	public function whitelisted_ips() {
		return array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $this->get(self::OPTION_2FA_WHITELISTED, ''))));
	}
	
	/**
	 * Returns a cleaned array containing the trusted proxy entries.
	 *
	 * @return array
	 */
	public function trusted_proxies() {
		return array_filter(array_map(function($s) { return trim($s); }, preg_split('/[\r\n]/', $this->get(self::OPTION_IP_TRUSTED_PROXIES, ''))));
	}

	public function get_ntp_failure_count() {
		return $this->get_int(self::OPTION_NTP_FAILURE_COUNT, 0);
	}

	public function reset_ntp_failure_count() {
		$this->set(self::OPTION_NTP_FAILURE_COUNT, 0);
	}

	public function increment_ntp_failure_count() {
		$count = $this->get_ntp_failure_count();
		if ($count < 0)
			return false;
		$count++;
		$this->set(self::OPTION_NTP_FAILURE_COUNT, $count);
		return $count;
	}

	public function is_ntp_disabled_via_constant() {
		return defined('WORDFENCE_LS_DISABLE_NTP') && WORDFENCE_LS_DISABLE_NTP;
	}

	public function is_ntp_enabled($requireOffset = true) {
		if ($this->is_ntp_cron_disabled())
			return false;
		if ($this->get_bool(self::OPTION_USE_NTP, true)) {
			if ($requireOffset) {
				$offset = $this->get(self::OPTION_NTP_OFFSET, null);
				return $offset !== null && abs((int)$offset) <= Controller_TOTP::TIME_WINDOW_LENGTH;
			}
			else {
				return true;
			}
		}
		return false;
	}

	public function is_ntp_cron_disabled(&$failureCount = null) {
		if ($this->is_ntp_disabled_via_constant())
			return true;
		$failureCount = $this->get_ntp_failure_count();
		if ($failureCount >= Controller_Time::FAILURE_LIMIT) {
			return true;
		}
		else if ($failureCount < 0) {
			$failureCount = 0;
			return true;
		}
		return false;
	}

	public function disable_ntp_cron() {
		$this->set(self::OPTION_NTP_FAILURE_COUNT, -1);
	}

	public function are_login_history_columns_enabled() {
		return Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ENABLE_LOGIN_HISTORY_COLUMNS, true);
	}

	public function should_stack_ui_columns() {
		return self::shared()->get_bool(Controller_Settings::OPTION_STACK_UI_COLUMNS, true);
	}

	/**
	 * Utility
	 */
	
	/**
	 * Translates a value to a boolean, correctly interpreting various textual representations.
	 *
	 * @param $value
	 * @return bool
	 */
	protected function _truthy_to_bool($value) {
		if ($value === true || $value === false) {
			return $value;
		}
		
		if (is_null($value)) {
			return false;
		}
		
		if (is_numeric($value)) {
			return !!$value;
		}
		
		if (preg_match('/^(?:f(?:alse)?|no?|off)$/i', $value)) {
			return false;
		}
		else if (preg_match('/^(?:t(?:rue)?|y(?:es)?|on)$/i', $value)) {
			return true;
		}
		
		return !empty($value);
	}
	
	/**
	 * Parses the given time string and returns its DateTime with the server's configured time zone.
	 * 
	 * @param string $timestring
	 * @return \DateTime
	 */
	protected function _parse_local_time($timestring) {
		$utc = new \DateTimeZone('UTC');
		$tz = get_option('timezone_string');
		if (!empty($tz)) {
			$tz = new \DateTimeZone($tz);
			return new \DateTime($timestring, $tz);
		}
		else {
			$gmt = get_option('gmt_offset');
			if (!empty($gmt)) {
				if (PHP_VERSION_ID < 50510) {
					$timestamp = strtotime($timestring);
					$dtStr = gmdate("c", (int) ($timestamp + $gmt * 3600)); //Have to do it this way because of < PHP 5.5.10
					return new \DateTime($dtStr, $utc);
				}
				else {
					$direction = ($gmt > 0 ? '+' : '-');
					$gmt = abs($gmt);
					$h = (int) $gmt;
					$m = ($gmt - $h) * 60;
					$tz = new \DateTimeZone($direction . str_pad($h, 2, '0', STR_PAD_LEFT) . str_pad($m, 2, '0', STR_PAD_LEFT));
					return new \DateTime($timestring, $tz);
				}
			}
		}
		return new \DateTime($timestring);
	}
	
	/**
	 * Cleans a user-entered IP range of unnecessary characters and normalizes some glyphs.
	 *
	 * @param string $range
	 * @return string
	 */
	protected function _sanitize_ip_range($range) {
		$range = preg_replace('/\s/', '', $range); //Strip whitespace
		$range = preg_replace('/[\\x{2013}-\\x{2015}]/u', '-', $range); //Non-hyphen dashes to hyphen
		$range = strtolower($range);
		
		if (preg_match('/^\d+-\d+$/', $range)) { //v5 32 bit int style format
			list($start, $end) = explode('-', $range);
			$start = long2ip($start);
			$end = long2ip($end);
			$range = "{$start}-{$end}";
		}
		
		return $range;
	}

	private function _migrate_admin_2fa_requirements_to_roles() {
		if (!$this->get_bool(self::OPTION_REQUIRE_2FA_ADMIN))
			return;
		$time = time();
		if (is_multisite()) {
			$this->set($this->get_required_2fa_role_key('super-admin'), $time, true);
		}
		else {
			$roles = new \WP_Roles();
			foreach ($roles->roles as $key => $data) {
				$role = $roles->get_role($key);
				if (Controller_Permissions::shared()->can_role_manage_settings($role) && Controller_Permissions::shared()->allow_2fa_self($role->name)) {
					$this->set($this->get_required_2fa_role_key($role->name), $time, true);
				}
			}
		}
		$this->remove(self::OPTION_REQUIRE_2FA_ADMIN);
		$this->remove(self::OPTION_REQUIRE_2FA_GRACE_PERIOD);
		$this->remove(self::OPTION_REQUIRE_2FA_GRACE_PERIOD_ENABLED);
	}

	public function reset_ntp_disabled_flag() {
		$this->remove(self::OPTION_USE_NTP);
		$this->remove(self::OPTION_NTP_OFFSET);
		$this->remove(self::OPTION_NTP_FAILURE_COUNT);
	}
}