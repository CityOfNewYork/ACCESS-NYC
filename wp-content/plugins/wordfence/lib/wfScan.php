<?php
class wfScan {
	public static $debugMode = false;
	public static $errorHandlingOn = true;
	public static $peakMemAtStart = 0;
	
	/**
	 * Returns the stored cronkey or false if not set. If $expired is provided, will set to <timestamp>/false based
	 * on whether or not the cronkey is expired.
	 * 
	 * @param null $expired
	 * @return bool|string
	 */
	private static function storedCronKey(&$expired = null) {
		$currentCronKey = wfConfig::get('currentCronKey', false);
		if (empty($currentCronKey))
		{
			if ($expired !== null) {
				$expired = false;
			}
			return false;
		}
		
		$savedKey = explode(',',$currentCronKey);
		if (time() - $savedKey[0] > 86400) {
			if ($expired !== null) {
				$expired = $savedKey[0];
			}
			return $savedKey[1];
		}
		
		if ($expired !== null) {
			$expired = false;
		}
		return $savedKey[1];
	}
	
	public static function wfScanMain(){
		self::$peakMemAtStart = memory_get_peak_usage(true);
		$db = new wfDB();
		if($db->errorMsg){
			self::errorExit(sprintf(/* translators: Error message. */ __("Could not connect to database to start scan: %s", 'wordfence'), $db->errorMsg));
		}
		if(! wordfence::wfSchemaExists()){
			self::errorExit(__("Looks like the Wordfence database tables have been deleted. You can fix this by de-activating and re-activating the Wordfence plugin from your Plugins menu.", 'wordfence'));
		}
		if( isset( $_GET['test'] ) && $_GET['test'] == '1'){
			echo "WFCRONTESTOK:" . wfConfig::get('cronTestID');
			self::status(4, 'info', __("Cron test received and message printed", 'wordfence'));
			exit();
		}
		
		self::status(4, 'info', __("Scan engine received request.", 'wordfence'));
		
		/* ----------Starting signature check -------- */
		self::status(4, 'info', __("Verifying start request signature.", 'wordfence'));
		if (!isset($_GET['signature']) || !wfScanEngine::verifyStartSignature($_GET['signature'], isset($_GET['isFork']) ? wfUtils::truthyToBoolean($_GET['isFork']) : false, isset($_GET['scanMode']) ? $_GET['scanMode'] : '', isset($_GET['cronKey']) ? $_GET['cronKey'] : '', isset($_GET['remote']) ? wfUtils::truthyToBoolean($_GET['remote']) : false)) {
			self::errorExit(__('The signature on the request to start a scan is invalid. Please try again.', 'wordfence'));
		}
		
		/* ----------Starting cronkey check -------- */
		self::status(4, 'info', __("Fetching stored cronkey for comparison.", 'wordfence'));
		$expired = false;
		$storedCronKey = self::storedCronKey($expired);
		$displayCronKey_received = (isset($_GET['cronKey']) ? (preg_match('/^[a-f0-9]+$/i', $_GET['cronKey']) && strlen($_GET['cronKey']) == 32 ? $_GET['cronKey'] : __('[invalid]', 'wordfence')) : __('[none]', 'wordfence'));
		$displayCronKey_stored = (!empty($storedCronKey) && !$expired ? $storedCronKey : __('[none]', 'wordfence'));
		self::status(4, 'info', sprintf(/* translators: 1. WordPress nonce. 2. WordPress nonce. */ __('Checking cronkey: %1$s (expecting %2$s)', 'wordfence'), $displayCronKey_received, $displayCronKey_stored));
		if (empty($_GET['cronKey'])) { 
			self::status(4, 'error', __("Wordfence scan script accessed directly, or WF did not receive a cronkey.", 'wordfence'));
			echo "If you see this message it means Wordfence is working correctly. You should not access this URL directly. It is part of the Wordfence security plugin and is designed for internal use only.";
			exit();
		}
		
		if ($expired) {
			self::errorExit(sprintf(
			/* translators: 1. Unix timestamp. 2. WordPress nonce. 3. Unix timestamp. */
				__('The key used to start a scan expired. The value is: %1$s and split is: %2$s and time is: %3$d', 'wordfence'), $expired, $storedCronKey, time()));
		} //keys only last 60 seconds and are used within milliseconds of creation
		
		if (!$storedCronKey) {
			wordfence::status(4, 'error', __("Wordfence could not find a saved cron key to start the scan so assuming it started and exiting.", 'wordfence'));
			exit();
		} 
		
		self::status(4, 'info', __("Checking saved cronkey against cronkey param", 'wordfence'));
		if (!hash_equals($storedCronKey, $_GET['cronKey'])) { 
			self::errorExit(
				sprintf(
				/* translators: 1. WordPress nonce (used for debugging). 2. WordPress nonce (used for debugging). 3. WordPress nonce (used for debugging). */
					__('Wordfence could not start a scan because the cron key does not match the saved key. Saved: %1$s Sent: %2$s Current unexploded: %3$s', 'wordfence'),
					$storedCronKey,
					$_GET['cronKey'],
					wfConfig::get('currentCronKey', false)
				)
			);
		}
		wfConfig::set('currentCronKey', '');
		/* --------- end cronkey check ---------- */

		wfScanMonitor::logLastSuccess();

		$scanMode = wfScanner::SCAN_TYPE_STANDARD;
		if (isset($_GET['scanMode']) && wfScanner::isValidScanType($_GET['scanMode'])) {
			$scanMode = $_GET['scanMode'];
		}
		$scanController = new wfScanner($scanMode);

		wfConfig::remove('scanStartAttempt');
		$isFork = ($_GET['isFork'] == '1' ? true : false);

		wfScanMonitor::handleStageStart($isFork);

		if(! $isFork){
			self::status(4, 'info', __("Checking if scan is already running", 'wordfence'));
			if(! wfUtils::getScanLock()){
				self::errorExit(__("There is already a scan running.", 'wordfence'));
			}
			
			wfIssues::updateScanStillRunning();
			wfConfig::set('wfPeakMemory', 0, wfConfig::DONT_AUTOLOAD);
			wfConfig::set('wfScanStartVersion', wfUtils::getWPVersion());
			wfConfig::set('lowResourceScanWaitStep', false);
			
			if ($scanController->useLowResourceScanning()) {
				self::status(1, 'info', __("Using low resource scanning", 'wordfence'));
			}
		}
		self::status(4, 'info', __("Requesting max memory", 'wordfence'));
		wfUtils::requestMaxMemory();
		self::status(4, 'info', __("Setting up error handling environment", 'wordfence'));
		set_error_handler('wfScan::error_handler', E_ALL);
		register_shutdown_function('wfScan::shutdown');
		if(! self::$debugMode){
			ob_start('wfScan::obHandler');
		}
		@error_reporting(E_ALL);
		wfUtils::iniSet('display_errors','On');
		self::status(4, 'info', __("Setting up scanRunning and starting scan", 'wordfence'));
		try {
			if ($isFork) {
				$scan = wfConfig::get_ser('wfsd_engine', false, false);
				if ($scan) {
					self::status(4, 'info', sprintf(/* translators: Error message (used for debugging). */ __("Got a true deserialized value back from 'wfsd_engine' with type: %s", 'wordfence'), gettype($scan)));
					wfConfig::set('wfsd_engine', '', wfConfig::DONT_AUTOLOAD);
				}
				else {
					self::status(2, 'error', sprintf(/* translators: Error message (used for debugging). */ __("Scan can't continue - stored data not found after a fork. Got type: %s", 'wordfence'), gettype($scan)));
					wfConfig::set('wfsd_engine', '', wfConfig::DONT_AUTOLOAD);
					wfConfig::set('lastScanCompleted', __('Scan can\'t continue - stored data not found after a fork.', 'wordfence'));
					wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_FORK_FAILED);
					wfUtils::clearScanLock();
					self::status(2, 'error', "Scan terminated with error: " . __('Scan can\'t continue - stored data not found after a fork.', 'wordfence'));
					self::status(10, 'info', "SUM_KILLED:" . __('Previous scan terminated with an error. See below.', 'wordfence'));
					exit();
				}
			}
			else {
				$delay = -1;
				$isScheduled = false;
				$originalScanStart = wfConfig::get('originalScheduledScanStart', 0);
				$lastScanStart = wfConfig::get('lastScheduledScanStart', 0);
				$minimumFrequency = ($scanController->schedulingMode() == wfScanner::SCAN_SCHEDULING_MODE_MANUAL ? 1800 : 43200);
				if ($lastScanStart && (time() - $lastScanStart) < $minimumFrequency) {
					$isScheduled = true;
					
					if ($originalScanStart > 0) {
						$delay = max($lastScanStart - $originalScanStart, 0);
					}
				}
				
				wfIssues::statusPrep(); //Re-initializes all status counters
				$scanController->resetStages();
				$scanController->resetSummaryItems();
				
				if ($scanMode != wfScanner::SCAN_TYPE_QUICK) {
					wordfence::status(1, 'info', __("Contacting Wordfence to initiate scan", 'wordfence'));
					$wp_version = wfUtils::getWPVersion();
					$apiKey = wfConfig::get('apiKey');
					$api = new wfAPI($apiKey, $wp_version);
					$response = $api->call('log_scan', array(), array('delay' => $delay, 'scheduled' => (int) $isScheduled, 'mode' => wfConfig::get('schedMode')/*, 'forcedefer' => 1*/));
					
					if ($scanController->schedulingMode() == wfScanner::SCAN_SCHEDULING_MODE_AUTOMATIC && $isScheduled) {
						if (isset($response['defer'])) {
							$defer = (int) $response['defer'];
							wordfence::status(2, 'info', sprintf(/* translators: Time until. */ __("Deferring scheduled scan by %s", 'wordfence'), wfUtils::makeDuration($defer)));
							wfConfig::set('lastScheduledScanStart', 0);
							wfConfig::set('lastScanCompleted', 'ok');
							wfConfig::set('lastScanFailureType', false);
							wfConfig::set_ser('wfStatusStartMsgs', array());
							$scanController->recordLastScanTime();
							$i = new wfIssues();
							wfScanEngine::refreshScanNotification($i);
							wfScanner::shared()->scheduleSingleScan(time() + $defer, $originalScanStart);
							wfUtils::clearScanLock();
							exit();
						}
					}
					
					$malwarePrefixesHash = (isset($response['malwarePrefixes']) ? $response['malwarePrefixes'] : '');
					$coreHashesHash = (isset($response['coreHashes']) ? $response['coreHashes'] : '');
					
					$scan = new wfScanEngine($malwarePrefixesHash, $coreHashesHash, $scanMode);
					$scan->deleteNewIssues();
				}
				else {
					wordfence::status(1, 'info', __("Initiating quick scan", 'wordfence'));
					$scan = new wfScanEngine('', '', $scanMode);
				}
			}
			
			$scan->go();
		}
		catch (wfScanEngineDurationLimitException $e) { //User error set in wfScanEngine
			wfUtils::clearScanLock();
			$peakMemory = self::logPeakMemory();
			self::status(2, 'info', sprintf(
				/* translators: 1. Bytes of memory. 2. Bytes of memory. */
				__('Wordfence used %1$s of memory for scan. Server peak memory usage was: %2$s', 'wordfence'),
				wfUtils::formatBytes($peakMemory - self::$peakMemAtStart),
				wfUtils::formatBytes($peakMemory)
			));
			self::status(2, 'error', sprintf(__("Scan terminated with error: %s", 'wordfence'), $e->getMessage()));
			exit();
		}
		catch (wfScanEngineCoreVersionChangeException $e) { //User error set in wfScanEngine
			wfUtils::clearScanLock();
			$peakMemory = self::logPeakMemory();
			self::status(2, 'info', sprintf(
				/* translators: 1. Bytes of memory. 2. Bytes of memory. */
				__('Wordfence used %1$s of memory for scan. Server peak memory usage was: %2$s', 'wordfence'),
				wfUtils::formatBytes($peakMemory - self::$peakMemAtStart),
				wfUtils::formatBytes($peakMemory)
			));
			self::status(2, 'error', sprintf(/* translators: Error message. */ __("Scan terminated with error: %s", 'wordfence'), $e->getMessage()));
			
			$nextScheduledScan = wfScanner::shared()->nextScheduledScanTime();
			if ($nextScheduledScan !== false && $nextScheduledScan - time() > 21600 /* 6 hours */) {
				$nextScheduledScan = time() + 3600;
				wfScanner::shared()->scheduleSingleScan($nextScheduledScan);
			}
			self::status(2, 'error', wordfence::getNextScanStartTime($nextScheduledScan));
			
			exit();
		}
		catch (wfAPICallSSLUnavailableException $e) {
			wfConfig::set('lastScanCompleted', $e->getMessage());
			wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_API_SSL_UNAVAILABLE);
			
			wfUtils::clearScanLock();
			$peakMemory = self::logPeakMemory();
			self::status(2, 'info', sprintf(
				/* translators: 1. Bytes of memory. 2. Bytes of memory. */
				__('Wordfence used %1$s of memory for scan. Server peak memory usage was: %2$s', 'wordfence'),
				wfUtils::formatBytes($peakMemory - self::$peakMemAtStart),
				wfUtils::formatBytes($peakMemory)
			));
			self::status(2, 'error', sprintf(/* translators: Error message. */__("Scan terminated with error: %s", 'wordfence'), $e->getMessage()));
			exit();
		}
		catch (wfAPICallFailedException $e) {
			wfConfig::set('lastScanCompleted', $e->getMessage());
			wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_API_CALL_FAILED);
			
			wfUtils::clearScanLock();
			$peakMemory = self::logPeakMemory();
			self::status(2, 'info', sprintf(
				/* translators: 1. Bytes of memory. 2. Bytes of memory. */
				__('Wordfence used %1$s of memory for scan. Server peak memory usage was: %2$s', 'wordfence'),
				wfUtils::formatBytes($peakMemory - self::$peakMemAtStart),
				wfUtils::formatBytes($peakMemory)
			));
			self::status(2, 'error', sprintf(/* translators: Error message. */ __("Scan terminated with error: %s", 'wordfence'), $e->getMessage()));
			exit();
		}
		catch (wfAPICallInvalidResponseException $e) {
			wfConfig::set('lastScanCompleted', $e->getMessage());
			wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_API_INVALID_RESPONSE);
			
			wfUtils::clearScanLock();
			$peakMemory = self::logPeakMemory();
			self::status(2, 'info', sprintf(
				/* translators: 1. Bytes of memory. 2. Bytes of memory. */
				__('Wordfence used %1$s of memory for scan. Server peak memory usage was: %2$s', 'wordfence'),
				wfUtils::formatBytes($peakMemory - self::$peakMemAtStart),
				wfUtils::formatBytes($peakMemory)
			));
			self::status(2, 'error', sprintf(/* translators: Error message. */ __("Scan terminated with error: %s", 'wordfence'), $e->getMessage()));
			exit();
		}
		catch (wfAPICallErrorResponseException $e) {
			wfConfig::set('lastScanCompleted', $e->getMessage());
			wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_API_ERROR_RESPONSE);
			
			wfUtils::clearScanLock();
			$peakMemory = self::logPeakMemory();
			self::status(2, 'info', sprintf(
				/* translators: 1. Bytes of memory. 2. Bytes of memory. */
				__('Wordfence used %1$s of memory for scan. Server peak memory usage was: %2$s', 'wordfence'),
				wfUtils::formatBytes($peakMemory - self::$peakMemAtStart),
				wfUtils::formatBytes($peakMemory)
			));
			self::status(2, 'error', sprintf(/* translators: Error message. */ __("Scan terminated with error: %s", 'wordfence'), $e->getMessage()));

			if (preg_match('/The Wordfence API key you\'re using is already being used by: (\S*?) /', $e->getMessage(), $matches)) {
				wordfence::alert(__('Wordfence scan failed because of license site URL conflict', 'wordfence'), sprintf(
				/* translators: Site URL. */
					__(<<<MSG
The Wordfence scan has failed because the Wordfence API key you're using is already being used by: %s

If you have changed your blog URL, please sign-in to Wordfence, purchase a new key or reset an existing key, and then enter that key on this site's Wordfence Options page.
MSG
					, 'wordfence'), $matches[1]), false);
			}

			exit();
		}
		catch (Exception $e) {
			wfUtils::clearScanLock();
			self::status(2, 'error', sprintf(/* translators: Error message. */ __("Scan terminated with error: %s", 'wordfence'), $e->getMessage()));
			self::status(10, 'info', "SUM_KILLED:" . __('Previous scan terminated with an error. See below.', 'wordfence'));
			exit();
		}
		wfUtils::clearScanLock();
	}
	public static function logPeakMemory(){
		$oldPeak = wfConfig::get('wfPeakMemory', 0, false);
		$peak = memory_get_peak_usage(true);
		if ($peak > $oldPeak) {
			wfConfig::set('wfPeakMemory', $peak, wfConfig::DONT_AUTOLOAD);
			return $peak;
		}
		return $oldPeak;
	}
	public static function obHandler($buf){
		if(strlen($buf) > 1000){
			$buf = substr($buf, 0, 255);
		}
		if(empty($buf) === false && preg_match('/[a-zA-Z0-9]+/', $buf)){
			self::status(1, 'error', $buf);
		}
	}
	public static function error_handler($errno, $errstr, $errfile, $errline){
		if(self::$errorHandlingOn && error_reporting() > 0){
			if(preg_match('/wordfence\//', $errfile)){
				$level = 1; //It's one of our files, so level 1
			} else {
				$level = 4; //It's someone elses plugin so only show if debug is enabled
			}
			self::status($level, 'error', "$errstr ($errno) File: $errfile Line: $errline");
		}
		return false;
	}
	public static function shutdown(){
		self::logPeakMemory();
	}
	private static function errorExit($msg){
		wordfence::status(1, 'error', sprintf(/* translators: Error message. */ __('Scan Engine Error: %s', 'wordfence'), $msg));
		exit();	
	}
	private static function status($level, $type, $msg){
		wordfence::status($level, $type, $msg);
	}
}