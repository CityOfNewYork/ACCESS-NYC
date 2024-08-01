<?php

namespace WordfenceLS;

class Controller_Permissions {
	const CAP_ACTIVATE_2FA_SELF = 'wf2fa_activate_2fa_self'; //Activate 2FA on its own user account
	const CAP_ACTIVATE_2FA_OTHERS = 'wf2fa_activate_2fa_others'; //Activate 2FA on user accounts other than its own
	const CAP_MANAGE_SETTINGS = 'wf2fa_manage_settings'; //Edit settings for the plugin
	
	const SETTING_LAST_ROLE_CHANGE = 'wfls_last_role_change';
	const SETTING_LAST_ROLE_SYNC = 'wfls_last_role_sync';

	private $network_roles = array();
	private $multisite_roles = null;
	
	/**
	 * Returns the singleton Controller_Permissions.
	 *
	 * @return Controller_Permissions
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_Permissions();
		}
		return $_shared;
	}
	
	public function install() {
		$this->_on_role_change();
		if (is_multisite()) {
			//Super Admin automatically gets all capabilities, so we don't need to explicitly add them
			$this->_add_cap_multisite('administrator', self::CAP_ACTIVATE_2FA_SELF, $this->get_primary_sites());
		}
		else {
			$this->_add_cap('administrator', self::CAP_ACTIVATE_2FA_SELF);
			$this->_add_cap('administrator', self::CAP_ACTIVATE_2FA_OTHERS);
			$this->_add_cap('administrator', self::CAP_MANAGE_SETTINGS);
		}
	}
	
	public function uninstall() {
		if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_DELETE_ON_DEACTIVATION)) {
			if (is_multisite()) {
				$sites = $this->get_sites();
				foreach ($sites as $id) {
					switch_to_blog($id);
					wp_clear_scheduled_hook('wordfence_ls_role_sync_cron');
					restore_current_blog();
				}
			}
		}
	}
	
	public static function _init_actions() {
		add_action('wordfence_ls_role_sync_cron', array(Controller_Permissions::shared(), '_role_sync_cron'));
	}

	public function init() {
		global $wp_version;
		if (is_multisite()) {
			if (version_compare($wp_version, '5.1.0', '>=')) {
				add_action('wp_initialize_site', array($this, '_wp_initialize_site'), 99);
			}
			else {
				add_action('wpmu_new_blog', array($this, '_wpmu_new_blog'), 10, 5);
			}
			
			add_action('init', array($this, '_validate_role_sync_cron'), 1);
		}
	}
	
	/**
	 * Syncs roles to the new multisite blog.
	 * 
	 * @param $site_id
	 * @param $user_id
	 * @param $domain
	 * @param $path
	 * @param $network_id
	 */
	public function _wpmu_new_blog($site_id, $user_id, $domain, $path, $network_id) {
		$this->sync_roles($network_id, $site_id);
	}
	
	/**
	 * Syncs roles to the new multisite blog. 
	 * 
	 * @param $new_site
	 */
	public function _wp_initialize_site($new_site) {
		$this->sync_roles($new_site->site_id, $new_site->blog_id);
	}
	
	/**
	 * Creates the hourly cron (if needed) that handles syncing the roles/permissions for the current blog. Because crons
	 * are specific to individual blogs on multisite rather than to the network itself, this will end up creating a cron
	 * for every member blog of the multisite.
	 * 
	 * If there is a new role change since the last sync, a one-off cron will be fired to sync it sooner than the normal
	 * recurrence period.
	 * 
	 * Multisite only.
	 * 
	 */
	public function _validate_role_sync_cron() {
		if (!wp_next_scheduled('wordfence_ls_role_sync_cron')) {
			wp_schedule_event(time(), 'hourly', 'wordfence_ls_role_sync_cron');
		}
		else {
			$last_role_change = (int) get_site_option(self::SETTING_LAST_ROLE_CHANGE, 0);
			if ($last_role_change >= get_option(self::SETTING_LAST_ROLE_SYNC, 0)) {
				wp_schedule_single_event(time(), 'wordfence_ls_role_sync_cron'); //Force queue an update in case the normal cron is still a while out
			}
		}
	}
	
	/**
	 * Handles syncing the roles/permissions for the current blog when the cron fires.
	 */
	public function _role_sync_cron() {
		$last_role_change = (int) get_site_option(self::SETTING_LAST_ROLE_CHANGE, 0);
		if ($last_role_change === 0) {
			$this->_on_role_change();
		}
		
		if ($last_role_change >= get_option(self::SETTING_LAST_ROLE_SYNC, 0)) {
			$network_id = get_current_site()->id;
			$blog_id = get_current_blog_id();
			$this->sync_roles($network_id, $blog_id);
			update_option(self::SETTING_LAST_ROLE_SYNC, time());
		}
	}
	
	private function _on_role_change() {
		update_site_option(self::SETTING_LAST_ROLE_CHANGE, time());
	}

	/**
	 * Get the primary site ID for a given network
	 */
	private function get_primary_site_id($network_id) {
		global $wpdb;
		if(function_exists('get_network')){
			$network=get_network($network_id); //TODO: Support multi-network throughout plugin
			return (int)$network->blog_id;
		}
		else{
			return (int)$wpdb->get_var($wpdb->prepare("SELECT blogs.blog_id FROM {$wpdb->site} sites JOIN {$wpdb->blogs} blogs ON blogs.site_id=sites.id AND blogs.path=sites.path WHERE sites.id=%d", $network_id));
		}
	}

	/**
	 * Get all primary sites in a multi-network setup
	 */
	private function get_primary_sites() {
		global $wpdb;
		if(function_exists('get_networks')){
			return array_map(function($network){ return $network->blog_id; }, get_networks());
		}
		else{
			return $wpdb->get_col("SELECT blogs.blog_id FROM {$wpdb->site} sites JOIN {$wpdb->blogs} blogs ON blogs.site_id=sites.id AND blogs.path=sites.path");
		}
	}
	
	/**
	 * Returns an array of all multisite `blog_id` values, optionally limiting the result to the subset between 
	 * ($from, $from + $count].
	 * 
	 * @param int $from
	 * @param int $count
	 * @return array
	 */
	private function get_sites($from = 0, $count = 0) {
		global $wpdb;
		if ($from === 0 && $count === 0) {
			return $wpdb->get_col("SELECT `blog_id` FROM `{$wpdb->blogs}` WHERE `deleted` = 0 ORDER BY blog_id ");
		}
		return $wpdb->get_col($wpdb->prepare("SELECT `blog_id` FROM `{$wpdb->blogs}` WHERE `deleted` = 0 AND blog_id > %d ORDER BY blog_id LIMIT %d", $from, $count));
	}

	/**
	 * Sync role capabilities from the default site to a newly added site
	 * @param int $network_id the relevant network
	 * @param int $site_id the newly added site(blog)
	 */
	private function sync_roles($network_id, $site_id){
		if(array_key_exists($network_id, $this->network_roles)){
			$current_roles=$this->network_roles[$network_id];
		}
		else{
			$current_roles=$this->_wp_roles($this->get_primary_site_id($network_id));
			$this->network_roles[$network_id]=$current_roles;
		}
		$new_site_roles=$this->_wp_roles($site_id);
		$capabilities=array(
			self::CAP_ACTIVATE_2FA_SELF,
			self::CAP_ACTIVATE_2FA_OTHERS,
			self::CAP_MANAGE_SETTINGS
		);
		foreach($current_roles->get_names() as $role_name=>$role_label){
			if($new_site_roles->get_role($role_name)===null)
				$new_site_roles->add_role($role_name, $role_label);
			$role=$current_roles->get_role($role_name);
			foreach($capabilities as $cap){
				if($role->has_cap($cap)){
					$this->_add_cap_multisite($role_name, $cap, array($site_id));
				}
				else{
					$this->_remove_cap_multisite($role_name, $cap, array($site_id));
				}
			}
		}
	}
	
	public function allow_2fa_self($role_name) {
		$this->_on_role_change();
		if (is_multisite()) {
			return $this->_add_cap_multisite($role_name, self::CAP_ACTIVATE_2FA_SELF, $this->get_primary_sites());
		}
		else {
			return $this->_add_cap($role_name, self::CAP_ACTIVATE_2FA_SELF);
		}
	}
	
	public function disallow_2fa_self($role_name) {
		$this->_on_role_change();
		if (is_multisite()) {
			return $this->_remove_cap_multisite($role_name, self::CAP_ACTIVATE_2FA_SELF, $this->get_primary_sites());
		}
		else {
			if ($role_name == 'administrator') {
				return true;
			}
			return $this->_remove_cap($role_name, self::CAP_ACTIVATE_2FA_SELF);
		}
	}
	
	public function can_manage_settings($user = false) {
		if ($user === false) {
			$user = wp_get_current_user();
		}
		
		if (!($user instanceof \WP_User)) {
			return false;
		}
		return $user->has_cap(self::CAP_MANAGE_SETTINGS);
	}

	public function can_role_manage_settings($role) {
		if (is_string($role)) {
			$role = get_role($role);
		}
		if ($role)
			return $role->has_cap(self::CAP_MANAGE_SETTINGS);
		return false;
	}
	
	private function _wp_roles($site_id = null) {
		require(ABSPATH . 'wp-includes/version.php'); /** @var string $wp_version */
		if (version_compare($wp_version, '4.9', '>=')) {
			return new \WP_Roles($site_id);
		}
		
		//\WP_Roles in WP < 4.9 initializes based on the current blog ID
		if (is_multisite()) {
			switch_to_blog($site_id);
		}
		$wp_roles = new \WP_Roles();
		if (is_multisite()) {
			restore_current_blog();
		}
		return $wp_roles;
	}
	
	private function _add_cap_multisite($role_name, $cap, $blog_ids=null) {
		if ($role_name === 'super-admin')
			return true;
		global $wpdb;
		$blogs = $blog_ids===null?$wpdb->get_col("SELECT `blog_id` FROM `{$wpdb->blogs}` WHERE `deleted` = 0"):$blog_ids;
		$added = false;
		foreach ($blogs as $id) {
			$wp_roles = $this->_wp_roles($id);
			switch_to_blog($id);
			$added = $this->_add_cap($role_name, $cap, $wp_roles) || $added;
			restore_current_blog();
		}
		return $added;
	}
	
	private function _add_cap($role_name, $cap, $wp_roles = null) {
		if ($wp_roles === null) { $wp_roles = $this->_wp_roles(); }
		$role = $wp_roles->get_role($role_name);
		if ($role === null) {
			return false;
		}
		
		$wp_roles->add_cap($role_name, $cap);
		return true;
	}
	
	private function _remove_cap_multisite($role_name, $cap, $blog_ids=null) {
		if ($role_name === 'super-admin')
			return false;
		global $wpdb;
		$blogs = $blog_ids===null?$wpdb->get_col("SELECT `blog_id` FROM `{$wpdb->blogs}` WHERE `deleted` = 0"):$blog_ids;
		$removed = false;
		foreach ($blogs as $id) {
			$wp_roles = $this->_wp_roles($id);
			switch_to_blog($id);
			$removed = $this->_remove_cap($role_name, $cap, $wp_roles) || $removed;
			restore_current_blog();
		}
		return $removed;
	}
	
	private function _remove_cap($role_name, $cap, $wp_roles = null) {
		if ($wp_roles === null) { $wp_roles = $this->_wp_roles(); }
		$role = $wp_roles->get_role($role_name);
		if ($role === null) {
			return false;
		}
		
		$wp_roles->remove_cap($role_name, $cap);
		return true;
	}
	
	/**
	 * Loads the role capability info for the multisite blog IDs in `$includedSites` and appends it to 
	 * `$this->multisite_roles`. Role capability data that is already loaded will be skipped.
	 * 
	 * @param array $includeSites An array of multisite blog IDs to load.
	 */
	private function _load_multisite_roles($includeSites) {
		global $wpdb;
		
		$needed = array_diff($includeSites, array_keys($this->multisite_roles));
		if (empty($needed)) {
			return;
		}
		
		$suffix = "user_roles";
		$queries = array();
		foreach ($needed as $b) {
			$tables = $wpdb->tables('blog', true, $b);
			$queries[] = "SELECT CAST(option_name AS CHAR UNICODE) AS option_name, CAST(option_value AS CHAR UNICODE) AS option_value FROM {$tables['options']} WHERE option_name LIKE '%{$suffix}'";
		}
		
		$chunks = array_chunk($queries, 50);
		$options = array();
		foreach ($chunks as $c) {
			$rows = $wpdb->get_results(implode(' UNION ', $c), OBJECT_K);
			foreach ($rows as $row) {
				$options[$row->option_name] = $row->option_value;
			}
		}
		
		$extractor = new Utility_MultisiteConfigurationExtractor($wpdb->base_prefix, $suffix);
		foreach ($extractor->extract($options) as $site => $option) {
			$this->multisite_roles[$site] = maybe_unserialize($option);
		}
	}
	
	/**
	 * Returns an array of multisite roles. This is guaranteed to include the multisite blogs in `$includeSites` but may 
	 * include others from earlier calls that are cached.
	 * 
	 * @param array $includeSites An array for multisite blog IDs.
	 * @return array
	 */
	public function get_multisite_roles($includeSites) {
		if ($this->multisite_roles === null) {
			$this->multisite_roles = array();
		}
		
		$this->_load_multisite_roles($includeSites);
		return $this->multisite_roles;
	}
	
	/**
	 * Returns the sites + roles that a user has on multisite. The structure of the returned array has the keys as the 
	 * individual site IDs and the associated value as an array of the user's capabilities on that site.
	 * 
	 * @param WP_User $user
	 * @return array
	 */
	public function get_multisite_roles_for_user($user) {
		global $wpdb;
		$roles = array();
		$meta = get_user_meta($user->ID);
		if (is_array($meta)) {
			$extractor = new Utility_MultisiteConfigurationExtractor($wpdb->base_prefix, 'capabilities');
			foreach ($extractor->extract($meta) as $site => $capabilities) {
				if (!is_array($capabilities)) { continue; }
				$capabilities = array_map('maybe_unserialize', $capabilities);
				$localRoles = array();
				foreach ($capabilities as $entry) {
					foreach ($entry as $role => $state) {
						if ($state)
							$localRoles[$role] = true;
					}
				}
				$roles[$site] = array_keys($localRoles);
			}
		}
		return $roles;
	}

	public function get_all_roles($user) {
		global $wpdb;
		if (is_multisite()) {
			$roles = array();
			if (is_super_admin($user->ID)) {
				$roles['super-admin'] = true;
			}
			foreach ($this->get_multisite_roles_for_user($user) as $site => $siteRoles) {
				foreach ($siteRoles as $role) {
					$roles[$role] = true;
				}
			}
			return array_keys($roles);
		}
		else {
			return $user->roles;
		}
	}

	public function does_user_have_multisite_capability($user, $capability) {
		$userRoles = $this->get_multisite_roles_for_user($user);
		if (in_array('super-admin', $userRoles)) {
			return true;
		}
		
		$blogRoles = $this->get_multisite_roles(array_keys($userRoles));
		$blogs = get_blogs_of_user($user->ID);
		foreach ($blogs as $blogId => $blog) {
			$blogId = (int) $blogId;
			if (!array_key_exists($blogId, $userRoles) || !array_key_exists($blogId, $blogRoles)) { continue; } //Blog with ID `$blogId` should be ignored
			foreach ($userRoles[$blogId] as $userRole) {
				if (!array_key_exists($userRole, $blogRoles[$blogId]) || !array_key_exists('capabilities', $blogRoles[$blogId][$userRole])) { continue; } //Sanity check for needed keys, should not happen
				
				$capabilities = $blogRoles[$blogId][$userRole]['capabilities'];
				if (array_key_exists($capability, $capabilities) && $capabilities[$capability]) { return true; }
			}
		}
		return false;
	}
}