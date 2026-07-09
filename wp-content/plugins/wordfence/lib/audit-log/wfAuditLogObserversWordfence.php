<?php

abstract class wfAuditLogObserversWordfence extends wfAuditLog {
	const WORDFENCE_WAF_MODE_CHANGED = 'wordfence.waf.mode.changed';
	const WORDFENCE_WAF_RULE_STATUS_CHANGED = 'wordfence.waf.rule-status.changed';
	const WORDFENCE_WAF_PROTECTION_LEVEL_CHANGED = 'wordfence.waf.protection-level.changed';
	const WORDFENCE_WAF_ALLOW_ENTRY_CREATED = 'wordfence.waf.allow-entry.created';
	const WORDFENCE_WAF_ALLOW_ENTRY_DELETED = 'wordfence.waf.allow-entry.deleted';
	const WORDFENCE_WAF_ALLOW_ENTRY_TOGGLED = 'wordfence.waf.allow-entry.toggled';
	const WORDFENCE_WAF_BLOCKLIST_TOGGLED = 'wordfence.waf.blocklist.toggled';
	
	const WORDFENCE_ALLOWED_IPS_UPDATED = 'wordfence.allowed-ips.updated';
	const WORDFENCE_ALLOWED_SERVICES_UPDATED = 'wordfence.allowed-services.updated';
	const WORDFENCE_ALLOWED_404S_UPDATED = 'wordfence.allowed-404.updated';
	const WORDFENCE_IGNORED_ALERT_IPS_UPDATED = 'wordfence.ignored-alert-ips.updated';
	
	const WORDFENCE_BANNED_URLS_UPDATED = 'wordfence.banned-urls.updated';
	const WORDFENCE_BANNED_USERNAMES_UPDATED = 'wordfence.banned-usernames.updated';
	
	const WORDFENCE_BRUTE_FORCE_TOGGLED = 'wordfence.brute-force.toggled';
	const WORDFENCE_GENERAL_RATE_LIMITING_BLOCKING_TOGGLED = 'wordfence.general-rate-limiting-blocking.toggled';
	const WORDFENCE_NEVER_BLOCK_CRAWLERS_CHANGED = 'wordfence.never-block-crawlers.changed';
	const WORDFENCE_LOCKOUT_INVALID_TOGGLED = 'wordfence.lock-out-invalid.toggled';
	const WORDFENCE_BREACHED_PASSWORDS_TOGGLED = 'wordfence.breached-passwords.toggled';
	const WORDFENCE_ENFORCE_STRONG_PASSWORDS_TOGGLED = 'wordfence.enforce-strong-passwords.toggled';
	const WORDFENCE_MASK_LOGIN_ERRORS_TOGGLED = 'wordfence.mask-login-errors.toggled';
	const WORDFENCE_PREVENT_ADMIN_USERNAME_TOGGLED = 'wordfence.prevent-admin-username.toggled';
	const WORDFENCE_BLOCK_AUTHOR_SCAN_TOGGLED = 'wordfence.block-author-scan.toggled';
	const WORDFENCE_PREVENT_APPLICATION_PASSWORDS_TOGGLED = 'wordfence.prevent-application-passwords.toggled';
	const WORDFENCE_BLOCK_BAD_POST_TOGGLED = 'wordfence.block-bad-post.toggled';
	const WORDFENCE_CHANGE_PASSWORD_CHECK_STRENGTH_TOGGLED = 'wordfence.change-password-check-strength.toggled';
	
	const WORDFENCE_LOGIN_FAILURE_COUNT_UPDATED = 'wordfence.login-failure-count.updated';
	const WORDFENCE_FORGOT_PASSWORD_COUNT_UPDATED = 'wordfence.forgot-password-count.updated';
	const WORDFENCE_LOGIN_SECURITY_PERIOD_UPDATED = 'wordfence.login-security-period.updated';
	const WORDFENCE_LOGIN_SECURITY_DURATION_UPDATED = 'wordfence.login-security-duration.updated';
	const WORDFENCE_BLOCK_DURATION_UPDATED = 'wordfence.block-duration.updated';
	
	const WORDFENCE_CUSTOM_BLOCK_TEXT_UPDATED = 'wordfence.custom-block-text.updated';
	
	const WORDFENCE_RATE_LIMITS_GLOBAL_UPDATED = 'wordfence.rate-limits.global.updated';
	const WORDFENCE_RATE_LIMITS_CRAWLER_UPDATED = 'wordfence.rate-limits.crawler.updated';
	const WORDFENCE_RATE_LIMITS_CRAWLER_404_UPDATED = 'wordfence.rate-limits.crawler-404.updated';
	const WORDFENCE_RATE_LIMITS_HUMAN_UPDATED = 'wordfence.rate-limits.human.updated';
	const WORDFENCE_RATE_LIMITS_HUMAN_404_UPDATED = 'wordfence.rate-limits.human-404.updated';
	
	const WORDFENCE_SCAN_OPTIONS_UPDATED = 'wordfence.scan.options.updated';
	const WORDFENCE_SCAN_SCHEDULE_UPDATED = 'wordfence.scan.schedule.updated';
	
	const WORDFENCE_BLOCKING_COUNTRY_UPDATED = 'wordfence.blocking.country.updated';
	const WORDFENCE_BLOCKING_IP_PATTERN_CREATED = 'wordfence.blocking.ip-pattern.created';
	const WORDFENCE_BLOCKING_DELETED = 'wordfence.blocking.deleted';
	
	const WORDFENCE_PARTICIPATE_SECURITY_NETWORK_TOGGLED = 'wordfence.participate-security-network.toggled';
	const WORDFENCE_AUDIT_LOG_MODE_CHANGED = 'wordfence.audit-log.mode.changed';
	const WORDFENCE_LICENSE_KEY_CHANGED = 'wordfence.license-key.changed';
	
	const WORDFENCE_IP_SOURCE_CHANGED = 'wordfence.ip-source.changed';
	const WORDFENCE_TRUSTED_PROXIES_UPDATED = 'wordfence.trusted-proxies.updated';
	const WORDFENCE_TRUSTED_PROXY_PRESET_CHANGED = 'wordfence.trusted-proxies-preset.changed';
	
	const WORDFENCE_LS_2FA_DEACTIVATED = 'wordfence.ls.2fa.deactivated';
	const WORDFENCE_LS_2FA_ACTIVATED = 'wordfence.ls.2fa.activated';
	const WORDFENCE_LS_XML_RPC_REQUIRES_2FA_TOGGLED = 'wordfence.ls.xml-rpc-2fa.toggled';
	const WORDFENCE_LS_ALLOWED_IPS_UPDATED = 'wordfence.ls.allowed-ips.updated';
	const WORDFENCE_LS_IP_SOURCE_CHANGED = 'wordfence.ls.ip-source.changed';
	const WORDFENCE_LS_TRUSTED_PROXIES_UPDATED = 'wordfence.ls.trusted-proxies.updated';
	const WORDFENCE_LS_2FA_REQUIRED_CHANGED = 'wordfence.ls.2fa-required.changed';
	const WORDFENCE_LS_2FA_GRACE_PERIOD_CHANGED = 'wordfence.ls.2fa-grace-period.changed';
	const WORDFENCE_LS_XML_RPC_TOGGLED = 'wordfence.ls.xml-rpc-enabled.toggled';
	const WORDFENCE_LS_CAPTCHA_TOGGLED = 'wordfence.ls.captcha-enabled.toggled';
	const WORDFENCE_LS_CAPTCHA_THRESHOLD_CHANGED = 'wordfence.ls.captcha-threshold.changed';
	const WORDFENCE_LS_WOOCOMMERCE_INTEGRATION_TOGGLED = 'wordfence.ls.woocommerce-enabled.toggled';
	const WORDFENCE_LS_CAPTCHA_TEST_MODE_TOGGLED = 'wordfence.ls.captcha-test-mode.toggled';
	
	public static function immediateSendEvents() {
		return array(
			self::WORDFENCE_WAF_MODE_CHANGED,
			self::WORDFENCE_WAF_PROTECTION_LEVEL_CHANGED,
			self::WORDFENCE_WAF_BLOCKLIST_TOGGLED,
			self::WORDFENCE_PARTICIPATE_SECURITY_NETWORK_TOGGLED,
			self::WORDFENCE_AUDIT_LOG_MODE_CHANGED,
			self::WORDFENCE_LICENSE_KEY_CHANGED,
		);
	}
	
	public static function eventCategories() {
		return array(
			wfAuditLog::AUDIT_LOG_CATEGORY_SITE_SETTINGS => array(
				self::WORDFENCE_IGNORED_ALERT_IPS_UPDATED,
				
				self::WORDFENCE_PREVENT_ADMIN_USERNAME_TOGGLED,
				
				self::WORDFENCE_CUSTOM_BLOCK_TEXT_UPDATED,
				
				self::WORDFENCE_SCAN_OPTIONS_UPDATED,
				self::WORDFENCE_SCAN_SCHEDULE_UPDATED,
				
				self::WORDFENCE_PARTICIPATE_SECURITY_NETWORK_TOGGLED,
				self::WORDFENCE_AUDIT_LOG_MODE_CHANGED,
				self::WORDFENCE_LICENSE_KEY_CHANGED,
				
				self::WORDFENCE_IP_SOURCE_CHANGED,
				self::WORDFENCE_TRUSTED_PROXIES_UPDATED,
				self::WORDFENCE_TRUSTED_PROXY_PRESET_CHANGED,
				
				self::WORDFENCE_LS_IP_SOURCE_CHANGED,
				self::WORDFENCE_LS_TRUSTED_PROXIES_UPDATED,
				self::WORDFENCE_LS_WOOCOMMERCE_INTEGRATION_TOGGLED,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_AUTHENTICATION => array(
				self::WORDFENCE_LS_2FA_DEACTIVATED,
				self::WORDFENCE_LS_2FA_ACTIVATED,
				
				self::WORDFENCE_ENFORCE_STRONG_PASSWORDS_TOGGLED,
				self::WORDFENCE_MASK_LOGIN_ERRORS_TOGGLED,
				self::WORDFENCE_PREVENT_APPLICATION_PASSWORDS_TOGGLED,
				self::WORDFENCE_CHANGE_PASSWORD_CHECK_STRENGTH_TOGGLED,
				
				self::WORDFENCE_LS_XML_RPC_REQUIRES_2FA_TOGGLED,
				self::WORDFENCE_LS_ALLOWED_IPS_UPDATED,
				self::WORDFENCE_LS_2FA_REQUIRED_CHANGED,
				self::WORDFENCE_LS_2FA_GRACE_PERIOD_CHANGED,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_FIREWALL => array(
				self::WORDFENCE_WAF_MODE_CHANGED,
				self::WORDFENCE_WAF_RULE_STATUS_CHANGED,
				self::WORDFENCE_WAF_PROTECTION_LEVEL_CHANGED,
				self::WORDFENCE_WAF_ALLOW_ENTRY_CREATED,
				self::WORDFENCE_WAF_ALLOW_ENTRY_DELETED,
				self::WORDFENCE_WAF_ALLOW_ENTRY_TOGGLED,
				self::WORDFENCE_WAF_BLOCKLIST_TOGGLED,
				
				self::WORDFENCE_ALLOWED_IPS_UPDATED,
				self::WORDFENCE_ALLOWED_SERVICES_UPDATED,
				self::WORDFENCE_ALLOWED_404S_UPDATED,
				
				self::WORDFENCE_BANNED_URLS_UPDATED,
				self::WORDFENCE_BANNED_USERNAMES_UPDATED,
				
				self::WORDFENCE_BRUTE_FORCE_TOGGLED,
				self::WORDFENCE_GENERAL_RATE_LIMITING_BLOCKING_TOGGLED,
				self::WORDFENCE_NEVER_BLOCK_CRAWLERS_CHANGED,
				self::WORDFENCE_LOCKOUT_INVALID_TOGGLED,
				self::WORDFENCE_BREACHED_PASSWORDS_TOGGLED,
				self::WORDFENCE_BLOCK_AUTHOR_SCAN_TOGGLED,
				self::WORDFENCE_BLOCK_BAD_POST_TOGGLED,
				
				self::WORDFENCE_LOGIN_FAILURE_COUNT_UPDATED,
				self::WORDFENCE_FORGOT_PASSWORD_COUNT_UPDATED,
				self::WORDFENCE_LOGIN_SECURITY_PERIOD_UPDATED,
				self::WORDFENCE_LOGIN_SECURITY_DURATION_UPDATED,
				self::WORDFENCE_BLOCK_DURATION_UPDATED,
				
				self::WORDFENCE_RATE_LIMITS_GLOBAL_UPDATED,
				self::WORDFENCE_RATE_LIMITS_CRAWLER_UPDATED,
				self::WORDFENCE_RATE_LIMITS_CRAWLER_404_UPDATED,
				self::WORDFENCE_RATE_LIMITS_HUMAN_UPDATED,
				self::WORDFENCE_RATE_LIMITS_HUMAN_404_UPDATED,
				
				self::WORDFENCE_BLOCKING_COUNTRY_UPDATED,
				self::WORDFENCE_BLOCKING_IP_PATTERN_CREATED,
				self::WORDFENCE_BLOCKING_DELETED,
				
				self::WORDFENCE_LS_XML_RPC_TOGGLED,
				self::WORDFENCE_LS_CAPTCHA_TOGGLED,
				self::WORDFENCE_LS_CAPTCHA_THRESHOLD_CHANGED,
				self::WORDFENCE_LS_CAPTCHA_TEST_MODE_TOGGLED,
			),
		);
	}
	
	public static function eventNames() {
		return array(
			self::WORDFENCE_WAF_MODE_CHANGED => __('Wordfence WAF Mode Changed', 'wordfence'),
			self::WORDFENCE_WAF_RULE_STATUS_CHANGED => __('Wordfence WAF Rule Status Changed', 'wordfence'),
			self::WORDFENCE_WAF_PROTECTION_LEVEL_CHANGED => __('Wordfence WAF Protection Level Changed', 'wordfence'),
			self::WORDFENCE_WAF_ALLOW_ENTRY_CREATED => __('Wordfence WAF Allow Entry Created', 'wordfence'),
			self::WORDFENCE_WAF_ALLOW_ENTRY_DELETED => __('Wordfence WAF Allow Entry Deleted', 'wordfence'),
			self::WORDFENCE_WAF_ALLOW_ENTRY_TOGGLED => __('Wordfence WAF Allow Entry Toggled', 'wordfence'),
			self::WORDFENCE_WAF_BLOCKLIST_TOGGLED => __('Wordfence WAF Blocklist Toggled', 'wordfence'),
			
			self::WORDFENCE_ALLOWED_IPS_UPDATED => __('Allowlisted IPs Updated', 'wordfence'),
			self::WORDFENCE_ALLOWED_SERVICES_UPDATED => __('Allowlisted Services Updated', 'wordfence'),
			self::WORDFENCE_ALLOWED_404S_UPDATED => __('Allowed 404s Updated', 'wordfence'),
			self::WORDFENCE_IGNORED_ALERT_IPS_UPDATED => __('Ignored Alert IPs Updated', 'wordfence'),
			
			self::WORDFENCE_BANNED_URLS_UPDATED => __('Banned URLs Updated', 'wordfence'),
			self::WORDFENCE_BANNED_USERNAMES_UPDATED => __('Banned Usernames Updated', 'wordfence'),
			
			self::WORDFENCE_BRUTE_FORCE_TOGGLED => __('Brute Force Protection Toggled', 'wordfence'),
			self::WORDFENCE_GENERAL_RATE_LIMITING_BLOCKING_TOGGLED => __('General Blocking and Rate Limiting Toggled', 'wordfence'),
			self::WORDFENCE_NEVER_BLOCK_CRAWLERS_CHANGED => __('Never Block Crawlers Toggled', 'wordfence'),
			self::WORDFENCE_LOCKOUT_INVALID_TOGGLED => __('Lockout Invalid Users Toggled', 'wordfence'),
			self::WORDFENCE_BREACHED_PASSWORDS_TOGGLED => __('Prevent Use of Breached Passwords Toggled', 'wordfence'),
			self::WORDFENCE_ENFORCE_STRONG_PASSWORDS_TOGGLED => __('Enforce Strong Passwords Toggled', 'wordfence'),
			self::WORDFENCE_MASK_LOGIN_ERRORS_TOGGLED => __('Mask Login Errors Toggled', 'wordfence'),
			self::WORDFENCE_PREVENT_ADMIN_USERNAME_TOGGLED => __('Prevent Using "admin" Username Toggled', 'wordfence'),
			self::WORDFENCE_BLOCK_AUTHOR_SCAN_TOGGLED => __('Block Author Scanning Toggled', 'wordfence'),
			self::WORDFENCE_PREVENT_APPLICATION_PASSWORDS_TOGGLED => __('Prevent Use of Application Passwords Toggled', 'wordfence'),
			self::WORDFENCE_BLOCK_BAD_POST_TOGGLED => __('Block Bad POST Requests Toggled', 'wordfence'),
			self::WORDFENCE_CHANGE_PASSWORD_CHECK_STRENGTH_TOGGLED => __('Check Password Strength on Reset Toggled', 'wordfence'),
			
			self::WORDFENCE_LOGIN_FAILURE_COUNT_UPDATED => __('Failed Login Failure Threshold Updated', 'wordfence'),
			self::WORDFENCE_FORGOT_PASSWORD_COUNT_UPDATED => __('Forgot Password Threshold Updated', 'wordfence'),
			self::WORDFENCE_LOGIN_SECURITY_PERIOD_UPDATED => __('Login Security Counting Period Updated', 'wordfence'),
			self::WORDFENCE_LOGIN_SECURITY_DURATION_UPDATED => __('Login Security Lockout Threshold Updated', 'wordfence'),
			self::WORDFENCE_BLOCK_DURATION_UPDATED => __('Automatic Block Duration Updated', 'wordfence'),
			
			self::WORDFENCE_CUSTOM_BLOCK_TEXT_UPDATED => __('Custom Block Text Updated', 'wordfence'),
			
			self::WORDFENCE_RATE_LIMITS_GLOBAL_UPDATED => __('Global Rate Limit Settings Updated', 'wordfence'),
			self::WORDFENCE_RATE_LIMITS_CRAWLER_UPDATED => __('Crawler Rate Limit Settings Updated', 'wordfence'),
			self::WORDFENCE_RATE_LIMITS_CRAWLER_404_UPDATED => __('Crawler 404 Rate Limit Settings Updated', 'wordfence'),
			self::WORDFENCE_RATE_LIMITS_HUMAN_UPDATED => __('Human Rate Limit Settings Updated', 'wordfence'),
			self::WORDFENCE_RATE_LIMITS_HUMAN_404_UPDATED => __('Human 404 Rate Limit Settings Updated', 'wordfence'),
			
			self::WORDFENCE_SCAN_OPTIONS_UPDATED => __('Scan Options Updated', 'wordfence'),
			self::WORDFENCE_SCAN_SCHEDULE_UPDATED => __('Scan Schedule Updated', 'wordfence'),
			
			self::WORDFENCE_BLOCKING_COUNTRY_UPDATED => __('Country Blocking Updated', 'wordfence'),
			self::WORDFENCE_BLOCKING_IP_PATTERN_CREATED => __('Manual Block Created', 'wordfence'),
			self::WORDFENCE_BLOCKING_DELETED => __('Block Deleted', 'wordfence'),
			
			self::WORDFENCE_PARTICIPATE_SECURITY_NETWORK_TOGGLED => __('Participate in the Wordfence Security Network Toggled', 'wordfence'),
			self::WORDFENCE_AUDIT_LOG_MODE_CHANGED => __('Audit Log Mode Changed', 'wordfence'),
			self::WORDFENCE_LICENSE_KEY_CHANGED => __('License Key Changed', 'wordfence'),
			
			self::WORDFENCE_IP_SOURCE_CHANGED => __('IP Source Changed', 'wordfence'),
			self::WORDFENCE_TRUSTED_PROXIES_UPDATED => __('Trusted Proxies Updated', 'wordfence'),
			self::WORDFENCE_TRUSTED_PROXY_PRESET_CHANGED => __('Trusted Proxy Preset Changed', 'wordfence'),
			
			self::WORDFENCE_LS_2FA_DEACTIVATED => __('2FA Deactivated on User', 'wordfence'),
			self::WORDFENCE_LS_2FA_ACTIVATED => __('2FA Activated on User', 'wordfence'),
			self::WORDFENCE_LS_XML_RPC_REQUIRES_2FA_TOGGLED => __('XML-RPC Requires 2FA Toggled', 'wordfence'),
			self::WORDFENCE_LS_ALLOWED_IPS_UPDATED => __('IPs Bypassing 2FA Updated', 'wordfence'),
			self::WORDFENCE_LS_IP_SOURCE_CHANGED => __('IP Source Changed', 'wordfence'),
			self::WORDFENCE_LS_TRUSTED_PROXIES_UPDATED => __('Trusted Proxies Updated', 'wordfence'),
			self::WORDFENCE_LS_2FA_REQUIRED_CHANGED => __('2FA Role Requirements Changed', 'wordfence'),
			self::WORDFENCE_LS_2FA_GRACE_PERIOD_CHANGED => __('2FA Grace Period Changed', 'wordfence'),
			self::WORDFENCE_LS_XML_RPC_TOGGLED => __('XML-RPC Interface Toggled', 'wordfence'),
			self::WORDFENCE_LS_CAPTCHA_TOGGLED => __('Login Captcha Toggled', 'wordfence'),
			self::WORDFENCE_LS_CAPTCHA_THRESHOLD_CHANGED => __('reCAPTCHA Threshold Changed', 'wordfence'),
			self::WORDFENCE_LS_WOOCOMMERCE_INTEGRATION_TOGGLED => __('WooCommerce 2FA Integration Toggled', 'wordfence'),
			self::WORDFENCE_LS_CAPTCHA_TEST_MODE_TOGGLED => __('Captcha Test Mode Toggled', 'wordfence'),
		);
	}
	
	public static function eventRateLimiters() {
		return array();
	}
	
	/**
	 * Registers the observers for this class's chunk of functionality that should run regardless of other settings.
	 * These observers are expected to do their own check and application of settings like the audit log's mode or
	 * the `Participate in the Wordfence Security Network` setting.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerForcedObservers($auditLog) {
		if (!wfLicense::current()->isAtLeastPremium()) {
			return;
		}
		
		$auditLog->_addObserver('wordfence_changed_audit_log_mode', function($before, $after) use ($auditLog) { //Audit log mode changed, run in all modes
			$auditLog->_recordAction(self::WORDFENCE_AUDIT_LOG_MODE_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		if ($auditLog->mode() == wfAuditLog::AUDIT_LOG_MODE_DISABLED) {
			return;
		}
		
		$auditLog->_addObserver('wordfence_toggled_participate_security_network', function($before, $after) use ($auditLog) { //Participate WFSN toggled, always send if audit log enabled
			$auditLog->_recordAction(self::WORDFENCE_PARTICIPATE_SECURITY_NETWORK_TOGGLED, array('state' => $after));
		});
	}
	
	/**
	 * Registers the observers for this class's chunk of functionality.
	 * 
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerObservers($auditLog) {
		//WAF
		$auditLog->_addObserver('wordfence_waf_mode', function($before, $after) use ($auditLog) { //WAF mode setting changed
			$auditLog->_recordAction(self::WORDFENCE_WAF_MODE_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_waf_changed_rule_status', function($changes) use ($auditLog) { //WAF rule mode(s) changed
			$auditLog->_recordAction(self::WORDFENCE_WAF_RULE_STATUS_CHANGED, $changes);
		});
		
		$auditLog->_addObserver('wordfence_waf_changed_protection_level', function($before, $after) use ($auditLog) { //WAF protection level changed
			$auditLog->_recordAction(self::WORDFENCE_WAF_PROTECTION_LEVEL_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_waf_created_allow_entry', function($entries) use ($auditLog) { //WAF allow entry created
			$auditLog->_recordAction(self::WORDFENCE_WAF_ALLOW_ENTRY_CREATED, array('added' => $entries));
		});
		
		$auditLog->_addObserver('wordfence_waf_deleted_allow_entry', function($entries) use ($auditLog) { //WAF allow entry deleted
			$auditLog->_recordAction(self::WORDFENCE_WAF_ALLOW_ENTRY_DELETED, array('deleted' => $entries));
		});
		
		$auditLog->_addObserver('wordfence_waf_toggled_allow_entry', function($entries) use ($auditLog) { //WAF allow entry toggled
			$auditLog->_recordAction(self::WORDFENCE_WAF_ALLOW_ENTRY_TOGGLED, array('toggled' => $entries));
		});
		
		$auditLog->_addObserver('wordfence_waf_toggled_blocklist', function($before, $after) use ($auditLog) { //WAF blocklist toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_WAF_BLOCKLIST_TOGGLED, array('state' => $after));
		});
		
		//Allowed/ignored
		$auditLog->_addObserver('wordfence_updated_allowed_ips', function($before, $after) use ($auditLog) { //Allowed IP list changed, only care about additions
			$changes = wfUtils::array_diff($before, $after);
			if (!empty($changes['added'])) {
				$auditLog->_recordAction(self::WORDFENCE_ALLOWED_IPS_UPDATED, array('added' => $changes['added']));
			}
		});
		
		$auditLog->_addObserver('wordfence_updated_allowed_services', function($before, $after) use ($auditLog) { //Allowed services list changed, only care about additions
			$resolvedBefore = wfUtils::whitelistedServiceIPs($before);
			$resolvedAfter = wfUtils::whitelistedServiceIPs($after);
			$changes = wfUtils::array_diff(array_keys($resolvedBefore), array_keys($resolvedAfter));
			if (!empty($changes['added'])) {
				$auditLog->_recordAction(self::WORDFENCE_ALLOWED_SERVICES_UPDATED, array('added' => $changes['added']));
			}
		});
		
		$auditLog->_addObserver('wordfence_updated_allowed_404', function($before, $after) use ($auditLog) { //Allowed 404 list changed, only care about additions
			$changes = wfUtils::array_diff($before, $after);
			if (!empty($changes['added'])) {
				$auditLog->_recordAction(self::WORDFENCE_ALLOWED_404S_UPDATED, array('added' => $changes['added']));
			}
		});
		
		$auditLog->_addObserver('wordfence_updated_ignored_alert_ips', function($before, $after) use ($auditLog) { //Ignored alert IP list changed, only care about additions
			$changes = wfUtils::array_diff($before, $after);
			if (!empty($changes['added'])) {
				$auditLog->_recordAction(self::WORDFENCE_IGNORED_ALERT_IPS_UPDATED, array('added' => $changes['added']));
			}
		});
		
		//Banned/prohibited
		$auditLog->_addObserver('wordfence_updated_banned_urls', function($before, $after) use ($auditLog) { //Banned URL list changed, only care about removals
			$changes = wfUtils::array_diff($before, $after);
			if (!empty($changes['removed'])) {
				$auditLog->_recordAction(self::WORDFENCE_BANNED_URLS_UPDATED, array('removed' => $changes['removed']));
			}
		});
		
		$auditLog->_addObserver('wordfence_updated_banned_usernames', function($before, $after) use ($auditLog) { //Banned username list changed, only care about removals
			$changes = wfUtils::array_diff($before, $after);
			if (!empty($changes['removed'])) {
				$auditLog->_recordAction(self::WORDFENCE_BANNED_USERNAMES_UPDATED, array('removed' => $changes['removed']));
			}
		});
		
		//General blocking/brute force
		$auditLog->_addObserver('wordfence_toggled_brute_force_protection', function($before, $after) use ($auditLog) { //Brute force protection toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_BRUTE_FORCE_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_general_rate_limiting_blocking', function($before, $after) use ($auditLog) { //General rate limiting and blocking toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_GENERAL_RATE_LIMITING_BLOCKING_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_never_block_crawlers', function($before, $after) use ($auditLog) { //Never block crawlers changed
			$auditLog->_recordAction(self::WORDFENCE_NEVER_BLOCK_CRAWLERS_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_lock_out_invalid', function($before, $after) use ($auditLog) { //Lock out invalid usernames toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_LOCKOUT_INVALID_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_breached_password_protection', function($before, $after) use ($auditLog) { //Breached password protection toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_BREACHED_PASSWORDS_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_enforce_strong_passwords', function($before, $after) use ($auditLog) { //Enforce strong passwords toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_ENFORCE_STRONG_PASSWORDS_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_mask_login_errors', function($before, $after) use ($auditLog) { //Mask login errors toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_MASK_LOGIN_ERRORS_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_prevent_admin_username', function($before, $after) use ($auditLog) { //Prevent using "admin" username toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_PREVENT_ADMIN_USERNAME_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_block_author_scan', function($before, $after) use ($auditLog) { //Block author scan toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_BLOCK_AUTHOR_SCAN_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_prevent_application_passwords', function($before, $after) use ($auditLog) { //Prevent use of application passwords toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_PREVENT_APPLICATION_PASSWORDS_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_block_bad_post', function($before, $after) use ($auditLog) { //Block bad POST requests toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_BLOCK_BAD_POST_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_toggled_change_password_check_strength', function($before, $after) use ($auditLog) { //Check strength on password change toggled on/off
			$auditLog->_recordAction(self::WORDFENCE_CHANGE_PASSWORD_CHECK_STRENGTH_TOGGLED, array('state' => $after));
		});
		
		//Thresholds/Durations
		$auditLog->_addObserver('wordfence_updated_login_failure_count', function($before, $after) use ($auditLog) { //Login failure count before lockout
			$auditLog->_recordAction(self::WORDFENCE_LOGIN_FAILURE_COUNT_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_forgot_password_count', function($before, $after) use ($auditLog) { //Forgot password request count before lockout
			$auditLog->_recordAction(self::WORDFENCE_FORGOT_PASSWORD_COUNT_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_login_security_period', function($before, $after) use ($auditLog) { //Count failures over this period
			$auditLog->_recordAction(self::WORDFENCE_LOGIN_SECURITY_PERIOD_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_login_security_duration', function($before, $after) use ($auditLog) { //Duration of lockout
			$auditLog->_recordAction(self::WORDFENCE_LOGIN_SECURITY_DURATION_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_block_duration', function($before, $after) use ($auditLog) { //Rate limit block/lockout duration
			$auditLog->_recordAction(self::WORDFENCE_BLOCK_DURATION_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		//Custom text
		$auditLog->_addObserver('wordfence_updated_custom_block_text', function($before, $after) use ($auditLog) { //Custom block text
			$auditLog->_recordAction(self::WORDFENCE_CUSTOM_BLOCK_TEXT_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		//Rate limits
		$auditLog->_addObserver('wordfence_updated_max_global_requests', function($before, $after) use ($auditLog) { //Global rate limit
			$auditLog->_recordAction(self::WORDFENCE_RATE_LIMITS_GLOBAL_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_max_crawler_requests', function($before, $after) use ($auditLog) { //Crawler rate limit
			$auditLog->_recordAction(self::WORDFENCE_RATE_LIMITS_CRAWLER_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_max_crawler_404', function($before, $after) use ($auditLog) { //Crawler 404 rate limit
			$auditLog->_recordAction(self::WORDFENCE_RATE_LIMITS_CRAWLER_404_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_max_human_requests', function($before, $after) use ($auditLog) { //Human rate limit
			$auditLog->_recordAction(self::WORDFENCE_RATE_LIMITS_HUMAN_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_max_human_404', function($before, $after) use ($auditLog) { //Human 404 rate limit
			$auditLog->_recordAction(self::WORDFENCE_RATE_LIMITS_HUMAN_404_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		//Scan
		$auditLog->_addObserver('wordfence_updated_scan_options', function($before, $after) use ($auditLog) { //Scan options
			$auditLog->_recordAction(self::WORDFENCE_SCAN_OPTIONS_UPDATED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_scan_schedule', function($before, $after) use ($auditLog) { //Scan schedule
			$auditLog->_recordAction(self::WORDFENCE_SCAN_SCHEDULE_UPDATED, array('before' => $before, 'after' => $after));
		});
	
		//Custom blocking
		$auditLog->_addObserver('wordfence_updated_country_blocking', function($before, $after) use ($auditLog) { //Country block changed
			$diff = wfUtils::array_diff($before, $after);
			if (!empty($diff['added']) || !empty($diff['removed'])) {
				$auditLog->_recordAction(self::WORDFENCE_BLOCKING_COUNTRY_UPDATED, array('before' => $before, 'after' => $after));
			}
		});
		
		$auditLog->_addObserver('wordfence_created_ip_pattern_block', function($type, $reason, $parameters) use ($auditLog) { //IP or Pattern block created manually
			$auditLog->_recordAction(self::WORDFENCE_BLOCKING_IP_PATTERN_CREATED, array('type' => $type, 'reason' => $reason, 'parameters' => $parameters));
		});
		
		$auditLog->_addObserver('wordfence_deleted_block', function($type, $reason, $parameters) use ($auditLog) { //Block deleted manually
			$auditLog->_recordAction(self::WORDFENCE_BLOCKING_DELETED, array('type' => $type, 'reason' => $reason, 'parameters' => $parameters));
		});
		
		//Core functionality
		$auditLog->_addObserver('wordfence_changed_license_key', function($before, $after) use ($auditLog) { //License key changed
			$auditLog->_recordAction(self::WORDFENCE_LICENSE_KEY_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		//IP resolution
		$auditLog->_addObserver('wordfence_changed_ip_source', function($before, $after) use ($auditLog) { //IP source changed
			$auditLog->_recordAction(self::WORDFENCE_IP_SOURCE_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_updated_trusted_proxies', function($before, $after) use ($auditLog) { //Trusted proxy list changed
			$changes = wfUtils::array_diff($before, $after);
			$auditLog->_recordAction(self::WORDFENCE_TRUSTED_PROXIES_UPDATED, array('changes' => $changes));
		});
		
		$auditLog->_addObserver('wordfence_changed_trusted_proxy_preset', function($before, $after) use ($auditLog) { //Trusted proxy preset selection changed
			$auditLog->_recordAction(self::WORDFENCE_TRUSTED_PROXY_PRESET_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		//Login Security
		$auditLog->_addObserver('wordfence_ls_2fa_deactivated', function($user) use ($auditLog) { //2FA deactivated on a user
			$auditLog->_recordAction(self::WORDFENCE_LS_2FA_DEACTIVATED, $auditLog->_sanitizeUserdata($user));
		});
		
		$auditLog->_addObserver('wordfence_ls_2fa_activated', function($user) use ($auditLog) { //2FA activated on a user
			$auditLog->_recordAction(self::WORDFENCE_LS_2FA_ACTIVATED, $auditLog->_sanitizeUserdata($user));
		});
		
		$auditLog->_addObserver('wordfence_ls_xml_rpc_2fa_toggled', function($before, $after) use ($auditLog) { //2FA required for XML-RPC calls
			$auditLog->_recordAction(self::WORDFENCE_LS_XML_RPC_REQUIRES_2FA_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_ls_updated_allowed_ips', function($before, $after) use ($auditLog) { //Ignored 2FA IP list changed
			$changes = wfUtils::array_diff($before, $after);
			$auditLog->_recordAction(self::WORDFENCE_LS_ALLOWED_IPS_UPDATED, array('changes' => $changes));
		});
		
		$auditLog->_addObserver('wordfence_ls_changed_ip_source', function($before, $after) use ($auditLog) { //IP source changed (WFLS only)
			$auditLog->_recordAction(self::WORDFENCE_LS_IP_SOURCE_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_ls_updated_trusted_proxies', function($before, $after) use ($auditLog) { //Trusted proxy list changed (WFLS only)
			$changes = wfUtils::array_diff($before, $after);
			$auditLog->_recordAction(self::WORDFENCE_LS_TRUSTED_PROXIES_UPDATED, array('changes' => $changes));
		});
		
		$auditLog->_addObserver('wordfence_ls_changed_grace_period', function($before, $after) use ($auditLog) { //2FA grace period changed
			$auditLog->_recordAction(self::WORDFENCE_LS_2FA_GRACE_PERIOD_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_ls_xml_rpc_enabled_toggled', function($before, $after) use ($auditLog) { //XML-RPC enabled/disabled
			$auditLog->_recordAction(self::WORDFENCE_LS_XML_RPC_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_ls_captcha_enabled_toggled', function($before, $after) use ($auditLog) { //Captcha enabled/disabled
			$auditLog->_recordAction(self::WORDFENCE_LS_CAPTCHA_TOGGLED, array('state' => $after));
		});

		$auditLog->_addObserver('wordfence_ls_captcha_threshold_changed', function($before, $after) use ($auditLog) { //Captcha threshold changed
			$auditLog->_recordAction(self::WORDFENCE_LS_CAPTCHA_THRESHOLD_CHANGED, array('before' => $before, 'after' => $after));
		});
		
		$auditLog->_addObserver('wordfence_ls_woocommerce_enabled_toggled', function($before, $after) use ($auditLog) { //WooCommerce integration enabled/disabled
			$auditLog->_recordAction(self::WORDFENCE_LS_WOOCOMMERCE_INTEGRATION_TOGGLED, array('state' => $after));
		});
		
		$auditLog->_addObserver('wordfence_ls_captcha_test_mode_toggled', function($before, $after) use ($auditLog) { //Captcha test mode enabled/disabled
			$auditLog->_recordAction(self::WORDFENCE_LS_CAPTCHA_TEST_MODE_TOGGLED, array('state' => $after));
		});
	}
	
	/**
	 * Registers the data gatherers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerDataGatherers($auditLog) {
		$auditLog->_addObserver('wordfence_ls_changed_2fa_required', function($role, $value) use ($auditLog) { //2FA requirement changed on a role
			if (!$auditLog->_hasState('wordfence_ls_changed_2fa_required.changes', 0)) {
				$auditLog->_trackState('wordfence_ls_changed_2fa_required.changes', array(), 0);
			}
			
			$state = $auditLog->_getState('wordfence_ls_changed_2fa_required.changes', 0);
			$state[$role] = $value;
			$auditLog->_trackState('wordfence_ls_changed_2fa_required.changes', $state, 0);
			
			$auditLog->_needsDestruct();
		});
	}
	
	/**
	 * Registers the coalescers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerCoalescers($auditLog) {
		$auditLog->_addCoalescer(function() use ($auditLog) { //Network active plugins changed
			$changes = $auditLog->_getState('wordfence_ls_changed_2fa_required.changes', 0);
			if (!is_array($changes) || !count($changes)) {
				return;
			}
			
			$auditLog->_recordAction(self::WORDFENCE_LS_2FA_REQUIRED_CHANGED, array('changes' => $changes));
		});
	}
}