<?php

abstract class wfAuditLogObserversWordPressCoreUser extends wfAuditLog {
	const USER_CREATED = 'user.account.created';
	const USER_DELETED = 'user.account.deleted';
	const USER_UPDATED = 'user.account.updated';
	
	const USER_APP_PASSWORD_CREATED = 'user.permissions.app-password.created';
	const USER_APP_PASSWORD_DELETED = 'user.permissions.app-password.deleted';
	const USER_APP_PASSWORD_ACCEPTED = 'user.auth.app-password.accepted';
	
	const USER_LOGGED_IN = 'user.auth.logged-in';
	const USER_LOGGED_OUT = 'user.auth.logged-out';
	const USER_AUTH_COOKIE_SET = 'user.auth.cookie-set';
	const USER_PASSWORD_RESET = 'user.auth.password-reset';
	
	const USER_ROLE_ADDED = 'user.permissions.role-added';
	const USER_ROLE_REMOVED = 'user.permissions.role-removed';
	const USER_META_CAPABILITIES = 'user.meta.capabilities';
	const USER_META_LEVEL = 'user.meta.level';
	
	const USER_STATUS_HAM = 'user.status.ham';
	const USER_STATUS_SPAM = 'user.status.spam';
	
	protected static $initialUserID = 0;
	
	public static function immediateSendEvents() {
		return array();
	}
	
	public static function eventCategories() {
		return array(
			wfAuditLog::AUDIT_LOG_CATEGORY_USER_PERMISSIONS => array(
				self::USER_CREATED,
				self::USER_DELETED,
				self::USER_UPDATED,
				
				self::USER_ROLE_ADDED,
				self::USER_ROLE_REMOVED,
				self::USER_META_CAPABILITIES,
				self::USER_META_LEVEL,
				
				self::USER_STATUS_HAM,
				self::USER_STATUS_SPAM,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_AUTHENTICATION => array(
				self::USER_APP_PASSWORD_CREATED,
				self::USER_APP_PASSWORD_DELETED,
				self::USER_APP_PASSWORD_ACCEPTED,
				
				self::USER_LOGGED_IN,
				self::USER_LOGGED_OUT,
				self::USER_AUTH_COOKIE_SET,
				self::USER_PASSWORD_RESET,
			),
		);
	}
	
	public static function eventNames() {
		return array(
			self::USER_CREATED => __('User Created', 'wordfence'),
			self::USER_DELETED => __('User Deleted', 'wordfence'),
			self::USER_UPDATED => __('User Updated', 'wordfence'),
			
			self::USER_APP_PASSWORD_CREATED => __('App Password Created', 'wordfence'),
			self::USER_APP_PASSWORD_DELETED => __('App Password Deleted', 'wordfence'),
			self::USER_APP_PASSWORD_ACCEPTED => __('App Password Accepted', 'wordfence'),
			
			self::USER_LOGGED_IN => __('User Logged In', 'wordfence'),
			self::USER_LOGGED_OUT => __('User Logged Out', 'wordfence'),
			self::USER_AUTH_COOKIE_SET => __('Auth Cookie Set', 'wordfence'),
			self::USER_PASSWORD_RESET => __('Password Reset', 'wordfence'),
			
			self::USER_ROLE_ADDED => __('Role Added to User', 'wordfence'),
			self::USER_ROLE_REMOVED => __('Role Removed from User', 'wordfence'),
			self::USER_META_CAPABILITIES => __('User Capabilities Meta Value Changed', 'wordfence'),
			self::USER_META_LEVEL => __('User Level Meta Value Changed', 'wordfence'),
			
			self::USER_STATUS_HAM => __('User Unmarked as Spam', 'wordfence'),
			self::USER_STATUS_SPAM => __('User Marked as Spam', 'wordfence'),
		);
	}
	
	public static function eventRateLimiters() {
		return array();
	}
	
	/**
	 * Registers the observers for this class's chunk of functionality.
	 * 
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerObservers($auditLog) {
		$auditLog->_addObserver('init', function() use ($auditLog) {
			self::$initialUserID = get_current_user_id();
		});
		
		$auditLog->_addObserver('user_register', function($user_id, $userdata = null /* added WP 5.8.0 */) use ($auditLog) { //User created
			$auditLog->_recordAction(self::USER_CREATED, $auditLog->_sanitizeUserdata($userdata, $user_id));
		});
		
		$auditLog->_addObserver('profile_update', function($user_id, $old_user_data, $userdata = null /* added WP 5.8.0 */) use ($auditLog) { //User edited
			if ($userdata === null && $user_id !== null) { //May hit this on older WP versions where $userdata wasn't populated by the hook call
				$userdata = get_user_by('ID', $user_id);
			}
			
			$changes = array_keys($auditLog->_userdataDiff($old_user_data, $userdata));
			if (empty($changes)) { //No actual changes to the record itself, just to usermeta so skip this entry
				return;
			}
			
			$auditLog->_recordAction(self::USER_UPDATED, array_merge(array(
				'changed' => $changes,
			), $auditLog->_sanitizeUserdata($userdata, $user_id)));
		});
		
		$auditLog->_addObserver('rest_insert_user', function($user, $request, $creating) use ($auditLog) { //User created/updated via REST API, userdata already populated
			$auditLog->_recordAction($creating ? self::USER_CREATED : self::USER_UPDATED, array(
				'source' => 'REST',
			), true);
		});
		
		$auditLog->_addObserver('deleted_user', function($user_id, $reassign_id) use ($auditLog) { //User deleted
			if ($auditLog->_hasState('delete_user.user')) {
				$auditLog->_recordAction(self::USER_DELETED, array_merge(array(
					'reassigned' => $reassign_id,
				), $auditLog->_sanitizeUserdata($auditLog->_getState('delete_user.user'), $user_id)));
			}
		});
		
		$auditLog->_addObserver('rest_delete_user', function($user, $response, $request) use ($auditLog) { //User deleted via REST API, userdata already populated
			$auditLog->_recordAction(self::USER_DELETED, array(
				'source' => 'REST',
			), true);
		});
		
		$auditLog->_addObserver('wp_login', function($user_login, $user) use ($auditLog) { //User logged in
			$auditLog->_recordAction(self::USER_LOGGED_IN, $auditLog->_sanitizeUserdata($user));
		});
		
		$auditLog->_addObserver('wp_logout', function($user_id = 0) use ($auditLog) { //User logged out
			if ($user_id == 0) {
				$user_id = self::$initialUserID;
			}
			
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_LOGGED_OUT, $auditLog->_sanitizeUserdata($user));
		});
		
		$auditLog->_addObserver('after_password_reset', function($user, $new_pass) use ($auditLog) { //User password reset
			$auditLog->_recordAction(self::USER_PASSWORD_RESET, $auditLog->_sanitizeUserdata($user));
		});
		
		$auditLog->_addObserver('set_auth_cookie', function($auth_cookie, $expire, $expiration, $user_id, $scheme) use ($auditLog) { //Auth cookie set
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_AUTH_COOKIE_SET, array(
				'grace_expiration' => $expire,
				'expiration' => $expiration,
				'scheme' => $scheme,
				'user' => $auditLog->_sanitizeUserdata($user),
			));
		});
		
		$auditLog->_addObserver('add_user_role', function($user_id, $new_role) use ($auditLog) { //User role assigned
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_ROLE_ADDED, array_merge(array(
				'role_added' => $new_role,
			), $auditLog->_sanitizeUserdata($user)));
		});
		
		$auditLog->_addObserver('remove_user_role', function($user_id, $removed_role) use ($auditLog) { //User role assigned
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_ROLE_REMOVED, array_merge(array(
				'role_removed' => $removed_role,
			), $auditLog->_sanitizeUserdata($user)));
		});
		
		$auditLog->_addObserver('make_spam_user', function($user_id) use ($auditLog) { //User marked as spam
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_STATUS_SPAM, $auditLog->_sanitizeUserdata($user));
		});
		
		$auditLog->_addObserver('make_ham_user', function($user_id) use ($auditLog) { //User unmarked as spam
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_STATUS_HAM, $auditLog->_sanitizeUserdata($user));
		});
		
		$auditLog->_addObserver('wp_create_application_password', function($user_id, $new_item, $new_password, $args) use ($auditLog) { //User application password created
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_APP_PASSWORD_CREATED, array_merge($auditLog->_sanitizeAppPassword($new_item), $auditLog->_sanitizeUserdata($user)));
		});
		
		$auditLog->_addObserver('wp_delete_application_password', function($user_id, $item) use ($auditLog) { //User application password deleted
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_APP_PASSWORD_DELETED, array_merge($auditLog->_sanitizeAppPassword($item), $auditLog->_sanitizeUserdata($user)));
		});
	}
	
	/**
	 * Registers the data gatherers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerDataGatherers($auditLog) {
		$auditLog->_addObserver('delete_user', function($user_id, $reassign_id) use ($auditLog) { //About to delete user
			$user = get_user_by('ID', $user_id);
			$auditLog->_trackState('delete_user.user', $user);
		});
		
		$auditLog->_addObserver('update_user_meta', function($meta_id, $object_id, $meta_key, $meta_value) use ($auditLog) { //Update user meta
			$suffixes = array('capabilities', 'user_level'); //will be <table prefix><suffix>, e.g., typically `wp_capabilities` but not always
			$match = false;
			foreach ($suffixes as $s) {
				if (preg_match('/' . preg_quote($s) . '$/i', $meta_key)) {
					$match = true;
					break;
				}
			}
			if (!$match) { return; }
			
			if (!$auditLog->_hasState('update_user_meta.old', $object_id)) {
				$auditLog->_trackState('update_user_meta.old', array(), $object_id);
			}
			
			$old = array();
			if ($auditLog->_hasState('update_user_meta.old', $object_id)) {
				$old = $auditLog->_getState('update_user_meta.old', $object_id);
			}
			
			if (!isset($old[$meta_key])) {
				$old[$meta_key] = get_user_meta($object_id, $meta_key, true);
				$auditLog->_trackState('update_user_meta.old', $old, $object_id);
			}
			
			if (!$auditLog->_hasState('update_user_meta.new', $object_id)) {
				$auditLog->_trackState('update_user_meta.new', array(), $object_id);
			}
			
			$new = $auditLog->_getState('update_user_meta.new', $object_id);
			$new[$meta_key] = $meta_value;
			$auditLog->_trackState('update_user_meta.new', $new, $object_id);
			
			$auditLog->_needsDestruct();
		});
		
		$auditLog->_addObserver('application_password_did_authenticate', function($user, $item) use ($auditLog) { //User application password authenticated
			//We can't record this directly because wp_get_current_user re-authenticates everything when called later, causing an infinite loop
			if (!$auditLog->isFinalizing() && !empty($item['uuid'])) {
				$auditLog->_trackState('application_password_did_authenticate.items', array('user' => $user, 'item' => $item), $item['uuid']);
				$auditLog->_needsDestruct();
			}
		});
	}
	
	/**
	 * Registers the coalescers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerCoalescers($auditLog) {
		$auditLog->_addCoalescer(function() use ($auditLog) { //User meta changed, specific key patterns only
			$old = $auditLog->_getAllStates('update_user_meta.old');
			if (!is_array($old) || !count($old)) {
				return;
			}
			
			$payload = array();
			foreach ($old as $user_id => $meta) {
				$user = get_user_by('ID', $user_id);
				$new = $auditLog->_getState('update_user_meta.new', $user_id);
				foreach ($meta as $key => $old_value) {
					$new_value = $new[$key];
					$event = null;
					if (preg_match('/capabilities$/i', $key)) {
						$event = self::USER_META_CAPABILITIES;
					}
					else if (preg_match('/user_level$/i', $key)) {
						$event = self::USER_META_LEVEL;
					}
					
					if ($event) {
						if (!isset($payload[$event])) { $payload[$event] = array(); }
						if (!isset($payload[$event][$user_id])) { $payload[$event][$user_id] = array('user' => $auditLog->_sanitizeUserdata($user), 'changes' => array()); }
						
						if (is_array($old_value) && is_array($new_value)) {
							$diff = wfUtils::array_diff($old_value, $new_value);
							if (empty($diff['added']) && empty($diff['removed'])) {
								continue;
							}
						}
						else {
							$diff = array('before' => $old_value, 'after' => $new_value);
							if ($diff['before'] == $diff['after']) {
								continue;
							}
						}
						$payload[$event][$user_id]['changes'][] = array('key' => $key, 'diff' => $diff);
					}
				}
			}
			
			foreach ($payload as $event => $data) {
				$auditLog->_recordAction($event, array_values($data));
			}
		});
		
		$auditLog->_addCoalescer(function() use ($auditLog) { //App password authentications
			$items = $auditLog->_getAllStates('application_password_did_authenticate.items');
			foreach ($items as $uuid => $payload) {
				$auditLog->_recordAction(self::USER_APP_PASSWORD_ACCEPTED, array_merge($auditLog->_sanitizeAppPassword($payload['item']), $auditLog->_sanitizeUserdata($payload['user'])));
			}
		});
	}
}