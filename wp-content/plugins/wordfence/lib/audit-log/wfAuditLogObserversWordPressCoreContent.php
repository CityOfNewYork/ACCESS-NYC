<?php

abstract class wfAuditLogObserversWordPressCoreContent extends wfAuditLog {
	//Attachments
	const ATTACHMENT_CREATED = 'attachment.created';
	const ATTACHMENT_DELETED = 'attachment.deleted';
	const ATTACHMENT_UPDATED = 'attachment.updated';
	
	//Pages
	const PAGE_CREATED = 'page.created';
	const PAGE_DELETED = 'page.deleted';
	const PAGE_UPDATED = 'page.updated';
	
	const PAGE_MARK_TRASHED = 'page.mark-trashed';
	const PAGE_UNMARK_TRASHED = 'page.unmark-trashed';
	
	//Posts
	const POST_CREATED = 'post.created';
	const POST_DELETED = 'post.deleted';
	const POST_UPDATED = 'post.updated';
	
	const POST_MARK_TRASHED = 'post.mark-trashed';
	const POST_UNMARK_TRASHED = 'post.unmark-trashed';
	
	
	const WP_POST_TYPE_POST = 'post';
	const WP_POST_TYPE_PAGE = 'page';
	const WP_POST_TYPE_REVISION = 'revision';
	const WP_POST_TYPE_ATTACHMENT = 'attachment';
	const WP_POST_TYPE_NAV_MENU_ITEM = 'nav_menu_item';
	const WP_POST_TYPE_THEME_CUSTOMIZATION = 'customize_changeset';
	
	const WP_POST_STATUS_AUTO_DRAFT = 'auto-draft';
	
	
	public static function immediateSendEvents() {
		return array();
	}
	
	public static function eventCategories() {
		return array(
			wfAuditLog::AUDIT_LOG_CATEGORY_CONTENT => array(
				self::ATTACHMENT_CREATED,
				self::ATTACHMENT_DELETED,
				self::ATTACHMENT_UPDATED,
				
				self::PAGE_CREATED,
				self::PAGE_DELETED,
				self::PAGE_UPDATED,
				
				self::PAGE_MARK_TRASHED,
				self::PAGE_UNMARK_TRASHED,
				
				self::POST_CREATED,
				self::POST_DELETED,
				self::POST_UPDATED,
				
				self::POST_MARK_TRASHED,
				self::POST_UNMARK_TRASHED,
			),
		);
	}
	
	public static function eventNames() {
		return array(
			self::ATTACHMENT_CREATED => __('Attachment Created', 'wordfence'),
			self::ATTACHMENT_DELETED => __('Attachment Deleted', 'wordfence'),
			self::ATTACHMENT_UPDATED => __('Attachment Updated', 'wordfence'),
			
			//Pages
			self::PAGE_CREATED => __('Page Created', 'wordfence'),
			self::PAGE_DELETED => __('Page Deleted', 'wordfence'),
			self::PAGE_UPDATED => __('Page Updated', 'wordfence'),
			
			self::PAGE_MARK_TRASHED => __('Page Moved to Trash', 'wordfence'),
			self::PAGE_UNMARK_TRASHED => __('Page Removed from Trash', 'wordfence'),
			
			//Posts
			self::POST_CREATED => __('Post Created', 'wordfence'),
			self::POST_DELETED => __('Post Deleted', 'wordfence'),
			self::POST_UPDATED => __('Post Updated', 'wordfence'),
			
			self::POST_MARK_TRASHED => __('Post Moved to Trash', 'wordfence'),
			self::POST_UNMARK_TRASHED => __('Post Removed from Trash', 'wordfence'),
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
		if ($auditLog->mode() != self::AUDIT_LOG_MODE_ALL) {
			return;
		}
		
		//Attachments
		$auditLog->_addObserver('add_attachment', function($post_id) use ($auditLog) { //Attachment created
			$post = get_post($post_id);
			$auditLog->_recordAction(self::ATTACHMENT_CREATED, $auditLog->_sanitizePost($post));
		});
		
		$auditLog->_addObserver('attachment_updated', function($post_id, $post_after, $post_before) use ($auditLog) { //Attachment updated
			$changes = array_keys($auditLog->_postDiff($post_before, $post_after));
			if (!$auditLog->_shouldRecordPostChanges($changes)) { //No meaningful changes to the record itself, skip this entry
				return;
			}
			
			$auditLog->_recordAction(self::ATTACHMENT_UPDATED, array_merge(array(
				'changes' => $changes,
			), $auditLog->_sanitizePost($post_after)));
		});
		
		$auditLog->_addObserver('delete_attachment', function($post_id, $post = null /* WP >= 5.5 */) use ($auditLog) { //Attachment deleted
			if ($post === null) {
				$post = get_post($post_id);
			}
			$auditLog->_recordAction(self::ATTACHMENT_DELETED, $auditLog->_sanitizePost($post));
		});
		
		$auditLog->_addObserver('rest_after_insert_attachment', function($attachment, $request, $creating) use ($auditLog) { //Attachment added/updated via REST API, data already populated
			$auditLog->_recordAction($creating ? self::ATTACHMENT_CREATED : self::ATTACHMENT_UPDATED, array(
				'source' => 'REST',
			), true);
		});
		
		$auditLog->_addObserver('rest_insert_attachment', function($attachment, $request, $creating) use ($auditLog) { //Attachment added/updated via REST API, data already populated
			$auditLog->_recordAction($creating ? self::ATTACHMENT_CREATED : self::ATTACHMENT_UPDATED, array(
				'source' => 'REST',
			), true);
		});
		
		$auditLog->_addObserver('xmlrpc_call_success_mw_newMediaObject', function($id) use ($auditLog) { //Attachment added via XML-RPC API, data already populated
			$auditLog->_recordAction(self::ATTACHMENT_CREATED, array(
				'source' => 'XMLRPC',
			), true);
		});
		
		//Post/Page
		$auditLog->_addObserver('wp_insert_post', function($post_id, $post /** @var WP_Post $post */, $update) use ($auditLog) { //Post/page created
			if (function_exists('wp_after_insert_post')) { //WP >= 5.6, prefer that hook when present
				return;
			}
			
			if ($post->post_type == self::WP_POST_TYPE_REVISION || $post->post_status == self::WP_POST_STATUS_AUTO_DRAFT || $post->post_type == self::WP_POST_TYPE_THEME_CUSTOMIZATION || $post->post_type == self::WP_POST_TYPE_NAV_MENU_ITEM) {
				//Ignore -- covered by other actions
				return;
			}
			
			if ($update) {
				$action = self::POST_UPDATED;
				if ($post->post_type == self::WP_POST_TYPE_PAGE) {
					$action = self::PAGE_UPDATED;
				}
				
				if ($auditLog->_hasState('pre_post_update.post', $post_id)) {
					$before = $auditLog->_getState('pre_post_update.post', $post_id);
					if (isset($before->post_status) && $before->post_status == self::WP_POST_STATUS_AUTO_DRAFT) { //Technically an update but really just converting the auto-draft into a populated post so call it a creation
						$action = self::POST_CREATED;
						if ($post->post_type == self::WP_POST_TYPE_PAGE) {
							$action = self::PAGE_CREATED;
						}
					}
				}
			}
			else {
				$action = self::POST_CREATED;
				if ($post->post_type == self::WP_POST_TYPE_PAGE) {
					$action = self::PAGE_CREATED;
				}
			}
			
			$auditLog->_recordAction($action, $auditLog->_sanitizePost($post));
		});
		
		$auditLog->_addObserver('post_updated', function($post_id, $post_after, $post_before) use ($auditLog) { //Post/page updated
			if (function_exists('wp_after_insert_post')) { //WP >= 5.6, prefer that hook when present
				return;
			}
			
			$changes = array_keys($auditLog->_postDiff($post_before, $post_after));
			if (!$auditLog->_shouldRecordPostChanges($changes)) { //No meaningful changes to the record itself, skip this entry
				return;
			}
			
			if ($post_after->post_type == self::WP_POST_TYPE_REVISION || //Ignore -- relevant revision changes will be captured when they're saved to the owning post record
				($post_before && $post_before->post_status == self::WP_POST_STATUS_AUTO_DRAFT) || $post_after->post_status == self::WP_POST_STATUS_AUTO_DRAFT || //Not interested in these until they become a post 
				$post_after->post_type == self::WP_POST_TYPE_THEME_CUSTOMIZATION || $post_after->post_type == self::WP_POST_TYPE_NAV_MENU_ITEM //Not a type we care about
			) {
				return;
			}
			
			$auditLog->_recordAction($post_after->post_type == self::WP_POST_TYPE_PAGE ? self::PAGE_UPDATED : self::POST_UPDATED, array_merge(array(
				'changes' => $changes,
			), $auditLog->_sanitizePost($post_after)));
		});
		
		$auditLog->_addObserver('wp_after_insert_post' /* WP >= 5.6 */, function($post_id, $_ignored, $update, $post_before /** @var WP_Post $post_before */) use ($auditLog) { //Post/page created
			$post_after = get_post($post_id);
			if ($post_after->post_type == self::WP_POST_TYPE_REVISION || //Ignore -- relevant revision changes will be captured when they're saved to the owning post record
				$post_after->post_status == self::WP_POST_STATUS_AUTO_DRAFT || //Not interested in these until they become a permanent post 
				$post_after->post_type == self::WP_POST_TYPE_THEME_CUSTOMIZATION || $post_after->post_type == self::WP_POST_TYPE_NAV_MENU_ITEM //Not a type we care about
			) {
				return;
			}
			
			$changes = null;
			if ($post_before) {
				$changes = array_keys($auditLog->_postDiff($post_before, $post_after));
				if (!$auditLog->_shouldRecordPostChanges($changes)) { //No meaningful changes to the record itself, skip this entry
					return;
				}
			}
			
			if ($update) {
				$action = self::POST_UPDATED;
				if ($post_after->post_type == self::WP_POST_TYPE_PAGE) {
					$action = self::PAGE_UPDATED;
				}
				
				if ($auditLog->_hasState('pre_post_update.post', $post_id)) {
					$before = $auditLog->_getState('pre_post_update.post', $post_id);
					if (isset($before->post_status) && $before->post_status == self::WP_POST_STATUS_AUTO_DRAFT) { //Technically an update but really just converting the auto-draft into a populated post so call it a creation
						$changes = null;
						$action = self::POST_CREATED;
						if ($post_after->post_type == self::WP_POST_TYPE_PAGE) {
							$action = self::PAGE_CREATED;
						}
					}
				}
			}
			else {
				$action = self::POST_CREATED;
				if ($post_after->post_type == self::WP_POST_TYPE_PAGE) {
					$action = self::PAGE_CREATED;
				}
			}
			
			$payload = $auditLog->_sanitizePost($post_after);
			if ($changes) {
				$payload['changes'] = $changes;
			}
			
			$auditLog->_recordAction($action, $payload);
		});
		
		$auditLog->_addObserver('rest_after_insert_page', function($page, $request, $creating) use ($auditLog) { //Page created/updated via REST API, data already populated
			$auditLog->_recordAction($creating ? self::PAGE_CREATED : self::PAGE_UPDATED, array(
				'source' => 'REST',
			), true);
		});
		
		$auditLog->_addObserver('rest_after_insert_post', function($post, $request, $creating) use ($auditLog) { //Post created/updated via REST API, data already populated
			$auditLog->_recordAction($creating ? self::POST_CREATED : self::POST_UPDATED, array(
				'source' => 'REST',
			), true);
		});
		
		$auditLog->_addObserver(array('xmlrpc_call_success_blogger_newPost', 'xmlrpc_call_success_mw_newPost'), function($post_id) use ($auditLog) { //Page/Post added via XML-RPC API, data already populated
			$post = WP_Post::get_instance($post_id);
			if (!$post) { return; }
			$auditLog->_recordAction($post->post_type == self::WP_POST_TYPE_PAGE ? self::PAGE_CREATED : self::POST_CREATED, array(
				'source' => 'XMLRPC',
			), true);
		});
		
		$auditLog->_addObserver('xmlrpc_call', function($action, $args = array() /* WP >= 5.7 */) use ($auditLog) { //Page/Post action via XML-RPC API, data already populated
			switch ($action) {
				case 'wp.newPost':
					if (!empty($args)) { //Not populated prior to WP 5.7 so omit this from the event (it will still record the rest, not not tagged as XML-RPC)
						$content_struct = $args[3];
						if (!isset($content_struct['post_type'])) { $content_struct['post_type'] = 'post'; } //Apply the default
						$auditLog->_recordAction($content_struct['post_type'] == self::WP_POST_TYPE_PAGE ? self::PAGE_CREATED : self::POST_CREATED, array(
							'source' => 'XMLRPC',
						), true);
					}
					break;
				case 'wp.editPost':
					if (!empty($args)) { //Not populated prior to WP 5.7 so omit this from the event (it will still record the rest, not not tagged as XML-RPC)
						$post_id = (int) $args[3];
						$post = WP_Post::get_instance($post_id);
						if (!$post) { return; }
						$auditLog->_recordAction($post->post_type == self::WP_POST_TYPE_PAGE ? self::PAGE_UPDATED : self::POST_UPDATED, array(
							'source' => 'XMLRPC',
						), true);
					}
					break;
				case 'wp.deletePost':
					if (!empty($args)) { //Not populated prior to WP 5.7 so omit this from the event (it will still record the rest, not not tagged as XML-RPC)
						$post_id = (int) $args[3];
						$post = WP_Post::get_instance($post_id);
						if (!$post) { return; }
						$auditLog->_recordAction($post->post_type == self::WP_POST_TYPE_PAGE ? self::PAGE_DELETED : self::POST_DELETED, array(
							'source' => 'XMLRPC',
						), true);
					}
					break;
				case 'wp.newPage':
					if (!empty($args)) { //Not populated prior to WP 5.7 so omit this from the event (it will still record the rest, not not tagged as XML-RPC)
						$content_struct = $args[3];
						if (!isset($content_struct['post_type'])) { $content_struct['post_type'] = 'post'; } //Apply the default
						$auditLog->_recordAction($content_struct['post_type'] == self::WP_POST_TYPE_PAGE ? self::PAGE_CREATED : self::POST_CREATED, array(
							'source' => 'XMLRPC',
						), true);
					}
					break;
				case 'wp.editPage':
					if (!empty($args)) { //Not populated prior to WP 5.7 so omit this from the event (it will still record the rest, not not tagged as XML-RPC)
						$post_id = (int) $args[1];
						$post = WP_Post::get_instance($post_id);
						if (!$post) { return; }
						$auditLog->_recordAction($post->post_type == self::WP_POST_TYPE_PAGE ? self::PAGE_UPDATED : self::POST_UPDATED, array(
							'source' => 'XMLRPC',
						), true);
					}
					break;
				case 'mt.publishPost':
					if (!empty($args)) { //Not populated prior to WP 5.7 so omit this from the event (it will still record the rest, not not tagged as XML-RPC)
						$post_id = (int) $args[0];
						$post = WP_Post::get_instance($post_id);
						if (!$post) { return; }
						$auditLog->_recordAction($post->post_type == self::WP_POST_TYPE_PAGE ? self::PAGE_UPDATED : self::POST_UPDATED, array(
							'source' => 'XMLRPC',
						), true);
					}
					break;
			}
		});
		
		$auditLog->_addObserver(array('xmlrpc_call_success_blogger_editPost', 'xmlrpc_call_success_mw_editPost'), function($post_id) use ($auditLog) { //Page/Post updated via XML-RPC API, data already populated
			$post = WP_Post::get_instance($post_id);
			if (!$post) { return; }
			
			$auditLog->_recordAction($post->post_type == self::WP_POST_TYPE_PAGE ? self::PAGE_UPDATED : self::POST_UPDATED, array(
				'source' => 'XMLRPC',
			), true);
		});
		
		$auditLog->_addObserver('deleted_post', function($post_id /** @var WP_Post $post also passed in WP > 5.5 */) use ($auditLog) { //Post/page deleted -- WP wraps a lot of functionality under the post storage type, so there are multiple events covered here
			if (!$auditLog->_hasState('delete_post.post')) {
				return;
			}
			
			$action = self::POST_DELETED;
			if ($auditLog->_getState('delete_post.post')['type'] == self::WP_POST_TYPE_PAGE) {
				$action = self::PAGE_DELETED;
			}
			
			$auditLog->_recordAction($action, $auditLog->_getState('delete_post.post'));
		});
		
		$auditLog->_addObserver(array('xmlrpc_call_success_blogger_deletePost', 'xmlrpc_call_success_wp_deletePage'), function($post_id) use ($auditLog) { //Page/Post deleted via XML-RPC API, data already populated
			if (!$auditLog->_hasState('delete_post.post')) {
				return;
			}
			
			$auditLog->_recordAction($auditLog->_getState('delete_post.post')['type'] == self::WP_POST_TYPE_PAGE ? self::PAGE_CREATED : self::POST_CREATED, array(
				'source' => 'XMLRPC',
			), true);
		});
		
		$auditLog->_addObserver('trashed_post', function($post_id) use ($auditLog) { //Post/page trashed
			$post = WP_Post::get_instance($post_id);
			if (!$post) { return; }
			
			if ($post->post_type == self::WP_POST_TYPE_REVISION || $post->post_type == self::WP_POST_TYPE_THEME_CUSTOMIZATION || $post->post_type == self::WP_POST_TYPE_NAV_MENU_ITEM) {
				//Ignore -- relevant revision changes will be captured when they're saved to the owning post record
				return;
			}
			
			$action = self::POST_MARK_TRASHED;
			if ($post->post_type == self::WP_POST_TYPE_PAGE) {
				$action = self::PAGE_MARK_TRASHED;
			}
			
			$auditLog->_recordAction($action, $auditLog->_sanitizePost($post));
		});
		
		$auditLog->_addObserver('untrashed_post', function($post_id) use ($auditLog) { //Post/page untrashed
			$post = WP_Post::get_instance($post_id);
			if (!$post) { return; }
			
			if ($post->post_type == self::WP_POST_TYPE_REVISION || $post->post_type == self::WP_POST_TYPE_THEME_CUSTOMIZATION || $post->post_type == self::WP_POST_TYPE_NAV_MENU_ITEM) {
				//Ignore -- relevant revision changes will be captured when they're saved to the owning post record
				return;
			}
			
			$action = self::POST_UNMARK_TRASHED;
			if ($post->post_type == self::WP_POST_TYPE_PAGE) {
				$action = self::PAGE_UNMARK_TRASHED;
			}
			
			$auditLog->_recordAction($action, $auditLog->_sanitizePost($post));
		});
	}
	
	/**
	 * Registers the data gatherers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerDataGatherers($auditLog) {
		$auditLog->_addObserver('delete_post', function($post_id /** @var WP_Post $post also passed in WP > 5.5 */) use ($auditLog) { //Post/page will be deleted
			$post = WP_Post::get_instance($post_id);
			if ($post) {
				if ($post->post_type == self::WP_POST_TYPE_ATTACHMENT) {
					$auditLog->_trackState('delete_post.attachment', $auditLog->_sanitizePost($post));
				}
				else if ($post->post_type == self::WP_POST_TYPE_REVISION) {
					//Ignore -- relevant revision changes will be captured when they're saved to the owning post record
				}
				else if ($post->post_type == self::WP_POST_TYPE_THEME_CUSTOMIZATION) {
					//Ignore -- covered by a dedicated event
				}
				else if ($post->post_type == self::WP_POST_TYPE_NAV_MENU_ITEM) {
					//Ignore
				}
				else if ($post->post_status != self::WP_POST_STATUS_AUTO_DRAFT) { //Post, page, or a custom one that is not an auto-draft
					$auditLog->_trackState('delete_post.post', $auditLog->_sanitizePost($post)); //We grab this here so it's available in `deleted_post` for WP < 5.5
				}
			}
		});
		
		$auditLog->_addObserver('pre_post_update', function($post_id, $data) use ($auditLog) { //Post will be updated
			$auditLog->_trackState('pre_post_update.post', get_post($post_id), $post_id);
		});
	}
	
	/**
	 * Registers the coalescers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerCoalescers($auditLog) {
		
	}
}