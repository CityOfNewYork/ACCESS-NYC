<?php

namespace WordfenceLS;

use WordfenceLS\Text\Model_HTML;

class Controller_Notices {
	const USER_META_KEY = 'wfls_notices';
	const PERSISTENT_NOTICE_DISMISS_PREFIX = 'wfls-dismiss-';
	const PERSISTENT_NOTICE_WOOCOMMERCE_INTEGRATION = 'wfls-woocommerce-integration-notice';
	
	/**
	 * Returns the singleton Controller_Notices.
	 *
	 * @return Controller_Notices
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_Notices();
		}
		return $_shared;
	}

	private $persistentNotices = array();
	
	/**
	 * Adds an admin notice to the display queue. If $user is provided, it will show only for that user, otherwise it
	 * will show for all administrators.
	 *
	 * @param string $severity
	 * @param string|Model_HTML $message
	 * @param bool|string $category If not false, notices with the same category will be removed prior to adding this one.
	 * @param bool|\WP_User $user If not false, the user that the notice should show for.
	 */
	public function add_notice($severity, $message, $category = false, $user = false) {
		$notices = $this->_notices($user);
		foreach ($notices as $id => $n) {
			if ($category !== false && isset($n['category']) && $n['category'] == $category) { //Same category overwrites previous entry
				unset($notices[$id]);
			}
		}
		
		$id = Model_Crypto::uuid();
		$notices[$id] = array(
			'severity' => $severity,
			'messageHTML' => Model_HTML::esc_html($message),
		);
		
		if ($category !== false) {
			$notices[$id]['category'] = $category;
		}
		
		$this->_save_notices($notices, $user);
	}
	
	/**
	 * Removes a notice using one of two possible search methods:
	 *
	 * 	1. If $id matches. $category is ignored but only notices for $user are checked.
	 * 	2. If $category matches. Only notices for $user are checked.
	 *
	 * @param bool|int $id
	 * @param bool|string $category
	 * @param bool|\WP_User $user
	 */
	public function remove_notice($id = false, $category = false, $user = false) {
		if ($id === false && $category === false) {
			return;
		}
		else if ($id !== false) {
			$category = false;
		}
		
		$notices = $this->_notices($user);
		foreach ($notices as $nid => $n) {
			if ($id == $nid) { //ID match
				unset($notices[$nid]);
				break;
			}
			else if ($id !== false) {
				continue;
			}
			
			if ($category !== false && isset($n['category']) && $category == $n['category']) { //Category match
				unset($notices[$nid]);
			}
		}
		$this->_save_notices($notices, $user);
	}
	
	/**
	 * Returns whether or not a notice exists for the given user.
	 * 
	 * @param bool|\WP_User $user
	 * @return bool
	 */
	public function has_notice($user) {
		$notices = $this->_notices($user);
		return !!count($notices) || $this->has_persistent_notices();
	}
	
	/**
	 * Enqueues a user's notices. For administrators this also includes global notices.
	 * 
	 * @return bool Whether any notices were enqueued.
	 */
	public function enqueue_notices() {
		$user = wp_get_current_user();
		if ($user->ID == 0) {
			return false;
		}
		
		$added = false;
		$notices = array();
		if (Controller_Permissions::shared()->can_manage_settings($user)) {
			$globalNotices = $this->_notices(false);
			$notices = array_merge($notices, $globalNotices);
		}
		
		$userNotices = $this->_notices($user);
		$notices = array_merge($notices, $userNotices);
		
		foreach ($notices as $nid => $n) {
			$notice = new Model_Notice($nid, $n['severity'], $n['messageHTML'], $n['category']);
			if (is_multisite()) {
				add_action('network_admin_notices', array($notice, 'display_notice'));
			}
			else {
				add_action('admin_notices', array($notice, 'display_notice'));
			}
			
			$added = true;
		}
		
		return $added;
	}
	
	/**
	 * Utility
	 */
	
	/**
	 * Returns the notices for a user if provided, otherwise the global notices.
	 * 
	 * @param bool|\WP_User $user
	 * @return array
	 */
	protected function _notices($user) {
		if ($user instanceof \WP_User) {
			$notices = get_user_meta($user->ID, self::USER_META_KEY, true);
			return array_filter((array) $notices);
		}
		return Controller_Settings::shared()->get_array(Controller_Settings::OPTION_GLOBAL_NOTICES);
	}
	
	/**
	 * Saves the notices.
	 * 
	 * @param array $notices
	 * @param bool|\WP_User $user
	 */
	protected function _save_notices($notices, $user) {
		if ($user instanceof \WP_User) {
			update_user_meta($user->ID, self::USER_META_KEY, $notices);
			return;
		}
		Controller_Settings::shared()->set_array(Controller_Settings::OPTION_GLOBAL_NOTICES, $notices, true);
	}

	public function get_persistent_notice_ids() {
		return array(
			self::PERSISTENT_NOTICE_WOOCOMMERCE_INTEGRATION
		);
	}

	private static function get_persistent_notice_dismiss_key($noticeId) {
		return self::PERSISTENT_NOTICE_DISMISS_PREFIX . $noticeId;
	}

	public function register_persistent_notice($noticeId) {
		$this->persistentNotices[] = $noticeId;
	}

	public function has_persistent_notices() {
		return count($this->persistentNotices) > 0;
	}

	public function dismiss_persistent_notice($userId, $noticeId) {
		if (!in_array($noticeId, $this->get_persistent_notice_ids(), true))
			return false;
		update_user_option($userId, self::get_persistent_notice_dismiss_key($noticeId), true, true);
		return true;
	}

	public function is_persistent_notice_dismissed($userId, $noticeId) {
		return (bool) get_user_option(self::get_persistent_notice_dismiss_key($noticeId), $userId);
	}
}