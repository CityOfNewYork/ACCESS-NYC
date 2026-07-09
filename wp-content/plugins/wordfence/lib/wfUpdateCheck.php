<?php

class wfUpdateCheck {
	const VULN_SEVERITY_CRITICAL = 90;
	const VULN_SEVERITY_HIGH = 70;
	const VULN_SEVERITY_MEDIUM = 40;
	const VULN_SEVERITY_LOW = 1;
	const VULN_SEVERITY_NONE = 0;
	
	const LAST_UPDATE_CHECK_ERROR_KEY = 'lastUpdateCheckError';
	const LAST_UPDATE_CHECK_ERROR_SLUG_KEY = 'lastUpdateCheckErrorSlug';

	private $needs_core_update = false;
	private $core_update_patch_available = false;
	private $core_earlier_branch = false;
	private $core_update_version = 0;
	private $core_update_patch_version = 0;
	private $plugin_updates = array();
	private $all_plugins = array();
	private $plugin_slugs = array();
	private $theme_updates = array();
	private $api = null;
	
	/**
	 * This hook exists because some plugins override their own update check and can return invalid 
	 * responses (e.g., null) due to logic errors or their update check server being unreachable. This
	 * can interfere with our scan running the outdated plugins check. When scanning, we adjust the 
	 * response in those cases to be `false`, which causes WP to fall back to the plugin repo data.
	 */
	public static function installPluginAPIFixer() {
		add_filter('plugins_api', 'wfUpdateCheck::_pluginAPIFixer', 999, 3);
	}
	
	public static function _pluginAPIFixer($result, $action, $args) {
		if ($result === false || is_object($result) || is_array($result)) {
			return $result;
		}
		
		if (!wfScanEngine::isScanRunning(true)) { //Skip fixing if it's not the call the scanner made
			return $result;
		}
		
		$slug = null;
		if (is_object($args) && isset($args->slug)) {
			$slug = $args->slug;
		}
		else if (is_array($args) && isset($args['slug'])) {
			$slug = $args['slug'];
		}
		wordfence::status(2, 'info', sprintf(/* translators: 1. Plugin slug. */ __('Outdated plugin scan adjusted invalid return value in plugins_api filter for %s', 'wordfence'), $slug));
		return false;
	}

	public static function syncAllVersionInfo() {
		// Load the core/plugin/theme versions into the WAF configuration.
		wfConfig::set('wordpressVersion', wfUtils::getWPVersion());
		wfWAFConfig::set('wordpressVersion', wfUtils::getWPVersion(), wfWAF::getInstance(), 'synced');

		if (!function_exists('get_plugins')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}

		$pluginVersions = array();
		foreach (get_plugins() as $pluginFile => $pluginData) {
			$slug = plugin_basename($pluginFile);
			if (preg_match('/^([^\/]+)\//', $pluginFile, $matches)) {
				$slug = $matches[1];
			} else if (preg_match('/^([^\/.]+)\.php$/', $pluginFile, $matches)) {
				$slug = $matches[1];
			}
			$pluginVersions[$slug] = isset($pluginData['Version']) ? $pluginData['Version'] : null;
		}

		wfConfig::set_ser('wordpressPluginVersions', $pluginVersions);
		wfWAFConfig::set('wordpressPluginVersions', $pluginVersions, wfWAF::getInstance(), 'synced');

		if (!function_exists('wp_get_themes')) {
			require_once(ABSPATH . '/wp-includes/theme.php');
		}

		$themeVersions = array();
		foreach (wp_get_themes() as $slug => $theme) {
			$themeVersions[$slug] = isset($theme['Version']) ? $theme['Version'] : null;
		}

		wfConfig::set_ser('wordpressThemeVersions', $themeVersions);
		wfWAFConfig::set('wordpressThemeVersions', $themeVersions, wfWAF::getInstance(), 'synced');
	}
	
	public static function cvssScoreSeverity($score) {
		$intScore = floor($score * 10);
		if ($intScore >= self::VULN_SEVERITY_CRITICAL) {
			return self::VULN_SEVERITY_CRITICAL;
		}
		else if ($intScore >= self::VULN_SEVERITY_HIGH) {
			return self::VULN_SEVERITY_HIGH;
		}
		else if ($intScore >= self::VULN_SEVERITY_MEDIUM) {
			return self::VULN_SEVERITY_MEDIUM;
		}
		else if ($intScore >= self::VULN_SEVERITY_LOW) {
			return self::VULN_SEVERITY_LOW;
		}
		
		return self::VULN_SEVERITY_NONE;
	}
	
	public static function cvssScoreSeverityLabel($score) {
		$severity = self::cvssScoreSeverity($score);
		switch ($severity) {
			case self::VULN_SEVERITY_CRITICAL:
				return __('Critical', 'wordfence');
			case self::VULN_SEVERITY_HIGH:
				return __('High', 'wordfence');
			case self::VULN_SEVERITY_MEDIUM:
				return __('Medium', 'wordfence');
			case self::VULN_SEVERITY_LOW:
				return __('Low', 'wordfence');
		}
		return __('None', 'wordfence');
	}
	
	public static function cvssScoreSeverityHexColor($score) {
		$severity = self::cvssScoreSeverity($score);
		switch ($severity) {
			case self::VULN_SEVERITY_CRITICAL:
				return '#cc0500';
			case self::VULN_SEVERITY_HIGH:
				return '#df3d03';
			case self::VULN_SEVERITY_MEDIUM:
				return '#f9a009';
			case self::VULN_SEVERITY_LOW:
				return '#ffcb0d';
		}
		return '#000000';
	}
	
	public static function cvssScoreSeverityClass($score) {
		$severity = self::cvssScoreSeverity($score);
		switch ($severity) {
			case self::VULN_SEVERITY_CRITICAL:
				return 'wf-vulnerability-severity-critical';
			case self::VULN_SEVERITY_HIGH:
				return 'wf-vulnerability-severity-high';
			case self::VULN_SEVERITY_MEDIUM:
				return 'wf-vulnerability-severity-medium';
			case self::VULN_SEVERITY_LOW:
				return 'wf-vulnerability-severity-low';
		}
		return 'wf-vulnerability-severity-none';
	}
	
	public function __construct() {
		$this->api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
	}
	
	public function __sleep() {
		return array('needs_core_update', 'core_update_version', 'plugin_updates', 'all_plugins', 'plugin_slugs', 'theme_updates');
	}
	
	public function __wakeup() {
		$this->api = new wfAPI(wfConfig::get('apiKey'), wfUtils::getWPVersion());
	}
	
	/**
	 * @return bool
	 */
	public function needsAnyUpdates() {
		return $this->needsCoreUpdate() || count($this->getPluginUpdates()) > 0 || count($this->getThemeUpdates()) > 0;
	}

	/**
	 * Check for any core, plugin or theme updates.
	 *
	 * @return $this
	 */
	public function checkAllUpdates($useCachedValued = true) {
		if (!$useCachedValued) {
			wfConfig::remove(self::LAST_UPDATE_CHECK_ERROR_KEY);
			wfConfig::remove(self::LAST_UPDATE_CHECK_ERROR_SLUG_KEY);
		}
		
		return $this->checkCoreUpdates($useCachedValued)
			->checkPluginUpdates($useCachedValued)
			->checkThemeUpdates($useCachedValued);
	}

	/**
	 * Check if there is an update to the WordPress core.
	 *
	 * @return $this
	 */
	public function checkCoreUpdates($useCachedValued = true) {
		$this->needs_core_update = false;

		if (!function_exists('wp_version_check')) {
			require_once(ABSPATH . WPINC . '/update.php');
		}
		if (!function_exists('get_preferred_from_update_core')) {
			require_once(ABSPATH . 'wp-admin/includes/update.php');
		}
		
		include(ABSPATH . WPINC . '/version.php'); /** @var $wp_version */
		
		$availableUpdates = get_site_transient('update_core');
		/**
		 * Sample Structure:
		 * 
		 * class stdClass#1 (4) {
			  public $updates =>
			  array(3) {
				[0] =>
				class stdClass#2 (10) {
				  public $response => string(7) "upgrade"
				  public $version => string(5) "6.4.2"
				  ...
				}
				[1] =>
				class stdClass#4 (11) {
				  public $response => string(10) "autoupdate"
				  public $version => string(5) "6.4.2"
				  ...
				}
				[2] =>
				class stdClass#6 (11) {
				  public $response => string(10) "autoupdate"
				  public $version => string(5) "6.3.2"
				  ...
				}
			  }
			  public $last_checked => int(1703025218)
			  public $version_checked => string(5) "6.3.1"
			  public $translations => ...
			}

		 */
		
		if ($useCachedValued && 
			isset($availableUpdates->updates) && is_array($availableUpdates->updates) && 
			isset($availableUpdates->last_checked) && 12 * HOUR_IN_SECONDS > (time() - $availableUpdates->last_checked) && $availableUpdates->version_checked == $wp_version) {
			//Do nothing, use cached value
		}
		else {
			wp_version_check();
			$availableUpdates = get_site_transient('update_core');
		}
		
		if (isset($availableUpdates->updates) && is_array($availableUpdates->updates)) {
			$current = wfUtils::parse_version($wp_version);
			$updates = $availableUpdates->updates;
			foreach ($updates as $update) {
				if (version_compare($update->version, $wp_version) <= 0) { continue; } //Array will contain the reinstall info for the current version if non-prerelease or the last production version if prerelease, skip
				
				if (version_compare($update->version, $this->core_update_version) > 0) {
					$this->needs_core_update = true;
					$this->core_update_version = $update->version;
				}
				
				$checking = wfUtils::parse_version($update->version);
				if ($checking[wfUtils::VERSION_MAJOR] == $current[wfUtils::VERSION_MAJOR] && $checking[wfUtils::VERSION_MINOR] == $current[wfUtils::VERSION_MINOR] && $checking[wfUtils::VERSION_PATCH] > $current[wfUtils::VERSION_PATCH]) {
					$this->core_update_patch_available = true;
					$this->core_update_patch_version = $update->version;
				}
			}
			
			if ($this->needs_core_update && $this->core_update_patch_available && version_compare($this->core_update_version, $this->core_update_patch_version) === 0) { //Patch and edge update are the same, clear patch values
				$this->core_update_patch_available = false;
				$this->core_update_patch_version = 0;
			}
			
			if ($this->needs_core_update) {
				$checking = wfUtils::parse_version($this->core_update_version);
				$this->core_earlier_branch = ($checking[wfUtils::VERSION_MAJOR] > $current[wfUtils::VERSION_MAJOR] || $checking[wfUtils::VERSION_MINOR] > $current[wfUtils::VERSION_MINOR]);
			}
		}

		return $this;
	}

	private function checkPluginFile($plugin, &$installedPlugins) {
		if (!array_key_exists($plugin, $installedPlugins))
			return null;
		$file = wfUtils::getPluginBaseDir() . $plugin;
		if (!file_exists($file)) {
			unset($installedPlugins[$plugin]);
			return null;
		}
		return $file;
	}

	private function initializePluginUpdateData($plugin, &$installedPlugins, $checkVulnerabilities, $populator = null) {
		$file = $this->checkPluginFile($plugin, $installedPlugins);
		if ($file === null)
			return null;
		$data = $installedPlugins[$plugin];
		$data['pluginFile'] = $file;
		if ($populator !== null)
			$populator($data, $file);
		if (!array_key_exists('slug', $data) || empty($data['slug']))
			$data['slug'] = $this->extractSlug($plugin);
		$slug = $data['slug'];
		if ($slug !== null) {
			$vulnerable = $checkVulnerabilities ? $this->isPluginVulnerable($slug, $data['Version']) : null;
			$data['vulnerable'] = !empty($vulnerable);
			if ($data['vulnerable']) {
				if (isset($vulnerable['link']) && is_string($vulnerable['link'])) { $data['vulnerabilityLink'] = $vulnerable['link']; }
				if (isset($vulnerable['score'])) {
					$data['cvssScore'] = number_format(floatval($vulnerable['score']), 1);
					$data['severityColor'] = self::cvssScoreSeverityHexColor($data['cvssScore']);
					$data['severityLabel'] = self::cvssScoreSeverityLabel($data['cvssScore']);
					$data['severityClass'] = self::cvssScoreSeverityClass($data['cvssScore']);
				}
				if (isset($vulnerable['vector']) && is_string($vulnerable['vector'])) { $data['cvssVector'] = $vulnerable['vector']; }
			}
			$this->plugin_slugs[] = $slug;
			$this->all_plugins[$slug] = $data;
		}
		unset($installedPlugins[$plugin]);
		return $data;
	}

	public function extractSlug($plugin, $data = null) {
		$slug = null;
		if (is_array($data) && array_key_exists('slug', $data))
			$slug = $data['slug'];
		if (!is_string($slug) || empty($slug)) {
			if (preg_match('/^([^\/]+)\//', $plugin, $matches)) {
				$slug = $matches[1];
			}
			else if (preg_match('/^([^\/.]+)\.php$/', $plugin, $matches)) {
				$slug = $matches[1];
			}
		}
		return $slug;
	}

	private static function requirePluginsApi() {
		if (!function_exists('plugins_api'))
			require_once(ABSPATH . '/wp-admin/includes/plugin-install.php');
	}

	private function fetchPluginUpdates($useCache = true) {
		$update_plugins = get_site_transient('update_plugins');
		if ($useCache && isset($update_plugins->last_checked) && 12 * HOUR_IN_SECONDS > (time() - $update_plugins->last_checked)) //Duplicate of _maybe_update_plugins, which is a private call
			return $update_plugins;
		if (!function_exists('wp_update_plugins'))
			require_once(ABSPATH . WPINC . '/update.php');
		try {
			wp_update_plugins();
		}
		catch (Exception $e) {
			wfConfig::set(self::LAST_UPDATE_CHECK_ERROR_KEY, $e->getMessage(), false);
			wfConfig::remove(self::LAST_UPDATE_CHECK_ERROR_SLUG_KEY);
			error_log('Caught exception while attempting to refresh plugin update status: ' . $e->getMessage());
		}
		catch (Throwable $t) {
			wfConfig::set(self::LAST_UPDATE_CHECK_ERROR_KEY, $t->getMessage(), false);
			wfConfig::remove(self::LAST_UPDATE_CHECK_ERROR_SLUG_KEY);
			error_log('Caught error while attempting to refresh plugin update status: ' . $t->getMessage());
		}
		return get_site_transient('update_plugins');
	}

	/**
	 * Check if any plugins need an update.
	 *
	 * @param bool $checkVulnerabilities whether or not to check for vulnerabilities while checking updates
	 *
	 * @return $this
	 */
	public function checkPluginUpdates($useCachedValued = true, $checkVulnerabilities = true) {
		if($checkVulnerabilities)
			$this->plugin_updates = array();

		self::requirePluginsApi();

		$update_plugins = $this->fetchPluginUpdates($useCachedValued);
		
		//Get the full plugin list
		if (!function_exists('get_plugins')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}
		$installedPlugins = get_plugins();

		$context = $this;

		if ($update_plugins && !empty($update_plugins->response)) {
			foreach ($update_plugins->response as $plugin => $vals) {
				$data = $this->initializePluginUpdateData($plugin, $installedPlugins, $checkVulnerabilities, function (&$data, $file) use ($context, $plugin, $vals) {
					$vals = (array) $vals;
					$data['slug'] = $context->extractSlug($plugin, $vals);
					$data['newVersion'] = (isset($vals['new_version']) ? $vals['new_version'] : 'Unknown');
					$data['wpURL'] = (isset($vals['url']) ? rtrim($vals['url'], '/') : null);
					$data['updateAvailable'] = true;
				});

				if($checkVulnerabilities && $data !== null)
					$this->plugin_updates[] = $data;
			}
		}
		
		//We have to grab the slugs from the update response because no built-in function exists to return the true slug from the local files
		if ($update_plugins && !empty($update_plugins->no_update)) {
			foreach ($update_plugins->no_update as $plugin => $vals) {
				$this->initializePluginUpdateData($plugin, $installedPlugins, $checkVulnerabilities, function (&$data, $file) use ($context, $plugin, $vals) {
					$vals = (array) $vals;
					$data['slug'] = $context->extractSlug($plugin, $vals);
					$data['wpURL'] = (isset($vals['url']) ? rtrim($vals['url'], '/') : null);
				});
			}	
		}
		
		//Get the remaining plugins (not in the wordpress.org repo for whatever reason)
		foreach ($installedPlugins as $plugin => $data) {
			$data = $this->initializePluginUpdateData($plugin, $installedPlugins, $checkVulnerabilities);
		}

		return $this;
	}

	/**
	 * Check if any themes need an update.
	 *
	 * @param bool $checkVulnerabilities whether or not to check for vulnerabilities while checking for updates
	 *
	 * @return $this
	 */
	public function checkThemeUpdates($useCachedValued = true, $checkVulnerabilities = true) {
		if($checkVulnerabilities)
			$this->theme_updates = array();

		if (!function_exists('wp_update_themes')) {
			require_once(ABSPATH . WPINC . '/update.php');
		}
		
		$update_themes = get_site_transient('update_themes');
		if ($useCachedValued && isset($update_themes->last_checked) && 12 * HOUR_IN_SECONDS > (time() - $update_themes->last_checked)) { //Duplicate of _maybe_update_themes, which is a private call
			//Do nothing, use cached value
		}
		else {
			try {
				wp_update_themes();
			}
			catch (Exception $e) {
				wfConfig::set(self::LAST_UPDATE_CHECK_ERROR_KEY, $e->getMessage(), false);
				error_log('Caught exception while attempting to refresh theme update status: ' . $e->getMessage());
			}
			catch (Throwable $t) {
				wfConfig::set(self::LAST_UPDATE_CHECK_ERROR_KEY, $t->getMessage(), false);
				error_log('Caught error while attempting to refresh theme update status: ' . $t->getMessage());
			}
			
			$update_themes = get_site_transient('update_themes');
		}

		if ($update_themes && (!empty($update_themes->response)) && $checkVulnerabilities) {
			if (!function_exists('wp_get_themes')) {
				require_once(ABSPATH . '/wp-includes/theme.php');
			}
			$themes = wp_get_themes();
			foreach ($update_themes->response as $theme => $vals) {
				foreach ($themes as $name => $themeData) {
					if (strtolower($name) == $theme) {
						$vulnerable = false;
						if (isset($themeData['Version'])) {
							$vulnerable = $this->isThemeVulnerable($theme, $themeData['Version']);
						}
						
						$data = array(
							'newVersion' => (isset($vals['new_version']) ? $vals['new_version'] : 'Unknown'),
							'package'    => (isset($vals['package']) ? $vals['package'] : null),
							'URL'        => (isset($vals['url']) ? $vals['url'] : null),
							'Name'       => $themeData['Name'],
							'name'       => $themeData['Name'],
							'version'    => $themeData['Version'],
							'vulnerable' => $vulnerable
						);
						
						$data['vulnerable'] = !empty($vulnerable);
						if ($data['vulnerable']) {
							if (isset($vulnerable['link']) && is_string($vulnerable['link'])) { $data['vulnerabilityLink'] = $vulnerable['link']; }
							if (isset($vulnerable['score'])) {
								$data['cvssScore'] = number_format(floatval($vulnerable['score']), 1);
								$data['severityColor'] = self::cvssScoreSeverityHexColor($data['cvssScore']);
								$data['severityLabel'] = self::cvssScoreSeverityLabel($data['cvssScore']);
								$data['severityClass'] = self::cvssScoreSeverityClass($data['cvssScore']);
							}
							if (isset($vulnerable['vector']) && is_string($vulnerable['vector'])) { $data['cvssVector'] = $vulnerable['vector']; }
						}
						
						$this->theme_updates[] = $data;
					}
				}
			}
		}
		return $this;
	}
	
	/**
	 * @param bool $initial if true, treat as the initial scan run
	 */
	public function checkCoreVulnerabilities($initial = false) {
		$vulnerabilities = array();
		
		include(ABSPATH . WPINC . '/version.php'); /** @var $wp_version */
		
		$core = array(
			'current' => $wp_version,
		);
		
		if ($this->needs_core_update) {
			$core['edge'] = $this->core_update_version;
		}
		
		if ($this->core_update_patch_available) {
			$core['patch'] = $this->core_update_patch_version;
		}
		
		try {
			$result = $this->api->call('core_vulnerability_check', array(), array(
				'core' => json_encode($core),
			));
			
			wfConfig::set_ser('vulnerabilities_core', $result['vulnerable'], false, wfConfig::DONT_AUTOLOAD); //Will have the index `current` with possibly `edge` and `patch` depending on what was provided above
		}
		catch (Exception $e) {
			//Do nothing
		}
	}

	private function initializePluginVulnerabilityData($plugin, &$installedPlugins, &$records, $values = null, $update = false) {
		$file = $this->checkPluginFile($plugin, $installedPlugins);
		if ($file === null)
			return null;
		$data = $installedPlugins[$plugin];
		$record = array(
			'slug' => $this->extractSlug($plugin, $values),
			'fromVersion' => isset($data['Version']) ? $data['Version'] : 'Unknown',
			'vulnerable' => false
		);
		if ($update && is_array($values))
			$record['toVersion'] = isset($values['new_version']) ? $values['new_version'] : 'Unknown';
		$records[] = $record;
		unset($installedPlugins[$plugin]);
	}

	/**
	 * @param bool $initial if true, treat as the initial scan run
	 */
	public function checkPluginVulnerabilities($initial=false) {

		self::requirePluginsApi();
		
		$vulnerabilities = array();
		
		//Get the full plugin list
		if (!function_exists('get_plugins')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}
		$installedPlugins = get_plugins();
		
		//Get the info for plugins on wordpress.org
		$update_plugins = $this->fetchPluginUpdates();
		if ($update_plugins) {
			if (!empty($update_plugins->response)) {
				foreach ($update_plugins->response as $plugin => $vals) {
					$this->initializePluginVulnerabilityData($plugin, $installedPlugins, $vulnerabilities, (array) $vals, true);
				}
			}
			
			if (!empty($update_plugins->no_update)) {
				foreach ($update_plugins->no_update as $plugin => $vals) {
					$this->initializePluginVulnerabilityData($plugin, $installedPlugins, $vulnerabilities, (array) $vals);
				}
			}
		}
		
		//Get the remaining plugins (not in the wordpress.org repo for whatever reason)
		foreach ($installedPlugins as $plugin => $data) {
			$this->initializePluginVulnerabilityData($plugin, $installedPlugins, $vulnerabilities, $data);
		}
		
		if (count($vulnerabilities) > 0) {
			try {
				$result = $this->api->call('plugin_vulnerability_check', array(), array(
					'plugins' => json_encode($vulnerabilities),
				));
				
				foreach ($vulnerabilities as &$v) {
					$vulnerableList = $result['vulnerable'];
					foreach ($vulnerableList as $r) {
						if ($r['slug'] == $v['slug']) {
							$v['vulnerable'] = !!$r['vulnerable'];
							if (isset($r['link'])) {
								$v['link'] = $r['link'];
							}
							if (isset($r['score'])) {
								$v['score'] = $r['score'];
							}
							if (isset($r['vector'])) {
								$v['vector'] = $r['vector'];
							}
							break;
						}
					}
				}
			}
			catch (Exception $e) {
				//Do nothing
			}
			
			wfConfig::set_ser('vulnerabilities_plugin', $vulnerabilities, false, wfConfig::DONT_AUTOLOAD);
		}
	}

	/**
	 * @param bool $initial whether or not this is the initial run
	 */
	public function checkThemeVulnerabilities($initial = false) {
		if (!function_exists('wp_update_themes')) {
			require_once(ABSPATH . WPINC . '/update.php');
		}
		
		self::requirePluginsApi();
		
		$this->checkThemeUpdates(!$initial, false);
		$update_themes = get_site_transient('update_themes');
		
		$vulnerabilities = array();
		if ($update_themes && !empty($update_themes->response)) {
			if (!function_exists('get_plugin_data'))
			{
				require_once(ABSPATH . '/wp-admin/includes/plugin.php');
			}
			
			foreach ($update_themes->response as $themeSlug => $vals) {
				
				$valsArray = (array) $vals;
				$theme = wp_get_theme($themeSlug);
				
				$record = array();
				$record['slug'] = $themeSlug;
				$record['toVersion'] = (isset($valsArray['new_version']) ? $valsArray['new_version'] : 'Unknown');
				$record['fromVersion'] = $theme->version;
				$record['vulnerable'] = false;
				$vulnerabilities[] = $record;
			}
			
			try {
				$result = $this->api->call('theme_vulnerability_check', array(), array(
					'themes' => json_encode($vulnerabilities),
				));
				
				foreach ($vulnerabilities as &$v) {
					$vulnerableList = $result['vulnerable'];
					foreach ($vulnerableList as $r) {
						if ($r['slug'] == $v['slug']) {
							$v['vulnerable'] = !!$r['vulnerable'];
							if (isset($r['link'])) {
								$v['link'] = $r['link'];
							}
							if (isset($r['score'])) {
								$v['score'] = $r['score'];
							}
							if (isset($r['vector'])) {
								$v['vector'] = $r['vector'];
							}
							break;
						}
					}
				}
			}
			catch (Exception $e) {
				//Do nothing
			}
			
			wfConfig::set_ser('vulnerabilities_theme', $vulnerabilities, false, wfConfig::DONT_AUTOLOAD);
		}
	}
	
	/**
	 * Returns whether the core version is vulnerable. Available $which values are `current` for the version running now,
	 * `patch` for the patch update (if available), and `edge` for the most recent update available. `patch` and `edge`
	 * are accurate only if an update is actually available and will return false otherwise.
	 * 
	 * @param string $which
	 * @return bool
	 */
	public function isCoreVulnerable($which = 'current') {
		static $_vulnerabilitiesRefreshed = false;
		$vulnerabilities = wfConfig::get_ser('vulnerabilities_core', null);
		if ($vulnerabilities === null) {
			if (!$_vulnerabilitiesRefreshed) {
				$this->checkCoreVulnerabilities(true);
				$_vulnerabilitiesRefreshed = true;
			}
			
			//Verify that we got a valid response, if not, avoid infinite recursion
			$vulnerabilities = wfConfig::get_ser('vulnerabilities_core', null);
			if ($vulnerabilities === null) {
				wordfence::status(4, 'error', __("Failed obtaining core vulnerability data, skipping check.", 'wordfence'));
				return false;
			}
			
			return $this->isCoreVulnerable($which);
		}
		
		if (!isset($vulnerabilities[$which])) {
			return false;
		}
		
		return !!$vulnerabilities[$which]['vulnerable'];
	}
	
	public function isPluginVulnerable($slug, $version) {
		return $this->_isSlugVulnerable('vulnerabilities_plugin', $slug, $version, function(){ $this->checkPluginVulnerabilities(true); });
	}
	
	public function isThemeVulnerable($slug, $version) {
		return $this->_isSlugVulnerable('vulnerabilities_theme', $slug, $version, function(){ $this->checkThemeVulnerabilities(true); });
	}
	
	private function _isSlugVulnerable($vulnerabilitiesKey, $slug, $version, $populateVulnerabilities=null) {
		static $_vulnerabilitiesRefreshed = array();
		$vulnerabilities = wfConfig::get_ser($vulnerabilitiesKey, null);
		if ( $vulnerabilities === null) {
			if (is_callable($populateVulnerabilities)) {
				if (!isset($_vulnerabilitiesRefreshed[$vulnerabilitiesKey])) {
					$populateVulnerabilities();
					$_vulnerabilitiesRefreshed[$vulnerabilitiesKey] = true;
				}
				
				$vulnerabilities = wfConfig::get_ser($vulnerabilitiesKey, null);
				if ($vulnerabilities === null) {
					wordfence::status(4, 'error', __("Failed obtaining vulnerability data, skipping check.", 'wordfence'));
					return false;
				}
				
				return $this->_isSlugVulnerable($vulnerabilitiesKey, $slug, $version);
			}
			return false;
		}
		foreach ($vulnerabilities as $v) {
			if ($v['slug'] == $slug) {
				if (
					($v['fromVersion'] == 'Unknown' && $v['toVersion'] == 'Unknown') ||
					((!isset($v['toVersion']) || $v['toVersion'] == 'Unknown') && version_compare($version, $v['fromVersion']) >= 0) ||
					($v['fromVersion'] == 'Unknown' && isset($v['toVersion']) && version_compare($version, $v['toVersion']) < 0) ||
					(version_compare($version, $v['fromVersion']) >= 0 && isset($v['toVersion']) && version_compare($version, $v['toVersion']) < 0)
				) {
					if ($v['vulnerable']) { return $v; }
					return false;
				}
			}
		}
		return false;
	}

	/**
	 * @return boolean
	 */
	public function needsCoreUpdate() {
		return $this->needs_core_update;
	}

	/**
	 * @return string
	 */
	public function getCoreUpdateVersion() {
		return $this->core_update_version;
	}
	
	/**
	 * Returns true if there is a patch version available for the site's current minor branch and the site is not on
	 * the most recent minor branch (e.g., a backported security update).
	 * 
	 * Example: suppose the site is currently on 4.1.37. This will return true and `getCoreUpdatePatchVersion` will 
	 * return 4.1.39. `getCoreUpdateVersion` will return 6.4.2 (as of writing this comment). 
	 * 
	 * @return bool
	 */
	public function coreUpdatePatchAvailable() {
		return $this->core_update_patch_available;
	}
	
	/**
	 * The version number for the patch update if available.
	 * 
	 * @return string
	 */
	public function getCoreUpdatePatchVersion() {
		return $this->core_update_patch_version;
	}
	
	/**
	 * Returns whether or not the current core version is on a major or minor release earlier than the current available 
	 * edge update.
	 * 
	 * @return bool
	 */
	public function getCoreEarlierBranch() {
		return $this->core_earlier_branch;
	}

	/**
	 * @return array
	 */
	public function getPluginUpdates() {
		return $this->plugin_updates;
	}
	
	/**
	 * @return array
	 */
	public function getAllPlugins() {
		return $this->all_plugins;
	}
	
	/**
	 * @return array
	 */
	public function getPluginSlugs() {
		return $this->plugin_slugs;
	}

	/**
	 * @return array
	 */
	public function getThemeUpdates() {
		return $this->theme_updates;
	}
}