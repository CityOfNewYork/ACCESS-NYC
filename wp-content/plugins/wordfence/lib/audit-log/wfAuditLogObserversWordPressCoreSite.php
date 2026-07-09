<?php

abstract class wfAuditLogObserversWordPressCoreSite extends wfAuditLog {
	const SITE_DATA_EXPORTED = 'site.data.exported';
	const SITE_RECOVERY_MODE_KEY_GENERATED = 'site.recovery-mode.key-generated';
	
	const SITE_MAIL_SEND_FAILED = 'site.mail.send-failed';
	const SITE_MAIL_SENT = 'site.mail.sent';
	
	const SITE_OPTION_ACTIVE_PLUGINS = 'site.option.active-plugins';
	const SITE_OPTION_ADMIN_EMAIL = 'site.option.admin-email';
	const SITE_OPTION_ANONYMOUS_COMMENTS = 'site.option.anonymous-comments';
	const SITE_OPTION_COMMENT_MODERATION = 'site.option.comment-moderation';
	const SITE_OPTION_DEFAULT_COMMENT_STATUS = 'site.option.default-comment-status';
	const SITE_OPTION_DEFAULT_ROLE = 'site.option.default-role';
	const SITE_OPTION_HOME_URL = 'site.option.home-url';
	const SITE_OPTION_SITE_URL = 'site.option.site-url';
	const SITE_OPTION_STYLESHEET = 'site.option.stylesheet';
	const SITE_OPTION_TEMPLATE = 'site.option.template';
	const SITE_OPTION_USER_REGISTRATION = 'site.option.user-registration';
	
	const SITE_PERMISSIONS_ROLE_CAPABILITIES = 'site.permissions.role-capabilities';
	const SITE_PERMISSIONS_ADMIN_PAGE_DENIED = 'site.permissions.admin-page.denied';
	
	const SITE_PLUGIN_INSTALLED = 'site.plugin.installed';
	const SITE_PLUGIN_DELETED = 'site.plugin.deleted';
	const SITE_PLUGIN_ACTIVATED = 'site.plugin.activated';
	const SITE_PLUGIN_DEACTIVATED = 'site.plugin.deactivated';
	
	const SITE_THEME_INSTALLED = 'site.theme.installed';
	const SITE_THEME_DELETED = 'site.theme.deleted';
	const SITE_THEME_SWITCHED = 'site.theme.switched';
	const SITE_THEME_CUSTOMIZED = 'site.theme.customized';
	const SITE_THEME_SIDEBAR_UPDATED = 'site.theme.sidebar.updated';
	
	const SITE_UPDATE_AUTOMATIC_COMPLETED = 'site.update.automatic-completed';
	const SITE_UPDATE_CORE = 'site.update.core';
	const SITE_UPDATE_PLUGIN = 'site.update.plugin';
	const SITE_UPDATE_THEME = 'site.update.theme';
	
	public static function immediateSendEvents() {
		return array(
			self::SITE_OPTION_ACTIVE_PLUGINS,
			self::SITE_OPTION_DEFAULT_ROLE,
			self::SITE_PERMISSIONS_ROLE_CAPABILITIES,
			self::SITE_PLUGIN_ACTIVATED,
			self::SITE_THEME_INSTALLED,
		);
	}
	
	public static function eventCategories() {
		return array(
			wfAuditLog::AUDIT_LOG_CATEGORY_SITE_SETTINGS => array(
				self::SITE_DATA_EXPORTED,
				
				self::SITE_OPTION_ADMIN_EMAIL,
				self::SITE_OPTION_ANONYMOUS_COMMENTS,
				self::SITE_OPTION_COMMENT_MODERATION,
				self::SITE_OPTION_DEFAULT_COMMENT_STATUS,
				self::SITE_OPTION_DEFAULT_ROLE,
				self::SITE_OPTION_HOME_URL,
				self::SITE_OPTION_SITE_URL,
				self::SITE_OPTION_USER_REGISTRATION,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_AUTHENTICATION => array(
				self::SITE_RECOVERY_MODE_KEY_GENERATED,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_CONTENT => array(
				self::SITE_MAIL_SEND_FAILED,
				self::SITE_MAIL_SENT,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_USER_PERMISSIONS => array(
				self::SITE_PERMISSIONS_ROLE_CAPABILITIES,
				self::SITE_PERMISSIONS_ADMIN_PAGE_DENIED,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_PLUGINS_THEMES_UPDATES => array(
				self::SITE_OPTION_ACTIVE_PLUGINS,
				self::SITE_OPTION_STYLESHEET,
				self::SITE_OPTION_TEMPLATE,
				
				self::SITE_PLUGIN_INSTALLED,
				self::SITE_PLUGIN_DELETED,
				self::SITE_PLUGIN_ACTIVATED,
				self::SITE_PLUGIN_DEACTIVATED,
				
				self::SITE_THEME_INSTALLED,
				self::SITE_THEME_DELETED,
				self::SITE_THEME_SWITCHED,
				self::SITE_THEME_CUSTOMIZED,
				self::SITE_THEME_SIDEBAR_UPDATED,
				
				self::SITE_UPDATE_AUTOMATIC_COMPLETED,
				self::SITE_UPDATE_CORE,
				self::SITE_UPDATE_PLUGIN,
				self::SITE_UPDATE_THEME,
			),
		);
	}
	
	public static function eventNames() {
		return array(
			self::SITE_DATA_EXPORTED => __('Site Data Exported', 'wordfence'),
			self::SITE_RECOVERY_MODE_KEY_GENERATED => __('Recovery Key Generated', 'wordfence'),
			
			self::SITE_MAIL_SEND_FAILED => __('Mail Send Failed', 'wordfence'),
			self::SITE_MAIL_SENT => __('Mail Sent', 'wordfence'),
			
			self::SITE_OPTION_ACTIVE_PLUGINS => __('Active Plugins Option Changed', 'wordfence'),
			self::SITE_OPTION_ADMIN_EMAIL => __('Admin Email Option Changed', 'wordfence'),
			self::SITE_OPTION_ANONYMOUS_COMMENTS => __('Anonymous Comments Allowed Option Changed', 'wordfence'),
			self::SITE_OPTION_COMMENT_MODERATION => __('Comment Moderation Default Option Changed', 'wordfence'),
			self::SITE_OPTION_DEFAULT_COMMENT_STATUS => __('Default Comment Status Option Changed', 'wordfence'),
			self::SITE_OPTION_DEFAULT_ROLE => __('Default User Role Option Changed', 'wordfence'),
			self::SITE_OPTION_HOME_URL => __('Home URL Option Changed', 'wordfence'),
			self::SITE_OPTION_SITE_URL => __('Site URL Option Changed', 'wordfence'),
			self::SITE_OPTION_STYLESHEET => __('Child Theme Option Changed', 'wordfence'),
			self::SITE_OPTION_TEMPLATE => __('Parent Theme Option Changed', 'wordfence'),
			self::SITE_OPTION_USER_REGISTRATION => __('User Registration Permission Option Changed', 'wordfence'),
			
			self::SITE_PERMISSIONS_ROLE_CAPABILITIES => __('Role Capabilities Changed', 'wordfence'),
			self::SITE_PERMISSIONS_ADMIN_PAGE_DENIED => __('Admin Page View Denied', 'wordfence'),
			
			self::SITE_PLUGIN_INSTALLED => __('Plugin Installed', 'wordfence'),
			self::SITE_PLUGIN_DELETED => __('Plugin Deleted', 'wordfence'),
			self::SITE_PLUGIN_ACTIVATED => __('Plugin Activated', 'wordfence'),
			self::SITE_PLUGIN_DEACTIVATED => __('Plugin Deactivated', 'wordfence'),
			
			self::SITE_THEME_INSTALLED => __('Theme Installed', 'wordfence'),
			self::SITE_THEME_DELETED => __('Theme Deleted', 'wordfence'),
			self::SITE_THEME_SWITCHED => __('Theme Switched', 'wordfence'),
			self::SITE_THEME_CUSTOMIZED => __('Theme Customized', 'wordfence'),
			self::SITE_THEME_SIDEBAR_UPDATED => __('Theme Sidebar Updated', 'wordfence'),
			
			self::SITE_UPDATE_AUTOMATIC_COMPLETED => __('Automatic Updates Completed', 'wordfence'),
			self::SITE_UPDATE_CORE => __('Core Update Completed', 'wordfence'),
			self::SITE_UPDATE_PLUGIN => __('Plugin Update Completed', 'wordfence'),
			self::SITE_UPDATE_THEME => __('Theme Update Completed', 'wordfence'),
		);
	}
	
	public static function eventRateLimiters() {
		return array(
			self::SITE_PERMISSIONS_ROLE_CAPABILITIES => function($auditLog, $payload) {
				$hash = self::_normalizedPayloadHash($payload);
				if (self::_rateLimiterCheck(self::SITE_PERMISSIONS_ROLE_CAPABILITIES, $hash)) {
					self::_rateLimiterConsume(self::SITE_PERMISSIONS_ROLE_CAPABILITIES, $hash);
					return true;
				}
				return false;
			},
		);
	}
	
	/**
	 * Registers the observers for this class's chunk of functionality.
	 * 
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerObservers($auditLog) {
		$auditLog->_addObserver('export_wp', function($args) use ($auditLog) { //Exported WP data
			$auditLog->_recordAction(self::SITE_DATA_EXPORTED, array('settings' => $args));
		});
		
		if ($auditLog->mode() == self::AUDIT_LOG_MODE_ALL) {
			$auditLog->_addObserver('wp_mail_succeeded', function($args) use ($auditLog) { //Mail sent
				if (isset($args['to']) && !is_array($args['to'])) { $args['to'] = array($args['to']); } //$args['to'] is supposed to be an array per the docs, but some plugins call it incorrectly
				$payload = array(
					'to_count' => isset($args['to']) ? count($args['to']) : 0,
					'subject' => isset($args['subject']) ? $args['subject'] : null,
					'attachment_count' => isset($args['attachments']) ? count($args['attachments']) : 0,
				);
				$auditLog->_recordAction(self::SITE_MAIL_SENT, $payload);
			});
			
			$auditLog->_addObserver('wp_mail_failed', function($error /** @var WP_Error $error */) use ($auditLog) { //Mail failed sending
				$args = $error->get_error_data();
				if (isset($args['to']) && !is_array($args['to'])) { $args['to'] = array($args['to']); } //$args['to'] is supposed to be an array per the docs, but some plugins call it incorrectly
				$payload = array(
					'to_count' => isset($args['to']) ? count($args['to']) : 0,
					'subject' => isset($args['subject']) ? $args['subject'] : null,
					'attachment_count' => isset($args['attachments']) ? count($args['attachments']) : 0,
					'error' => $error->get_error_message(),
				);
				$auditLog->_recordAction(self::SITE_MAIL_SEND_FAILED, $payload);
			});
		}
		
		$auditLog->_addObserver('update_option_comment_registration', function($old_value, $value, $option) use ($auditLog) { //Comment registration required enabled/disabled
			$auditLog->_recordAction(self::SITE_OPTION_ANONYMOUS_COMMENTS, array('state' => wfUtils::truthyToBoolean($value)));
		});
		
		$auditLog->_addObserver('update_option_default_role', function($old_value, $value, $option) use ($auditLog) { //Default role on user registration
			$auditLog->_recordAction(self::SITE_OPTION_DEFAULT_ROLE, array('state' => $value));
		});
		
		$auditLog->_addObserver('update_option_users_can_register', function($old_value, $value, $option) use ($auditLog) { //User registration allowed
			$auditLog->_recordAction(self::SITE_OPTION_USER_REGISTRATION, array('state' => wfUtils::truthyToBoolean($value)));
		});
		
		$auditLog->_addObserver('update_option_siteurl', function($old_value, $value, $option) use ($auditLog) { //Site URL
			$auditLog->_recordAction(self::SITE_OPTION_SITE_URL, array('url' => $value));
		});
		
		$auditLog->_addObserver('update_option_home', function($old_value, $value, $option) use ($auditLog) { //Home URL
			$auditLog->_recordAction(self::SITE_OPTION_HOME_URL, array('url' => $value));
		});
		
		$auditLog->_addObserver('update_option_admin_email', function($old_value, $value, $option) use ($auditLog) { //Admin email
			$auditLog->_recordAction(self::SITE_OPTION_ADMIN_EMAIL, array('email' => $value));
		});
		
		$auditLog->_addObserver('update_option_default_comment_status', function($old_value, $value, $option) use ($auditLog) { //Default comment status
			$auditLog->_recordAction(self::SITE_OPTION_DEFAULT_COMMENT_STATUS, array('status' => $value));
		});
		
		$auditLog->_addObserver('update_option_comment_moderation', function($old_value, $value, $option) use ($auditLog) { //Comment moderation enabled/disabled
			$auditLog->_recordAction(self::SITE_OPTION_COMMENT_MODERATION, array('state' => wfUtils::truthyToBoolean($value)));
		});
		
		$auditLog->_addObserver('update_option_template', function($old_value, $value, $option) use ($auditLog) { //Theme selected, this is the parent theme value
			$auditLog->_recordAction(self::SITE_OPTION_TEMPLATE, array('theme' => $value));
		});
		
		$auditLog->_addObserver('update_option_stylesheet', function($old_value, $value, $option) use ($auditLog) { //Theme selected, this is the child theme value
			$auditLog->_recordAction(self::SITE_OPTION_STYLESHEET, array('theme' => $value));
		});
		
		$auditLog->_addObserver('admin_page_access_denied', function() use ($auditLog) { //Admin page view denied
			$auditLog->_recordAction(self::SITE_PERMISSIONS_ADMIN_PAGE_DENIED, array());
		});
		
		$auditLog->_addObserver('activated_plugin', function($relative_path, $network_wide) use ($auditLog) { //Plugin activated
			$path = trailingslashit(WP_PLUGIN_DIR) . $relative_path;
			if (is_readable($path)) {
				$plugin = $auditLog->_getPlugin($path);
				if ($plugin) {
					$auditLog->_recordAction(self::SITE_PLUGIN_ACTIVATED, array('plugin' => $plugin, 'network' => $network_wide));
				}
			}
		});
		
		$auditLog->_addObserver('deactivated_plugin', function($relative_path, $network_wide) use ($auditLog) { //Plugin deactivated
			$path = trailingslashit(WP_PLUGIN_DIR) . $relative_path;
			if (is_readable($path)) {
				$plugin = $auditLog->_getPlugin($path);
				if ($plugin) {
					$auditLog->_recordAction(self::SITE_PLUGIN_DEACTIVATED, array('plugin' => $plugin, 'network' => $network_wide));
				}
			}
		});
		
		$auditLog->_addObserver('deleted_plugin', function($relative_path, $deleted) use ($auditLog) { //Plugin deleted
			if ($deleted && $auditLog->_hasState('delete_plugin.plugin')) {
				$auditLog->_recordAction(self::SITE_PLUGIN_DELETED, array('plugin' => $auditLog->_getState('delete_plugin.plugin')));
			}
		});
		
		$auditLog->_addObserver('switch_theme', function($new_name, $new_theme, $old_theme) use ($auditLog) { //Theme switched
			$auditLog->_recordAction(self::SITE_THEME_SWITCHED, array('from' => $auditLog->_getTheme($old_theme), 'to' => $auditLog->_getTheme($new_theme)));
		});
		
		$auditLog->_addObserver('deleted_theme', function($stylesheet, $deleted) use ($auditLog) { //Theme deleted
			if ($deleted && $auditLog->_hasState('delete_theme.theme')) {
				$auditLog->_recordAction(self::SITE_THEME_DELETED, array('theme' => $auditLog->_getState('delete_theme.theme')));
			}
		});
		
		$auditLog->_addObserver('customize_save_after', function($manager /** @var WP_Customize_Manager $manager */) use ($auditLog) { //Theme customized
			$auditLog->_recordAction(self::SITE_THEME_CUSTOMIZED, array('theme' => $auditLog->_getTheme($manager->theme())));
		});
		
		$auditLog->_addObserver('upgrader_process_complete', function($upgrader, $hook_extra) use ($auditLog) { //Updates completed
			$afterVersions = $auditLog->_installedVersions();
			
			//Core
			if (is_array($hook_extra) && isset($hook_extra['type']) && $hook_extra['type'] == 'core' && isset($hook_extra['action']) && $hook_extra['action'] == 'update') {
				$payload = array(
					'core' => $afterVersions['core'],
				);
				$payload['previous_version'] = self::$initialCoreVersion;
				$auditLog->_recordAction(self::SITE_UPDATE_CORE, $payload);
			}
			
			//Plugins/themes
			if ($auditLog->_hasState('upgrader_post_install.pending', 0)) {
				$pending = $auditLog->_getState('upgrader_post_install.pending', 0);
				
				foreach ($pending as $p) {
					if ($p['action'] == self::SITE_PLUGIN_INSTALLED || $p['action'] == self::SITE_UPDATE_PLUGIN) {
						$relativePath = preg_replace('/^' . preg_quote(WP_PLUGIN_DIR, '/') . '/', '', $p['path']);
						if (!(validate_file($relativePath) === 0 //this conditional matches the plugin loader's requirements
							&& preg_match('/\.php$/i', $relativePath)
							&& file_exists(WP_PLUGIN_DIR . '/' . $relativePath)
							&& is_readable($p['path']))) {
							continue;
						}
						
						$plugin = $auditLog->_getPlugin($p['path']);
						if ($plugin) {
							$auditLog->_recordAction($p['action'], array('plugin' => $plugin));
						}
					}
					else if ($p['action'] == self::SITE_THEME_INSTALLED || $p['action'] == self::SITE_UPDATE_THEME) {
						if (!is_readable($p['path'])) {
							continue;
						}
						
						$theme = $auditLog->_getTheme($p['path']);
						if ($theme) {
							$auditLog->_recordAction($p['action'], array('theme' => $theme));
						}
					}
				}
				
				$auditLog->_trackState('upgrader_post_install.pending', array(), 0);
			}
		});
		
		$auditLog->_addObserver('automatic_updates_complete', function($update_results) use ($auditLog) { //Automatic updates complete
			$auditLog->_recordAction(self::SITE_UPDATE_AUTOMATIC_COMPLETED, array('results' => $update_results));
		});
		
		$auditLog->_addObserver('generate_recovery_mode_key', function($token, $key) use ($auditLog) { //Recovery key generated
			$auditLog->_recordAction(self::SITE_RECOVERY_MODE_KEY_GENERATED, array());
		});
	}
	
	/**
	 * Registers the data gatherers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerDataGatherers($auditLog) {
		$auditLog->_addObserver('update_option', function($option, $old_value, $value) use ($auditLog) { //User role capabilities changed
			if (preg_match('/user_roles$/i', $option)) { //For some reason this option is stored prefixed inside a table that is already prefixed on multisite, so we have to treat it special
				if (!$auditLog->_hasState('update_option_wp_user_roles.old', $auditLog->_extractMultisiteID($option, 'user_roles'))) {
					$auditLog->_trackState('update_option_wp_user_roles.old', $old_value, $auditLog->_extractMultisiteID($option, 'user_roles'));
				}
				
				$auditLog->_trackState('update_option_wp_user_roles.new', $value, $auditLog->_extractMultisiteID($option, 'user_roles'));
				
				$auditLog->_needsDestruct();
			}
		});
		
		$auditLog->_addObserver('update_option_active_plugins', function($old_value, $value, $option) use ($auditLog) { //Active plugins changed
			if (!$auditLog->_hasState('update_option_active_plugins.old', get_current_blog_id())) {
				$auditLog->_trackState('update_option_active_plugins.old', $old_value, get_current_blog_id());
			}
			
			$auditLog->_trackState('update_option_active_plugins.new', $value, get_current_blog_id());
			
			$auditLog->_needsDestruct();
		});
		
		$auditLog->_addObserver('delete_plugin', function($relative_path) use ($auditLog) { //Plugin will be deleted
			$path = trailingslashit(WP_PLUGIN_DIR) . $relative_path;
			if (is_readable($path)) {
				$plugin = $auditLog->_getPlugin($path);
				if ($plugin) {
					$auditLog->_trackState('delete_plugin.plugin', $plugin);
				}
			}
		});
		
		$auditLog->_addObserver('delete_theme', function($stylesheet) use ($auditLog) { //Theme will be deleted
			$theme = $auditLog->_getTheme(wp_get_theme($stylesheet));
			if ($theme) {
				$auditLog->_trackState('delete_theme.theme', $theme);
			}
		});
		
		$auditLog->_addObserver('upgrader_pre_install', function($response, $hook_extra) use ($auditLog) { //Plugin/theme/core will be installed/updated, capture initial versions
			if (!$auditLog->_hasState('upgrader_pre_install.versions', 0)) {
				$auditLog->_trackState('upgrader_pre_install.versions', $auditLog->_installedVersions(), 0);
			}
		}, 'filter');
		
		$auditLog->_addObserver('upgrader_post_install', function($response, $hook_extra, $result) use ($auditLog) { //Plugin/theme installed/updated
			if ($response && !is_wp_error($result)) {
				$pending = array();
				if ($auditLog->_hasState('upgrader_post_install.pending', 0)) {
					$pending = $auditLog->_getState('upgrader_post_install.pending', 0);
				}
				
				/*
				 * $hook_extra install example:
				 * 
				 * array (
				 *	  'type' => 'plugin',
				 *	  'action' => 'install',
				 *	)
				 * 
				 * 
				 * $hook_extra update example:
				 * 
				 * array (
				 *	  'plugin' => 'wordfence/wordfence.php',
				 *	  'temp_backup' => 
				 *	  array (
				 *		'slug' => 'wordfence',
				 *		'src' => '/path/to/wp-content/plugins',
				 *		'dir' => 'plugins',
				 *	  ),
				 *	)
				 */
				
				/*
				 * $result example:
				 * 
				 * array (
				 *	  'source' => '/path/to/wp-content/upgrade/wordfence.8.0.0/wordfence/',
				 *	  'source_files' => 
				 *	  array (
				 *		0 => 'LICENSE.txt',
				 *		1 => 'readme.txt',
				 *		2 => 'wordfence.php',
				 *		3 => ...
				 *	  ),
				 *	  'destination' => '/path/to/wp-content/plugins/wordfence/',
				 *	  'destination_name' => 'wordfence',
				 *	  'local_destination' => '/path/to/wp-content/plugins',
				 *	  'remote_destination' => '/path/to/plugins/wordfence/',
				 *	  'clear_destination' => false,
				 *	)
				 */
				
				if (isset($hook_extra['action']) && isset($hook_extra['type']) && isset($result['source']) && isset($result['destination'])) { //Install
					if ($hook_extra['action'] == 'install') {
						if ($hook_extra['type'] == 'plugin') {
							$path = $auditLog->_resolvePlugin(untrailingslashit($result['destination']));
							if ($path) {
								$pending[] = array('action' => self::SITE_PLUGIN_INSTALLED, 'path' => $path);
							}
						}
						else if ($hook_extra['type'] == 'theme') {
							$path = $result['destination'];
							$pending[] = array('action' => self::SITE_THEME_INSTALLED, 'path' => $path); //Can't record here since version data hasn't refreshed yet
						}
					}
				}
				else if (isset($hook_extra['plugin']) && isset($result['source']) && isset($result['destination'])) { //Plugin update
					$path = $auditLog->_resolvePlugin(trailingslashit(WP_PLUGIN_DIR) . $hook_extra['plugin']);
					if ($path) {
						$pending[] = array('action' => self::SITE_UPDATE_PLUGIN, 'path' => $path);
					}
				}
				else if (isset($hook_extra['theme']) && isset($result['source']) && isset($result['destination'])) { //Theme update
					$path = trailingslashit(get_theme_root()) . $hook_extra['theme'];
					$pending[] = array('action' => self::SITE_UPDATE_THEME, 'path' => $path);
				}
				
				$auditLog->_trackState('upgrader_post_install.pending', $pending, 0);
			}
			
			return $response;
		}, 'filter');
	}
	
	/**
	 * Registers the coalescers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerCoalescers($auditLog) {
		$auditLog->_addCoalescer(function() use ($auditLog) { //Role capabilities changed
			$old = $auditLog->_getAllStates('update_option_wp_user_roles.old');
			if (!count($old)) {
				return;
			}
			
			if (count($old) > 1) {
				$payload = array();
				foreach ($old as $blog_id => $o) {
					$new = $auditLog->_getState('update_option_wp_user_roles.new', $blog_id);
					$diff = wfUtils::array_diff($o, $new);
					if (!empty($diff['added']) || !empty($diff['removed'])) {
						$payload[] = array('capabilities' => $new, 'diff' => $diff, 'multisite_blog_id' => $blog_id);
					}
				}
				if (count($payload)) {
					$auditLog->_recordAction(self::SITE_PERMISSIONS_ROLE_CAPABILITIES, array('changes' => $payload));
				}
			}
			else {
				$blog_id = wfUtils::array_key_first($old);
				$old = $old[$blog_id];
				$new = $auditLog->_getState('update_option_wp_user_roles.new', $blog_id);
				$diff = wfUtils::array_diff($old, $new);
				if (!empty($diff['added']) || !empty($diff['removed'])) {
					$auditLog->_recordAction(self::SITE_PERMISSIONS_ROLE_CAPABILITIES, array('capabilities' => $new, 'diff' => $diff));
				}
			}
		});
		
		$auditLog->_addCoalescer(function() use ($auditLog) { //Active plugins changed
			$old = $auditLog->_getAllStates('update_option_active_plugins.old');
			if (!count($old)) {
				return;
			}
			
			if (count($old) > 1) {
				$payload = array();
				foreach ($old as $blog_id => $o) {
					$new = $auditLog->_getState('update_option_active_plugins.new', $blog_id);
					$diff = wfUtils::array_diff($o, $new);
					if (!empty($diff['added']) || !empty($diff['removed'])) {
						$payload[] = array('plugins' => $new, 'diff' => $diff, 'multisite_blog_id' => $blog_id);
					}
				}
				if (count($payload)) {
					$auditLog->_recordAction(self::SITE_OPTION_ACTIVE_PLUGINS, array('changes' => $payload));
				}
			}
			else {
				$blog_id = wfUtils::array_key_first($old);
				$old = $old[$blog_id];
				$new = $auditLog->_getState('update_option_active_plugins.new', $blog_id);
				$diff = wfUtils::array_diff($old, $new);
				if (!empty($diff['added']) || !empty($diff['removed'])) {
					$auditLog->_recordAction(self::SITE_OPTION_ACTIVE_PLUGINS, array('plugins' => $new, 'diff' => $diff));
				}
			}
		});
	}
}