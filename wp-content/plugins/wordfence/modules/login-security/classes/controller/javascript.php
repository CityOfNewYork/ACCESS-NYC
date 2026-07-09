<?php

namespace WordfenceLS;

use WordfenceLS\Controller_WordfenceLS;
use WordfenceLS\Controller_Settings;
use WordfenceLS\Model_Asset;
use WordfenceLS\Model_Request;
use WordfenceLS\Controller_Permissions;
use WordfenceLS\Controller_Support;
use WordfenceLS\Controller_Time;

class Controller_Javascript {
	/**
	 * Returns a mapping of translation strings for the Javascript frontend to use, populated via the WordPress
	 * translation system.
	 *
	 * It would be nice to be less redundant here, but the support for that is in WP 5.0 and unavailable in our
	 * current oldest supported version.
	 *
	 * @return array
	 */
	public static function i18nStrings() {
		return array(
			'(definitely a human)' => __('(definitely a human)', 'wordfence'),
			'(probably a bot)' => __('(probably a bot)', 'wordfence'),
			'(probably a human)' => __('(probably a human)', 'wordfence'),
			'2FA' => __('2FA', 'wordfence'),
			'2FA Notifications' => __('2FA Notifications', 'wordfence'),
			'2FA Relative URL (optional)' => __('2FA Relative URL (optional)', 'wordfence'),
			'2FA Role' => __('2FA Role', 'wordfence'),
			'2FA Roles' => __('2FA Roles', 'wordfence'),
			'2FA management shortcode' => __('2FA management shortcode', 'wordfence'),
			'A reCAPTCHA score equal to or higher than this value will be considered human. Anything lower will be treated as a bot and require additional verification for login and registration.' => __('A reCAPTCHA score equal to or higher than this value will be considered human. Anything lower will be treated as a bot and require additional verification for login and registration.', 'wordfence'),
			'Allow remembering device for 30 days' => __('Allow remembering device for 30 days', 'wordfence'),
			'Allowlisted IP addresses that bypass 2FA and reCAPTCHA' => __('Allowlisted IP addresses that bypass 2FA and reCAPTCHA', 'wordfence'),
			'Allowlisted IPs must be placed on separate lines. You can specify ranges using the following formats: 127.0.0.1/24, 127.0.0.[1-100], or 127.0.0.1-127.0.1.100.' => __('Allowlisted IPs must be placed on separate lines. You can specify ranges using the following formats: 127.0.0.1/24, 127.0.0.[1-100], or 127.0.0.1-127.0.1.100.', 'wordfence'),
			'An error occurred' => __('An error occurred', 'wordfence'),
			'An error was encountered while trying to disable NTP. Please try again.' => __('An error was encountered while trying to disable NTP. Please try again.', 'wordfence'),
			'An error was encountered while trying to reset the NTP state. Please try again.' => __('An error was encountered while trying to reset the NTP state. Please try again.', 'wordfence'),
			'An error was encountered while trying to send the notification. Please try again.' => __('An error was encountered while trying to send the notification. Please try again.', 'wordfence'),
			'Cancel' => __('Cancel', 'wordfence'),
			'Cancel Changes' => __('Cancel Changes', 'wordfence'),
			'Close' => __('Close', 'wordfence'),
			'Count' => __('Count', 'wordfence'),
			'Detected IP(s)' => __('Detected IP(s)', 'wordfence'),
			'days' => __('days', 'wordfence'),
			'Delete Login Security tables and data on deactivation' => __('Delete Login Security tables and data on deactivation', 'wordfence'),
			'Disable' => __('Disable', 'wordfence'),
			'Disable XML-RPC authentication' => __('Disable XML-RPC authentication', 'wordfence'),
			'Edit trusted proxies' => __('Edit trusted proxies', 'wordfence'),
			'e.g., /my-account/' => __('e.g., /my-account/', 'wordfence'),
			'Enable reCAPTCHA on the login and user registration pages' => __('Enable reCAPTCHA on the login and user registration pages', 'wordfence'),
			'Error Disabling NTP' => __('Error Disabling NTP', 'wordfence'),
			'Error Resetting NTP' => __('Error Resetting NTP', 'wordfence'),
			'Error Resetting reCAPTCHA Statistics' => __('Error Resetting reCAPTCHA Statistics', 'wordfence'),
			'Error Saving Option' => __('Error Saving Option', 'wordfence'),
			'Error Saving Options' => __('Error Saving Options', 'wordfence'),
			'Error Sending Notification' => __('Error Sending Notification', 'wordfence'),
			'For roles that require 2FA, users will have this many days to set up 2FA. Failure to set up 2FA during this period will result in the user losing account access. This grace period will apply to new users from the time of account creation. For existing users, this grace period will apply relative to the time at which the requirement is implemented. This grace period will not automatically apply to admins and must be manually enabled for each admin user.' => __('For roles that require 2FA, users will have this many days to set up 2FA. Failure to set up 2FA during this period will result in the user losing account access. This grace period will apply to new users from the time of account creation. For existing users, this grace period will apply relative to the time at which the requirement is implemented. This grace period will not automatically apply to admins and must be manually enabled for each admin user.', 'wordfence'),
			'General' => __('General', 'wordfence'),
			'Grace Period' => __('Grace Period', 'wordfence'),
			'How to get IPs' => __('How to get IPs', 'wordfence'),
			'If enabled, users with 2FA enabled may choose to be prompted for a code only once every 30 days per device.' => __('If enabled, users with 2FA enabled may choose to be prompted for a code only once every 30 days per device.', 'wordfence'),
			'If enabled, XML-RPC calls that require authentication will also require a valid 2FA code to be appended to the password. You must choose the "Skipped" option if you use the WordPress app, the Jetpack plugin, or other services that require XML-RPC.' => __('If enabled, XML-RPC calls that require authentication will also require a valid 2FA code to be appended to the password. You must choose the "Skipped" option if you use the WordPress app, the Jetpack plugin, or other services that require XML-RPC.', 'wordfence'),
			'If enabled, all settings and 2FA records will be deleted on deactivation. If later reactivated, all users that previously had 2FA active will need to set it up again.' => __('If enabled, all settings and 2FA records will be deleted on deactivation. If later reactivated, all users that previously had 2FA active will need to set it up again.', 'wordfence'),
			'In order to use 2FA with the WooCommerce customer role, you must either enable the "WooCommerce integration" option or use the "wordfence_2fa_management" shortcode to provide customers with access to the 2FA management interface. The default interface is only available through WordPress admin pages which are not accessible to users in the customer role.' => __('In order to use 2FA with the WooCommerce customer role, you must either enable the "WooCommerce integration" option or use the "wordfence_2fa_management" shortcode to provide customers with access to the 2FA management interface. The default interface is only available through WordPress admin pages which are not accessible to users in the customer role.', 'wordfence'),
			'Learn More' => __('Learn More', 'wordfence'),
			'NTP' => __('NTP', 'wordfence'),
			'NTP is a protocol that allows for remote time synchronization. Wordfence Login Security uses this protocol to ensure that it has the most accurate time which is necessary for TOTP-based two-factor authentication.' => __('NTP is a protocol that allows for remote time synchronization. Wordfence Login Security uses this protocol to ensure that it has the most accurate time which is necessary for TOTP-based two-factor authentication.', 'wordfence'),
			'NTP is currently <strong>enabled</strong>.' => __('NTP is currently <strong>enabled</strong>.', 'wordfence'),
			'NTP is currently disabled as %d subsequent attempts have failed.' => /* translators: number of attempts */ __('NTP is currently disabled as %d subsequent attempts have failed.', 'wordfence'),
			'NTP updates are currently failing.' => __('NTP updates are currently failing.', 'wordfence'),
			'NTP was manually disabled.' => __('NTP was manually disabled.', 'wordfence'),
			'NTP will be automatically disabled after %d more attempts.' => /* translators: number of attempts */ __('NTP will be automatically disabled after %d more attempts.', 'wordfence'),
			'NTP will be automatically disabled after 1 more attempt.' => __('NTP will be automatically disabled after 1 more attempt.', 'wordfence'),
			'Note: This feature requires a free site key and secret for the <a href="https://www.google.com/recaptcha/about/" target="_blank" rel="noopener noreferrer">Google reCAPTCHA v3 Service</a>. To set up new reCAPTCHA keys, log into your Google account and go to the <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">reCAPTCHA admin page</a>.' => __('Note: This feature requires a free site key and secret for the <a href="https://www.google.com/recaptcha/about/" target="_blank" rel="noopener noreferrer">Google reCAPTCHA v3 Service</a>. To set up new reCAPTCHA keys, log into your Google account and go to the <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">reCAPTCHA admin page</a>.', 'wordfence'),
			'Notification Results' => __('Notification Results', 'wordfence'),
			'Notification Sent' => __('Notification Sent', 'wordfence'),
			'Notify' => __('Notify', 'wordfence'),
			'reCAPTCHA' => __('reCAPTCHA', 'wordfence'),
			'reCAPTCHA human/bot threshold score' => __('reCAPTCHA human/bot threshold score', 'wordfence'),
			'reCAPTCHA Score History' => __('reCAPTCHA Score History', 'wordfence'),
			'reCAPTCHA v3 does not make users solve puzzles or click a checkbox like previous versions. The only visible part is the reCAPTCHA logo. If a visitor\'s browser fails the CAPTCHA, Wordfence will send an email to the user\'s address with a link they can click to verify that they are a user of your site. You can read further details <a href="%s" target="_blank" rel="noopener noreferrer">in our documentation</a>.' => /* translators: Support URL */ __('reCAPTCHA v3 does not make users solve puzzles or click a checkbox like previous versions. The only visible part is the reCAPTCHA logo. If a visitor\'s browser fails the CAPTCHA, Wordfence will send an email to the user\'s address with a link they can click to verify that they are a user of your site. You can read further details <a href="%s" target="_blank" rel="noopener noreferrer">in our documentation</a>.', 'wordfence'),
			'reCAPTCHA v3 Secret' => __('reCAPTCHA v3 Secret', 'wordfence'),
			'reCAPTCHA v3 Site Key' => __('reCAPTCHA v3 Site Key', 'wordfence'),
			'Requests' => __('Requests', 'wordfence'),
			'Required' => __('Required', 'wordfence'),
			'Requiring 2FA for customers is not recommended as some customers may experience difficulties setting up or using two-factor authentication. Instead, using the "Optional" mode for users with the customer role is recommended which will allow customers to enable 2FA, but will not require them to do so.' => __('Requiring 2FA for customers is not recommended as some customers may experience difficulties setting up or using two-factor authentication. Instead, using the "Optional" mode for users with the customer role is recommended which will allow customers to enable 2FA, but will not require them to do so.', 'wordfence'),
			'Reset' => __('Reset', 'wordfence'),
			'Reset Score Statistics' => __('Reset Score Statistics', 'wordfence'),
			'Run reCAPTCHA in test mode' => __('Run reCAPTCHA in test mode', 'wordfence'),
			'Save' => __('Save', 'wordfence'),
			'Save Changes' => __('Save Changes', 'wordfence'),
			'Send Anyway' => __('Send Anyway', 'wordfence'),
			'Send an email to users with the selected role to notify them of the grace period for enabling 2FA. Select the desired role and optionally specify the URL to be sent in the email to setup 2FA. If left blank, the URL defaults to the standard wordpress login and Wordfence’s Two-Factor Authentication plugin page. For example, if using WooCommerce, input the relative URL of the account page.' => __('Send an email to users with the selected role to notify them of the grace period for enabling 2FA. Select the desired role and optionally specify the URL to be sent in the email to setup 2FA. If left blank, the URL defaults to the standard wordpress login and Wordfence’s Two-Factor Authentication plugin page. For example, if using WooCommerce, input the relative URL of the account page.', 'wordfence'),
			'Setting the grace period to 0 will prevent users in roles where 2FA is required, including newly created users, from logging in if they have not already enabled two-factor authentication.' => __('Setting the grace period to 0 will prevent users in roles where 2FA is required, including newly created users, from logging in if they have not already enabled two-factor authentication.', 'wordfence'),
			'Skipped' => __('Skipped', 'wordfence'),
			'Show Wordfence 2FA menu on WooCommerce Account page' => __('Show Wordfence 2FA menu on WooCommerce Account page', 'wordfence'),
			'Show last login column on WP Users page' => __('Show last login column on WP Users page', 'wordfence'),
			'The constant WORDFENCE_LS_DISABLE_NTP is defined which disables NTP entirely. Remove this constant or set it to a falsy value to enable NTP.' => __('The constant WORDFENCE_LS_DISABLE_NTP is defined which disables NTP entirely. Remove this constant or set it to a falsy value to enable NTP.', 'wordfence'),
			'These IPs (or CIDR ranges) will be ignored when determining the requesting IP via the X-Forwarded-For HTTP header. Enter one IP or CIDR range per line.' => __('These IPs (or CIDR ranges) will be ignored when determining the requesting IP via the X-Forwarded-For HTTP header. Enter one IP or CIDR range per line.', 'wordfence'),
			'Trusted Proxies' => __('Trusted Proxies', 'wordfence'),
			'Use single-column layout for WooCommerce/shortcode 2FA management interface' => __('Use single-column layout for WooCommerce/shortcode 2FA management interface', 'wordfence'),
			'When enabled, a Wordfence 2FA tab will be added to the WooCommerce account menu which will provide access for users to manage 2FA settings outside of the WordPress admin area. Testing the WooCommerce account interface after enabling this feature is recommended to ensure theme compatibility.' => __('When enabled, a Wordfence 2FA tab will be added to the WooCommerce account menu which will provide access for users to manage 2FA settings outside of the WordPress admin area. Testing the WooCommerce account interface after enabling this feature is recommended to ensure theme compatibility.', 'wordfence'),
			'When enabled, reCAPTCHA and 2FA prompt support will be added to WooCommerce login and registration forms in addition to the default WordPress forms. Testing WooCommerce forms after enabling this feature is recommended to ensure plugin compatibility.' => __('When enabled, reCAPTCHA and 2FA prompt support will be added to WooCommerce login and registration forms in addition to the default WordPress forms. Testing WooCommerce forms after enabling this feature is recommended to ensure plugin compatibility.', 'wordfence'),
			'When enabled, the "wordfence_2fa_management" shortcode may be used to provide access for users to manage 2FA settings on custom pages.' => __('When enabled, the "wordfence_2fa_management" shortcode may be used to provide access for users to manage 2FA settings on custom pages.', 'wordfence'),
			'When enabled, the 2FA management interface embedded through the WooCommerce integration or via a shortcode will use a vertical stacked layout as opposed to horizontal columns. Adjust this setting as appropriate to match your theme. This may be overridden using the "stacked" attribute for individual shortcodes.' => __('When enabled, the 2FA management interface embedded through the WooCommerce integration or via a shortcode will use a vertical stacked layout as opposed to horizontal columns. Adjust this setting as appropriate to match your theme. This may be overridden using the "stacked" attribute for individual shortcodes.', 'wordfence'),
			'When enabled, the last login timestamp will be displayed for each user on the WP Users page. When used in conjunction with reCAPTCHA, the most recent score will also be displayed for each user.' => __('When enabled, the last login timestamp will be displayed for each user on the WP Users page. When used in conjunction with reCAPTCHA, the most recent score will also be displayed for each user.', 'wordfence'),
			'While in test mode, reCAPTCHA will score login and registration requests but not actually block them. The scores will be recorded and can be used to select a human/bot threshold value.' => __('While in test mode, reCAPTCHA will score login and registration requests but not actually block them. The scores will be recorded and can be used to select a human/bot threshold value.', 'wordfence'),
			'Wordfence Login Security Installed' => __('Wordfence Login Security Installed', 'wordfence'),
			'You have just installed the Wordfence Login Security plugin. It contains a subset of the functionality found in the full Wordfence plugin: Two-factor Authentication, XML-RPC Protection and Login Page CAPTCHA.' => __('You have just installed the Wordfence Login Security plugin. It contains a subset of the functionality found in the full Wordfence plugin: Two-factor Authentication, XML-RPC Protection and Login Page CAPTCHA.', 'wordfence'),
			'If you\'re looking for a more comprehensive solution, the <a href="https://wordpress.org/plugins/wordfence/" target="_blank" rel="noopener noreferrer">full Wordfence plugin</a> includes all of the features in this plugin as well as a full-featured WordPress firewall, a security scanner, live traffic, and more. The standard installation includes a robust set of free features that can be upgraded via a Premium license key.' => __('If you\'re looking for a more comprehensive solution, the <a href="https://wordpress.org/plugins/wordfence/" target="_blank" rel="noopener noreferrer">full Wordfence plugin</a> includes all of the features in this plugin as well as a full-featured WordPress firewall, a security scanner, live traffic, and more. The standard installation includes a robust set of free features that can be upgraded via a Premium license key.', 'wordfence'),
			'Your IP with this setting' => __('Your IP with this setting', 'wordfence'),
			'WooCommerce & Custom Integrations' => __('WooCommerce & Custom Integrations', 'wordfence'),
			'WooCommerce integration' => __('WooCommerce integration', 'wordfence'),
		);
	}
	
	/**
	 * Returns an array of constants/initial state values for use on the Javascript frontend to avoid hardcoding values.
	 *
	 * @return array
	 */
	public static function jsConstants() {
		$response = array();
		
		$response['plugin'] = array(
			'ip' => array(
				'current' => Model_Request::current()->ip(),
				'preview' => Model_Request::current()->detected_ip_preview(),
			),
			'ls_from_core' => defined('WORDFENCE_LS_FROM_CORE') && WORDFENCE_LS_FROM_CORE,
			'ntp' => array(
				'constant_disabled' => Controller_Settings::shared()->is_ntp_disabled_via_constant(),
				'cron_disabled' => Controller_Settings::shared()->is_ntp_cron_disabled($failureCount),
				'cron_failure_count' => $failureCount,
				'max_failures' => Controller_Time::FAILURE_LIMIT,
			),
			'should_use_core_font_awesome' => Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles(),
			'server' => array(
				'has_woocommerce' => class_exists('woocommerce'),
			),
		);
		
		$response['roles'] = array(
			'labels' => array(
				Controller_Settings::STATE_2FA_DISABLED => __('Disabled', 'wordfence'),
				Controller_Settings::STATE_2FA_OPTIONAL => __('Optional', 'wordfence'),
				Controller_Settings::STATE_2FA_REQUIRED => __('Required', 'wordfence'),
			),
			'states' => array(
				'disabled' => Controller_Settings::STATE_2FA_DISABLED,
				'optional' => Controller_Settings::STATE_2FA_OPTIONAL,
				'required' => Controller_Settings::STATE_2FA_REQUIRED,
			),
		);
		
		$response['support'] = array(
			'url' => Controller_Support::supportURLs(),
		);
		
		$roles = new \WP_Roles();
		$options = array();
		if (is_multisite()) {
			$options[] = array(
				'role' => 'super-admin',
				'name' => 'enabled-roles.super-admin',
				'title' => __('Super Administrator', 'wordfence'),
				'editable' => true,
				'allow_disabling' => false,
				'state' => Controller_Settings::shared()->get_required_2fa_role_activation_time('super-admin') !== false ? 'required' : 'optional'
			);
		}
		
		foreach ($roles->role_objects as $name => $r) {
			/** @var \WP_Role $r */
			$options[] = array(
				'role' => $name,
				'name' => 'enabled-roles.' . $name,
				'title' => $roles->role_names[$name],
				'editable' => true,
				'allow_disabling' => (!is_multisite() && $name == 'administrator' ? false : true),
				'state' => Controller_Settings::shared()->get_required_2fa_role_activation_time($name) !== false ? 'required' : ($r->has_cap(Controller_Permissions::CAP_ACTIVATE_2FA_SELF) ? 'optional' : 'disabled')
			);
		}
		$response['options'] = array(
			'roles' => $options,
			'ip_source' => array(
				array('value' => Model_Request::IP_SOURCE_AUTOMATIC, 'label' => __('Use the most secure method to get visitor IP addresses. Prevents spoofing and works with most sites.', 'wordfence'), 'recommended' => true),
				array('value' => Model_Request::IP_SOURCE_REMOTE_ADDR, 'label' => __('Use PHP\'s built in REMOTE_ADDR and don\'t use anything else. Very secure if this is compatible with your site.', 'wordfence')),
				array('value' => Model_Request::IP_SOURCE_X_FORWARDED_FOR, 'label' => __('Use the X-Forwarded-For HTTP header. Only use if you have a front-end proxy or spoofing may result.', 'wordfence')),
				array('value' => Model_Request::IP_SOURCE_X_REAL_IP, 'label' => __('Use the X-Real-IP HTTP header. Only use if you have a front-end proxy or spoofing may result.', 'wordfence')),
			),
			'value' => self::_prefixOptions(Controller_Settings::shared()->all()),
		);
		
		return $response;
	}
	
	/**
	 * Prefixes all keys in the given options with "wfls-" to avoid name collisions with the main plugin.
	 */
	private static function _prefixOptions($options) {
		$result = array();
		foreach ($options as $key => $value) {
			$result['wfls-' . $key] = $value;
		}
		return $result;
	}
	
	/**
	 * Returns the importmap array for our bundled modules.
	 *
	 * @return array
	 */
	public static function importMap() {
		return array('imports' => array(
			'vue' => Model_Asset::js('vue.esm-browser.prod.js'),
		));
	}
}
