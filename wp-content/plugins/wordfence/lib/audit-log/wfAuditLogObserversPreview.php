<?php

/**
 * wfAuditLogObserversPreview is a special subset of observers that is only registered when the audit log is in preview 
 * mode. It does not actually record and send any events to Wordfence Central due to the audit log being disabled but 
 * instead updates the local-only recent events list that is shown within the plugin UI. The data recorded is only a 
 * low-overhead sampling of the possible events to provide a preview of the feature.
 */
abstract class wfAuditLogObserversPreview extends wfAuditLog {
	/**
	 * Registers the observers for this class's chunk of functionality.
	 * 
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerObservers($auditLog) {
		$auditLog->_addObserver('user_register', function() use ($auditLog) { //User created
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreUser::USER_CREATED);
		});
		
		$auditLog->_addObserver('deleted_user', function() use ($auditLog) { //User deleted
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreUser::USER_DELETED);
		});
		
		$auditLog->_addObserver('wp_login', function() use ($auditLog) { //User logged in
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreUser::USER_LOGGED_IN);
		});
		
		$auditLog->_addObserver('after_password_reset', function() use ($auditLog) { //User password reset
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreUser::USER_PASSWORD_RESET);
		});
		
		$auditLog->_addObserver('set_auth_cookie', function() use ($auditLog) { //Auth cookie set
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreUser::USER_AUTH_COOKIE_SET);
		});
		
		$auditLog->_addObserver('add_user_role', function() use ($auditLog) { //User role assigned
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreUser::USER_ROLE_ADDED);
		});
		
		$auditLog->_addObserver('wp_create_application_password', function() use ($auditLog) { //User application password created
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreUser::USER_APP_PASSWORD_CREATED);
		});
		
		$auditLog->_addObserver('export_wp', function() use ($auditLog) { //Exported WP data
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_DATA_EXPORTED);
		});
		
		$auditLog->_addObserver('update_option_default_role', function() use ($auditLog) { //Default role on user registration
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_OPTION_DEFAULT_ROLE);
		});
		
		$auditLog->_addObserver('update_option_users_can_register', function() use ($auditLog) { //User registration allowed
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_OPTION_USER_REGISTRATION);
		});
		
		$auditLog->_addObserver('update_option_siteurl', function() use ($auditLog) { //Site URL
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_OPTION_SITE_URL);
		});
		
		$auditLog->_addObserver('update_option_home', function() use ($auditLog) { //Home URL
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_OPTION_HOME_URL);
		});
		
		$auditLog->_addObserver('update_option_admin_email', function() use ($auditLog) { //Admin email
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_OPTION_ADMIN_EMAIL);
		});
		
		$auditLog->_addObserver('update_option_default_comment_status', function() use ($auditLog) { //Default comment status
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_OPTION_DEFAULT_COMMENT_STATUS);
		});
		
		$auditLog->_addObserver('update_option_template', function() use ($auditLog) { //Theme selected, this is the parent theme value
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_OPTION_TEMPLATE);
		});
		
		$auditLog->_addObserver('update_option_stylesheet', function() use ($auditLog) { //Theme selected, this is the child theme value
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_OPTION_STYLESHEET);
		});
		
		$auditLog->_addObserver('upgrader_post_install', function($response, $hook_extra, $result) use ($auditLog) { //Plugin/theme installed/updated
			if ($response && !is_wp_error($result)) {
				//Same flow as wfAuditLogObserversWordPressCoreSite->upgrader_post_install handler, which contains a data structure reference
				if (isset($hook_extra['action']) && isset($hook_extra['type']) && isset($result['source']) && isset($result['destination'])) { //Install
					if ($hook_extra['action'] == 'install') {
						if ($hook_extra['type'] == 'plugin') {
							self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_PLUGIN_INSTALLED);
						}
						else if ($hook_extra['type'] == 'theme') {
							self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_THEME_INSTALLED);
						}
					}
				}
				else if (isset($hook_extra['plugin']) && isset($result['source']) && isset($result['destination'])) { //Plugin update
					self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_UPDATE_PLUGIN);
				}
				else if (isset($hook_extra['theme']) && isset($result['source']) && isset($result['destination'])) { //Theme update
					self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_UPDATE_THEME);
				}
			}
			
			return $response;
		}, 'filter');
		
		$auditLog->_addObserver('activated_plugin', function() use ($auditLog) { //Plugin activated
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_PLUGIN_ACTIVATED);
		});
		
		$auditLog->_addObserver('deactivated_plugin', function() use ($auditLog) { //Plugin deactivated
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_PLUGIN_DEACTIVATED);
		});
		
		$auditLog->_addObserver('deleted_plugin', function() use ($auditLog) { //Plugin deleted
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_PLUGIN_DELETED);
		});
		
		$auditLog->_addObserver('switch_theme', function() use ($auditLog) { //Theme switched
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_THEME_SWITCHED);
		});
		
		$auditLog->_addObserver('deleted_theme', function() use ($auditLog) { //Theme deleted
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_THEME_DELETED);
		});
		
		$auditLog->_addObserver('customize_save_after', function() use ($auditLog) { //Theme customized
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_THEME_CUSTOMIZED);
		});
		
		$auditLog->_addObserver('upgrader_process_complete', function($upgrader, $hook_extra) use ($auditLog) { //Core updated
			if (is_array($hook_extra) && isset($hook_extra['type']) && $hook_extra['type'] == 'core' && isset($hook_extra['action']) && $hook_extra['action'] == 'update') {
				self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_UPDATE_CORE);
			}
		});
		
		$auditLog->_addObserver('automatic_updates_complete', function() use ($auditLog) { //Automatic updates complete
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_UPDATE_AUTOMATIC_COMPLETED);
		});
		
		$auditLog->_addObserver('generate_recovery_mode_key', function() use ($auditLog) { //Recovery key generated
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordPressCoreSite::SITE_RECOVERY_MODE_KEY_GENERATED);
		});
		
		$auditLog->_addObserver('wordfence_ls_2fa_deactivated', function() use ($auditLog) { //2FA deactivated on a user
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_LS_2FA_DEACTIVATED);
		});
		
		$auditLog->_addObserver('wordfence_ls_2fa_activated', function() use ($auditLog) { //2FA activated on a user
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_LS_2FA_ACTIVATED);
		});
		
		$auditLog->_addObserver('wordfence_waf_mode', function() use ($auditLog) { //WAF mode setting changed
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_WAF_MODE_CHANGED);
		});
		
		$auditLog->_addObserver('wordfence_waf_changed_rule_status', function() use ($auditLog) { //WAF rule mode(s) changed
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_WAF_RULE_STATUS_CHANGED);
		});
		
		$auditLog->_addObserver('wordfence_waf_changed_protection_level', function() use ($auditLog) { //WAF protection level changed
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_WAF_PROTECTION_LEVEL_CHANGED);
		});
		
		$auditLog->_addObserver('wordfence_waf_toggled_blocklist', function() use ($auditLog) { //WAF blocklist toggled on/off
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_WAF_BLOCKLIST_TOGGLED);
		});
		
		$auditLog->_addObserver('wordfence_updated_country_blocking', function() use ($auditLog) { //Country block changed
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_BLOCKING_COUNTRY_UPDATED);
		});
		
		$auditLog->_addObserver('wordfence_created_ip_pattern_block', function() use ($auditLog) { //IP or Pattern block created manually
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_BLOCKING_IP_PATTERN_CREATED);
		});
		
		$auditLog->_addObserver('wordfence_deleted_block', function() use ($auditLog) { //Block deleted manually
			self::_recordLocalEvent($auditLog, wfAuditLogObserversWordfence::WORDFENCE_BLOCKING_DELETED);
		});
	}
	
	/**
	 * Queues an audit event for saving to the local audit log preview.
	 * 
	 * @param wfAuditLog $auditLog
	 * @param string $type
	 * @param int|null $timestamp
	 */
	private static function _recordLocalEvent($auditLog, $type, $timestamp = null) {
		if ($timestamp === null) {
			$timestamp = time();
		}
		
		$recentEvents = $auditLog->_getState('disabledAuditLogRecentEvents', 0);
		if (empty($recentEvents)) {
			$recentEvents = array();
		}
		
		array_unshift($recentEvents, array($type, $timestamp));
		$auditLog->_trackState('disabledAuditLogRecentEvents', $recentEvents, 0);
		
		if (!$auditLog->_getState('disabledAuditLogDestructRegistered', 0)) {
			register_shutdown_function(function($auditLog) { self::_recentEventsLastAction($auditLog); }, $auditLog); //Wrapped in a closure because `register_shutdown_function` can't handle private static functions directly
			$auditLog->_trackState('disabledAuditLogDestructRegistered', true, 0);
		}
	}
	
	/**
	 * Performed as a shutdown handler to save the recent events list.
	 * 
	 * @param wfAuditLog $auditLog
	 */
	private static function _recentEventsLastAction($auditLog) {
		global $wpdb;
		$suppressed = $wpdb->suppress_errors(!(defined('WFWAF_DEBUG') && WFWAF_DEBUG));
		
		$recentEvents = $auditLog->_getState('disabledAuditLogRecentEvents', 0);
		$auditLog->_updateAuditPreview(array($recentEvents));
		$auditLog->_trackState('disabledAuditLogRecentEvents', array(), 0);
		
		$wpdb->suppress_errors($suppressed);
	}
}