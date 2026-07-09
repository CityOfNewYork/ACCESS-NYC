<?php

abstract class wfAuditLogObserversWordPressCoreMultisite extends wfAuditLog {
	const MULTISITE_BLOG_CREATED = 'multisite.blog.created';
	const MULTISITE_BLOG_DELETED = 'multisite.blog.deleted';
	const MULTISITE_BLOG_UPDATED = 'multisite.blog.updated';
	
	const MULTISITE_BLOG_ACTIVATED = 'multisite.blog.activated';
	const MULTISITE_BLOG_DEACTIVATED = 'multisite.blog.deactivated';
	const MULTISITE_BLOG_SIGNUP_SUBMITTED = 'multisite.blog.signup-submitted';
	
	const MULTISITE_BLOG_MARK_ARCHIVED = 'multisite.blog.mark-archived';
	const MULTISITE_BLOG_MARK_DELETED = 'multisite.blog.mark-deleted';
	const MULTISITE_BLOG_MARK_PUBLIC = 'multisite.blog.mark-public';
	const MULTISITE_BLOG_MARK_SPAM = 'multisite.blog.mark-spam';
	const MULTISITE_BLOG_UNMARK_ARCHIVED = 'multisite.blog.unmark-archived';
	const MULTISITE_BLOG_UNMARK_DELETED = 'multisite.blog.unmark-deleted';
	const MULTISITE_BLOG_UNMARK_PUBLIC = 'multisite.blog.unmark-public';
	const MULTISITE_BLOG_UNMARK_SPAM = 'multisite.blog.unmark-spam';
	
	const MULTISITE_USER_CREATED = 'multisite.user.created'; //User record itself created
	const MULTISITE_USER_DELETED = 'multisite.user.deleted'; //User record deleted
	const MULTISITE_USER_ACTIVATED = 'multisite.user.activated';
	const MULTISITE_USER_ADDED = 'multisite.user.added'; //Existing user added to a blog
	const MULTISITE_USER_REMOVED = 'multisite.user.removed'; //Existing user removed from a blog
	const MULTISITE_USER_INVITED = 'multisite.user.invited';
	const MULTISITE_USER_SIGNED_UP = 'multisite.user.signed-up';
	
	const MULTISITE_NETWORK_OPTION_ACTIVE_PLUGINS = 'multisite.plugin.network-activated';
	
	const USER_SUPER_ADMIN_GRANTED = 'user.permissions.super-admin-granted';
	const USER_SUPER_ADMIN_REVOKED = 'user.permissions.super-admin-revoked';
	
	public static function immediateSendEvents() {
		return array(
			self::MULTISITE_NETWORK_OPTION_ACTIVE_PLUGINS,
			self::USER_SUPER_ADMIN_GRANTED,
		);
	}
	
	public static function eventCategories() {
		return array(
			wfAuditLog::AUDIT_LOG_CATEGORY_MULTISITE => array(
				self::MULTISITE_BLOG_CREATED,
				self::MULTISITE_BLOG_DELETED,
				self::MULTISITE_BLOG_UPDATED,
				
				self::MULTISITE_BLOG_ACTIVATED,
				self::MULTISITE_BLOG_DEACTIVATED,
				self::MULTISITE_BLOG_SIGNUP_SUBMITTED,
				
				self::MULTISITE_BLOG_MARK_ARCHIVED,
				self::MULTISITE_BLOG_MARK_DELETED,
				self::MULTISITE_BLOG_MARK_PUBLIC,
				self::MULTISITE_BLOG_MARK_SPAM,
				self::MULTISITE_BLOG_UNMARK_ARCHIVED,
				self::MULTISITE_BLOG_UNMARK_DELETED,
				self::MULTISITE_BLOG_UNMARK_PUBLIC,
				self::MULTISITE_BLOG_UNMARK_SPAM,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_PLUGINS_THEMES_UPDATES => array(
				self::MULTISITE_NETWORK_OPTION_ACTIVE_PLUGINS,
			),
			wfAuditLog::AUDIT_LOG_CATEGORY_USER_PERMISSIONS => array(
				self::MULTISITE_USER_CREATED,
				self::MULTISITE_USER_DELETED,
				self::MULTISITE_USER_ACTIVATED,
				self::MULTISITE_USER_ADDED,
				self::MULTISITE_USER_REMOVED,
				self::MULTISITE_USER_INVITED,
				self::MULTISITE_USER_SIGNED_UP,
				
				self::USER_SUPER_ADMIN_GRANTED,
				self::USER_SUPER_ADMIN_REVOKED,
			),
		);
	}
	
	public static function eventNames() {
		return array(
			self::MULTISITE_BLOG_CREATED => __('Multisite Blog Created', 'wordfence'),
			self::MULTISITE_BLOG_DELETED => __('Multisite Blog Deleted', 'wordfence'),
			self::MULTISITE_BLOG_UPDATED => __('Multisite Blog Updated', 'wordfence'),
			
			self::MULTISITE_BLOG_ACTIVATED => __('Multisite Blog Activated', 'wordfence'),
			self::MULTISITE_BLOG_DEACTIVATED => __('Multisite Blog Deactivated', 'wordfence'),
			self::MULTISITE_BLOG_SIGNUP_SUBMITTED => __('Multisite Blog Signup Submitted', 'wordfence'),
			
			self::MULTISITE_BLOG_MARK_ARCHIVED => __('Multisite Blog Archived', 'wordfence'),
			self::MULTISITE_BLOG_MARK_DELETED => __('Multisite Blog Moved to Trash', 'wordfence'),
			self::MULTISITE_BLOG_MARK_PUBLIC => __('Multisite Blog Made Public', 'wordfence'),
			self::MULTISITE_BLOG_MARK_SPAM => __('Multisite Blog Marked as Spam', 'wordfence'),
			self::MULTISITE_BLOG_UNMARK_ARCHIVED => __('Multisite Blog Unarchived', 'wordfence'),
			self::MULTISITE_BLOG_UNMARK_DELETED => __('Multisite Blog Removed from Trash', 'wordfence'),
			self::MULTISITE_BLOG_UNMARK_PUBLIC => __('Multisite Blog Made Private', 'wordfence'),
			self::MULTISITE_BLOG_UNMARK_SPAM => __('Multisite Blog Unmarked as Spam', 'wordfence'),
			
			self::MULTISITE_USER_CREATED => __('Multisite User Created', 'wordfence'),
			self::MULTISITE_USER_DELETED => __('Multisite User Deleted', 'wordfence'),
			self::MULTISITE_USER_ACTIVATED => __('Multisite User Activated', 'wordfence'),
			self::MULTISITE_USER_ADDED => __('User Added to Multisite Blog', 'wordfence'),
			self::MULTISITE_USER_REMOVED => __('User Removed from Multisite Blog', 'wordfence'),
			self::MULTISITE_USER_INVITED => __('User Invited to Multisite Blog', 'wordfence'),
			self::MULTISITE_USER_SIGNED_UP => __('User Signed Up on Multisite Blog', 'wordfence'),
			
			self::MULTISITE_NETWORK_OPTION_ACTIVE_PLUGINS => __('Multisite Network Plugins Changed', 'wordfence'),
			
			self::USER_SUPER_ADMIN_GRANTED => __('Super Admin Granted to User', 'wordfence'),
			self::USER_SUPER_ADMIN_REVOKED => __('Super Admin Revoked from User', 'wordfence'),
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
		if (!is_multisite()) { return; }
		
		$auditLog->_addObserver('activate_blog', function($id) use ($auditLog) { //Multisite blog activated (from deactivated state -- WP uses trashed/untrashed pattern internally for this)
			$blog = get_blog_details(array('blog_id' => $id));
			$auditLog->_recordAction(self::MULTISITE_BLOG_ACTIVATED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('wp_insert_site', function($blog) use ($auditLog) { //Multisite blog created
			$auditLog->_recordAction(self::MULTISITE_BLOG_CREATED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('wp_delete_site', function($blog) use ($auditLog) { //Multisite blog deleted
			if ($auditLog->_hasState('wp_validate_site_deletion.blog')) {
				$auditLog->_recordAction(self::MULTISITE_BLOG_DELETED, array('blog' => $auditLog->_getState('wp_validate_site_deletion.blog')));
			}
		});
		
		$auditLog->_addObserver('wp_update_site', function($new_blog, $old_blog) use ($auditLog) { //Multisite blog updated
			$before = $auditLog->_sanitizeMultisiteData(false, $old_blog);
			$after = $auditLog->_sanitizeMultisiteData(false, $new_blog);
			$changes = array_keys($auditLog->_multisiteDiff($before, $after));
			$auditLog->_recordAction(self::MULTISITE_BLOG_UPDATED, array('blog' => $after, 'changes' => $changes));
		});
		
		$auditLog->_addObserver('archive_blog', function($blog_id) use ($auditLog) { //Multisite blog archived
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$auditLog->_recordAction(self::MULTISITE_BLOG_MARK_ARCHIVED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('unarchive_blog', function($blog_id) use ($auditLog) { //Multisite blog unarchived
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$auditLog->_recordAction(self::MULTISITE_BLOG_UNMARK_ARCHIVED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('make_delete_blog', function($blog_id) use ($auditLog) { //Multisite blog trashed
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$auditLog->_recordAction(self::MULTISITE_BLOG_MARK_DELETED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('make_undelete_blog', function($blog_id) use ($auditLog) { //Multisite blog untrashed
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$auditLog->_recordAction(self::MULTISITE_BLOG_UNMARK_DELETED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('update_blog_public', function($blog_id, $public) use ($auditLog) { //Multisite blog made public/private
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$auditLog->_recordAction(wfUtils::truthyToBoolean($public) ? self::MULTISITE_BLOG_MARK_PUBLIC : self::MULTISITE_BLOG_UNMARK_PUBLIC, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('make_spam_blog', function($blog_id) use ($auditLog) { //Multisite blog marked spam
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$auditLog->_recordAction(self::MULTISITE_BLOG_MARK_SPAM, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('make_ham_blog', function($blog_id) use ($auditLog) { //Multisite blog unmarked spam
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$auditLog->_recordAction(self::MULTISITE_BLOG_UNMARK_SPAM, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog)));
		});
		
		$auditLog->_addObserver('after_signup_site', function($domain, $path, $title, $user, $user_email, $key, $meta) use ($auditLog) { //Multisite blog signup
			$auditLog->_recordAction(self::MULTISITE_BLOG_SIGNUP_SUBMITTED, array(
				'blog' => array(
					'blog_domain' => $domain,
					'blog_path' => $path,
					'blog_name' => $title,
				),
				'user' => array(
					'user_login' => $user,
				),
			));
		});
		
		$auditLog->_addObserver('add_user_to_blog', function($user_id, $role, $blog_id) use ($auditLog) { //User added to multisite blog
			$user = get_user_by('ID', $user_id);
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$auditLog->_recordAction(self::MULTISITE_USER_ADDED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog), 'user' => $auditLog->_sanitizeUserdata($user), 'role' => $role));
		});
		
		$auditLog->_addObserver('wpmu_new_user', function($user_id) use ($auditLog) { //New unprivileged multisite user created
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::MULTISITE_USER_CREATED, array('user' => $auditLog->_sanitizeUserdata($user)));
		});
		
		$auditLog->_addObserver('wpmu_delete_user', function($id, $user) use ($auditLog) { //Multisite user will be deleted
			$auditLog->_recordAction(self::MULTISITE_USER_DELETED, array('user' => $auditLog->_sanitizeUserdata($user)));
		});
		
		$auditLog->_addObserver('invite_user', function($user_id, $role, $newuser_key) use ($auditLog) { //Multisite user invited to blog
			$user = get_user_by('ID', $user_id);
			$blog = get_blog_details();
			$auditLog->_recordAction(self::MULTISITE_USER_INVITED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog), 'user' => $auditLog->_sanitizeUserdata($user), 'role' => $role));
		});
		
		$auditLog->_addObserver('remove_user_from_blog', function($user_id, $blog_id, $reassign_id) use ($auditLog) { //Multisite user removed from blog
			$user = get_user_by('ID', $user_id);
			$blog = get_blog_details(array('blog_id' => $blog_id));
			$reassign = get_user_by('ID', $reassign_id);
			$auditLog->_recordAction(self::MULTISITE_USER_REMOVED, array('blog' => $auditLog->_sanitizeMultisiteData(false, $blog), 'user' => $auditLog->_sanitizeUserdata($user), 'reassign' => $auditLog->_sanitizeUserdata($reassign)));
		});
		
		$auditLog->_addObserver('after_signup_user', function($user, $user_email, $key, $meta) use ($auditLog) { //Multisite user signup
			$auditLog->_recordAction(self::MULTISITE_USER_SIGNED_UP, array(
				'user' => array(
					'user_login' => $user,
				),
			));
		});
		
		$auditLog->_addObserver('granted_super_admin', function($user_id) use ($auditLog) { //Super admin granted
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_SUPER_ADMIN_GRANTED, $auditLog->_sanitizeUserdata($user));
		});
		
		$auditLog->_addObserver('revoked_super_admin', function($user_id) use ($auditLog) { //Super admin revoked
			$user = get_user_by('ID', $user_id);
			$auditLog->_recordAction(self::USER_SUPER_ADMIN_REVOKED, $auditLog->_sanitizeUserdata($user));
		});
	}
	
	/**
	 * Registers the data gatherers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerDataGatherers($auditLog) {
		if (!is_multisite()) { return; }
		
		$auditLog->_addObserver('wp_validate_site_deletion', function($errors, $blog) use ($auditLog) { //Multisite site will be deleted
			$auditLog->_trackState('wp_validate_site_deletion.blog', $auditLog->_sanitizeMultisiteData(false, $blog));
		});
		
		$auditLog->_addObserver('update_site_option_active_sitewide_plugins', function($option, $value, $old_value, $network_id) use ($auditLog) { //Network active plugins changed
			if (!$auditLog->_hasState('update_site_option_active_sitewide_plugins.old', 0)) {
				$auditLog->_trackState('update_site_option_active_sitewide_plugins.old', $old_value, 0);
			}
			
			$auditLog->_trackState('update_site_option_active_sitewide_plugins.new', $value, 0);
			
			$auditLog->_needsDestruct();
		});
	}
	
	/**
	 * Registers the coalescers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerCoalescers($auditLog) {
		if (!is_multisite()) { return; }
		
		$auditLog->_addCoalescer(function() use ($auditLog) { //Network active plugins changed
			$old = $auditLog->_getState('update_site_option_active_sitewide_plugins.old', 0);
			if (!is_array($old) || !count($old)) {
				return;
			}
			
			$new = $auditLog->_getState('update_site_option_active_sitewide_plugins.new', 0);
			$diff = wfUtils::array_diff($old, $new);
			$auditLog->_recordAction(self::MULTISITE_NETWORK_OPTION_ACTIVE_PLUGINS, array('plugins' => $new, 'diff' => $diff));
		});
	}
}