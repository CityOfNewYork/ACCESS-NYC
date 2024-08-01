<?php

namespace WordfenceLS;

class Controller_Support {
	const ITEM_INDEX = 'index';
	
	const ITEM_CHANGELOG = 'changelog';
	
	const ITEM_VERSION_WORDPRESS = 'version-wordpress';
	const ITEM_VERSION_PHP = 'version-php';
	const ITEM_VERSION_OPENSSL = 'version-ssl';
	
	const ITEM_GDPR = 'gdpr';
	const ITEM_GDPR_DPA = 'gdpr-dpa';
	
	const ITEM_MODULE_LOGIN_SECURITY = 'module-login-security';
	const ITEM_MODULE_LOGIN_SECURITY_2FA = 'module-login-security-2fa';
	const ITEM_MODULE_LOGIN_SECURITY_2FA_APPS = 'module-login-security-2fa-apps';
	const ITEM_MODULE_LOGIN_SECURITY_CAPTCHA = 'module-login-security-captcha';
	const ITEM_MODULE_LOGIN_SECURITY_ROLES = 'module-login-security-roles';
	const ITEM_MODULE_LOGIN_SECURITY_OPTION_WOOCOMMERCE_ACCOUNT_INTEGRATION = 'module-login-security-option-woocommerce-account-integration';
	const ITEM_MODULE_LOGIN_SECURITY_OPTION_SHORTCODE = 'module-login-security-option-shortcode';
	const ITEM_MODULE_LOGIN_SECURITY_OPTION_STACK_UI_COLUMNS = 'module-login-security-option-stack-ui-columns';
	const ITEM_MODULE_LOGIN_SECURITY_2FA_NOTIFICATIONS = 'module-login-security-2fa-notifications';
	
	public static function esc_supportURL($item = self::ITEM_INDEX) {
		return esc_url(self::supportURL($item));
	}
	
	public static function supportURL($item = self::ITEM_INDEX) {
		$base = 'https://www.wordfence.com/help/';
		switch ($item) {
			case self::ITEM_INDEX:
				return 'https://www.wordfence.com/help/';
			
			//These all fall through to the query format
			
			case self::ITEM_VERSION_WORDPRESS:
			case self::ITEM_VERSION_PHP:
			case self::ITEM_VERSION_OPENSSL:
			
			case self::ITEM_GDPR:
			case self::ITEM_GDPR_DPA:
			
			case self::ITEM_MODULE_LOGIN_SECURITY:
			case self::ITEM_MODULE_LOGIN_SECURITY_2FA:
			case self::ITEM_MODULE_LOGIN_SECURITY_CAPTCHA:
			case self::ITEM_MODULE_LOGIN_SECURITY_ROLES:
			case self::ITEM_MODULE_LOGIN_SECURITY_OPTION_WOOCOMMERCE_ACCOUNT_INTEGRATION:
			case self::ITEM_MODULE_LOGIN_SECURITY_OPTION_SHORTCODE:
			case self::ITEM_MODULE_LOGIN_SECURITY_OPTION_STACK_UI_COLUMNS:
			case self::ITEM_MODULE_LOGIN_SECURITY_2FA_NOTIFICATIONS:
				return $base . '?query=' . $item;
		}
		
		return '';
	}
}