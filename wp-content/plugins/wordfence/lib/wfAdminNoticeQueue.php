<?php

class wfAdminNoticeQueue {
	const USERS_ALL = 'all';
	
	protected static function _notices() {
		return self::_purgeObsoleteNotices(wfConfig::get_ser('adminNoticeQueue', array()));
	}

	private static function _purgeObsoleteNotices($notices) {
		$altered = false;
		foreach ($notices as $id => $notice) {
			if (!empty($notice['category']) && $notice['category'] === 'php8') {
				unset($notices[$id]);
				$altered = true;
			}
		}
		if ($altered)
			self::_setNotices($notices);
		return $notices;
	}
	
	protected static function _setNotices($notices) {
		wfConfig::set_ser('adminNoticeQueue', $notices);
	}
	
	/**
	 * Adds an admin notice to the display queue.
	 * 
	 * @param string $severity
	 * @param string $messageHTML
	 * @param bool|string $category If not false, notices with the same category will be removed prior to adding this one.
	 * @param bool|array $users If not false, an array of user IDs the notice should show for.
	 */
	public static function addAdminNotice($severity, $messageHTML, $category = false, $users = false) {
		$notices = self::_notices();
		foreach ($notices as $id => $n) {
			$usersMatches = false;
			if (isset($n['users'])) {
				$usersMatches = wfUtils::sets_equal($n['users'], $users);
			}
			else if ($users === false) {
				$usersMatches = true;
			}
			
			$categoryMatches = false;
			if ($category !== false && isset($n['category']) && $n['category'] == $category) {
				$categoryMatches = true;
			}
			
			if ($usersMatches && $categoryMatches) {
				unset($notices[$id]);
			}
		}
		
		$id = wfUtils::uuid();
		$notices[$id] = array(
			'severity' => $severity,
			'messageHTML' => $messageHTML,
		);
		
		if ($category !== false) {
			$notices[$id]['category'] = $category;
		}
		
		if ($users !== false) {
			$notices[$id]['users'] = $users;
		}
		
		self::_setNotices($notices);
	}
	
	/**
	 * Removes an admin notice by ID. An admin may remove any notice where lower privileged users can only
	 * remove themselves from the notice.
	 *
	 * @param string $id
	 */
	public static function removeAdminNoticeForID($id) {
		$user = wp_get_current_user();
		if (!$user->exists()) {
			return;
		}
		
		$notices = self::_notices();
		$found = false;
		foreach ($notices as $nid => $n) {
			if ($id == $nid) { //ID match
				$currentUserInUsers = !empty($n['users']) && in_array($user->ID, $n['users']);
				if (wfUtils::isAdmin($user)) {
					unset($notices[$nid]);
					$found = true;
				}
				else if ($currentUserInUsers) {
					$notices[$nid]['users'] = array_diff($n['users'], array($user->ID));
					if (empty($notices[$nid]['users'])) {
						unset($notices[$nid]);
					}
					$found = true;
				}
				break;
			}
		}
		
		if ($found) {
			self::_setNotices($notices);
		}
	}
	
	/**
	 * Removes any admin notices matching $category that are global (i.e. not specific to a user).
	 *
	 * @param string $category
	 * @return void
	 */
	public static function removeGlobalAdminNoticeForCategory($category) {
		$notices = self::_notices();
		$found = false;
		foreach ($notices as $nid => $n) {
			if (isset($n['category']) && $category == $n['category']) {
				if (empty($n['users'])) {
					unset($notices[$nid]);
					$found = true;
				}
			}
		}
		
		if ($found) {
			self::_setNotices($notices);
		}
	}
	
	/**
	 * Removes any admin notices matching $category that are specific to the user with ID $userID.
	 *
	 * @param string $category
	 * @param null|int|string $userID `null` means the current user, `all` means all users, and an integer means a specific user
	 * @return void
	 */
	public static function removeAdminNoticeForCategory($category, $userID = null) {
		if ($userID === null) {
			$user = wp_get_current_user();
			if (!$user->exists()) { return; }
			$userID = $user->ID;
		}
		
		$notices = self::_notices();
		$found = false;
		foreach ($notices as $nid => $n) {
			if (isset($n['category']) && $category == $n['category']) {
				if ($userID === 'all') {
					unset($notices[$nid]);
					$found = true;
				}
				else {
					$currentUserInUsers = !empty($n['users']) && in_array($userID, $n['users']);
					if ($currentUserInUsers) {
						$notices[$nid]['users'] = array_diff($n['users'], array($userID));
						if (empty($notices[$nid]['users'])) {
							unset($notices[$nid]);
						}
						$found = true;
					}
				}
			}
		}
		
		if ($found) {
			self::_setNotices($notices);
		}
	}
	
	/**
	 * Returns whether at least one queued admin notice matches the provided filters.
	 *
	 * Matching behavior:
	 * - `$category === null` matches notices with no `category` field.
	 * - `$category === false` matches notices with any `category` field.
	 * - `$category === {string}` matches notices whose `category` equals `$category`.
	 * - `$users === null` matches notices with no `users` field (global notices).
	 * - `$users === false` matches notices with any `users` field
	 * - `$users === {array}` matches notices with a `users` field where the notice's
	 *   user IDs contain the IDs in `$users` (`wfUtils::is_subset($noticeUsers, $users)`).
	 *
	 * A notice is considered a match only when both category and user checks pass.
	 *
	 * @param string|null|false $category Category to match, `false` for any category, or `null` for uncategorized notices.
	 * @param int[]|null|false $users User IDs to match against, `false` for any user, or `null` for global notices.
	 * @return bool True if a matching notice exists; otherwise false.
	 */
	public static function hasNotice($category = null, $users = null) {
		$notices = self::_notices();
		foreach ($notices as $nid => $n) {
			$categoryMatches = false;
			if ($category === false || ($category === null && !isset($n['category'])) || ($category !== false && $category !== null && isset($n['category']) && $category == $n['category'])) {
				$categoryMatches = true;
			}
			
			$usersMatches = null;
			if ($users === false || ($users === null && !isset($n['users'])) || ($users !== false && $users !== null && isset($n['users']) && wfUtils::is_subset($n['users'], $users))) {
				$usersMatches = true;
			}
			
			if ($categoryMatches && $usersMatches) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Returns whether the provided user has any admin notices that will show.
	 *
	 * @param WP_User $user
	 * @return bool
	 */
	public static function hasAnyNotice($user) {
		if (!$user->exists()) {
			return false;
		}
		
		$notices = self::_notices();
		foreach ($notices as $nid => $n) {
			if ((wfUtils::isAdmin($user) && !isset($n['users'])) || (isset($n['users']) && wfUtils::is_subset($n['users'], array($user->ID)))) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Enqueues any admin notices that are applicable to the current user.
	 *
	 * @param bool $userSpecificOnly If true, only notices that are specific to the current user will be enqueued.
	 */
	public static function enqueueAdminNotices($userSpecificOnly = false) {
		$user = wp_get_current_user();
		if ($user->ID == 0) {
			return false;
		}
		
		$networkAdmin = is_multisite() && is_network_admin();
		$notices = self::_notices();
		$added = false;
		foreach ($notices as $nid => $n) {
			if (isset($n['users'])) {
				if (!in_array($user->ID, $n['users'])) { continue; }
			}
			else {
				if ($userSpecificOnly) { continue; }
			}
			
			$notice = new wfAdminNotice($nid, $n['severity'], $n['messageHTML']);
			if ($networkAdmin) {
				add_action('network_admin_notices', array($notice, 'displayNotice'));
			}
			else {
				add_action('admin_notices', array($notice, 'displayNotice'));
			}
			
			$added = true;
		}
		
		return $added;
	}
}

class wfAdminNotice {
	const SEVERITY_CRITICAL = 'critical';
	const SEVERITY_WARNING = 'warning';
	const SEVERITY_INFO = 'info';
	
	private $_id;
	private $_severity;
	private $_messageHTML;
	
	public function __construct($id, $severity, $messageHTML) {
		$this->_id = $id;
		$this->_severity = $severity;
		$this->_messageHTML = $messageHTML;
	}
	
	public function displayNotice() {
		$severityClass = 'notice-info';
		if ($this->_severity == self::SEVERITY_CRITICAL) {
			$severityClass = 'notice-error';
		}
		else if ($this->_severity == self::SEVERITY_WARNING) {
			$severityClass = 'notice-warning';
		}
		
		echo '<div class="wf-admin-notice notice ' . $severityClass . '" data-notice-id="' . esc_attr($this->_id) . '"><p>' . $this->_messageHTML . '</p><p><a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="#" onclick="wordfenceExt.dismissAdminNotice(\'' . esc_attr($this->_id) . '\'); return false;" role="button">' . esc_html__('Dismiss', 'wordfence') . '</a></p></div>';
	}
}