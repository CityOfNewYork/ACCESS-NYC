<?php

namespace WordfenceLS;

class Utility_Multisite {
	
	/**
	 * Returns an array of all active multisite blogs (if `$blogIds` is `null`) or a list of active multisite blogs 
	 * filtered to only those in `$blogIds` if non-null.
	 * 
	 * @param array|null $blogIds
	 * @return array
	 */
	public static function retrieve_active_sites($blogIds = null) {
		$args = array(
			'number' => '', /* WordPress core passes an empty string which appears to remove the result set limit */
			'update_site_meta_cache' => false, /* Defaults to true which is not desirable for this use case */
			//Ignore archived/spam/deleted sites
			'archived' => 0,
			'spam' => 0,
			'deleted' => 0
		);
		
		if ($blogIds !== null) {
			$args['site__in'] = $blogIds;
		}
		
		if (function_exists('get_sites')) {
			return get_sites($args);
		}
		
		global $wpdb;
		if ($blogIds !== null) {
			$blogIdsQuery = implode(',', wp_parse_id_list($args['site__in']));
			return $wpdb->get_results("SELECT * FROM {$wpdb->blogs} WHERE blog_id IN ({$blogIdsQuery}) AND archived = 0 AND spam = 0 AND deleted = 0");
		}
		
		return $wpdb->get_results("SELECT * FROM {$wpdb->blogs} WHERE archived = 0 AND spam = 0 AND deleted = 0");
	}
}
