<?php

class wfCentralAPIRequest {
	/**
	 * @var string
	 */
	private $endpoint;
	/**
	 * @var string
	 */
	private $method;
	/**
	 * @var null
	 */
	private $token;
	/**
	 * @var array
	 */
	private $body;
	/**
	 * @var array
	 */
	private $args;


	/**
	 * @param string $endpoint
	 * @param string $method
	 * @param string|null $token
	 * @param array $body
	 * @param array $args
	 */
	public function __construct($endpoint, $method = 'GET', $token = null, $body = array(), $args = array()) {
		$this->endpoint = $endpoint;
		$this->method = $method;
		$this->token = $token;
		$this->body = $body;
		$this->args = $args;
	}
	
	/**
	 * Handles an internal error when making a Central API request (e.g., a second sodium_compat library with an
	 * incompatible interface loading instead or in addition to ours).
	 * 
	 * @param Exception|Throwable $e
	 */
	public static function handleInternalCentralAPIError($e) {
		error_log('Wordfence encountered an internal Central API error: ' . $e->getMessage());
		error_log('Wordfence stack trace: ' . $e->getTraceAsString());
	}

	public function execute($timeout = 10) {
		$args = array(
			'timeout' => $timeout,
		);
		$args = wp_parse_args($this->getArgs(), $args);
		$args['method'] = $this->getMethod();
		if (empty($args['headers'])) {
			$args['headers'] = array();
		}

		$token = $this->getToken();
		if ($token) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}
		if ($this->getBody()) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body'] = json_encode($this->getBody());
		}

		$http = _wp_http_get_object();
		$response = $http->request(WORDFENCE_CENTRAL_API_URL_SEC . $this->getEndpoint(), $args);

		if (!is_wp_error($response)) {
			$body = wp_remote_retrieve_body($response);
			$statusCode = wp_remote_retrieve_response_code($response);

			// Check if site has been disconnected on Central's end, but the plugin is still trying to connect.
			if ($statusCode === 404 && strpos($body, 'Site has been disconnected') !== false) {
				// Increment attempt count.
				$centralDisconnectCount = (int) get_site_transient('wordfenceCentralDisconnectCount');
				set_site_transient('wordfenceCentralDisconnectCount', ++$centralDisconnectCount, 86400);

				// Once threshold is hit, disconnect Central.
				if ($centralDisconnectCount > 3) {
					wfRESTConfigController::disconnectConfig(wfRESTConfigController::WF_CENTRAL_FAILURE_MARKER);
				}
			}
		}

		return new wfCentralAPIResponse($response);
	}

	/**
	 * @return string
	 */
	public function getEndpoint() {
		return $this->endpoint;
	}

	/**
	 * @param string $endpoint
	 */
	public function setEndpoint($endpoint) {
		$this->endpoint = $endpoint;
	}

	/**
	 * @return string
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * @param string $method
	 */
	public function setMethod($method) {
		$this->method = $method;
	}

	/**
	 * @return null
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * @param null $token
	 */
	public function setToken($token) {
		$this->token = $token;
	}

	/**
	 * @return array
	 */
	public function getBody() {
		return $this->body;
	}

	/**
	 * @param array $body
	 */
	public function setBody($body) {
		$this->body = $body;
	}

	/**
	 * @return array
	 */
	public function getArgs() {
		return $this->args;
	}

	/**
	 * @param array $args
	 */
	public function setArgs($args) {
		$this->args = $args;
	}
}

class wfCentralAPIResponse {

	public static function parseErrorJSON($json) {
		$data = json_decode($json, true);
		if (is_array($data) && array_key_exists('message', $data)) {
			return $data['message'];
		}
		return $json;
	}

	/**
	 * @var array|null
	 */
	private $response;

	/**
	 * @param array $response
	 */
	public function __construct($response = null) {
		$this->response = $response;
	}

	public function getStatusCode() {
		return wp_remote_retrieve_response_code($this->getResponse());
	}

	public function getBody() {
		return wp_remote_retrieve_body($this->getResponse());
	}

	public function getJSONBody() {
		return json_decode($this->getBody(), true);
	}

	public function isError() {
		if (is_wp_error($this->getResponse())) {
			return true;
		}
		$statusCode = $this->getStatusCode();
		return !($statusCode >= 200 && $statusCode < 300);
	}

	public function returnErrorArray() {
		return array(
			'err'      => 1,
			'errorMsg' => sprintf(
				/* translators: 1. HTTP status code. 2. Error message. */
				__('HTTP %1$d received from Wordfence Central: %2$s', 'wordfence'),
				$this->getStatusCode(), $this->parseErrorJSON($this->getBody())),
		);
	}

	/**
	 * @return array|null
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * @param array|null $response
	 */
	public function setResponse($response) {
		$this->response = $response;
	}
}


class wfCentralAuthenticatedAPIRequest extends wfCentralAPIRequest {

	private $retries = 3;

	/**
	 * @param string $endpoint
	 * @param string $method
	 * @param array $body
	 * @param array $args
	 */
	public function __construct($endpoint, $method = 'GET', $body = array(), $args = array()) {
		parent::__construct($endpoint, $method, null, $body, $args);
	}

	/**
	 * @return mixed|null
	 * @throws wfCentralAPIException
	 */
	public function getToken() {
		$token = parent::getToken();
		if ($token) {
			return $token;
		}

		$token = get_transient('wordfenceCentralJWT' . wfConfig::get('wordfenceCentralSiteID'));
		if ($token) {
			return $token;
		}

		for ($i = 0; $i < $this->retries; $i++) {
			try {
				$token = $this->fetchToken();
				break;
			} catch (wfCentralConfigurationException $e) {
				wfConfig::set('wordfenceCentralConfigurationIssue', true);
				throw new wfCentralAPIException(__('Fetching token for Wordfence Central authentication due to configuration issue.', 'wordfence'));
			} catch (wfCentralAPIException $e) {
				continue;
			}
		}
		if (empty($token)) {  
			if (isset($e)) {
				throw $e;
			} else {
				throw new wfCentralAPIException(__('Unable to authenticate with Wordfence Central.', 'wordfence'));
			}
		}
		$tokenContents = wfJWT::extractTokenContents($token);

		if (!empty($tokenContents['body']['exp'])) {
			set_transient('wordfenceCentralJWT' . wfConfig::get('wordfenceCentralSiteID'), $token, $tokenContents['body']['exp'] - time());
		}
		wfConfig::set('wordfenceCentralConfigurationIssue', false);
		return $token;
	}

	public function fetchToken() {
		require_once(WORDFENCE_PATH . '/lib/sodium_compat_fast.php');

		$defaultArgs = array(
			'timeout' => 6,
		);
		$siteID = wfConfig::get('wordfenceCentralSiteID');
		if (!$siteID) {
			throw new wfCentralAPIException(__('Wordfence Central site ID has not been created yet.', 'wordfence'));
		}
		$secretKey = wfConfig::get('wordfenceCentralSecretKey');
		if (!$secretKey) {
			throw new wfCentralAPIException(__('Wordfence Central secret key has not been created yet.', 'wordfence'));
		}

		// Pull down nonce.
		$request = new wfCentralAPIRequest(sprintf('/site/%s/login', $siteID), 'GET', null, array(), $defaultArgs);
		$nonceResponse = $request->execute();
		if ($nonceResponse->isError()) {
			$errorArray = $nonceResponse->returnErrorArray();
			throw new wfCentralAPIException($errorArray['errorMsg']);
		}
		$body = $nonceResponse->getJSONBody();
		if (!is_array($body) || !isset($body['nonce'])) {
			throw new wfCentralAPIException(__('Invalid response received from Wordfence Central when fetching nonce.', 'wordfence'));
		}
		$nonce = $body['nonce'];

		// Sign nonce to pull down JWT.
		$data = $nonce . '|' . $siteID;
		try {
			$signature = ParagonIE_Sodium_Compat::crypto_sign_detached($data, $secretKey);
		}
		catch (SodiumException $e) {
			throw new wfCentralConfigurationException('Signing failed, likely due to malformed secret key', $e);
		}
		$request = new wfCentralAPIRequest(sprintf('/site/%s/login', $siteID), 'POST', null, array(
			'data'      => $data,
			'signature' => ParagonIE_Sodium_Compat::bin2hex($signature),
		), $defaultArgs);
		$authResponse = $request->execute();
		if ($authResponse->isError()) {
			$errorArray = $authResponse->returnErrorArray();
			throw new wfCentralAPIException($errorArray['errorMsg']);
		}
		$body = $authResponse->getJSONBody();
		if (!is_array($body)) {
			throw new wfCentralAPIException(__('Invalid response received from Wordfence Central when fetching token.', 'wordfence'));
		}
		if (!isset($body['jwt'])) { // Possible authentication error.
			throw new wfCentralAPIException(__('Unable to authenticate with Wordfence Central.', 'wordfence'));
		}
		return $body['jwt'];
	}
}

class wfCentralAPIException extends Exception {

}

class wfCentralConfigurationException extends RuntimeException {

	public function __construct($message, $previous = null) {
		parent::__construct($message, 0, $previous);
	}

}

class wfCentral {

	/**
	 * @return bool
	 */
	public static function isSupported() {
		return function_exists('register_rest_route') && version_compare(phpversion(), '5.3', '>=');
	}

	/**
	 * @return bool
	 */
	public static function isConnected() {
		return self::isSupported() && ((bool) self::_isConnected());
	}

	/**
	 * @return bool
	 */
	public static function isPartialConnection() {
		return !self::_isConnected() && wfConfig::get('wordfenceCentralSiteID');
	}

	public static function _isConnected($forceUpdate = false) {
		static $isConnected;
		if (!isset($isConnected) || $forceUpdate) {
			$isConnected = wfConfig::get('wordfenceCentralConnected', false);
		}
		return $isConnected;
	}

	/**
	 * @param array $issue
	 * @return bool|wfCentralAPIResponse
	 */
	public static function sendIssue($issue) {
		return self::sendIssues(array($issue));
	}

	/**
	 * @param $issues
	 * @return bool|wfCentralAPIResponse
	 */
	public static function sendIssues($issues) {
		$data = array();
		foreach ($issues as $issue) {
			$issueData = array(
				'type'       => 'issue',
				'attributes' => $issue,
			);
			if (array_key_exists('id', $issueData)) {
				$issueData['id'] = $issue['id'];
			}
			$data[] = $issueData;
		}

		$siteID = wfConfig::get('wordfenceCentralSiteID');
		$request = new wfCentralAuthenticatedAPIRequest('/site/' . $siteID . '/issues', 'POST', array(
			'data' => $data,
		));
		try {
			$response = $request->execute();
			return $response;
		}
		catch (wfCentralAPIException $e) {
			error_log($e);
		}
		catch (Exception $e) {
			wfCentralAPIRequest::handleInternalCentralAPIError($e);
		}
		catch (Throwable $t) {
			wfCentralAPIRequest::handleInternalCentralAPIError($t);
		}
		return false;
	}

	/**
	 * @param int $issueID
	 * @return bool|wfCentralAPIResponse
	 */
	public static function deleteIssue($issueID) {
		return self::deleteIssues(array($issueID));
	}

	/**
	 * @param $issues
	 * @return bool|wfCentralAPIResponse
	 */
	public static function deleteIssues($issues) {
		if (empty($issues)) { return true; }
		$siteID = wfConfig::get('wordfenceCentralSiteID');
		$request = new wfCentralAuthenticatedAPIRequest('/site/' . $siteID . '/issues', 'DELETE', array(
			'data' => array(
				'type'       => 'issue-list',
				'attributes' => array(
					'ids' => $issues,
				)
			),
		));
		try {
			$response = $request->execute();
			return $response;
		}
		catch (wfCentralAPIException $e) {
			error_log($e);
		}
		catch (Exception $e) {
			wfCentralAPIRequest::handleInternalCentralAPIError($e);
		}
		catch (Throwable $t) {
			wfCentralAPIRequest::handleInternalCentralAPIError($t);
		}
		return false;
	}

	/**
	 * @return bool|wfCentralAPIResponse
	 */
	public static function deleteNewIssues() {
		$siteID = wfConfig::get('wordfenceCentralSiteID');
		$request = new wfCentralAuthenticatedAPIRequest('/site/' . $siteID . '/issues', 'DELETE', array(
			'data' => array(
				'type'       => 'issue-list',
				'attributes' => array(
					'status' => 'new',
				)
			),
		));
		try {
			$response = $request->execute();
			return $response;
		}
		catch (wfCentralAPIException $e) {
			error_log($e);
		}
		catch (Exception $e) {
			wfCentralAPIRequest::handleInternalCentralAPIError($e);
		}
		catch (Throwable $t) {
			wfCentralAPIRequest::handleInternalCentralAPIError($t);
		}
		return false;
	}

	/**
	 * @param array $types Array of issue types to delete
	 * @param string $status Issue status to delete
	 * @return bool|wfCentralAPIResponse
	 */
	public static function deleteIssueTypes($types, $status = 'new') {
		$siteID = wfConfig::get('wordfenceCentralSiteID');
		$request = new wfCentralAuthenticatedAPIRequest('/site/' . $siteID . '/issues', 'DELETE', array(
			'data' => array(
				'type'       => 'issue-list',
				'attributes' => array(
					'types' => $types,
					'status' => $status,
				)
			),
		));
		try {
			$response = $request->execute();
			return $response;
		}
		catch (wfCentralAPIException $e) {
			error_log($e);
		}
		catch (Exception $e) {
			wfCentralAPIRequest::handleInternalCentralAPIError($e);
		}
		catch (Throwable $t) {
			wfCentralAPIRequest::handleInternalCentralAPIError($t);
		}
		return false;
	}

	public static function requestConfigurationSync() {
		if (! wfCentral::isConnected() || !self::$syncConfig) {
			return;
		}

		$endpoint = '/site/'.wfConfig::get('wordfenceCentralSiteID').'/config';
		$args = array('timeout' => 0.01, 'blocking'  => false);
		$request = new wfCentralAuthenticatedAPIRequest($endpoint, 'POST', array(), $args);

		try {
			$request->execute();
		}
		catch (Exception $e) {
			// We can safely ignore an error here for now.
		}
		catch (Throwable $t) {
			wfCentralAPIRequest::handleInternalCentralAPIError($t);
		}
	}

	protected static $syncConfig = true;

	public static function preventConfigurationSync() {
		self::$syncConfig = false;
	}

	/**
	 * @param $scan
	 * @param $running
	 * @return bool|wfCentralAPIResponse
	 */
	public static function updateScanStatus($scan = null) {
		if ($scan === null) {
			$scan = wfConfig::get_ser('scanStageStatuses');
			if (!is_array($scan)) {
				$scan = array();
			}
		}
		
		wfScanner::shared()->flushSummaryItems();

		$siteID = wfConfig::get('wordfenceCentralSiteID');
		$running = wfScanner::shared()->isRunning();
		$request = new wfCentralAuthenticatedAPIRequest('/site/' . $siteID . '/scan', 'PATCH', array(
			'data' => array(
				'type'       => 'scan',
				'attributes' => array(
					'running'      => $running,
					'scan'         => $scan,
					'scan-summary' => wfConfig::get('wf_summaryItems'),
				),
			),
		));
		try {
			$response = $request->execute();
			wfConfig::set('lastScanStageStatusUpdate', time(), wfConfig::DONT_AUTOLOAD);
			return $response;
		}
		catch (wfCentralAPIException $e) {
			error_log($e);
		}
		catch (Exception $e) {
			wfCentralAPIRequest::handleInternalCentralAPIError($e);
		}
		catch (Throwable $t) {
			wfCentralAPIRequest::handleInternalCentralAPIError($t);
		}
		return false;
	}

	/**
	 * @param string $event
	 * @param array $data
	 * @param callable|null $alertCallback
	 */
	public static function sendSecurityEvent($event, $data = array(), $alertCallback = null, $sendImmediately = false) {
		return self::sendSecurityEvents(array(array('type' => $event, 'data' => $data, 'event_time' => microtime(true))), $alertCallback, $sendImmediately);
	}
	
	public static function sendSecurityEvents($events, $alertCallback = null, $sendImmediately = false) {
		if (empty($events)) {
			return true;
		}
		
		if (!$sendImmediately && defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
			$sendImmediately = true;
		}
		
		$alerted = false;
		if (!self::pluginAlertingDisabled() && is_callable($alertCallback)) {
			call_user_func($alertCallback);
			$alerted = true;
		}
		
		if ($sendImmediately) {
			$payload = array();
			foreach ($events as $e) {
				$payload[] = array(
					'type' => 'security-event',
					'attributes' => array(
						'type' => $e['type'],
						'data' => $e['data'],
						'event_time' => $e['event_time'],
					),
				);
			}
			
			$siteID = wfConfig::get('wordfenceCentralSiteID');
			$request = new wfCentralAuthenticatedAPIRequest('/site/' . $siteID . '/security-events', 'POST', array(
				'data' => $payload,
			));
			try {
				// Attempt to send the security events to Central.
				$doing_cron = function_exists('wp_doing_cron') /* WP >= 4.8 */ ? wp_doing_cron() : (defined('DOING_CRON') && DOING_CRON);
				$response = $request->execute($doing_cron ? 10 : 3);
			}
			catch (wfCentralAPIException $e) {
				// If we didn't alert previously, notify the user now in the event Central is down.
				if (!$alerted && is_callable($alertCallback)) {
					call_user_func($alertCallback);
				}
				return false;
			}
			catch (Exception $e) {
				wfCentralAPIRequest::handleInternalCentralAPIError($e);
				return false;
			}
			catch (Throwable $t) {
				wfCentralAPIRequest::handleInternalCentralAPIError($t);
				return false;
			}
		}
		else {
			$wfdb = new wfDB();
			$table_wfSecurityEvents = wfDB::networkTable('wfSecurityEvents');
			$query = "INSERT INTO {$table_wfSecurityEvents} (`type`, `data`, `event_time`, `state`, `state_timestamp`) VALUES ";
			$query .= implode(', ', array_fill(0, count($events), "('%s', '%s', %f, 'new', NOW())"));
			
			$immediateSendTypes = array('adminLogin',
										'adminLoginNewLocation',
										'nonAdminLogin',
										'nonAdminLoginNewLocation',
										'wordfenceDeactivated',
										'wafDeactivated',
										'autoUpdate');
			$args = array();
			foreach ($events as $e) {
				$sendImmediately = $sendImmediately || in_array($e['type'], $immediateSendTypes);
				$args[] = $e['type'];
				$args[] = json_encode($e['data']);
				$args[] = $e['event_time'];
			}
			$wfdb->queryWriteArray($query, $args);
			
			if (($ts = self::isScheduledSecurityEventCronOverdue()) || $sendImmediately) {
				if ($ts) {
					self::unscheduleSendPendingSecurityEvents($ts);
				}
				self::sendPendingSecurityEvents();
			}
			else {
				self::scheduleSendPendingSecurityEvents();
			}
		}
		
		return true;
	}
	
	public static function sendPendingSecurityEvents() {
		$wfdb = new wfDB();
		$table_wfSecurityEvents = wfDB::networkTable('wfSecurityEvents');
		
		$rawEvents = $wfdb->querySelect("SELECT * FROM {$table_wfSecurityEvents} WHERE `state` = 'new' ORDER BY `id` ASC LIMIT 100");

		if (empty($rawEvents))
			return;

		$ids = array();
		$events = array();
		foreach ($rawEvents as $r) {
			$ids[] = intval($r['id']);
			$events[] = array(
				'type' => $r['type'],
				'data' => json_decode($r['data'], true),
				'event_time' => $r['event_time'],
			);
		}
		
		$idParam = '(' . implode(', ', $ids) . ')';
		$wfdb->queryWrite("UPDATE {$table_wfSecurityEvents} SET `state` = 'sending', `state_timestamp` = NOW() WHERE `id` IN {$idParam}");
		if (self::sendSecurityEvents($events, null, true)) {
			$wfdb->queryWrite("UPDATE {$table_wfSecurityEvents} SET `state` = 'sent', `state_timestamp` = NOW() WHERE `id` IN {$idParam}");
			
			self::checkForUnsentSecurityEvents();
		}
		else {
			$wfdb->queryWrite("UPDATE {$table_wfSecurityEvents} SET `state` = 'new', `state_timestamp` = NOW() WHERE `id` IN {$idParam}");
			self::scheduleSendPendingSecurityEvents();
		}
	}
	
	public static function scheduleSendPendingSecurityEvents() {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		if (!wp_next_scheduled('wordfence_batchSendSecurityEvents')) {
			wp_schedule_single_event(time() + 300, 'wordfence_batchSendSecurityEvents');
		}
		if ($notMainSite) {
			restore_current_blog();
		}
	}
	
	public static function unscheduleSendPendingSecurityEvents($timestamp) {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		if (!wp_next_scheduled('wordfence_batchSendSecurityEvents')) {
			wp_unschedule_event($timestamp, 'wordfence_batchSendSecurityEvents');
		}
		if ($notMainSite) {
			restore_current_blog();
		}
	}
	
	public static function isScheduledSecurityEventCronOverdue() {
		if (!defined('DONOTCACHEDB')) { define('DONOTCACHEDB', true); }
		$notMainSite = is_multisite() && !is_main_site();
		if ($notMainSite) {
			global $current_site;
			switch_to_blog($current_site->blog_id);
		}
		
		$overdue = false;
		if ($ts = wp_next_scheduled('wordfence_batchSendSecurityEvents')) {
			if ((time() - $ts) > 900) {
				$overdue = $ts;
			}
		}
		
		if ($notMainSite) {
			restore_current_blog();
		}
		
		return $overdue;
	}
	
	public static function checkForUnsentSecurityEvents() {
		$wfdb = new wfDB();
		$table_wfSecurityEvents = wfDB::networkTable('wfSecurityEvents');
		$wfdb->queryWrite("UPDATE {$table_wfSecurityEvents} SET `state` = 'new', `state_timestamp` = NOW() WHERE `state` = 'sending' AND `state_timestamp` < DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
		
		$count = $wfdb->querySingle("SELECT COUNT(*) AS cnt FROM {$table_wfSecurityEvents} WHERE `state` = 'new'");
		if ($count) {
			self::scheduleSendPendingSecurityEvents();
		}
	}
	
	public static function trimSecurityEvents() {
		$wfdb = new wfDB();
		$table_wfSecurityEvents = wfDB::networkTable('wfSecurityEvents');
		$count = $wfdb->querySingle("SELECT COUNT(*) AS cnt FROM {$table_wfSecurityEvents}");
		if ($count > 20000) {
			$wfdb->truncate($table_wfSecurityEvents); //Similar behavior to other logged data, assume possible DoS so truncate
		}
		else if ($count > 1000) {
			$wfdb->queryWrite("DELETE FROM {$table_wfSecurityEvents} ORDER BY id ASC LIMIT %d", $count - 1000);
		}
	}

	/**
	 * @param $event
	 * @param array $data
	 * @param callable|null $alertCallback
	 */
	public static function sendAlertCallback($event, $data = array(), $alertCallback = null) {
		if (is_callable($alertCallback)) {
			call_user_func($alertCallback);
		}
	}

	public static function pluginAlertingDisabled() {
		if (!self::isConnected()) {
			return false;
		}

		return wfConfig::get('wordfenceCentralPluginAlertingDisabled', false);
	}
	
	/**
	 * Returns the site URL as associated with this site's Central linking.
	 * 
	 * The return value may be:
	 *  - null if there is no `site-url` key present in the stored Central data
	 *  - a string if there is a `site-url` value
	 * 
	 * @return string|null
	 */
	public static function getCentralSiteUrl() {
		$siteData = json_decode(wfConfig::get('wordfenceCentralSiteData', '[]'), true);
		return (is_array($siteData) && array_key_exists('site-url', $siteData)) ? (string) $siteData['site-url'] : null;
	}
	
	/**
	 * Populates the Central record's site data if missing or incomplete locally.
	 * 
	 * @return array|bool
	 */
	public static function populateCentralSiteData() {
		if (!wfCentral::_isConnected()) {
			return false;
		}
		
		$siteData = json_decode(wfConfig::get('wordfenceCentralSiteData', '[]'), true);
		if (!is_array($siteData) || !array_key_exists('site-url', $siteData) || !array_key_exists('audit-log-url', $siteData)) {
			try {
				$request = new wfCentralAuthenticatedAPIRequest('/site/' . wfConfig::get('wordfenceCentralSiteID'), 'GET', array(), array('timeout' => 2));
				$response = $request->execute();
				if ($response->isError()) {
					return $response->returnErrorArray();
				}
				$responseData = $response->getJSONBody();
				if (is_array($responseData) && isset($responseData['data']['attributes'])) {
					$siteData = $responseData['data']['attributes'];
					wfConfig::set('wordfenceCentralSiteData', json_encode($siteData));
				}
			}
			catch (wfCentralAPIException $e) {
				return false;
			}
			catch (Exception $e) {
				wfCentralAPIRequest::handleInternalCentralAPIError($e);
				return false;
			}
			catch (Throwable $t) {
				wfCentralAPIRequest::handleInternalCentralAPIError($t);
				return false;
			}
		}
		return true;
	}
	
	public static function isCentralSiteUrlMismatched() {
		if (!wfCentral::_isConnected()) {
			return false;
		}
		
		$centralSiteUrl = self::getCentralSiteUrl();
		if (!is_string($centralSiteUrl)) {
			return false;
		}
		
		$localSiteUrl = get_site_url();
		return !wfUtils::compareSiteUrls($centralSiteUrl, $localSiteUrl, array('www'));
	}
	
	public static function mismatchedCentralUrlNotice() {
		echo '<div id="wordfenceMismatchedCentralUrlNotice" class="fade notice notice-warning"><p><strong>' .
			esc_html__('Your site is currently linked to Wordfence Central under a different site URL.', 'wordfence')
			. '</strong> '
			. esc_html__('This may cause duplicated scan issues if both sites are currently active and reporting and is generally caused by duplicating the database from one site to another (e.g., from a production site to staging). We recommend disconnecting this site only, which will leave the matching site still connected.', 'wordfence')
			. '</p><p>'
			. esc_html__('If this is a single site with multiple domains or subdomains, you can dismiss this message.', 'wordfence')
			. '</p><p>'
			. '<a class="wf-btn wf-btn-primary wf-btn-sm wf-dismiss-link" href="#" onclick="wordfenceExt.centralUrlMismatchChoice(\'local\'); return false;" role="button">' .
			esc_html__('Disconnect This Site', 'wordfence')
			. '</a> '
			. '<a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="#" onclick="wordfenceExt.centralUrlMismatchChoice(\'global\'); return false;" role="button">' .
			esc_html__('Disconnect All', 'wordfence')
			. '</a> '
			. '<a class="wf-btn wf-btn-default wf-btn-sm wf-dismiss-link" href="#" onclick="wordfenceExt.centralUrlMismatchChoice(\'dismiss\'); return false;" role="button">' .
			esc_html__('Dismiss', 'wordfence')
			. '</a> '
			. '<a class="wfhelp" target="_blank" rel="noopener noreferrer" href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_DIAGNOSTICS_REMOVE_CENTRAL_DATA) . '"><span class="wfhelpextra">' . esc_html__('Click here to learn more', 'wordfence') . '</span><span class="screen-reader-text"> (' . esc_html__('opens in new tab', 'wordfence') . ')</span></a></p></div>';
	}
	
	/**
	 * Returns the audit log URL for this site in Wordfence Central.
	 *
	 * The return value may be:
	 *  - null if there is no `audit-log-url` key present in the stored Central data
	 *  - a string if there is a `audit-log-url` value
	 *
	 * @return string|null
	 */
	public static function getCentralAuditLogUrl() {
		$siteData = json_decode(wfConfig::get('wordfenceCentralSiteData', '[]'), true);
		return (is_array($siteData) && array_key_exists('audit-log-url', $siteData)) ? (string) $siteData['audit-log-url'] : null;
	}
}