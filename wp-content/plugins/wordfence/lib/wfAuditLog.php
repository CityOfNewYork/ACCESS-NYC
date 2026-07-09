<?php

require_once(__DIR__ . '/audit-log/wfAuditLogObserversWordPressCoreUser.php');
require_once(__DIR__ . '/audit-log/wfAuditLogObserversWordPressCoreSite.php');
require_once(__DIR__ . '/audit-log/wfAuditLogObserversWordPressCoreMultisite.php');
require_once(__DIR__ . '/audit-log/wfAuditLogObserversWordPressCoreContent.php');
require_once(__DIR__ . '/audit-log/wfAuditLogObserversWordfence.php');
require_once(__DIR__ . '/audit-log/wfAuditLogObserversPreview.php');

/**
 * Class wfAuditLog
 * 
 * Hooks into a variety of actions/filters to collect relevant data that can be recorded in an audit log. The data 
 * collected is focused around attack surfaces such as user registration and content insertion, but all attempts are
 * made to exclude potentially sensitive values from being recorded (e.g., for user profile changes, only the field
 * names are recorded).
 * 
 * Data is recorded into an intermediate table on the site itself, and a send action is scheduled. When this action
 * triggers, a send payload up to the maximum transmit count is generated. The payload is then automatically expanded so
 * that no partial request is sent, only full requests. Once sent, these are removed from the intermediate table, and
 * we check to see if there are more remaining to be sent, scheduling another send if so.
 * 
 * Because of how some of the hooks are called, there are three different points at which data may be recorded:
 * 
 * 1. At the moment the hook is called. This is most common and used for one-off actions where the recording should be 
 *    performed at that time.
 * 2. Pre-filters/actions. For these, an earlier hook in the flow is listened for, and we record state data for later
 *    use by the desired hook. This is typically used for deletions where we want some value from the record before it
 *    gets deleted.
 * 3. At the end of the request. For actions that may reasonably called multiple times in the same request (e.g., adding
 *    multiple capabilities to a role), we only need to record a single record of that action so this is done via a 
 *    coalescer at the end just prior to the request ending.
 * 
 * Some hooks do record for multiple events due to how overloaded some data structures are in WP. For example, many 
 * types are ultimately stored in `wp_posts` despite not being posts so the hooks surrounding that must check for the
 * context to determine which event to actually record.
 */
class wfAuditLog {
	const AUDIT_LOG_MODE_DEFAULT = 'default'; //Resolves to one of the below based on license type
	const AUDIT_LOG_MODE_DISABLED = 'disabled';
	const AUDIT_LOG_MODE_PREVIEW = 'preview';
	const AUDIT_LOG_MODE_SIGNIFICANT = 'significant';
	const AUDIT_LOG_MODE_ALL = 'all';
	
	//These category constants are used to divide events into the groupings in the event listing, one per event even if the event could fit under multiple
	const AUDIT_LOG_CATEGORY_AUTHENTICATION = 'authentication';
	const AUDIT_LOG_CATEGORY_USER_PERMISSIONS = 'user-permissions';
	const AUDIT_LOG_CATEGORY_PLUGINS_THEMES_UPDATES = 'plugins-themes-updates';
	const AUDIT_LOG_CATEGORY_SITE_SETTINGS = 'site-settings';
	const AUDIT_LOG_CATEGORY_MULTISITE = 'multisite';
	const AUDIT_LOG_CATEGORY_CONTENT = 'content';
	const AUDIT_LOG_CATEGORY_FIREWALL = 'firewall';
	
	const AUDIT_LOG_MAX_SAMPLES = 20; //Max number of requests to store in the local summary, each of which may have one or more events
	
	const AUDIT_LOG_HEARTBEAT = 'heartbeat'; //A unique event that is sent to signal the audit log is functioning even if no other events have triggered, not displayed on the front end
	
	private $_pending = array();
	private $_coalescers = array();
	private $_destructRegistered = false;
	
	private $_state = array();
	private $_performingFinalization = false;
	
	protected static $initialCoreVersion;
	protected static $initialMode;
	
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new wfAuditLog();
		}
		return $_shared;
	}
	
	/**
	 * Returns the events that will cause an immediate send rather than waiting for the cron event to execute. 
	 * Individual observer grouping subclasses must override this and return their subset of the event categories. The 
	 * primary audit log class will return an array of all observer groupings merged together.
	 * 
	 * @return array
	 */
	public static function immediateSendEvents() {
		static $eventCache = null;
		if ($eventCache === null) {
			$eventCache = array();
			
			$observers = self::_observers();
			foreach ($observers as $o) {
				$merging = call_user_func(array($o, 'immediateSendEvents'));
				$eventCache = array_merge($eventCache, $merging);
			}
		}
		
		return $eventCache;
	}
	
	/**
	 * Returns the event categories for use in the Audit Log page's UI. Individual observer grouping subclasses
	 * must override this and return their subset of the event categories. The primary audit log class will return an 
	 * array of all observer groupings merged together.
	 *
	 *
	 * @return array
	 */
	public static function eventCategories() {
		static $categoryCache = null;
		if ($categoryCache === null) {
			$categoryCache = array();
			
			$observers = self::_observers();
			foreach ($observers as $o) {
				$merging = call_user_func(array($o, 'eventCategories'));
				foreach ($merging as $category => $events) {
					if (isset($categoryCache[$category])) {
						$categoryCache[$category] = array_merge($categoryCache[$category], $events);
					}
					else {
						$categoryCache[$category] = $events;
					}
				}
			}
		}
		
		return $categoryCache;
	}
	
	/**
	 * Returns the category for $event, null if not found.
	 * 
	 * @param string $event
	 * @return string|null
	 */
	public static function eventCategory($event) {
		static $reverseCategoryMapCache = null;
		if ($reverseCategoryMapCache === null) {
			$reverseCategoryMapCache = array();
			$categories = self::eventCategories();
			foreach ($categories as $category => $events) {
				$reverseCategoryMapCache = array_merge($reverseCategoryMapCache, array_fill_keys($events, $category));
			}
		}
		
		if (isset($reverseCategoryMapCache[$event])) {
			return $reverseCategoryMapCache[$event];
		}
		return null;
	}
	
	/**
	 * Returns the event names suitable for display in the Audit Log page's UI. Individual observer grouping subclasses 
	 * must override this and return their subset of the event names. The primary audit log class will return an array 
	 * of all observer groupings merged together.
	 * 
	 * 
	 * @return array
	 */
	public static function eventNames() {
		static $nameCache = null;
		if ($nameCache === null) {
			$nameCache = array();
			
			$observers = self::_observers();
			foreach ($observers as $o) {
				$nameCache = array_merge($nameCache, call_user_func(array($o, 'eventNames')));
			}
		}
		
		return $nameCache;
	}
	
	/**
	 * Returns the display name for the given event identifier.
	 * 
	 * @param string $event
	 * @return string
	 */
	public static function eventName($event) {
		$map = self::eventNames();
		if (isset($map[$event])) {
			return $map[$event];
		}
		return __('Unknown Events', 'wordfence');
	}
	
	/**
	 * Returns the event rate limiters for use in preprocessing events that occur. A rate limiter for an event type 
	 * should use the passed $auditLog and $payload values to determine whether the proposed event should be recorded. 
	 * The primary audit log class will return an array of all observer groupings merged together.
	 *
	 *
	 * @return array
	 */
	public static function eventRateLimiters() {
		static $rateLimiterCache = null;
		if ($rateLimiterCache === null) {
			$rateLimiterCache = array();
			
			$observers = self::_observers();
			foreach ($observers as $o) {
				$rateLimiterCache = array_merge($rateLimiterCache, call_user_func(array($o, 'eventRateLimiters')));
			}
		}
		
		return $rateLimiterCache;
	}
	
	/**
	 * Consumes the rate limiter by setting a transient for the given $ttl. Currently this just allows a bucket of one,
	 * but this could be refactored in the future to allow variable rate limits.
	 * 
	 * @param string $event
	 * @param string $payloadSignature
	 * @param int $ttl Default is 10 minutes
	 */
	protected static function _rateLimiterConsume($event, $payloadSignature, $ttl = 600) {
		$key = 'wordfenceAuditEvent:' . $event . ':' . $payloadSignature;
		set_transient($key, time(), $ttl);
	}
	
	/**
	 * Returns whether or not the rate limiter is available. The return value is `true` if it is, otherwise `false`.
	 * 
	 * @param string $event
	 * @param string $payloadSignature
	 * @return bool
	 */
	protected static function _rateLimiterCheck($event, $payloadSignature) {
		$key = 'wordfenceAuditEvent:' . $event . ':' . $payloadSignature;
		return !get_transient($key);
	}
	
	/**
	 * Recursively computes a hash for the given payload in a deterministic way. This may be used in rate limiter
	 * implementations for deduplication checks.
	 * 
	 * @param mixed $payload
	 * @param null|HashContext $hasher
	 * @return bool|string
	 */
	protected static function _normalizedPayloadHash($payload, $hasher = null) {
		$first = is_null($hasher);
		if ($first) {
			$hasher = hash_init('sha256');
		}
		
		if (is_array($payload) || is_object($payload)) {
			$payload = (array) $payload;
			$keys = array_keys($payload);
			sort($keys, SORT_REGULAR);
			foreach ($keys as $k) {
				$v = $payload[$k];
				hash_update($hasher, $k);
				self::_normalizedPayloadHash($v, $hasher);
			}
		}
		else if (is_scalar($payload)) {
			hash_update($hasher, $payload);
		}
		
		if ($first) {
			return hash_final($hasher);
		}
		return true;
	}
	
	/**
	 * Returns an array of all observer groupings.
	 * 
	 * @return array
	 */
	private static function _observers() {
		return array(
			wfAuditLogObserversWordPressCoreUser::class,
			wfAuditLogObserversWordPressCoreSite::class,
			wfAuditLogObserversWordPressCoreMultisite::class,
			wfAuditLogObserversWordPressCoreContent::class,
			wfAuditLogObserversWordfence::class,
		);
	}
	
	/**
	 * Registers the observers for this class's chunk of functionality that should run regardless of other settings.
	 * These observers are expected to do their own check and application of settings like the audit log's mode or
	 * the `Participate in the Wordfence Security Network` setting.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerForcedObservers($auditLog) {
		//Individual forced observer groupings may override this
	}
	
	/**
	 * Registers the observers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerObservers($auditLog) {
		//Individual observer groupings will override this
	}
	
	/**
	 * Registers the data gatherers for this class's chunk of functionality. These are secondary hooks to support 
	 * intermediate data gathering (e.g., grabbing the user attempting to authenticate even if it fails)
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerDataGatherers($auditLog) {
		//Individual data gatherer groupings will override this
	}
	
	/**
	 * Registers the coalescers for this class's chunk of functionality.
	 *
	 * @param wfAuditLog $auditLog
	 */
	protected static function _registerCoalescers($auditLog) {
		//Individual coalescer groupings will override this
	}
	
	public static function heartbeat() {
		if (wfAuditLog::shared()->mode() != wfAuditLog::AUDIT_LOG_MODE_DISABLED && wfAuditLog::shared()->mode() != wfAuditLog::AUDIT_LOG_MODE_PREVIEW) {
			wfAuditLog::shared()->_recordAction(self::AUDIT_LOG_HEARTBEAT);
		}
	}
	
	/**
	 * Returns the effective audit log mode after factoring in the active license type and resolving the default based 
	 * on that type. Will be one of the wfAuditLog::AUDIT_LOG_MODE_* constants that is not AUDIT_LOG_MODE_DEFAULT.
	 * 
	 * @return string
	 */
	public function mode() {
		require(__DIR__ . '/wfVersionSupport.php'); /** @var $wfFeatureWPVersionAuditLog */
		require(ABSPATH . WPINC . '/version.php'); /** @var string $wp_version */
		if (version_compare($wp_version, $wfFeatureWPVersionAuditLog, '<')) {
			return self::AUDIT_LOG_MODE_DISABLED;
		}
		
		$mode = wfConfig::get('auditLogMode', self::AUDIT_LOG_MODE_DEFAULT);
		$license = wfLicense::current();
		if (!$license->isPaidAndCurrent() || !$license->isAtLeastPremium()) {
			if ($mode == self::AUDIT_LOG_MODE_DISABLED) {
				return $mode;
			}
			return self::AUDIT_LOG_MODE_PREVIEW;
		}
		
		if ($mode == self::AUDIT_LOG_MODE_DEFAULT) {
			if (!$license->isAtLeastCare()) {
				return self::AUDIT_LOG_MODE_PREVIEW;
			}
			
			return self::AUDIT_LOG_MODE_SIGNIFICANT;
		}
		
		return $mode;
	}
	
	public function registerHooks() {
		self::$initialMode = $this->mode();
		
		require(ABSPATH . WPINC . '/version.php'); /** @var string $wp_version */
		self::$initialCoreVersion = $wp_version;
		
		$observers = self::_observers();
		foreach ($observers as $o) {
			call_user_func(array($o, '_registerForcedObservers'), $this);
		}
		
		if ($this->mode() == self::AUDIT_LOG_MODE_DISABLED) {
			return;
		}
		
		if ($this->mode() == self::AUDIT_LOG_MODE_PREVIEW) { //When in preview mode, we register the local-only observers to keep the preview data fresh locally
			wfAuditLogObserversPreview::_registerObservers($this);
			wfAuditLogObserversPreview::_registerDataGatherers($this);
			wfAuditLogObserversPreview::_registerCoalescers($this);
			return;
		}
		
		foreach ($observers as $o) {
			call_user_func(array($o, '_registerObservers'), $this);
			call_user_func(array($o, '_registerDataGatherers'), $this);
			call_user_func(array($o, '_registerCoalescers'), $this);
		}
	}
	
	/**
	 * Convenience method to add a listener for one or more WordPress hooks. This simplifies the normal flow of adding
	 * a listener by using introspection on the passed callable to pass the correct arguments.
	 * 
	 * @param array|string $hooks
	 * @param callable $closure
	 * @param string $type
	 */
	protected function _addObserver($hooks, $closure, $type = 'action') {
		if (!is_array($hooks)) {
			$hooks = array($hooks);
		}
		
		try {
			$introspection = new ReflectionFunction($closure);
			if ($type == 'action') {
				foreach ($hooks as $hook) {
					add_action($hook, $closure, 1, $introspection->getNumberOfParameters());
				}
			}
			else if ($type == 'filter') {
				foreach ($hooks as $hook) {
					add_filter($hook, $closure, 1, $introspection->getNumberOfParameters());
				}
			}
		}
		catch (Exception $e) {
			//Ignore
		}
	}
	
	protected function _addCoalescer($closure) {
		$this->_coalescers[] = $closure;
	}
	
	/**
	 * Returns whether or not a state value exists for the given key/blog pair.
	 * 
	 * @param string $key
	 * @param int $id An ID when tracking multiple potential states. May be the blog ID if multisite or a user ID.
	 * @return bool
	 */
	protected function _hasState($key, $id = 1) {
		if ($id < 0) {
			$id = 0;
		}
		
		if (!isset($this->_state[$id])) {
			return false;
		}
		
		return isset($this->_state[$id][$key]);
	}
	
	/**
	 * Stores a state value under the key/blog pair for later use in this request.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param int $id An ID when tracking multiple potential states. May be the blog ID if multisite or a user ID.
	 */
	protected function _trackState($key, $value, $id = 1) {
		if ($id < 0) {
			$id = 0;
		}
		
		if (!isset($this->_state[$id])) {
			$this->_state[$id] = array();
		}
		
		$this->_state[$id][$key] = $value;
	}
	
	/**
	 * Returns the state value for the key/blog pair if present, otherwise null.
	 * 
	 * @param string $key
	 * @param int $id An ID when tracking multiple potential states. May be the blog ID if multisite or a user ID.
	 * @return mixed|null
	 */
	protected function _getState($key, $id = 1) {
		if ($id < 0) {
			$id = 0;
		}
		
		if (!isset($this->_state[$id]) || !isset($this->_state[$id][$key])) {
			return null;
		}
		
		return $this->_state[$id][$key];
	}
	
	/**
	 * Returns all site(s)' state values for $key if present. They keys in the returned array are the blog ID.
	 * 
	 * @param string $key
	 * @return array Will have at most 1 entry for single-site, potentially many for multisite when applicable.
	 */
	protected function _getAllStates($key) {
		$result = array();
		foreach ($this->_state as $id => $state) {
			if (isset($state[$key])) {
				$result[$id] = $state[$key];
			}
		}
		return $result;
	}
	
	/**
	 * Record the action and metadata for later sending to the audit log.
	 * 
	 * @param string $action
	 * @param array $metadata
	 * @param bool $appendToExisting When true, does not create a new entry and instead only appends to entries of the same $action
	 */
	protected function _recordAction($action, $metadata = array(), $appendToExisting = false) {
		$rateLimiters = self::eventRateLimiters();
		if (isset($rateLimiters[$action])) {
			if (!$rateLimiters[$action]($this, $metadata)) {
				return;
			}
		}
		
		if ($appendToExisting) {
			foreach ($this->_pending as &$entry) {
				if ($entry['action'] == $action) {
					$entry['metadata'] = array_merge($entry['metadata'], $metadata);
				}
			}
			return;
		}
		
		$path = null;
		$body = null;
		if (@php_sapi_name() === 'cli' || !array_key_exists('REQUEST_METHOD', $_SERVER)) {
			if (isset($_SERVER['argv']) && is_array($_SERVER['argv']) && count($_SERVER['argv']) > 0) {
				$path = $_SERVER['argv'][0] . ' ' . implode(' ', array_map(function($p) { return '\'' . addcslashes($p, '\'') . '\''; }, array_slice($_SERVER['argv'], 1)));
				$body = array('type' => 'cli', 'files' => array(), 'parameters' => array('argv' => $_SERVER['argv']));
			}
			$method = 'CLI';
		}
		else {
			$path = $_SERVER['REQUEST_URI'];
			$method = $_SERVER['REQUEST_METHOD'];
			if ($_SERVER['REQUEST_METHOD'] != 'GET') {
				$body = $this->_sanitizeRequestBody();
			}
		}
		
		$user = wp_get_current_user();
		$entry = array(
			'action' => $action,
			'time' => wfUtils::normalizedTime(),
			'metadata' => $metadata,
			'context' => array(
				'ip' => wfUtils::getIP(),
				'path' => $path,
				'method' => $method,
				'body' => $body,
				'user_id' => $user ? $user->ID : 0,
				'userdata' => $this->_sanitizeUserdata($user),
			),
		);
		
		if (is_multisite()) {
			$network = get_network();
			$blog = get_blog_details();
			$entry['multisite'] = $this->_sanitizeMultisiteData($network, $blog);
		}
		
		$this->_pending[] = $entry;
		
		$this->_needsDestruct();
	}
	
	/**
	 * Finalizes the pending actions. If cron is disabled or one of the types is on the immedate send list, they are 
	 * finalized by immediately sending to the audit log. Otherwise, they are saved to the intermediate storage table 
	 * and a send is scheduled.
	 */
	private function _savePending() {
		if (!empty($this->_pending)) {
			$sendImmediately = false;
			$immediateSend = self::immediateSendEvents();
			$payload = array();
			foreach ($this->_pending as $data) {
				$time = $data['time'];
				unset($data['time']);
				
				if ($data['action'] == self::AUDIT_LOG_HEARTBEAT) { //Minimize payload for heartbeat
					$payload[] = array(
						'type' => $data['action'],
						'data' => array(),
						'event_time' => $time,
					);
				}
				else {
					$payload[] = array(
						'type' => $data['action'],
						'data' => $data,
						'event_time' => $time,
					);
				}
				
				$sendImmediately = ($sendImmediately || in_array($data['action'], $immediateSend));
			}
			
			if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
				$sendImmediately = true;
			}
			
			if ($sendImmediately && !wfCentral::isConnected()) {
				$this->_saveEventsToTable($payload);
				if ($ts = wp_next_scheduled('wordfence_batchSendAuditEvents')) {
					$this->_unscheduleSendPendingAuditEvents($ts);
				}
				$this->_scheduleSendPendingAuditEvents();
				$this->_pending = array();
				return;
			}
			
			$before = $payload;
			if ($sendImmediately) {
				$requestID = wfConfig::atomicInc('auditLogRequestNumber');
				
				foreach ($payload as &$p) {
					$p['data'] = json_encode($p['data']);
					$p['request_id'] = $requestID;
				}
			}
			
			try {
				if ($this->_sendAuditLogEvents($payload, $sendImmediately)) {
					$this->_pending = array();
				}
			}
			catch (wfAuditLogSendFailedException $e) {
				if ($sendImmediately) {
					$this->_saveEventsToTable($before);
					if ($ts = wp_next_scheduled('wordfence_batchSendAuditEvents')) {
						$this->_unscheduleSendPendingAuditEvents($ts);
					}
					$this->_scheduleSendPendingAuditEvents(true);
					$this->_pending = array();
				}
			}
		}
	}
	
	protected function _needsDestruct() {
		if (!$this->_destructRegistered) {
			register_shutdown_function(array($this, '_lastAction'));
			$this->_destructRegistered = true;
		}
	}
	
	/**
	 * Performed as a shutdown handler to finalize all pending actions.
	 * 
	 * Note: must remain `public` for PHP 7 compatibility
	 */
	public function _lastAction() {
		global $wpdb;
		$suppressed = $wpdb->suppress_errors(!(defined('WFWAF_DEBUG') && WFWAF_DEBUG));
		
		$this->_performingFinalization = true;
		foreach ($this->_coalescers as $c) {
			call_user_func($c);
		}
		$this->_coalescers = array();
		$this->_savePending();
		$this->_performingFinalization = false;
		
		$wpdb->suppress_errors($suppressed);
	}
	
	public function isFinalizing() {
		return $this->_performingFinalization;
	}
	
	/**
	 * Performs the actual send of $events to the audit log if $sendImmediately is truthy, otherwise it writes them to
	 * the intermediate storage table and schedules a send.
	 * 
	 * @param array $events
	 * @param bool $sendImmediately
	 * @return bool
	 * @throws wfAuditLogSendFailedException
	 */
	private function _sendAuditLogEvents($events, $sendImmediately = false) {
		if (empty($events)) {
			return true;
		}
		
		if (!wfCentral::isConnected()) {
			return false; //This will cause it to mark them as unsent and try again later
		}
		
		if ($sendImmediately) {
			$payload = array();
			foreach ($events as $e) {
				$payload[] = self::_formatEventForTransmission($e);
			}
			
			$siteID = wfConfig::get('wordfenceCentralSiteID');
			$request = new wfCentralAuthenticatedAPIRequest('/site/' . $siteID . '/audit-log', 'POST', array(
				'data' => $payload,
			));
			try {
				$doing_cron = function_exists('wp_doing_cron') /* WP >= 4.8 */ ? wp_doing_cron() : (defined('DOING_CRON') && DOING_CRON);
				$response = $request->execute($doing_cron ? 10 : 3);
				
				if ($response->isError()) {
					throw new wfAuditLogSendFailedException();
				}
				
				//Group by request and update the local preview
				$preview = array();
				foreach ($payload as $r) {
					if (!isset($preview[$r['attributes']['request_id']])) {
						$preview[$r['attributes']['request_id']] = array();
					}
					$preview[$r['attributes']['request_id']][] = array($r['attributes']['type'], $r['attributes']['event_time']);
				}
				uksort($preview, function($k1, $k2) {
					if ($k1 == $k2) { return 0; }
					return ($k1 < $k2) ? 1 : -1;
				});
				$this->_updateAuditPreview(array_values($preview));
			}
			catch (Exception $e) {
				if (!defined('WORDFENCE_DEACTIVATING') || !WORDFENCE_DEACTIVATING) { wfCentralAPIRequest::handleInternalCentralAPIError($e); }
				throw new wfAuditLogSendFailedException();
			}
			catch (Throwable $t) {
				if (!defined('WORDFENCE_DEACTIVATING') || !WORDFENCE_DEACTIVATING) { wfCentralAPIRequest::handleInternalCentralAPIError($t); }
				throw new wfAuditLogSendFailedException();
			}
		}
		else {
			$this->_saveEventsToTable($events, $sendImmediately);
			
			if (($ts = $this->_isScheduledAuditEventCronOverdue()) || $sendImmediately) {
				if ($ts) {
					$this->_unscheduleSendPendingAuditEvents($ts);
				}
				self::sendPendingAuditEvents();
			}
			else {
				$this->_scheduleSendPendingAuditEvents();
			}
		}
		
		return true;
	}
	
	private function _saveEventsToTable($events, &$sendImmediately = false) {
		$requestID = wfConfig::atomicInc('auditLogRequestNumber');
		
		$wfdb = new wfDB();
		$table_wfAuditEvents = wfDB::networkTable('wfAuditEvents');
		$query = "INSERT INTO {$table_wfAuditEvents} (`type`, `data`, `event_time`, `request_id`, `state`, `state_timestamp`) VALUES ";
		$query .= implode(', ', array_fill(0, count($events), "('%s', '%s', %f, %d, 'new', NOW())"));
		
		$immediateSendTypes = self::immediateSendEvents();
		$args = array();
		foreach ($events as $e) {
			$sendImmediately = $sendImmediately || in_array($e['type'], $immediateSendTypes);
			$args[] = $e['type'];
			$args[] = json_encode($e['data']);
			$args[] = $e['event_time'];
			$args[] = $requestID;
		}
		$wfdb->queryWriteArray($query, $args);
	}
	
	/**
	 * Sends any pending audit events up to the limit (default 100). The list will automatically expand if needed to include 
	 * only complete requests so that no partial requests are sent.
	 * 
	 * If the events fail to send or there are more remaining, another future send will be scheduled if $scheduleFollowup is truthy.
	 * 
	 * @param int $limit
	 * @param bool $scheduleFollowup Whether or not to schedule a followup send if there are more events pending, if false also unschedules any pending cron
	 */
	public static function sendPendingAuditEvents($limit = 100, $scheduleFollowup = true) {
		$wfdb = new wfDB();
		$table_wfAuditEvents = wfDB::networkTable('wfAuditEvents');
		
		$limit = intval($limit);
		$rawEvents = $wfdb->querySelect("SELECT * FROM {$table_wfAuditEvents} WHERE `state` = 'new' ORDER BY `id` ASC LIMIT {$limit}");
		if (empty($rawEvents)) {
			return;
		}
		
		//Grab the entirety of the last request ID, even if it's beyond the 100 item limit
		$last = wfUtils::array_last($rawEvents);
		$extendedID = (int) $last['id'];
		$extendedRequestID = (int) $last['request_id'];
		$extendedEvents = $wfdb->querySelect("SELECT * FROM {$table_wfAuditEvents} WHERE `state` = 'new' AND `id` > {$extendedID} AND `request_id` = {$extendedRequestID} ORDER BY `id` ASC");
		$rawEvents = array_merge($rawEvents, $extendedEvents);
		
		//Process for submission
		$ids = array();
		foreach ($rawEvents as $r) {
			$ids[] = intval($r['id']);
		}
		
		$idParam = '(' . implode(', ', $ids) . ')';
		$wfdb->queryWrite("UPDATE {$table_wfAuditEvents} SET `state` = 'sending', `state_timestamp` = NOW() WHERE `id` IN {$idParam}");
		try {
			if (self::shared()->_sendAuditLogEvents($rawEvents, true)) {
				$wfdb->queryWrite("UPDATE {$table_wfAuditEvents} SET `state` = 'sent', `state_timestamp` = NOW() WHERE `id` IN {$idParam}");
				
				if ($scheduleFollowup) {
					self::checkForUnsentAuditEvents();
				}
			}
			else {
				$wfdb->queryWrite("UPDATE {$table_wfAuditEvents} SET `state` = 'new', `state_timestamp` = NOW() WHERE `id` IN {$idParam}");
				if ($scheduleFollowup) {
					self::shared()->_scheduleSendPendingAuditEvents();
				}
			}
			
			if (!$scheduleFollowup) {
				if ($ts = wp_next_scheduled('wordfence_batchSendAuditEvents')) {
					self::shared()->_unscheduleSendPendingAuditEvents($ts);
				}
			}
		}
		catch (wfAuditLogSendFailedException $e) {
			$wfdb->queryWrite("UPDATE {$table_wfAuditEvents} SET `state` = 'new', `state_timestamp` = NOW() WHERE `id` IN {$idParam}");
			if ($ts = wp_next_scheduled('wordfence_batchSendAuditEvents')) {
				self::shared()->_unscheduleSendPendingAuditEvents($ts);
			}
			
			if (!defined('WORDFENCE_DEACTIVATING') || !WORDFENCE_DEACTIVATING) {
				self::shared()->_scheduleSendPendingAuditEvents(true);
			}
		}
	}
	
	/**
	 * Formats the event record for transmission to Central for recording.
	 * 
	 * @param array $rawEvent
	 * @return array
	 */
	private static function _formatEventForTransmission($rawEvent) {
		if ($rawEvent['type'] == self::AUDIT_LOG_HEARTBEAT) { //Minimize payload for heartbeat
			return array(
				'type' => 'audit-event',
				'attributes' => array(
					'type' => $rawEvent['type'],
					'event_time' => (int) $rawEvent['event_time'],
					'request_id' => (int) $rawEvent['request_id'],
				)
			);
		}
		
		$data = json_decode($rawEvent['data'], true);
		if (empty($data)) { $data = array(); }
		unset($data['action']);
		$username = null; if (!empty($data['context']['userdata']) && isset($data['context']['userdata']['user_login'])) { $username = $data['context']['userdata']['user_login']; }
		$ip = null; if (!empty($data['context']['ip'])) { $ip = $data['context']['ip']; unset($data['context']['ip']); }
		$path = null; if (!empty($data['context']['path'])) { $path = $data['context']['path']; unset($data['context']['path']); }
		$method = null; if (!empty($data['context']['method'])) { $method = $data['context']['method']; unset($data['context']['method']); }
		$body = null; if (!empty($data['context']['body'])) { $body = $data['context']['body']; unset($data['context']['body']); }
		
		return array(
			'type' => 'audit-event',
			'attributes' => array(
				'type' => $rawEvent['type'],
				'username' => $username,
				'ip_address' => $ip,
				'method' => $method,
				'path' => $path,
				'request_body' => $body,
				'data' => $data,
				'event_time' => (int) $rawEvent['event_time'],
				'request_id' => (int) $rawEvent['request_id'],
			)
		);
	}
	
	/**
	 * Schedules a cron for sending pending audit events.
	 */
	private function _scheduleSendPendingAuditEvents($forceDelay = false) {
		if ((self::$initialMode == self::AUDIT_LOG_MODE_DISABLED || self::$initialMode == self::AUDIT_LOG_MODE_PREVIEW) && ($this->mode() == self::AUDIT_LOG_MODE_DISABLED || $this->mode() == self::AUDIT_LOG_MODE_PREVIEW)) {
			return; //Do not schedule cron if mode is disabled/preview and was not recently put into that state
		}
		
		$delay = 60;
		if ($forceDelay || !wfCentral::isConnected()) {
			$delay = 3600;
		}
		
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		if (!wp_next_scheduled('wordfence_batchSendAuditEvents')) {
			wp_schedule_single_event(time() + $delay, 'wordfence_batchSendAuditEvents');
		}
		if ($notMainSite) {
			restore_current_blog();
		}
	}
	
	/**
	 * @param int $timestamp
	 */
	private function _unscheduleSendPendingAuditEvents($timestamp) {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		if ($timestamp) {
			wp_unschedule_event($timestamp, 'wordfence_batchSendAuditEvents');
		}
		if ($notMainSite) {
			restore_current_blog();
		}
	}
	
	private function _isScheduledAuditEventCronOverdue() {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		
		$overdue = false;
		if ($ts = wp_next_scheduled('wordfence_batchSendAuditEvents')) {
			if ((time() - $ts) > 900) {
				$overdue = $ts;
			}
		}
		
		if ($notMainSite) {
			restore_current_blog();
		}
		
		return $overdue;
	}
	
	public static function checkForUnsentAuditEvents() {
		$wfdb = new wfDB();
		$table_wfAuditEvents = wfDB::networkTable('wfAuditEvents');
		$wfdb->queryWrite("UPDATE {$table_wfAuditEvents} SET `state` = 'new', `state_timestamp` = NOW() WHERE `state` = 'sending' AND `state_timestamp` < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
		
		$count = $wfdb->querySingle("SELECT COUNT(*) AS cnt FROM {$table_wfAuditEvents} WHERE `state` = 'new'");
		if ($count) {
			self::shared()->_scheduleSendPendingAuditEvents();
		}
	}
	
	public static function trimAuditEvents() {
		$wfdb = new wfDB();
		$table_wfAuditEvents = wfDB::networkTable('wfAuditEvents');
		$count = $wfdb->querySingle("SELECT COUNT(*) AS cnt FROM {$table_wfAuditEvents}");
		if ($count > 1000) {
			$wfdb->truncate($table_wfAuditEvents); //Similar behavior to other logged data, assume possible DoS so truncate
		}
		else if ($count > 100) {
			$wfdb->queryWrite("DELETE FROM {$table_wfAuditEvents} ORDER BY id ASC LIMIT %d", $count - 100);
		}
		else if ($count > 0) {
			$wfdb->queryWrite("DELETE FROM {$table_wfAuditEvents} WHERE (`state` = 'sending' OR `state` = 'sent') AND `state_timestamp` < DATE_SUB(NOW(), INTERVAL 1 DAY)");
		}
	}
	
	public static function hasOverdueEvents() {
		$wfdb = new wfDB();
		$table_wfAuditEvents = wfDB::networkTable('wfAuditEvents');
		$count = $wfdb->querySingle("SELECT COUNT(*) AS cnt FROM {$table_wfAuditEvents} WHERE `state` = 'new' AND `state_timestamp` < DATE_SUB(NOW(), INTERVAL 2 DAY)");
		return $count > 0;
	}
	
	/**
	 * Updates the locally-stored audit preview data that is used to populate the audit log page. The preview data is
	 * stored in descending order.
	 * 
	 * @param array $events Structure is [
	 * 										[ //Request 1
	 * 											[<event type>, <timestamp>],
	 * 											[<event type>, <timestamp>],
	 * 											[<event type>, <timestamp>],
	 * 										],
	 * 										[ //Request 2
	 * 											[<event type>, <timestamp>],
	 * 										],
	 * 										...
	 * 									]
	 */
	protected function _updateAuditPreview($events) {
		$filtered = array();
		foreach ($events as $request) {
			$request = array_filter($request, function($e) {
				return $e[0] != self::AUDIT_LOG_HEARTBEAT; //Don't save heartbeats to the local preview
			});
			if (!empty($request)) {
				$filtered[] = $request;
			}
		}
		$events = $filtered;
		if (empty($events)) { return; }
		
		$existing = wfConfig::get_ser('lastAuditEvents', array());
		if (!is_array($existing)) {
			$existing = array();
		}
		
		$lastAuditEvents = array_merge($events, $existing);
		usort($lastAuditEvents, function($a, $b) {
			$aMax = array_reduce($a, function($carry, $item) {
				return max($carry, $item[1]);
			}, 0);
			$bMax = array_reduce($b, function($carry, $item) {
				return max($carry, $item[1]);
			}, 0);
			if ($aMax == $bMax) { return 0; }
			return ($aMax < $bMax) ? 1 : -1;
		});
		
		$lastAuditEvents = array_slice($lastAuditEvents, 0, self::AUDIT_LOG_MAX_SAMPLES);
		wfConfig::set_ser('lastAuditEvents', $lastAuditEvents);
	}
	
	/**
	 * Returns a summary array of recent events for the audit log. The content of this array will be the most recent
	 * `AUDIT_LOG_MAX_SAMPLES` requests that were sent (or would have been sent if enabled) to Wordfence Central.
	 * 
	 * @return array
	 */
	public function auditPreview() {
		$requests = array_filter(wfConfig::get_ser('lastAuditEvents', array()), function($events) {
			return !empty($events);
		});
		
		$data = array();
		if (is_array($requests)) {
			$data['requests'] = array();
			foreach ($requests as $r) {
				$events = array_map(function($e) {
					return array(
						'ts' => $e[1],
						'event' => $e[0],
						'name' => self::eventName($e[0]),
						'category' => self::eventCategory($e[0]),
					);
				}, $r);
				
				$types = array_reduce($events, function($carry, $e) { //We'll use the most common category if a request covers multiple
					if (!isset($carry[$e['category']])) {
						$carry[$e['category']] = 0;
					}
					$carry[$e['category']]++;
					return $carry;
				}, array());
				asort($types, SORT_NUMERIC);
				
				$timestamp = array_reduce($events, function($carry, $e) {
					if ($e['ts'] > $carry) {
						return $e['ts'];
					}
					return $carry;
				}, 0);
				
				$data['requests'][] = array(
					'ts' => $timestamp,
					'category' => array_keys($types),
					'events' => $events,
				);
			}
		}
		
		return $data;
	}
	
	/**************************************
	 * Utility Functions
	 **************************************/
	
	private function _sanitizeRequestBody() {
		$input = wfUtils::rawPOSTBody();
		$contentType = null;
		if (isset($_SERVER['CONTENT_TYPE'])) {
			$contentType = strtolower($_SERVER['CONTENT_TYPE']);
			$boundary = strpos($contentType, ';');
			if ($boundary !== false) {
				$contentType = substr($contentType, 0, $boundary);
			}
		}
		
		$raw = null;
		$response = array('type' => null, 'parameters' => array(), 'files' => array());
		switch ($contentType) {
			case 'application/json':
				try {
					$raw = json_decode($input, true, 512, JSON_OBJECT_AS_ARRAY);
					$response['type'] = 'json';
				}
				catch (Exception $e) {
					//Ignore -- can throw on PHP 8+
				}
				break;
			case 'multipart/form-data': //PHP has already parsed this into $_POST and $_FILES
				$response['type'] = 'multipart';
				foreach ($_FILES as $k => $f) {
					$response['files'][] = array(
						'name' => $f['name'],
						'type' => $f['type'],
						'size' => $f['size'],
						'error' => $f['error'],
					);
				}
				$raw = $_POST;
				break;
			default: //Typically application/x-www-form-urlencoded
				if ($input) {
					parse_str($input, $raw);
					$response['type'] = 'urlencoded';
				}
				break;
		}
		
		if (!empty($raw)) {
			foreach ($raw as $k => $v) {
				$response['parameters'][$k] = null;
				if ($k == 'action' || //Used in admin-ajax and many other WP calls, typically relevant for auditing and not sensitive
					$k == 'id' || //Typically the record ID being affected
					$k == 'log' //Authentication username
				) {
					$response['parameters'][$k] = $v;
				}
				// else if -- future full value captures go here, otherwise we just capture the parameter name for privacy reasons
			}
			return $response;
		}
		
		return null;
	}
	
	/**
	 * Returns the desired fields from $userdata for the various user-related hooks, ignoring the rest. Returns null if
	 * there is no valid user.
	 * 
	 * @param array|object|WP_User $userdata
	 * @param null|int $user_id Used when provided, otherwise extracted from $userdata when possible
	 * @return array|null
	 */
	protected function _sanitizeUserdata($userdata, $user_id = null) {
		if ($userdata === null && $user_id !== null) { //May hit this on older WP versions where $userdata wasn't populated by the hook call
			$userdata = get_user_by('ID', $user_id);
		}
		
		$roles = array();
		if ($userdata instanceof stdClass) {
			$user = new WP_User($user_id !== null ? $user_id : (isset($userdata->ID) ? $userdata->ID : 0));
			if ($user->exists()) {
				$roles = $user->roles;
			}
			$userdata = get_object_vars( $userdata );
		} 
		else if ($userdata instanceof WP_User) {
			$roles = $userdata->roles;
			$userdata = $userdata->to_array();
		}
		else {
			$user = new WP_User($user_id !== null ? $user_id : (isset($userdata['ID']) ? $userdata['ID'] : 0));
			if (!$user) {
				return array(
					'user_id' => 0,
					'user_login' => '',
					'user_roles' => array(),
				);
			}
			
			if ($user->exists()) {
				$roles = $user->roles;
			}
		}
		
		return array(
			'user_id' => $user_id !== null ? $user_id : (isset($userdata['ID']) ? $userdata['ID'] : 0),
			'user_login' => isset($userdata['user_login']) ? $userdata['user_login'] : '',
			'user_roles' => $roles,
		);
	}
	
	protected function _userdataDiff($userdata1, $userdata2) {
		if ($userdata1 instanceof stdClass) {
			$userdata1 = get_object_vars( $userdata1 );
		}
		else if ($userdata1 instanceof WP_User) {
			$userdata1 = $userdata1->to_array();
		}
		
		if ($userdata2 instanceof stdClass) {
			$userdata2 = get_object_vars( $userdata2 );
		}
		else if ($userdata2 instanceof WP_User) {
			$userdata2 = $userdata2->to_array();
		}
		
		return wfUtils::array_diff_assoc($userdata1, $userdata2);
	}
	
	/**
	 * Returns the desired fields for the multisite ignoring the rest.
	 * 
	 * @param WP_Network|false $network
	 * @param WP_Site|false $blog
	 * @return array
	 */
	protected function _sanitizeMultisiteData($network, $blog) {
		$result = array();
		
		if ($network) {
			$result['network_id'] = $network->id;
			$result['network_domain'] = $network->domain;
			$result['network_path'] = $network->path;
			$result['network_name'] = $network->site_name;
		}
		
		if ($blog) {
			$result['blog_id'] = $blog->blog_id;
			$result['blog_domain'] = $blog->domain;
			$result['blog_path'] = $blog->path;
			$result['blog_name'] = $blog->blogname;
		}
		
		return $result;
	}
	
	protected function _multisiteDiff($blog1, $blog2) {
		if ($blog1 instanceof WP_Site) {
			$blog1 = $this->_sanitizeMultisiteData(false, $blog1);
		}
		
		if ($blog2 instanceof WP_Site) {
			$blog2 = $this->_sanitizeMultisiteData(false, $blog2);
		}
		
		return wfUtils::array_diff_assoc($blog1, $blog2);
	}
	
	/**
	 * Returns the desired fields from an app password record.
	 *
	 * @param array|object $item
	 * @return array
	 */
	protected function _sanitizeAppPassword($item) {
		if ($item instanceof stdClass) {
			$item = get_object_vars($item);
		}
		
		return array(
			'uuid' => empty($item['uuid']) ? '<unknown>' : $item['uuid'],
			'app_id' => empty($item['app_id']) ? '<unknown>' : $item['app_id'],
			'name' => empty($item['name']) ? '<empty>' : $item['name'],
			'created' => empty($item['created']) ? 0 : $item['created'],
			'last_used' => empty($item['last_used']) ? null : $item['last_used'],
			'last_ip' => empty($item['last_ip']) ? null : $item['last_ip'],
		);
	}
	
	/**
	 * Returns the desired fields from a post record.
	 *
	 * @param array|object|WP_Post $post
	 * @return array
	 */
	protected function _sanitizePost($post) {
		if ($post instanceof stdClass) {
			$post = get_object_vars($post);
		}
		else if ($post instanceof WP_Post) {
			$post = $post->to_array();
		}
		
		$author = isset($post['post_author']) ? get_user_by('ID', $post['post_author']) : null;
		
		$created = null;
		if (isset($post['post_date_gmt']) && $post['post_date_gmt'] != '0000-00-00 00:00:00') { //Prefer *_gmt, but sometimes WP doesn't set that
			$created = strtotime($post['post_date_gmt']);
		}
		else if (isset($post['post_date'])) {
			$created = strtotime($post['post_date']);
		}
		
		$modified = null;
		if (isset($post['post_modified_gmt']) && $post['post_modified_gmt'] != '0000-00-00 00:00:00') { //Prefer *_gmt, but sometimes WP doesn't set that
			$modified = strtotime($post['post_modified_gmt']);
		}
		else if (isset($post['post_modified'])) {
			$modified = strtotime($post['post_modified']);
		}
		
		$sanitized = array(
			'post_id' => $post['ID'],
			'author_id' => isset($post['post_author']) ? $post['post_author'] : null,
			'author' => $author ? $this->_sanitizeUserdata($author) : null,
			'title' => isset($post['post_title']) ? $post['post_title'] : null,
			'created' => $created,
			'last_modified' => $modified,
			'type' => isset($post['post_type']) ? $post['post_type'] : 'post',
			'status' => isset($post['post_status']) ? $post['post_status'] : 'publish',
		);
		if (isset($post['post_type']) && $post['post_type'] == wfAuditLogObserversWordPressCoreContent::WP_POST_TYPE_ATTACHMENT) {
			$sanitized['context'] = get_post_meta($post['ID'], '_wp_attachment_context', true);
		}
		return $sanitized;
	}
	
	protected function _postDiff($post1, $post2) {
		if ($post1 instanceof stdClass) {
			$post1 = get_object_vars($post1);
		}
		else if ($post1 instanceof WP_Post) {
			$post1 = $post1->to_array();
		}
		
		if ($post2 instanceof stdClass) {
			$post2 = get_object_vars($post2);
		}
		else if ($post2 instanceof WP_Post) {
			$post2 = $post2->to_array();
		}
		
		return wfUtils::array_diff_assoc($post1, $post2);
	}
	
	/**
	 * Returns whether or not the array of post changes should trigger an event recording. It will return false when
	 * there are no changes or when the only changes are innocuous values like post dates.
	 * 
	 * @param $changes
	 * @return bool
	 */
	protected function _shouldRecordPostChanges($changes) {
		if (empty($changes) || !is_array($changes)) {
			return false;
		}
		
		$ignored = array('post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'menu_order');
		$test = array_filter($changes, function($i) use ($ignored) {
			return !in_array($i, $ignored);
		});
		return !empty($test);
	}
	
	protected function _extractMultisiteID($option, $suffix) {
		global $wpdb;
		if (!is_multisite()) {
			return false;
		}
		
		if (substr($option, -1 * strlen($suffix)) == $suffix) {
			$option = substr($option, 0, strlen($option) - strlen($suffix));
			if (substr($option, 0, strlen($wpdb->base_prefix)) == $wpdb->base_prefix) {
				$option = substr($option, strlen($wpdb->base_prefix));
				$option = trim($option, '_');
				if (empty($option)) {
					return 1;
				}
				
				return intval($option);
			}
		}
		
		return false;
	}
	
	/**
	 * Returns an array containing the installed versions at the time of calling for core and all themes/plugins.
	 * 
	 * @return array
	 */
	protected function _installedVersions() {
		$state = array();
		
		require(ABSPATH . WPINC . '/version.php'); /** @var string $wp_version */
		$state['core'] = $wp_version;
		
		if (!function_exists('get_plugins')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}
		
		$plugins = get_plugins();
		$state['plugins'] = array_filter(array_map(function($p) { return isset($p['Version']) ? $p['Version'] : null; }, $plugins), function($v) { return $v != null; });
		
		if (!function_exists('wp_get_themes')) {
			require_once(ABSPATH . '/wp-includes/theme.php');
		}
		
		$themes = wp_get_themes();
		$state['themes'] = array_filter(array_map(function($t) { return isset($t['Version']) ? $t['Version'] : null; }, $themes), function($v) { return $v != null; });
		
		return $state;
	}
	
	/**
	 * Attempts to resolve the given plugin path to the file containing its header. Returns that path if found, otherwise
	 * null. Most plugins will simply be .../slug/slug.php, but some are single-file plugins while others have a 
	 * non-standard PHP file containing the header.
	 * 
	 * Based on `get_plugins()`.
	 * 
	 * @param string $path
	 * @return string|null
	 */
	protected function _resolvePlugin($path) {
		if (is_dir($path)) {
			$scanner = @opendir($path);
			
			if ($scanner) {
				while (($subfile = readdir($scanner)) !== false) {
					if (preg_match('/^\./i', $subfile)) {
						continue;
					}
					else if (preg_match('/\.php$/i', $subfile)) {
						if (!is_readable($path . DIRECTORY_SEPARATOR . $subfile)) {
							continue;
						}
						
						$plugin_data = get_plugin_data($path . DIRECTORY_SEPARATOR . $subfile, false, false);
						if (!empty($plugin_data['Name'])) {
							return $path . DIRECTORY_SEPARATOR . $subfile;
						}
					}
				}
				
				closedir($scanner);
			}
		}
		else if (preg_match('/\.php$/i', $path) && is_readable($path)) {
			$plugin_data = get_plugin_data($path, false, false);
			if (!empty($plugin_data['Name'])) {
				return $path;
			}
		}
		
		return null;
	}
	
	/**
	 * Returns data for the plugin at $path if possible, otherwise null.
	 * 
	 * @param string $path
	 * @return array|null
	 */
	protected function _getPlugin($path) {
		$original = $this->_getState('upgrader_pre_install.versions', 0);
		$raw = get_plugin_data($path);
		if ($raw) {
			$data = array();
			foreach ($raw as $f => $v) {
				$k = strtolower(preg_replace('/\s+/', '_', $f)); //Translates all headers: Plugin Name -> plugin_name
				$data[$k] = $v;
			}
			
			$base = plugin_basename($path);
			if ($original && isset($original['plugins'][$base])) {
				$data['previous_version'] = $original['plugins'][$base];
			}
			
			return $data;
		}
		return null;
	}
	
	/**
	 * Returns data for the theme if possible, otherwise null.
	 * 
	 * @param WP_Theme|string $theme_or_path
	 * @return array|null
	 */
	protected function _getTheme($theme_or_path) {
		$original = $this->_getState('upgrader_pre_install.versions', 0);
		
		if ($theme_or_path instanceof WP_Theme) {
			$theme = $theme_or_path;
		}
		else {
			$theme = wp_get_theme(basename($theme_or_path), dirname($theme_or_path));
		}
		
		if ($theme) {
			$fields = array(
				'Name',
				'ThemeURI',
				'Description',
				'Author',
				'AuthorURI',
				'Version',
				'Template',
				'Status',
				'Tags',
				'TextDomain',
				'DomainPath',
				'RequiresWP',
				'RequiresPHP',
				'UpdateURI',
			);
			$data = array();
			foreach ($fields as $f) {
				$k = strtolower(preg_replace('/\s+/', '_', $f));
				$data[$k] = $theme->display($f);
			}
			
			$base = $theme->get_stylesheet();
			if ($original && isset($original['themes'][$base])) {
				$data['previous_version'] = $original['themes'][$base];
			}
			
			return $data;
		}
		return null;
	}
}

class wfAuditLogSendFailedException extends Exception { }
