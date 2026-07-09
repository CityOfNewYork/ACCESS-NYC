<?php
require_once(__DIR__ . '/wordfenceClass.php');
require_once(__DIR__ . '/wordfenceHash.php');
require_once(__DIR__ . '/wfAPI.php');
require_once(__DIR__ . '/wordfenceScanner.php');
require_once(__DIR__ . '/wfIssues.php');
require_once(__DIR__ . '/wfDB.php');
require_once(__DIR__ . '/wfUtils.php');
require_once(__DIR__ . '/wfFileUtils.php');
require_once(__DIR__ . '/wfScanPath.php');
require_once(__DIR__ . '/wfScanFile.php');
require_once(__DIR__ . '/wfScanFileListItem.php');
require_once(__DIR__ . '/wfScanEntrypoint.php');
require_once(__DIR__ . '/wfCurlInterceptor.php');
require_once(__DIR__ . '/wfCommonPasswords.php');

class wfScanEngine {
	const SCAN_MANUALLY_KILLED = -999;
	const SCAN_CHECK_INTERVAL = 10; //Seconds
	
	private static $scanIsRunning = false; //Indicates that the scan is running in this specific process

	public $api = false;
	private $forkRequested = false;
	private $lastCheck = 0;

	//Beginning of serialized properties on sleep
	/** @var wordfenceHash */
	private $hasher = false;
	private $jobList = array();
	private $i = false;
	private $wp_version = false;
	private $apiKey = false;
	private $startTime = 0;
	public $maxExecTime = false; //If more than $maxExecTime has elapsed since last check, fork a new scan process and continue
	private $publicScanEnabled = false;
	private $fileContentsResults = false;
	/**
	 * @var bool|wordfenceScanner
	 */
	private $scanner = false;
	private $scanQueue = array();
	/**
	 * @var bool|wordfenceURLHoover
	 */
	private $hoover = false;
	private $scanData = array();
	private $statusIDX = array(
		'core'    => false,
		'plugin'  => false,
		'theme'   => false,
		'unknown' => false
	);
	private $userPasswdQueue = "";
	private $passwdHasIssues = wfIssues::STATE_SECURE;
	private $suspectedFiles = false; //Files found with the ".suspected" extension
	private $gsbMultisiteBlogOffset = 0;
	private $updateCheck = false;
	private $pluginRepoStatus = array();
	private $malwarePrefixesHash;
	private $coreHashesHash;
	private $scanMode = wfScanner::SCAN_TYPE_STANDARD;
	private $pluginsCounted = false;
	private $themesCounted = false;
	private $cycleStartTime;

	/**
	 * @var wfScanner
	 */
	private $scanController; //Not serialized

	/**
	 * @var wordfenceDBScanner
	 */
	private $dbScanner;

	/**
	 * @var wfScanKnownFilesLoader
	 */
	private $knownFilesLoader;

	private $metrics = array();

	private $checkHowGetIPsRequestTime = 0;
	
	/**
	 * Returns whether or not the Wordfence scan is running. When $inThisProcessOnly is true, it returns true only
	 * if the scan is running in this process. Otherwise it returns true if the scan is running at all.
	 * 
	 * @param bool $inThisProcessOnly
	 * @return bool
	 */
	public static function isScanRunning($inThisProcessOnly = true) {
		if ($inThisProcessOnly) {
			return self::$scanIsRunning;
		}
		
		return wfScanner::shared()->isRunning();
	}

	public static function testForFullPathDisclosure($url = null, $filePath = null) {
		if ($url === null && $filePath === null) {
			$url = includes_url('rss-functions.php');
			$filePath = ABSPATH . WPINC . '/rss-functions.php';
		}

		$response = wp_remote_get($url);
		$html = wp_remote_retrieve_body($response);
		return preg_match("/" . preg_quote(realpath($filePath), "/") . "/i", $html);
	}

	public static function isDirectoryListingEnabled($url = null) {
		if ($url === null) {
			$uploadPaths = wp_upload_dir();
			$url = $uploadPaths['baseurl'];
		}

		$response = wp_remote_get($url);
		return !is_wp_error($response) && ($responseBody = wp_remote_retrieve_body($response)) &&
			stripos($responseBody, '<title>Index of') !== false;
	}

	public static function refreshScanNotification($issuesInstance = null) {
		if ($issuesInstance === null) {
			$issuesInstance = new wfIssues();
		}

		$message = wfConfig::get('lastScanCompleted', false);
		if ($message === false || empty($message)) {
			$n = wfNotification::getNotificationForCategory('wfplugin_scan');
			if ($n !== null) {
				$n->markAsRead();
			}
		} else if ($message == 'ok') {
			$issueCount = $issuesInstance->getIssueCount();
			if ($issueCount) {
				new wfNotification(null, wfNotification::PRIORITY_HIGH_WARNING, "<a href=\"" . wfUtils::wpAdminURL('admin.php?page=WordfenceScan') . "\">" .
					/* translators: Number of scan results. */
					sprintf(_n('%d issue found in most recent scan', '%d issues found in most recent scan', $issueCount, 'wordfence'), $issueCount)
					. '</a>', 'wfplugin_scan');
			} else {
				$n = wfNotification::getNotificationForCategory('wfplugin_scan');
				if ($n !== null) {
					$n->markAsRead();
				}
			}
		} else {
			$failureType = wfConfig::get('lastScanFailureType');
			if ($failureType == 'duration') {
				new wfNotification(null, wfNotification::PRIORITY_HIGH_WARNING, '<a href="' . wfUtils::wpAdminURL('admin.php?page=WordfenceScan') . '">Scan aborted due to duration limit</a>', 'wfplugin_scan');
			} else if ($failureType == 'versionchange') {
				//No need to create a notification
			} else {
				$trimmedError = substr($message, 0, 100) . (strlen($message) > 100 ? '...' : '');
				new wfNotification(null, wfNotification::PRIORITY_HIGH_WARNING, '<a href="' . wfUtils::wpAdminURL('admin.php?page=WordfenceScan') . '">Scan failed: ' . esc_html($trimmedError) . '</a>', 'wfplugin_scan');
			}
		}
	}

	public function __sleep() { //Same order here as above for properties that are included in serialization
		return array('hasher', 'jobList', 'i', 'wp_version', 'apiKey', 'startTime', 'maxExecTime', 'publicScanEnabled', 'fileContentsResults', 'scanner', 'scanQueue', 'hoover', 'scanData', 'statusIDX', 'userPasswdQueue', 'passwdHasIssues', 'suspectedFiles', 'dbScanner', 'knownFilesLoader', 'metrics', 'checkHowGetIPsRequestTime', 'gsbMultisiteBlogOffset', 'updateCheck', 'pluginRepoStatus', 'malwarePrefixesHash', 'coreHashesHash', 'scanMode', 'pluginsCounted', 'themesCounted');
	}

	public function __construct($malwarePrefixesHash = '', $coreHashesHash = '', $scanMode = wfScanner::SCAN_TYPE_STANDARD) {
		$this->startTime = time();
		$this->recordMetric('scan', 'start', $this->startTime);
		$this->maxExecTime = self::getMaxExecutionTime();
		$this->i = new wfIssues();
		$this->cycleStartTime = time();
		$this->wp_version = wfUtils::getWPVersion();
		$this->apiKey = wfConfig::get('apiKey');
		$this->api = new wfAPI($this->apiKey, $this->wp_version);
		$this->malwarePrefixesHash = $malwarePrefixesHash;
		$this->coreHashesHash = $coreHashesHash;
		$this->scanMode = $scanMode;

		$this->scanController = new wfScanner($scanMode);
		$jobs = $this->scanController->jobs();
		foreach ($jobs as $job) {
			if (method_exists($this, 'scan_' . $job . '_init')) {
				foreach (array('init', 'main', 'finish') as $op) {
					$this->jobList[] = $job . '_' . $op;
				}
			} else if (method_exists($this, 'scan_' . $job)) {
				$this->jobList[] = $job;
			}
		}
	}

	public function scanController() {
		return $this->scanController;
	}

	/**
	 * Deletes all new issues. To only delete specific types, provide an array of issue types.
	 *
	 * @param null|array $types
	 */
	public function deleteNewIssues($types = null) {
		$this->i->deleteNew($types);
	}

	public function __wakeup() {
		$this->cycleStartTime = time();
		$this->api = new wfAPI($this->apiKey, $this->wp_version);
		$this->scanController = new wfScanner($this->scanMode);
	}

	public function isFullScan() {
		return $this->scanMode != wfScanner::SCAN_TYPE_QUICK;
	}

	public function go() {
		self::$scanIsRunning = true;
		try {
			self::checkScanStatus();
			$this->doScan();
			wfConfig::set('lastScanCompleted', 'ok');
			wfConfig::set('lastScanFailureType', false);
			self::checkForKill();
			//updating this scan ID will trigger the scan page to load/reload the results.
			$this->scanController->recordLastScanTime();
			//scan ID only incremented at end of scan to make UI load new results
			$this->emailNewIssues();
			if ($this->isFullScan()) {
				$this->recordMetric('scan', 'duration', (time() - $this->startTime));
				$this->recordMetric('scan', 'memory', wfConfig::get('wfPeakMemory', 0, false));
				$this->submitMetrics();
			}

			wfScanEngine::refreshScanNotification($this->i);

			if (wfCentral::isConnected()) {
				wfCentral::updateScanStatus();
			}
			self::$scanIsRunning = false;
		} catch (wfScanEngineDurationLimitException $e) {
			wfConfig::set('lastScanCompleted', $e->getMessage());
			wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_DURATION_REACHED);
			$this->scanController->recordLastScanTime();

			$this->emailNewIssues(true);
			$this->recordMetric('scan', 'duration', (time() - $this->startTime));
			$this->recordMetric('scan', 'memory', wfConfig::get('wfPeakMemory', 0, false));
			$this->submitMetrics();

			wfScanEngine::refreshScanNotification($this->i);
			self::$scanIsRunning = false;
			throw $e;
		} catch (wfScanEngineCoreVersionChangeException $e) {
			wfConfig::set('lastScanCompleted', $e->getMessage());
			wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_VERSION_CHANGE);
			$this->scanController->recordLastScanTime();

			$this->recordMetric('scan', 'duration', (time() - $this->startTime));
			$this->recordMetric('scan', 'memory', wfConfig::get('wfPeakMemory', 0, false));
			$this->submitMetrics();

			$this->deleteNewIssues();

			wfScanEngine::refreshScanNotification($this->i);
			self::$scanIsRunning = false;
			throw $e;
		} catch (wfScanEngineTestCallbackFailedException $e) {
			wfConfig::set('lastScanCompleted', $e->getMessage());
			wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_CALLBACK_TEST_FAILED);
			$this->scanController->recordLastScanTime();

			$this->recordMetric('scan', 'duration', (time() - $this->startTime));
			$this->recordMetric('scan', 'memory', wfConfig::get('wfPeakMemory', 0, false));
			$this->recordMetric('scan', 'failure', $e->getMessage());
			$this->submitMetrics();

			wfScanEngine::refreshScanNotification($this->i);
			self::$scanIsRunning = false;
			throw $e;
		} catch (Exception $e) {
			if ($e->getCode() != wfScanEngine::SCAN_MANUALLY_KILLED) {
				wfConfig::set('lastScanCompleted', $e->getMessage());
				wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_GENERAL);
			}

			$this->recordMetric('scan', 'duration', (time() - $this->startTime));
			$this->recordMetric('scan', 'memory', wfConfig::get('wfPeakMemory', 0, false));
			$this->recordMetric('scan', 'failure', $e->getMessage());
			$this->submitMetrics();

			wfScanEngine::refreshScanNotification($this->i);
			self::$scanIsRunning = false;
			throw $e;
		}
	}

	public function checkForDurationLimit() {
		static $timeLimit = false;
		if ($timeLimit === false) {
			$timeLimit = intval(wfConfig::get('scan_maxDuration'));
			if ($timeLimit < 1) {
				$timeLimit = WORDFENCE_DEFAULT_MAX_SCAN_TIME;
			}
		}

		if ((time() - $this->startTime) > $timeLimit) {
			$error = sprintf(
			/* translators: 1. Time duration. 2. Support URL. */
				__('The scan time limit of %1$s has been exceeded and the scan will be terminated. This limit can be customized on the options page. <a href="%2$s" target="_blank" rel="noopener noreferrer">Get More Information<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'),
				wfUtils::makeDuration($timeLimit),
				wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_TIME_LIMIT)
			);
			$this->addIssue('timelimit', wfIssues::SEVERITY_HIGH, md5($this->startTime), md5($this->startTime), __('Scan Time Limit Exceeded', 'wordfence'), $error, array());

			$this->status(1, 'info', '-------------------');
			$this->status(1, 'info', sprintf(
			/* translators: 1. Number of files. 2. Number of plugins. 3. Number of themes. 4. Number of posts. 5. Number of comments. 6. Number of URLs. 7. Time duration. */
				__('Scan interrupted. Scanned %1$d files, %2$d plugins, %3$d themes, %4$d posts, %5$d comments and %6$d URLs in %7$s.', 'wordfence'),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_FILES, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_PLUGINS, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_THEMES, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_POSTS, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_COMMENTS, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_URLS, 0),
				wfUtils::makeDuration(time() - $this->startTime, true)
			));
			if ($this->i->totalIssues > 0) {
				$this->status(10, 'info', "SUM_FINAL:" . sprintf(
					/* translators: Number of scan results. */
						_n(
							"Scan interrupted. You have %d new issue to fix. See below.",
							"Scan interrupted. You have %d new issues to fix. See below.",
							$this->i->totalIssues,
							'wordfence'),
						$this->i->totalIssues
					)
				);
			} else {
				$this->status(10, 'info', "SUM_FINAL:" . __('Scan interrupted. No problems found prior to stopping.', 'wordfence'));
			}
			throw new wfScanEngineDurationLimitException($error);
		}
	}

	public function checkForCoreVersionChange() {
		$startVersion = wfConfig::get('wfScanStartVersion');
		$currentVersion = wfUtils::getWPVersion(true);
		if (version_compare($startVersion, $currentVersion) != 0) {
			throw new wfScanEngineCoreVersionChangeException(sprintf(
			/* translators: 1. Software version. 2. Software version. */
				__('Aborting scan because WordPress updated from version %1$s to %2$s. The scan will be reattempted later.', 'wordfence'), $startVersion, $currentVersion));
		}
	}

	private function checkScanStatus() {
		wfIssues::updateScanStillRunning();
		$this->checkForCoreVersionChange();
		self::checkForKill();
		$this->checkForDurationLimit();
	}

	public function shouldFork() {
		$timestamp = time();

		if ($timestamp - $this->cycleStartTime > $this->maxExecTime) {
			$this->checkScanStatus();
			return true;
		}

		if ($this->lastCheck > $timestamp - $this->maxExecTime) {
			return false;
		}

		if ($timestamp - $this->lastCheck > self::SCAN_CHECK_INTERVAL)
			$this->checkScanStatus();

		$this->lastCheck = $timestamp;

		return false;
	}

	public function forkIfNeeded() {
		if ($this->shouldFork()) {
			wordfence::status(4, 'info', __("Forking during malware scan to ensure continuity.", 'wordfence'));
			$this->fork();
		}
	}

	public function fork() {
		wordfence::status(4, 'info', __("Entered fork()", 'wordfence'));
		if (wfConfig::set_ser('wfsd_engine', $this, true, wfConfig::DONT_AUTOLOAD)) {
			$this->scanController->flushSummaryItems();
			wordfence::status(4, 'info', __("Calling startScan(true)", 'wordfence'));
			self::startScan(true, $this->scanMode);
		} //Otherwise there was an error so don't start another scan.
		exit(0);
	}

	public function emailNewIssues($timeLimitReached = false) {
		if (!wfCentral::pluginAlertingDisabled()) {
			$this->i->emailNewIssues($timeLimitReached, $this->scanController);
		}
	}

	public function submitMetrics() {
		if (wfConfig::get('other_WFNet', true)) {
			//Trim down the malware matches if needed to allow the report call to succeed
			if (isset($this->metrics['malwareSignature'])) {
				//Get count
				$count = 0;
				$extra_count = 0;
				$rules_with_extras = 0;
				foreach ($this->metrics['malwareSignature'] as $rule => $payloads) {
					$count += count($payloads);
					$extra_count += (count($payloads) - 1);
					if (count($payloads) > 1) {
						$rules_with_extras++;
					}
				}

				//Trim additional matches
				$overage = $extra_count - WORDFENCE_SCAN_ISSUES_MAX_REPORT;
				if ($overage > 0) {
					foreach ($this->metrics['malwareSignature'] as $rule => $payloads) {
						$percent = min(1, (count($payloads) - 1) / $extra_count); //Percentage of the overage this rule is responsible for 
						$to_remove = min(count($payloads) - 1, ceil($percent * $overage)); //Remove the lesser of (all but one, the percentage of the overage)
						$sliced = array_slice($this->metrics['malwareSignature'][$rule], 0, max(1, count($payloads) - $to_remove));
						$count -= (count($this->metrics['malwareSignature'][$rule]) - count($sliced));
						$this->metrics['malwareSignature'][$rule] = $sliced;
					}
				}

				//Trim single matches
				if ($count > WORDFENCE_SCAN_ISSUES_MAX_REPORT) {
					$sliced = array_slice($this->metrics['malwareSignature'], 0, WORDFENCE_SCAN_ISSUES_MAX_REPORT, true);
					$this->metrics['malwareSignature'] = $sliced;
				}
			}

			$this->api->call('record_scan_metrics', array(), array('metrics' => $this->metrics));
		}
	}

	private function doScan() {
		if ($this->scanController->useLowResourceScanning()) {
			$isFork = ($_GET['isFork'] == '1' ? true : false);
			wfConfig::set('lowResourceScanWaitStep', !wfConfig::get('lowResourceScanWaitStep'));
			if ($isFork && wfConfig::get('lowResourceScanWaitStep')) {
				sleep((int) round($this->maxExecTime / 2));
				$this->fork(); //exits
			}
		}

		while (sizeof($this->jobList) > 0) {
			self::checkForKill();
			$jobName = $this->jobList[0];
			$callback = array($this, 'scan_' . $jobName);
			if (is_callable($callback)) {
				call_user_func($callback);
			}
			array_shift($this->jobList); //only shift once we're done because we may pause halfway through a job and need to pick up where we left off
			self::checkForKill();
			if ($this->forkRequested) {
				$this->fork();
			} else {
				$this->forkIfNeeded();
			}
		}

		$this->status(1, 'info', '-------------------');

		$peakMemory = wfScan::logPeakMemory();
		$this->status(2, 'info', sprintf(
			/* translators: 1. Bytes of memory. 2. Bytes of memory. */
			__('Wordfence used %1$s of memory for scan. Server peak memory usage was: %2$s', 'wordfence'),
			wfUtils::formatBytes($peakMemory - wfScan::$peakMemAtStart),
			wfUtils::formatBytes($peakMemory)
		));

		wfScanMonitor::endMonitoring();

		if ($this->isFullScan()) {
			$this->status(1, 'info', sprintf(
			/* translators: 1. Number of files. 2. Number of plugins. 3. Number of themes. 4. Number of posts. 5. Number of comments. 6. Number of URLs. 7. Time duration. */
				__('Scan Complete. Scanned %1$d files, %2$d plugins, %3$d themes, %4$d posts, %5$d comments and %6$d URLs in %7$s.', 'wordfence'),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_FILES, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_PLUGINS, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_THEMES, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_POSTS, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_COMMENTS, 0),
				$this->scanController->getSummaryItem(wfScanner::SUMMARY_SCANNED_URLS, 0),
				wfUtils::makeDuration(time() - $this->startTime, true)
			));
		} else {
			$this->status(1, 'info', sprintf(
			/* translators: 1. Time duration. */
				__("Quick Scan Complete. Scanned in %s.", 'wordfence'),
				wfUtils::makeDuration(time() - $this->startTime, true)
			));
		}

		$ignoredText = '';
		if ($this->i->totalIgnoredIssues > 0) {
			$ignoredText = ' ' . sprintf(
				/* translators: Number of scan results. */
					_n(
						'%d ignored issue was also detected.',
						'%d ignored issues were also detected.',
						$this->i->totalIgnoredIssues,
						'wordfence'
					), $this->i->totalIgnoredIssues);
		}

		if ($this->i->totalIssues > 0) {
			$this->status(10, 'info', "SUM_FINAL:" . sprintf(
				/* translators: Number of scan results. */
					_n(
						"Scan complete. You have %d new issue to fix.",
						"Scan complete. You have %d new issues to fix.",
						$this->i->totalIssues,
						'wordfence'),
					$this->i->totalIssues
				) .
				$ignoredText . ' ' .
				__('See below.', 'wordfence')
			);
		} else {
			$this->status(10, 'info', "SUM_FINAL:" . __('Scan complete. Congratulations, no new problems found.', 'wordfence') . $ignoredText);
		}
		return;
	}

	public function getCurrentJob() {
		return $this->jobList[0];
	}

	private function scan_checkSpamIP() {
		if ($this->scanController->isPremiumScan()) {
			$this->statusIDX['checkSpamIP'] = wfIssues::statusStart(__("Checking if your site IP is generating spam", 'wordfence'));
			$this->scanController->startStage(wfScanner::STAGE_SPAM_CHECK);
			$result = $this->api->call('check_spam_ip', array(), array(
				'siteURL' => site_url()
			));
			$haveIssues = wfIssues::STATE_SECURE;
			if (!empty($result['haveIssues']) && is_array($result['issues'])) {
				foreach ($result['issues'] as $issue) {
					$added = $this->addIssue($issue['type'], wfIssues::SEVERITY_HIGH, $issue['ignoreP'], $issue['ignoreC'], $issue['shortMsg'], $issue['longMsg'], $issue['data']);
					if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
						$haveIssues = wfIssues::STATE_PROBLEM;
					} else if ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC) {
						$haveIssues = wfIssues::STATE_IGNORED;
					}
				}
			}
			wfIssues::statusEnd($this->statusIDX['checkSpamIP'], $haveIssues);
			$this->scanController->completeStage(wfScanner::STAGE_SPAM_CHECK, $haveIssues);
		} else {
			wfIssues::statusPaidOnly(__("Checking if your IP is generating spam is for paid members only", 'wordfence'));
			sleep(2);
		}
	}

	private function scan_checkGSB_init() {
		if ($this->scanController->isPremiumScan()) {
			$this->statusIDX['checkGSB'] = wfIssues::statusStart(__("Checking if your site is on a domain blocklist", 'wordfence'));
			$this->scanController->startStage(wfScanner::STAGE_BLACKLIST_CHECK);
			$h = new wordfenceURLHoover($this->apiKey, $this->wp_version);
			$h->cleanup();
		} else {
			wfIssues::statusPaidOnly(__("Checking if your site is on a domain blocklist is for paid members only", 'wordfence'));
			sleep(2);
		}
	}

	private function scan_checkGSB_main() {
		if ($this->scanController->isPremiumScan()) {
			if (is_multisite()) {
				global $wpdb;
				$h = new wordfenceURLHoover($this->apiKey, $this->wp_version, true);
				$blogIDs = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs} WHERE blog_id > %d ORDER BY blog_id ASC", $this->gsbMultisiteBlogOffset)); //Can't use wp_get_sites or get_sites because they return empty at 10k sites
				foreach ($blogIDs as $id) {
					$homeURL = get_home_url($id);
					$h->hoover($id, $homeURL);
					$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_URLS);
					$siteURL = get_site_url($id);
					if ($homeURL != $siteURL) {
						$h->hoover($id, $siteURL);
						$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_URLS);
					}

					if ($this->shouldFork()) {
						$this->gsbMultisiteBlogOffset = $id;
						$this->forkIfNeeded();
					}
				}
			}
		}
	}

	private function scan_checkGSB_finish() {
		if ($this->scanController->isPremiumScan()) {
			if (is_multisite()) {
				$h = new wordfenceURLHoover($this->apiKey, $this->wp_version, true);
				$badURLs = $h->getBaddies();
				if ($h->errorMsg) {
					$this->status(4, 'info', sprintf(/* translators: Error message. */ __("Error checking domain blocklists: %s", 'wordfence'), $h->errorMsg));
					wfIssues::statusEnd($this->statusIDX['checkGSB'], wfIssues::STATE_FAILED);
					$this->scanController->completeStage(wfScanner::STAGE_BLACKLIST_CHECK, wfIssues::STATE_FAILED);
					return;
				}
				$h->cleanup();
			} else {
				$urlsToCheck = array(array(wfUtils::wpHomeURL(), wfUtils::wpSiteURL()));
				$badURLs = $this->api->call('check_bad_urls', array(), array('toCheck' => json_encode($urlsToCheck))); //Skipping the separate prefix check since there are just two URLs
				$finalResults = array();
				foreach ($badURLs as $file => $badSiteList) {
					if (!isset($finalResults[$file])) {
						$finalResults[$file] = array();
					}
					foreach ($badSiteList as $badSite) {
						$finalResults[$file][] = array(
							'URL'     => $badSite[0],
							'badList' => $badSite[1]
						);
					}
				}
				$badURLs = $finalResults;
			}

			$haveIssues = wfIssues::STATE_SECURE;
			if (is_array($badURLs) && count($badURLs) > 0) {
				foreach ($badURLs as $id => $badSiteList) {
					foreach ($badSiteList as $badSite) {
						$url = $badSite['URL'];
						$badList = $badSite['badList'];
						$data = array('badURL' => $url);

						if ($badList == 'wordfence-dbl') {
							if (is_multisite()) {
								$shortMsg = sprintf(
								/* translators: WordPress site ID. */
									__('The multisite blog with ID %d is listed on the Wordfence domain blocklist.', 'wordfence'), intval($id));
								$data['multisite'] = intval($id);
							} else {
								$shortMsg = __('Your site is listed on the Wordfence domain blocklist.', 'wordfence');
							}
							$longMsg = sprintf(
							/* translators: URL. */
								__("The URL %s is on the blocklist.", 'wordfence'), esc_html($url));
							$data['gsb'] = $badList;
						}
						else {
							if (is_multisite()) {
								$shortMsg = sprintf(
								/* translators: WordPress site ID. */
									__('The multisite blog with ID %d is listed on a domain blocklist.', 'wordfence'), intval($id));
								$data['multisite'] = intval($id);
							} else {
								$shortMsg = __('Your site is listed on a domain blocklist.', 'wordfence');
							}
							$longMsg = sprintf(/* translators: URL. */ __("The URL is: %s", 'wordfence'), esc_html($url));
							$data['gsb'] = 'unknown';
						}

						$added = $this->addIssue('checkGSB', wfIssues::SEVERITY_CRITICAL, 'checkGSB', 'checkGSB' . $url, $shortMsg, $longMsg, $data);
						if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
							$haveIssues = wfIssues::STATE_PROBLEM;
						} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
							$haveIssues = wfIssues::STATE_IGNORED;
						}
					}
				}
			}

			wfIssues::statusEnd($this->statusIDX['checkGSB'], $haveIssues);
			$this->scanController->completeStage(wfScanner::STAGE_BLACKLIST_CHECK, $haveIssues);
		}
	}

	private function scan_checkHowGetIPs_init() {
		$this->statusIDX['checkHowGetIPs'] = wfIssues::statusStart(__("Checking for the most secure way to get IPs", 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_SERVER_STATE);
		$this->checkHowGetIPsRequestTime = time();
		wfUtils::requestDetectProxyCallback();
	}

	private function scan_checkHowGetIPs_main() {
		if (!defined('WORDFENCE_CHECKHOWGETIPS_TIMEOUT')) {
			define('WORDFENCE_CHECKHOWGETIPS_TIMEOUT', 30);
		}

		$haveIssues = wfIssues::STATE_SECURE;
		$existing = wfConfig::get('howGetIPs', '');
		$recommendation = wfConfig::get('detectProxyRecommendation', '');
		while (empty($recommendation) && (time() - $this->checkHowGetIPsRequestTime) < WORDFENCE_CHECKHOWGETIPS_TIMEOUT) {
			sleep(1);
			$this->forkIfNeeded();
			$recommendation = wfConfig::get('detectProxyRecommendation', '');
		}

		if ($recommendation == 'DEFERRED') {
			//Do nothing
			$haveIssues = wfIssues::STATE_SKIPPED;
		} else if (empty($recommendation)) {
			$haveIssues = wfIssues::STATE_FAILED;
		} else if ($recommendation == 'UNKNOWN') {
			$added = $this->addIssue('checkHowGetIPs', wfIssues::SEVERITY_HIGH, 'checkHowGetIPs', 'checkHowGetIPs' . $recommendation . WORDFENCE_VERSION,
				__("Unable to accurately detect IPs", 'wordfence'),
				sprintf(/* translators: Support URL. */ __('Wordfence was unable to validate a test request to your website. This can happen if your website is behind a proxy that does not use one of the standard ways to convey the IP of the request or it is unreachable publicly. IP blocking and live traffic information may not be accurate. <a href="%s" target="_blank" rel="noopener noreferrer">Get More Information<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_NOTICE_MISCONFIGURED_HOW_GET_IPS))
				, array());
			if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
				$haveIssues = wfIssues::STATE_PROBLEM;
			} else if ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC) {
				$haveIssues = wfIssues::STATE_IGNORED;
			}
		} else if (!empty($existing) && $existing != $recommendation) {
			$extraMsg = '';
			if ($recommendation == 'REMOTE_ADDR') {
				$extraMsg = ' ' . __('For maximum security use PHP\'s built in REMOTE_ADDR.', 'wordfence');
			} else if ($recommendation == 'HTTP_X_FORWARDED_FOR') {
				$extraMsg = ' ' . __('This site appears to be behind a front-end proxy, so using the X-Forwarded-For HTTP header will resolve to the correct IPs.', 'wordfence');
			} else if ($recommendation == 'HTTP_X_REAL_IP') {
				$extraMsg = ' ' . __('This site appears to be behind a front-end proxy, so using the X-Real-IP HTTP header will resolve to the correct IPs.', 'wordfence');
			} else if ($recommendation == 'HTTP_CF_CONNECTING_IP') {
				$extraMsg = ' ' . __('This site appears to be behind Cloudflare, so using the Cloudflare "CF-Connecting-IP" HTTP header will resolve to the correct IPs.', 'wordfence');
			}

			$added = $this->addIssue('checkHowGetIPs', wfIssues::SEVERITY_HIGH, 'checkHowGetIPs', 'checkHowGetIPs' . $recommendation . WORDFENCE_VERSION,
				__("'How does Wordfence get IPs' is misconfigured", 'wordfence'),
				sprintf(
				/* translators: Support URL. */
					__('A test request to this website was detected on a different value for this setting. IP blocking and live traffic information may not be accurate. <a href="%s" target="_blank" rel="noopener noreferrer">Get More Information<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'),
					wfSupportController::esc_supportURL(wfSupportController::ITEM_NOTICE_MISCONFIGURED_HOW_GET_IPS)
				) . $extraMsg,
				array('recommendation' => $recommendation));
			if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
				$haveIssues = wfIssues::STATE_PROBLEM;
			} else if ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC) {
				$haveIssues = wfIssues::STATE_IGNORED;
			}
		}

		wfIssues::statusEnd($this->statusIDX['checkHowGetIPs'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_SERVER_STATE, $haveIssues);
	}

	private function scan_checkHowGetIPs_finish() {
		/* Do nothing */
	}

	private function scan_checkReadableConfig() {
		$haveIssues = wfIssues::STATE_SECURE;
		$status = wfIssues::statusStart(__("Check for publicly accessible configuration files, backup files and logs", 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_PUBLIC_FILES);

		$backupFileTests = array(
			wfCommonBackupFileTest::createFromRootPath('.env'),
			wfCommonBackupFileTest::createFromRootPath('.user.ini'),
//			wfCommonBackupFileTest::createFromRootPath('.htaccess'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php.bak'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php.bak.a2'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php.swo'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php.save'),
			new wfCommonBackupFileTest(home_url('%23wp-config.php%23'), ABSPATH . '#wp-config.php#'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php~'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.old'),
			wfCommonBackupFileTest::createFromRootPath('.wp-config.php.swp'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.bak'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.save'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php_bak'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php.swp'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php.old'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php.original'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.php.orig'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.txt'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.original'),
			wfCommonBackupFileTest::createFromRootPath('wp-config.orig'),
			new wfCommonBackupFileTest(content_url('/debug.log'), WP_CONTENT_DIR . '/debug.log', array(
				'headers' => array(
					'Range' => 'bytes=0-700',
				),
			)),
		);
		$backupFileTests = array_merge($backupFileTests, wfCommonBackupFileTest::createAllForFile('searchreplacedb2.php', wfCommonBackupFileTest::MATCH_REGEX, '/<title>Search and replace DB/i'));

		$userIniFilename = ini_get('user_ini.filename');
		if ($userIniFilename && $userIniFilename !== '.user.ini') {
			$backupFileTests[] = wfCommonBackupFileTest::createFromRootPath($userIniFilename);
		}


		/** @var wfCommonBackupFileTest $test */
		foreach ($backupFileTests as $test) {
			$pathFromRoot = (strpos($test->getPath(), ABSPATH) === 0) ? substr($test->getPath(), strlen(ABSPATH)) : $test->getPath();
			wordfence::status(4, 'info', "Testing {$pathFromRoot}");
			if ($test->fileExists() && $test->isPubliclyAccessible()) {
				$key = "configReadable" . bin2hex($test->getUrl());
				$added = $this->addIssue(
					'configReadable',
					wfIssues::SEVERITY_CRITICAL,
					$key,
					$key,
					sprintf(
					/* translators: File path. */
						__('Publicly accessible config, backup, or log file found: %s', 'wordfence'), $pathFromRoot),
					sprintf(
					/* translators: 1. URL to publicly accessible file. 2. Support URL. */
						__('<a href="%1$s" target="_blank" rel="noopener noreferrer">%1$s</a> is publicly accessible and may expose source code or sensitive information about your site. Files such as this one are commonly checked for by scanners and should be made inaccessible. Alternately, some can be removed if you are certain your site does not need them. Sites using the nginx web server may need manual configuration changes to protect such files. <a href="%2$s" target="_blank" rel="noopener noreferrer">Learn more<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'),
						$test->getUrl(),
						wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_PUBLIC_CONFIG)
					),
					array(
						'url'       => $test->getUrl(),
						'file'      => $pathFromRoot,
						'realFile'	=> $test->getPath(),
						'canDelete' => true,
					)
				);
				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}
			}
		}

		wfIssues::statusEnd($status, $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_PUBLIC_FILES, $haveIssues);
	}

	private function scan_wpscan_fullPathDisclosure() {
		$file = realpath(ABSPATH . WPINC . "/rss-functions.php");
		if (!$file) {
			return;
		}

		$haveIssues = wfIssues::STATE_SECURE;
		$status = wfIssues::statusStart(__("Checking if your server discloses the path to the document root", 'wordfence'));
		$testPage = includes_url() . basename($file);

		if (self::testForFullPathDisclosure($testPage, $file)) {
			$key = 'wpscan_fullPathDisclosure' . $testPage;
			$added = $this->addIssue(
				'wpscan_fullPathDisclosure',
				wfIssues::SEVERITY_HIGH,
				$key,
				$key,
				__('Web server exposes the document root', 'wordfence'),
				__('Full Path Disclosure (FPD) vulnerabilities enable the attacker to see the path to the webroot/file. e.g.: /home/user/htdocs/file/. Certain vulnerabilities, such as using the load_file() (within a SQL Injection) query to view the page source, require the attacker to have the full path to the file they wish to view.', 'wordfence'),
				array('url' => $testPage)
			);
			if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
				$haveIssues = wfIssues::STATE_PROBLEM;
			} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
				$haveIssues = wfIssues::STATE_IGNORED;
			}
		}

		wfIssues::statusEnd($status, $haveIssues);
	}

	private function scan_wpscan_directoryListingEnabled() {
		$this->statusIDX['wpscan_directoryListingEnabled'] = wfIssues::statusStart("Checking to see if directory listing is enabled");

		$uploadPaths = wp_upload_dir();
		$enabled = self::isDirectoryListingEnabled($uploadPaths['baseurl']);

		$haveIssues = wfIssues::STATE_SECURE;
		if ($enabled) {
			$added = $this->addIssue(
				'wpscan_directoryListingEnabled',
				wfIssues::SEVERITY_HIGH,
				'wpscan_directoryListingEnabled',
				'wpscan_directoryListingEnabled',
				__("Directory listing is enabled", 'wordfence'),
				__("Directory listing provides an attacker with the complete index of all the resources located inside of the directory. The specific risks and consequences vary depending on which files are listed and accessible, but it is recommended that you disable it unless it is needed.", 'wordfence'),
				array(
					'url' => $uploadPaths['baseurl'],
				)
			);
			if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
				$haveIssues = wfIssues::STATE_PROBLEM;
			} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
				$haveIssues = wfIssues::STATE_IGNORED;
			}
		}
		wfIssues::statusEnd($this->statusIDX['wpscan_directoryListingEnabled'], $haveIssues);
	}

	private function scan_checkSpamvertized() {
		if ($this->scanController->isPremiumScan()) {
			$this->statusIDX['spamvertizeCheck'] = wfIssues::statusStart(__("Checking if your site is being Spamvertised", 'wordfence'));
			$this->scanController->startStage(wfScanner::STAGE_SPAMVERTISING_CHECKS);
			$result = $this->api->call('spamvertize_check', array(), array(
				'siteURL' => site_url()
			));
			$haveIssues = wfIssues::STATE_SECURE;
			if ($result['haveIssues'] && is_array($result['issues'])) {
				foreach ($result['issues'] as $issue) {
					$added = $this->addIssue($issue['type'], wfIssues::SEVERITY_CRITICAL, $issue['ignoreP'], $issue['ignoreC'], $issue['shortMsg'], $issue['longMsg'], $issue['data']);
					if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
						$haveIssues = wfIssues::STATE_PROBLEM;
					} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
						$haveIssues = wfIssues::STATE_IGNORED;
					}
				}
			}
			wfIssues::statusEnd($this->statusIDX['spamvertizeCheck'], $haveIssues);
			$this->scanController->completeStage(wfScanner::STAGE_SPAMVERTISING_CHECKS, $haveIssues);
		} else {
			wfIssues::statusPaidOnly(__("Check if your site is being Spamvertized is for paid members only", 'wordfence'));
			sleep(2);
		}
	}

	private function _scannedSkippedPaths() {
		static $_cache = null;
		if ($_cache === null) {
			$scanPaths = array();
			$directoryConstants = array(
				'WP_PLUGIN_DIR' => '/wp-content/plugins',
				'UPLOADS' => '/wp-content/uploads',
				'WP_CONTENT_DIR' => '/wp-content',
			);
			foreach ($directoryConstants as $constant => $wordpressPath) {
				if (!defined($constant))
					continue;
				$path = constant($constant);
				if (!empty($path)) {
					if ($constant === 'UPLOADS')
						$path = ABSPATH . $path;
					try {
						$scanPaths[] = new wfScanPath(
							ABSPATH,
							$path,
							$wordpressPath
						);
					}
					catch (wfInvalidPathException $e) {
						//Ignore invalid scan paths
						wordfence::status(4, 'info', sprintf(/* translators: filesystem path */ __("Ignoring invalid scan path: %s", 'wordfence'), $e->getPath()));
					}
				}
			}
			$scanPaths[] = new wfScanPath(
				ABSPATH,
				ABSPATH,
				'/',
				array('.htaccess', 'index.php', 'license.txt', 'readme.html', 'wp-activate.php', 'wp-admin', 'wp-app.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-config-sample.php', 'wp-content', 'wp-cron.php', 'wp-includes', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-pass.php', 'wp-register.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', 'xmlrpc.php', '.well-known', 'cgi-bin')
			);
			if (WF_IS_FLYWHEEL && !empty($_SERVER['DOCUMENT_ROOT'])) {
				$scanPaths[] = new wfScanPath(
					ABSPATH,
					$_SERVER['DOCUMENT_ROOT'],
					'/../'
				);
			}
			$scanOutside = $this->scanController->scanOutsideWordPress();
			$entrypoints = array();
			foreach ($scanPaths as $scanPath) {
				if (!$scanOutside && $scanPath->hasExpectedFiles()) {
					try {
						foreach ($scanPath->getContents() as $fileName) {
							try {
								$file = $scanPath->createScanFile($fileName);
								if (wfUtils::fileTooBig($file->getRealPath()))
									continue;
								$entrypoint = new wfScanEntrypoint($file);
								if ($scanPath->expectsFile($fileName) || wfFileUtils::isReadableFile($file->getRealPath())) {
									$entrypoint->setIncluded();
								}
								$entrypoint->addTo($entrypoints);
							}
							catch (wfInvalidPathException $e) {
								wordfence::status(4, 'info', sprintf(/* translators: filesystem path */ __("Ignoring invalid expected scan file: %s", 'wordfence'), $e->getPath()));
							}
						}
					}
					catch (wfInaccessibleDirectoryException $e) {
						throw new Exception(__("Wordfence could not read the content of your WordPress directory. This usually indicates your permissions are so strict that your web server can't read your WordPress directory.", 'wordfence'));
					}
				}
				else {
					try {
						$entrypoint = new wfScanEntrypoint($scanPath->createScanFile('/'), true);
						$entrypoint->addTo($entrypoints);
					}
					catch (wfInvalidPathException $e) {
						wordfence::status(4, 'info', sprintf(/* translators: filesystem path */ __("Ignoring invalid base scan file: %s", 'wordfence'), $e->getPath()));
					}
				}
			}
			$_cache = wfScanEntrypoint::getScannedSkippedFiles($entrypoints);
		}
		return $_cache;
	}

	private function scan_checkSkippedFiles() {
		$haveIssues = wfIssues::STATE_SECURE;
		$status = wfIssues::statusStart(__("Checking for paths skipped due to scan settings", 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_SERVER_STATE);

		$paths = $this->_scannedSkippedPaths();
		if (!empty($paths['skipped'])) {
			$skippedList = '';
			foreach ($paths['skipped'] as $index => $file) {
				$path = esc_html($file->getDisplayPath());

				if ($index >= 10) {
					$skippedList .= sprintf(/* translators: Number of paths skipped in scan. */ __(', and %d more.', 'wordfence'), count($paths['skipped']) - 10);
					break;
				}

				if (!empty($skippedList)) {
					if (count($paths['skipped']) == 2) {
						$skippedList .= ' and ';
					} else if ($index == count($paths['skipped']) - 1) {
						$skippedList .= ', and ';
					} else {
						$skippedList .= ', ';
					}
				}

				$skippedList .= $path;
			}

			$c = count($paths['skipped']);
			$key = "skippedPaths";
			$added = $this->addIssue(
				'skippedPaths',
				wfIssues::SEVERITY_LOW,
				$key,
				$key,
				sprintf(/* translators: Number of paths skipped in scan. */ _n('%d path was skipped for the malware scan due to scan settings', '%d paths were skipped for the malware scan due to scan settings', $c, 'wordfence'), $c),
				sprintf(
				/* translators: 1. Number of paths skipped in scan. 2. Support URL. 3. List of skipped paths. */
					_n(
						'The option "Scan files outside your WordPress installation" is off by default, which means %1$d path and its file(s) will not be scanned for malware or unauthorized changes. To continue skipping this path, you may ignore this issue. Or to start scanning it, enable the option and subsequent scans will include it. Some paths may not be necessary to scan, so this is optional. <a href="%2$s" target="_blank" rel="noopener noreferrer">Learn More<span class="screen-reader-text"> (opens in new tab)</span></a><br><br>The path skipped is %3$s',
						'The option "Scan files outside your WordPress installation" is off by default, which means %1$d paths and their file(s) will not be scanned for malware or unauthorized changes. To continue skipping these paths, you may ignore this issue. Or to start scanning them, enable the option and subsequent scans will include them. Some paths may not be necessary to scan, so this is optional. <a href="%2$s" target="_blank" rel="noopener noreferrer">Learn More<span class="screen-reader-text"> (opens in new tab)</span></a><br><br>The paths skipped are %3$s',
						$c,
						'wordfence'
					),
					$c,
					wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_SKIPPED_PATHS),
					$skippedList
				),
				array()
			);

			if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
				$haveIssues = wfIssues::STATE_PROBLEM;
			} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
				$haveIssues = wfIssues::STATE_IGNORED;
			}
		}

		wfIssues::statusEnd($status, $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_SERVER_STATE, $haveIssues);
	}

	private function scan_knownFiles_init() {
		$paths = $this->_scannedSkippedPaths();
		$includeInKnownFilesScan = $paths['scanned'];
		if ($this->scanController->scanOutsideWordPress()) {
			wordfence::status(2, 'info', __("Including files that are outside the WordPress installation in the scan.", 'wordfence'));
		}

		$this->status(2, 'info', __("Getting plugin list from WordPress", 'wordfence'));
		$knownFilesPlugins = $this->getPlugins();
		$this->status(2, 'info', sprintf(/* translators: Number of plugins. */ _n("Found %d plugin", "Found %d plugins", sizeof($knownFilesPlugins), 'wordfence'), sizeof($knownFilesPlugins)));

		$this->status(2, 'info', __("Getting theme list from WordPress", 'wordfence'));
		$knownFilesThemes = $this->getThemes();
		$this->status(2, 'info', sprintf(/* translators: Number of themes. */ _n("Found %d theme", "Found %d themes", sizeof($knownFilesThemes), 'wordfence'), sizeof($knownFilesThemes)));

		$this->hasher = new wordfenceHash($includeInKnownFilesScan, $this, wfUtils::hex2bin($this->malwarePrefixesHash), $this->coreHashesHash, $this->scanMode);
	}

	private function scan_knownFiles_main() {
		$this->hasher->run($this); //Include this so we can call addIssue and ->api->
		$this->suspectedFiles = $this->hasher->getSuspectedFiles();
		$this->hasher = false;
	}

	private function scan_knownFiles_finish() {
	}

	private function scan_fileContents_init() {
		$options = $this->scanController->scanOptions();
		if ($options['scansEnabled_fileContents']) {
			$this->statusIDX['infect'] = wfIssues::statusStart(__('Scanning file contents for infections and vulnerabilities', 'wordfence'));
			//This stage is marked as started earlier in the hasher rather than here
		} else {
			wfIssues::statusDisabled(__("Skipping scan of file contents for infections and vulnerabilities", 'wordfence'));
		}

		if ($options['scansEnabled_fileContentsGSB']) {
			$this->statusIDX['GSB'] = wfIssues::statusStart(__('Scanning file contents for URLs on a domain blocklist', 'wordfence'));
			//This stage is marked as started earlier in the hasher rather than here
		} else {
			wfIssues::statusDisabled(__("Skipping scan of file contents for URLs on a domain blocklist", 'wordfence'));
		}

		if ($options['scansEnabled_fileContents'] || $options['scansEnabled_fileContentsGSB']) {
			$this->scanner = new wordfenceScanner($this->apiKey, $this->wp_version, ABSPATH, $this);
			$this->status(2, 'info', __("Starting scan of file contents", 'wordfence'));
		} else {
			$this->scanner = false;
		}
	}

	private function scan_fileContents_main() {
		$options = $this->scanController->scanOptions();
		if ($options['scansEnabled_fileContents'] || $options['scansEnabled_fileContentsGSB']) {
			$this->fileContentsResults = $this->scanner->scan($this);
		}
	}

	private function scan_fileContents_finish() {
		$options = $this->scanController->scanOptions();
		if ($options['scansEnabled_fileContents'] || $options['scansEnabled_fileContentsGSB']) {
			$this->status(2, 'info', __("Done file contents scan", 'wordfence'));
			if ($this->scanner->errorMsg) {
				throw new Exception($this->scanner->errorMsg);
			}
			$this->scanner = null;
			$haveIssues = wfIssues::STATE_SECURE;
			$haveIssuesGSB = wfIssues::STATE_SECURE;
			foreach ($this->fileContentsResults as $issue) {
				$this->status(2, 'info', sprintf(/* translators: Scan result description. */ __("Adding issue: %s", 'wordfence'), $issue['shortMsg']));
				$added = $this->addIssue($issue['type'], $issue['severity'], $issue['ignoreP'], $issue['ignoreC'], $issue['shortMsg'], $issue['longMsg'], $issue['data']);

				if (isset($issue['data']['gsb'])) {
					if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
						$haveIssuesGSB = wfIssues::STATE_PROBLEM;
					} else if ($haveIssuesGSB != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
						$haveIssuesGSB = wfIssues::STATE_IGNORED;
					}
				} else {
					if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
						$haveIssues = wfIssues::STATE_PROBLEM;
					} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
						$haveIssues = wfIssues::STATE_IGNORED;
					}
				}
			}
			$this->fileContentsResults = null;

			if ($options['scansEnabled_fileContents']) {
				wfIssues::statusEnd($this->statusIDX['infect'], $haveIssues);
				$this->scanController->completeStage(wfScanner::STAGE_MALWARE_SCAN, $haveIssues);
			}

			if ($options['scansEnabled_fileContentsGSB']) {
				wfIssues::statusEnd($this->statusIDX['GSB'], $haveIssuesGSB);
				$this->scanController->completeStage(wfScanner::STAGE_CONTENT_SAFETY, $haveIssuesGSB);
			}
		}
	}

	private function scan_suspectedFiles() {
		$haveIssues = wfIssues::STATE_SECURE;
		$status = wfIssues::statusStart(__("Scanning for publicly accessible quarantined files", 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_PUBLIC_FILES);

		if (is_array($this->suspectedFiles) && count($this->suspectedFiles) > 0) {
			foreach ($this->suspectedFiles as $file) {
				wordfence::status(4, 'info', sprintf(/* translators: File path. */ __("Testing accessibility of: %s", 'wordfence'), $file));
				$test = wfPubliclyAccessibleFileTest::createFromRootPath($file);
				if ($test->fileExists() && $test->isPubliclyAccessible()) {
					$key = "publiclyAccessible" . bin2hex($test->getUrl());
					$added = $this->addIssue(
						'publiclyAccessible',
						wfIssues::SEVERITY_HIGH,
						$key,
						$key,
						sprintf(/* translators: File path. */ __('Publicly accessible quarantined file found: %s', 'wordfence'), $file),
						sprintf(
						/* translators: URL to publicly accessible file. */
							__('<a href="%1$s" target="_blank" rel="noopener noreferrer">%1$s<span class="screen-reader-text"> (opens in new tab)</span></a> is publicly accessible and may expose source code or sensitive information about your site. Files such as this one are commonly checked for by scanners and should be removed or made inaccessible.', 'wordfence'),
							$test->getUrl()
						),
						array(
							'url'       => $test->getUrl(),
							'file'      => $file,
							'canDelete' => true,
						)
					);

					if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
						$haveIssues = wfIssues::STATE_PROBLEM;
					} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
						$haveIssues = wfIssues::STATE_IGNORED;
					}
				}
			}
		}

		wfIssues::statusEnd($status, $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_PUBLIC_FILES, $haveIssues);
	}

	private function scan_posts_init() {
		$this->statusIDX['posts'] = wfIssues::statusStart(__('Scanning posts for URLs on a domain blocklist', 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_CONTENT_SAFETY);
		$blogsToScan = self::getBlogsToScan('posts');
		$this->scanQueue = '';
		$wfdb = new wfDB();
		$this->hoover = new wordfenceURLHoover($this->apiKey, $this->wp_version);
		foreach ($blogsToScan as $blog) {
			$q1 = $wfdb->querySelect("select ID from " . $blog['table'] . " where post_type IN ('page', 'post') and post_status = 'publish'");
			foreach ($q1 as $idRow) {
				$this->scanQueue .= pack('LL', $blog['blog_id'], $idRow['ID']);
			}
		}
	}

	private function scan_posts_main() {
		global $wpdb;
		$wfdb = new wfDB();
		while (strlen($this->scanQueue) > 0) {
			$segment = substr($this->scanQueue, 0, 8);
			$this->scanQueue = substr($this->scanQueue, 8);
			$elem = unpack('Lblog/Lpost', $segment);
			$queueSize = strlen($this->scanQueue) / 8;
			if ($queueSize > 0 && $queueSize % 1000 == 0) {
				wordfence::status(2, 'info', sprintf(/* translators: Number of posts left to scan. */ __("Scanning posts with %d left to scan.", 'wordfence'), $queueSize));
			}

			$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_POSTS);

			$blogID = $elem['blog'];
			$postID = $elem['post'];

			$blogs = self::getBlogsToScan('posts', $blogID);
			$blog = array_shift($blogs);

			$table = wfDB::blogTable('posts', $blogID);

			$row = $wfdb->querySingleRec("select ID, post_title, post_type, post_date, post_content from {$table} where ID = %d", $postID);
			$found = $this->hoover->hoover($blogID . '-' . $row['ID'], $row['post_title'] . ' ' . $row['post_content'], wordfenceURLHoover::standardExcludedHosts());
			$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_URLS, $found);
			if (preg_match('/(?:<\s*script[^>]*>|<\s*meta.*refresh)/i', $row['post_title'])) {
				$this->addIssue(
					'postBadTitle',
					wfIssues::SEVERITY_HIGH,
					'postBadTitle-' . $blogID . '-' . $row['ID'],
					'postBadTitle-' . $blogID . '-' . $row['ID'] . '-' . md5(wfUtils::ifnull($row['post_title'])),
					__("Post title contains suspicious code", 'wordfence'),
					__("This post contains code that is suspicious. Please check the title of the post and confirm that the code in the title is not malicious.", 'wordfence'),
					array(
						'postID'       => $postID,
						'postTitle'    => $row['post_title'],
						'permalink'    => get_permalink($postID),
						'editPostLink' => get_edit_post_link($postID),
						'type'         => $row['post_type'],
						'postDate'     => $row['post_date'],
						'isMultisite'  => $blog['isMultisite'],
						'domain'       => $blog['domain'],
						'path'         => $blog['path'],
						'blog_id'      => $blog['blog_id']
					)
				);
			}

			$this->forkIfNeeded();
		}
	}

	private function scan_posts_finish() {
		global $wpdb;
		$wfdb = new wfDB();
		$this->status(2, 'info', __("Examining URLs found in posts we scanned for dangerous websites", 'wordfence'));
		$hooverResults = $this->hoover->getBaddies();
		$this->status(2, 'info', __("Done examining URLs", 'wordfence'));
		if ($this->hoover->errorMsg) {
			wfIssues::statusEndErr();
			throw new Exception($this->hoover->errorMsg);
		}
		$this->hoover->cleanup();
		$haveIssues = wfIssues::STATE_SECURE;
		foreach ($hooverResults as $idString => $hresults) {
			$arr = explode('-', $idString);
			$blogID = $arr[0];
			$postID = $arr[1];
			$table = wfDB::blogTable('posts', $blogID);
			$blog = null;
			$post = null;
			foreach ($hresults as $result) {
				if ($result['badList'] != 'wordfence-dbl') {
					continue; //A list type that may be new and the plugin has not been upgraded yet.
				}

				if ($blog === null) {
					$blogs = self::getBlogsToScan('posts', $blogID);
					$blog = array_shift($blogs);
				}

				if ($post === null) {
					$post = $wfdb->querySingleRec("select ID, post_title, post_type, post_date, post_content from {$table} where ID = %d", $postID);
					$type = $post['post_type'] ? $post['post_type'] : 'comment';
					$uctype = ucfirst($type);
					$postDate = $post['post_date'];
					$title = $post['post_title'];
					$contentMD5 = md5(wfUtils::ifnull($post['post_content']));
				}

				if ($result['badList'] == 'wordfence-dbl') {
					$shortMsg = sprintf(/* translators: 1. WordPress Post type. 2. URL. */ __('%1$s contains a suspected malware URL: %2$s', 'wordfence'), $uctype, $title);
					$longMsg = sprintf(
					/* translators: 1. WordPress post type. 2. URL. */
						__('This %1$s contains a URL that is currently listed on Wordfence\'s domain blocklist. The URL is: %2$s', 'wordfence'),
						esc_html($type),
						esc_html($result['URL'])
					);
				} else {
					//A list type that may be new and the plugin has not been upgraded yet.
					continue;
				}

				$this->status(2, 'info', sprintf(/* translators: Scan result description. */ __('Adding issue: %1$s', 'wordfence'), $shortMsg));
				if (is_multisite()) {
					switch_to_blog($blogID);
				}
				$ignoreP = 'postBadURL-' . $idString;
				$ignoreC = 'postBadURL-' . $idString . $contentMD5;
				$added = $this->addIssue('postBadURL', wfIssues::SEVERITY_HIGH, $ignoreP, $ignoreC, $shortMsg, $longMsg, array(
					'postID'       => $postID,
					'badURL'       => $result['URL'],
					'postTitle'    => $title,
					'type'         => $type,
					'uctype'       => $uctype,
					'permalink'    => get_permalink($postID),
					'editPostLink' => get_edit_post_link($postID),
					'postDate'     => $postDate,
					'isMultisite'  => $blog['isMultisite'],
					'domain'       => $blog['domain'],
					'path'         => $blog['path'],
					'blog_id'      => $blogID
				));
				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}
				if (is_multisite()) {
					restore_current_blog();
				}
			}
		}
		wfIssues::statusEnd($this->statusIDX['posts'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_CONTENT_SAFETY, $haveIssues);
		$this->scanQueue = '';
	}

	private function scan_comments_init() {
		$this->statusIDX['comments'] = wfIssues::statusStart(__('Scanning comments for URLs on a domain blocklist', 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_CONTENT_SAFETY);
		$this->scanData = array();
		$this->scanQueue = '';
		$this->hoover = new wordfenceURLHoover($this->apiKey, $this->wp_version);
		$blogsToScan = self::getBlogsToScan('comments');
		$wfdb = new wfDB();
		foreach ($blogsToScan as $blog) {
			$q1 = $wfdb->querySelect("select comment_ID from " . $blog['table'] . " where comment_approved=1 and not comment_type = 'order_note'");
			foreach ($q1 as $idRow) {
				$this->scanQueue .= pack('LL', $blog['blog_id'], $idRow['comment_ID']);
			}
		}
	}

	private function scan_comments_main() {
		global $wpdb;
		$wfdb = new wfDB();
		while (strlen($this->scanQueue) > 0) {
			$segment = substr($this->scanQueue, 0, 8);
			$this->scanQueue = substr($this->scanQueue, 8);
			$elem = unpack('Lblog/Lcomment', $segment);
			$queueSize = strlen($this->scanQueue) / 8;
			if ($queueSize > 0 && $queueSize % 1000 == 0) {
				wordfence::status(2, 'info', sprintf(/* translators: Number of comments left to scan. */ __("Scanning comments with %d left to scan.", 'wordfence'), $queueSize));
			}

			$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_COMMENTS);

			$blogID = $elem['blog'];
			$commentID = $elem['comment'];

			$table = wfDB::blogTable('comments', $blogID);

			$row = $wfdb->querySingleRec("select comment_ID, comment_date, comment_type, comment_author, comment_author_url, comment_content from {$table} where comment_ID=%d", $commentID);
			$found = $this->hoover->hoover($blogID . '-' . $row['comment_ID'], $row['comment_author_url'] . ' ' . $row['comment_author'] . ' ' . $row['comment_content'], wordfenceURLHoover::standardExcludedHosts());
			$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_URLS, $found);
			$this->forkIfNeeded();
		}
	}

	private function scan_comments_finish() {
		$wfdb = new wfDB();
		$hooverResults = $this->hoover->getBaddies();
		if ($this->hoover->errorMsg) {
			wfIssues::statusEndErr();
			throw new Exception($this->hoover->errorMsg);
		}
		$this->hoover->cleanup();
		$haveIssues = wfIssues::STATE_SECURE;
		foreach ($hooverResults as $idString => $hresults) {
			$arr = explode('-', $idString);
			$blogID = $arr[0];
			$commentID = $arr[1];
			$blog = null;
			$comment = null;
			foreach ($hresults as $result) {
				if ($result['badList'] != 'wordfence-dbl') {
					continue; //A list type that may be new and the plugin has not been upgraded yet.
				}

				if ($blog === null) {
					$blogs = self::getBlogsToScan('comments', $blogID);
					$blog = array_shift($blogs);
				}

				if ($comment === null) {
					$comment = $wfdb->querySingleRec("select comment_ID, comment_date, comment_type, comment_author, comment_author_url, comment_content from " . $blog['table'] . " where comment_ID=%d", $commentID);
					$type = $comment['comment_type'] ? $comment['comment_type'] : 'comment';
					$uctype = ucfirst($type);
					$author = $comment['comment_author'];
					$date = $comment['comment_date'];
					$contentMD5 = md5(wfUtils::ifnull($comment['comment_content'] . $comment['comment_author'] . $comment['comment_author_url']));
				}

				if ($result['badList'] == 'wordfence-dbl') {
					$shortMsg = sprintf(/* translators: URL. */ __("%s contains a suspected malware URL.", 'wordfence'), $uctype);
					$longMsg = sprintf(
					/* translators: 1. WordPress post type. 2. URL. */
						__('This %1$s contains a URL that is currently listed on Wordfence\'s domain blocklist. The URL is: %2$s', 'wordfence'),
						esc_html($type),
						esc_html($result['URL'])
					);
				}

				if (is_multisite()) {
					switch_to_blog($blogID);
				}

				$ignoreP = 'commentBadURL-' . $idString;
				$ignoreC = 'commentBadURL-' . $idString . '-' . $contentMD5;
				$added = $this->addIssue('commentBadURL', wfIssues::SEVERITY_LOW, $ignoreP, $ignoreC, $shortMsg, $longMsg, array(
					'commentID'       => $commentID,
					'badURL'          => $result['URL'],
					'author'          => $author,
					'type'            => $type,
					'uctype'          => $uctype,
					'editCommentLink' => get_edit_comment_link($commentID),
					'commentDate'     => $date,
					'isMultisite'     => $blog['isMultisite'],
					'domain'          => $blog['domain'],
					'path'            => $blog['path'],
					'blog_id'         => $blogID
				));
				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}

				if (is_multisite()) {
					restore_current_blog();
				}
			}
		}
		wfIssues::statusEnd($this->statusIDX['comments'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_CONTENT_SAFETY, $haveIssues);
		$this->scanQueue = '';
	}

	public function isBadComment($author, $email, $url, $IP, $content) {
		$content = $author . ' ' . $email . ' ' . $url . ' ' . $IP . ' ' . $content;
		$cDesc = '';
		if ($author) {
			$cDesc = sprintf(/* translators: WordPress username. */ __("Author: %s", 'wordfence'), $author) . ' ';
		}
		if ($email) {
			$cDesc .= sprintf(/* translators: Email address. */ __("Email: %s", 'wordfence'), $email) . ' ';
		}
		$cDesc .= sprintf(/* translators: IP address. */ __("Source IP: %s", 'wordfence'), $IP) . ' ';
		$this->status(2, 'info', sprintf(/* translators: Comment description. */ __("Scanning comment with %s", 'wordfence'), $cDesc));

		$h = new wordfenceURLHoover($this->apiKey, $this->wp_version);
		$h->hoover(1, $content, wordfenceURLHoover::standardExcludedHosts());
		$hooverResults = $h->getBaddies();
		if ($h->errorMsg) {
			return false;
		}
		$h->cleanup();
		if (sizeof($hooverResults) > 0 && isset($hooverResults[1])) {
			$hresults = $hooverResults[1];
			foreach ($hresults as $result) {
				if ($result['badList'] == 'goog-malware-shavar') {
					$this->status(2, 'info', sprintf(/* translators: Comment description. */ __("Marking comment as spam for containing a malware URL. Comment has %s", 'wordfence'), $cDesc));
					return true;
				} else if ($result['badList'] == 'googpub-phish-shavar') {
					$this->status(2, 'info', sprintf(/* translators: Comment description. */ __("Marking comment as spam for containing a phishing URL. Comment has %s", 'wordfence'), $cDesc));
					return true;
				} else if ($result['badList'] == 'wordfence-dbl') {
					$this->status(2, 'info', sprintf(/* translators: Comment description. */ __("Marking comment as spam for containing a malware URL. Comment has %s", 'wordfence'), $cDesc));
				} else {
					//A list type that may be new and the plugin has not been upgraded yet.
					continue;
				}
			}
		}
		$this->status(2, 'info', sprintf(/* translators: Comment description. */ __("Scanned comment with %s", 'wordfence'), $cDesc));
		return false;
	}

	public static function getBlogsToScan($table, $withID = null) {
		$wfdb = new wfDB();
		global $wpdb;
		$blogsToScan = array();
		if (is_multisite()) {
			if ($withID === null) {
				$q1 = $wfdb->querySelect("select blog_id, domain, path from {$wpdb->blogs} where deleted=0 order by blog_id asc");
			} else {
				$q1 = $wfdb->querySelect("select blog_id, domain, path from {$wpdb->blogs} where deleted=0 and blog_id = %d", $withID);
			}

			foreach ($q1 as $row) {
				$row['isMultisite'] = true;
				$row['table'] = wfDB::blogTable($table, $row['blog_id']);
				$blogsToScan[] = $row;
			}
		} else {
			$blogsToScan[] = array(
				'isMultisite' => false,
				'table'       => wfDB::networkTable($table),
				'blog_id'     => '1',
				'domain'      => '',
				'path'        => '',
			);
		}
		return $blogsToScan;
	}

	private function highestCap($caps) {
		foreach (array('administrator', 'editor', 'author', 'contributor', 'subscriber') as $cap) {
			if (empty($caps[$cap]) === false && $caps[$cap]) {
				return $cap;
			}
		}
		return '';
	}

	private function isEditor($caps) {
		foreach (array('contributor', 'author', 'editor', 'administrator') as $cap) {
			if (empty($caps[$cap]) === false && $caps[$cap]) {
				return true;
			}
		}
		return false;
	}

	private function scan_passwds_init() {
		$this->statusIDX['passwds'] = wfIssues::statusStart(__('Scanning for weak passwords', 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_PASSWORD_STRENGTH);
		
		global $wpdb;
		$counter = 0;
		$query = "select ID from " . $wpdb->users;
		$dbh = $wpdb->dbh;
		$useMySQLi = wfUtils::useMySQLi();
		if ($useMySQLi) { //If direct-access MySQLi is available, we use it to minimize the memory footprint instead of letting it fetch everything into an array first
			$result = $dbh->query($query);
			if (!is_object($result)) {
				return array(
					'errorMsg' => __("We were unable to generate the user list for your password check.", 'wordfence'),
				);
			}
			while ($rec = $result->fetch_assoc()) {
				$this->userPasswdQueue .= pack('N', $rec['ID']);
				$counter++;
			}
		} else {
			$res1 = $wpdb->get_results($query, ARRAY_A);
			foreach ($res1 as $rec) {
				$this->userPasswdQueue .= pack('N', $rec['ID']);
				$counter++;
			}
		}
		wordfence::status(2, 'info', sprintf(
		/* translators: Number of users. */
			_n("Starting password strength check on %d user.", "Starting password strength check on %d users.", $counter, 'wordfence'), $counter));
	}

	private function scan_passwds_main() {
		while (strlen($this->userPasswdQueue) > 3) {
			$usersLeft = strlen($this->userPasswdQueue) / 4; //4 byte ints
			if ($usersLeft % 100 == 0) {
				wordfence::status(2, 'info', sprintf(
				/* translators: Number of users. */
					_n(
						"Total of %d users left to process in password strength check.",
						"Total of %d users left to process in password strength check.",
						$usersLeft,
						'wordfence'),
					$usersLeft
				));
			}
			$userID = unpack('N', substr($this->userPasswdQueue, 0, 4));
			$userID = $userID[1];
			$this->userPasswdQueue = substr($this->userPasswdQueue, 4);
			$state = $this->scanUserPassword($userID);
			$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_USERS);
			if ($state == wfIssues::STATE_PROBLEM) {
				$this->passwdHasIssues = wfIssues::STATE_PROBLEM;
			} else if ($this->passwdHasIssues != wfIssues::STATE_PROBLEM && $state == wfIssues::STATE_IGNORED) {
				$this->passwdHasIssues = wfIssues::STATE_IGNORED;
			}

			$this->forkIfNeeded();
		}
	}

	private function scan_passwds_finish() {
		wfIssues::statusEnd($this->statusIDX['passwds'], $this->passwdHasIssues);
		$this->scanController->completeStage(wfScanner::STAGE_PASSWORD_STRENGTH, $this->passwdHasIssues);
	}

	public function scanUserPassword($userID) {
		$haveIssues = wfIssues::STATE_SECURE;
		$suspended = wp_suspend_cache_addition();
		wp_suspend_cache_addition(true);
		$userDat = get_userdata($userID);
		if ($userDat === false) {
			wordfence::status(2, 'error', sprintf(/* translators: WordPress user ID. */ __("Could not get username for user with ID %d when checking password strength.", 'wordfence'), $userID));
			return false;
		}
		//user_login
		$this->status(4, 'info', sprintf(
			/* translators: 1. WordPress username. 2. WordPress user ID. */
				__('Checking password strength of user \'%1$s\' with ID %2$d', 'wordfence'),
				$userDat->user_login,
				$userID
			) . (function_exists('memory_get_usage') ? " (Mem:" . sprintf('%.1f', memory_get_usage(true) / (1024 * 1024)) . "M)" : ""));
		$capabilities = $userDat->get_role_caps();
		$highCap = $this->highestCap($capabilities);
		$isAdmin = $highCap === "administrator";
		// bcrypt takes significantly longer than md5 to check so it's impractical to check the extended list
		$usesBcrypt = strpos($userDat->user_pass, '$wp') === 0;
		$emailLocalPart = explode("@", $userDat->user_email, 2)[0];
		$words = [
			$userDat->user_login => true,
			$userDat->user_email => true,
			$emailLocalPart => true
		];
		if ($usesBcrypt && !$isAdmin) {
			$this->status(10, 'info', sprintf(/* translators: WordPress username. */ __('Skipping password scan for non-admin user %s with bcrypt password', 'wordfence'), $userDat->user_login));
			// When using bcrypt, only admins are checked
			return $haveIssues;
		}
		if ($isAdmin || $this->isEditor($capabilities)) {
			wfCommonPasswords::addToKeyedList($words, !$usesBcrypt);
			$shortMsg = sprintf(
			/* translators: 1. WordPress username. 2. WordPress capability. */
				__('User "%1$s" with "%2$s" access has an easy password.', 'wordfence'),
				$userDat->user_login,
				$highCap
			);
			$longMsg = sprintf(
			/* translators: WordPress capability. */
				__("A user with the a role of '%s' has a password that is easy to guess. Please change this password yourself or ask the user to change it.", 'wordfence'),
				esc_html($highCap)
			);
			$level = wfIssues::SEVERITY_CRITICAL;
		}
		else {
			$shortMsg = sprintf(
			/* translators: WordPress username. */
				__("User \"%s\" with 'subscriber' access has a very easy password.", 'wordfence'), $userDat->user_login);
			$longMsg = __("A user with 'subscriber' access has a password that is very easy to guess. Please either change it or ask the user to change their password.", 'wordfence');
			$level = wfIssues::SEVERITY_HIGH;
		}
		foreach (array_keys($words) as $word) {
			if (wp_check_password($word, $userDat->user_pass, $userDat->ID)) {
				$this->status(2, 'info', sprintf(/* translators: Scan result description. */ __('Adding issue %s', 'wordfence'), $shortMsg));
				$added = $this->addIssue('easyPassword', $level, $userDat->ID, $userDat->ID . '-' . $userDat->user_pass, $shortMsg, $longMsg, array(
					'ID'           => $userDat->ID,
					'user_login'   => $userDat->user_login,
					'user_email'   => $userDat->user_email,
					'first_name'   => $userDat->first_name,
					'last_name'    => $userDat->last_name,
					'editUserLink' => wfUtils::editUserLink($userDat->ID)
				));
				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_SECURE && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}
				break;
			}
		}
		$this->status(4, 'info', sprintf(/* translators: WordPress username. */ __("Completed checking password strength of user '%s'", 'wordfence'), $userDat->user_login));
		wp_suspend_cache_addition($suspended);
		return $haveIssues;
	}
	
	private function scan_diskSpace() {
		$this->statusIDX['diskSpace'] = wfIssues::statusStart(__('Scanning to check available disk space', 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_SERVER_STATE);
		wfUtils::errorsOff();
		$total = wfUtils::funcEnabled('disk_total_space') ? disk_total_space('.') : false;
		$free = wfUtils::funcEnabled('disk_free_space') ? disk_free_space('.') : false; //Normally false if unreadable but can return 0 on some hosts even when there's space available
		wfUtils::errorsOn();
		if (!$total || !$free) {
			$this->status(2, 'info', __('Unable to access available disk space information', 'wordfence'));
			wfIssues::statusEnd($this->statusIDX['diskSpace'], wfIssues::STATE_SECURE);
			$this->scanController->completeStage(wfScanner::STAGE_SERVER_STATE, wfIssues::STATE_SECURE);
			return;
		}


		$this->status(2, 'info', sprintf(
		/* translators: 1. Number of bytes. 2. Number of bytes. */
			__('Total disk space: %1$s -- Free disk space: %2$s', 'wordfence'),
			wfUtils::formatBytes($total),
			wfUtils::formatBytes($free)
		));
		$freeMegs = round($free / 1024 / 1024, 2);
		$this->status(2, 'info', sprintf(/* translators: Number of bytes. */ __('The disk has %s MB available', 'wordfence'), $freeMegs));
		if ($freeMegs < 5) {
			$level = wfIssues::SEVERITY_CRITICAL;
		} else if ($freeMegs < 20) {
			$level = wfIssues::SEVERITY_HIGH;
		} else {
			wfIssues::statusEnd($this->statusIDX['diskSpace'], wfIssues::STATE_SECURE);
			$this->scanController->completeStage(wfScanner::STAGE_SERVER_STATE, wfIssues::STATE_SECURE);
			return;
		}
		$haveIssues = wfIssues::STATE_SECURE;
		$added = $this->addIssue('diskSpace',
			$level,
			'diskSpace',
			'diskSpace' . $level,
			sprintf(/* translators: Number of bytes. */ __('You have %s disk space remaining', 'wordfence'), wfUtils::formatBytes($free)),
			sprintf(/* translators: Number of bytes. */ __('You only have %s of your disk space remaining. Please free up disk space or your website may stop serving requests.', 'wordfence'), wfUtils::formatBytes($free)),
			array('spaceLeft' => wfUtils::formatBytes($free))
		);
		if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
			$haveIssues = wfIssues::STATE_PROBLEM;
		} else if ($haveIssues != wfIssues::STATE_SECURE && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
			$haveIssues = wfIssues::STATE_IGNORED;
		}
		wfIssues::statusEnd($this->statusIDX['diskSpace'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_SERVER_STATE, $haveIssues);
	}

	private function scan_wafStatus() {
		$this->statusIDX['wafStatus'] = wfIssues::statusStart(__('Checking Web Application Firewall status', 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_SERVER_STATE);

		$haveIssues = wfIssues::STATE_SECURE;
		$added = false;
		$firewall = new wfFirewall();
		if (wfConfig::get('waf_status') !== $firewall->firewallMode() && $firewall->firewallMode() == wfFirewall::FIREWALL_MODE_DISABLED) {
			$added = $this->addIssue('wafStatus',
				wfIssues::SEVERITY_CRITICAL,
				'wafStatus',
				'wafStatus' . $firewall->firewallMode(),
				__('Web Application Firewall is disabled', 'wordfence'),
				sprintf(/* translators: Support URL. */ __('Wordfence\'s Web Application Firewall has been unexpectedly disabled. If you see a notice at the top of the Wordfence admin pages that says "The Wordfence Web Application Firewall cannot run," click the link in that message to rebuild the configuration. If this does not work, you may need to fix file permissions. <a href="%s" target="_blank" rel="noopener noreferrer">More Details<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_WAF_DISABLED)),
				array('wafStatus' => $firewall->firewallMode(), 'wafStatusDisplay' => $firewall->displayText())
			);
		}

		if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
			$haveIssues = wfIssues::STATE_PROBLEM;
		} else if ($haveIssues != wfIssues::STATE_SECURE && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
			$haveIssues = wfIssues::STATE_IGNORED;
		}
		wfIssues::statusEnd($this->statusIDX['wafStatus'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_SERVER_STATE, $haveIssues);
	}

	private function scan_oldVersions_init() {
		$this->statusIDX['oldVersions'] = wfIssues::statusStart(__("Scanning for old themes, plugins and core files", 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_VULNERABILITY_SCAN);

		$this->updateCheck = new wfUpdateCheck();
		$this->updateCheck->checkCoreVulnerabilities();
		$this->updateCheck->checkPluginVulnerabilities();
		$this->updateCheck->checkThemeVulnerabilities();
		$this->updateCheck->checkAllUpdates(!$this->isFullScan());

		foreach ($this->updateCheck->getPluginSlugs() as $slug) {
			$this->pluginRepoStatus[$slug] = false;
		}

		//Strip plugins that have a pending update
		if (count($this->updateCheck->getPluginUpdates()) > 0) {
			foreach ($this->updateCheck->getPluginUpdates() as $plugin) {
				if (!empty($plugin['slug'])) {
					unset($this->pluginRepoStatus[$plugin['slug']]);
				}
			}
		}
	}

	private function scan_oldVersions_main() {
		if (!$this->isFullScan()) {
			return;
		}

		if (!function_exists('plugins_api')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
		}

		foreach ($this->pluginRepoStatus as $slug => $status) {
			if ($status === false) {
				try {
					$result = plugins_api('plugin_information', array(
						'slug'   => $slug,
						'fields' => array(
							'short_description' => false,
							'description'       => false,
							'sections'          => false,
							'tested'            => true,
							'requires'          => true,
							'rating'            => false,
							'ratings'           => false,
							'downloaded'        => false,
							'downloadlink'      => false,
							'last_updated'      => true,
							'added'             => false,
							'tags'              => false,
							'compatibility'     => true,
							'homepage'          => true,
							'versions'          => false,
							'donate_link'       => false,
							'reviews'           => false,
							'banners'           => false,
							'icons'             => false,
							'active_installs'   => false,
							'group'             => false,
							'contributors'      => false,
						),
					));
					unset($result->versions);
					unset($result->screenshots);
					$this->pluginRepoStatus[$slug] = $result;
				}
				catch (Exception $e) {
					error_log(sprintf('Caught exception while attempting to refresh update status for slug %s: %s', $slug, $e->getMessage()));
					$this->pluginRepoStatus[$slug] = false;
					wfConfig::set(wfUpdateCheck::LAST_UPDATE_CHECK_ERROR_KEY, sprintf('%s [%s]', $e->getMessage(), $slug), false);
					wfConfig::set(wfUpdateCheck::LAST_UPDATE_CHECK_ERROR_SLUG_KEY, $slug, false);
				}
				catch (Throwable $t) {
					error_log(sprintf('Caught error while attempting to refresh update status for slug %s: %s', $slug, $t->getMessage()));
					$this->pluginRepoStatus[$slug] = false;
					wfConfig::set(wfUpdateCheck::LAST_UPDATE_CHECK_ERROR_KEY, sprintf('%s [%s]', $t->getMessage(), $slug), false);
					wfConfig::set(wfUpdateCheck::LAST_UPDATE_CHECK_ERROR_SLUG_KEY, $slug, false);
				}

				$this->forkIfNeeded();
			}
		}
	}

	private static function get_cvss_metrics($record) {
		$vector = $record["cvssVector"];
		$metrics = [];
		if (!is_string($vector))
			return $metrics;
		$components = explode("/", $vector);
		foreach ($components as $component) {
			$pair = explode(":", $component, 2);
			if (count($pair) == 2) {
				$metrics[$pair[0]] = $pair[1];
			}
		}
		return $metrics;
	}

	private static function get_record_severity($record) {
		if (isset($record["vulnerable"]) && !$record["vulnerable"])
			return wfIssues::SEVERITY_MEDIUM;
		$metrics = self::get_cvss_metrics($record);
		// Vulnerabilities with high attack complexity or privileges required can be downgraded
		if ($metrics["AC"] == "H" || $metrics["PR"] == "H")
			return wfIssues::SEVERITY_HIGH;
		return wfIssues::SEVERITY_CRITICAL;
	}

	private function scan_oldVersions_finish() {
		$haveIssues = wfIssues::STATE_SECURE;

		if (!$this->isFullScan()) {
			$this->deleteNewIssues(array('wfUpgradeError', 'wfUpgrade', 'wfPluginUpgrade', 'wfThemeUpgrade'));
		}
		
		if ($lastError = wfConfig::get(wfUpdateCheck::LAST_UPDATE_CHECK_ERROR_KEY)) {
			$lastSlug = wfConfig::get(wfUpdateCheck::LAST_UPDATE_CHECK_ERROR_SLUG_KEY);
			$longMsg = sprintf(/* translators: error message. */ __("The update check performed during the scan encountered an error: %s", 'wordfence'), esc_html($lastError));
			if ($lastSlug === false) {
				$longMsg .= ' ' . __('Wordfence cannot detect if the installed plugins and themes are up to date. This might be caused by a PHP compatibility issue in one or more plugins/themes.', 'wordfence');
			}
			else {
				$longMsg .= ' ' . __('Wordfence cannot detect if this plugin/theme is up to date. This might be caused by a PHP compatibility issue in the plugin.', 'wordfence');
			}
			$longMsg .= ' ' . sprintf(
				/* translators: Support URL. */
					__('<a href="%s" target="_blank" rel="noopener noreferrer">Get more information.<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_UPDATE_CHECK_FAILED));
			
			$ignoreKey = ($lastSlug === false ? 'wfUpgradeErrorGeneral' : sprintf('wfUpgradeError-%s', $lastSlug));
			
			$added = $this->addIssue(
				'wfUpgradeError',
				wfIssues::SEVERITY_MEDIUM,
				$ignoreKey,
				$ignoreKey,
				($lastSlug === false ? __("Update Check Encountered Error", 'wordfence') : sprintf(/* translators: plugin/theme slug. */ __("Update Check Encountered Error on '%s'", 'wordfence'), $lastSlug)),
				$longMsg,
				array()
			);
			if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
				$haveIssues = wfIssues::STATE_PROBLEM;
			}
			else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
				$haveIssues = wfIssues::STATE_IGNORED;
			}
		}

		// WordPress core updates needed
		if ($this->updateCheck->needsCoreUpdate()) {
			$updateVersion = $this->updateCheck->getCoreUpdateVersion();
			$severity = wfIssues::SEVERITY_HIGH;
			$shortMsg = __("Your WordPress version is out of date", 'wordfence');
			$longMsg = sprintf(/* translators: Software version. */ __("WordPress version %s is now available. Please upgrade immediately to get the latest security updates from WordPress.", 'wordfence'), esc_html($updateVersion));
			
			$currentVulnerable = $this->updateCheck->isCoreVulnerable('current');
			$edgeVulnerable = $this->updateCheck->isCoreVulnerable('edge');
			if ($this->updateCheck->coreUpdatePatchAvailable()) { //Non-edge branch with available backported update
				$updateVersion = $this->updateCheck->getCoreUpdatePatchVersion();
				$patchVulnerable = $this->updateCheck->isCoreVulnerable('patch');
				if (!$currentVulnerable && !$patchVulnerable) { //Non-edge branch, neither the current version or patch version have a known vulnerability
					$severity = wfIssues::SEVERITY_MEDIUM;
					$longMsg = sprintf(/* translators: Software version. */ __("WordPress version %s is now available for your site's current branch. Please upgrade immediately to get the latest fixes and compatibility updates from WordPress.", 'wordfence'), esc_html($updateVersion));
				}
				else if ($currentVulnerable && !$patchVulnerable) { //Non-edge branch, current version is vulnerable but patch version is not
					$longMsg = sprintf(/* translators: Software version. */ __("WordPress version %s is now available for your site's current branch. Please upgrade immediately to get the latest security updates from WordPress.", 'wordfence'), esc_html($updateVersion));
					//keep existing $severity already set
				}
				else { //Non-edge branch, unpatched vulnerability -- shift recommendation from patch update to edge update
					$updateVersion = $this->updateCheck->getCoreUpdateVersion();
					//keep existing $severity and $longMsg already set
				}
			}
			else { //Edge branch or newest version of an older branch
				if (!$currentVulnerable && !$edgeVulnerable) { //Neither the current version or edge version have a known vulnerability
					if ($this->updateCheck->getCoreEarlierBranch()) { //Update available on the edge branch, but the older branch in current use is up-to-date for its patches
						$severity = wfIssues::SEVERITY_LOW;
					}
					else {
						$severity = wfIssues::SEVERITY_MEDIUM;
					}
					$longMsg = sprintf(/* translators: Software version. */ __("WordPress version %s is now available. Please upgrade immediately to get the latest fixes and compatibility updates from WordPress.", 'wordfence'), esc_html($updateVersion));
				}
				//else vulnerability fixed or unpatched vulnerability, keep the existing values already set
			}
			
			$longMsg .= ' <a href="' . wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_CORE_UPGRADE) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Learn more', 'wordfence') . '<span class="screen-reader-text"> (opens in new tab)</span></a>';
			
			if ($updateVersion) {
				$added = $this->addIssue(
					'wfUpgrade',
					$severity,
					'wfUpgrade' . $updateVersion,
					'wfUpgrade' . $updateVersion,
					$shortMsg,
					$longMsg,
					array(
						'currentVersion' => $this->wp_version,
						'newVersion'     => $updateVersion,
					)
				);
				
				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}
			}
		}

		$allPlugins = $this->updateCheck->getAllPlugins();

		if (wordfence::hasWordfenceAssistant($allPlugins)) {
			$key = "wfAssistantPresent";
			$added = $this->addIssue(
				$key,
				wfIssues::SEVERITY_LOW,
				$key,
				$key,
				__("Wordfence Assistant is currently installed and is not needed unless instructed by Wordfence support", "wordfence"),
				__("The Wordfence Assistant plugin is currently installed. It should only be retained during use and should be removed once it's no longer needed.", "wordfence"),
				[]
			);
		}

		// Plugin updates needed
		if (count($this->updateCheck->getPluginUpdates()) > 0) {
			foreach ($this->updateCheck->getPluginUpdates() as $plugin) {
				$severity = self::get_record_severity($plugin);
				$key = 'wfPluginUpgrade' . ' ' . $plugin['pluginFile'] . ' ' . $plugin['newVersion'] . ' ' . $plugin['Version'];
				$shortMsg = sprintf(
				/* translators: 1. Plugin name. 2. Software version. 3. Software version. */
					__('The Plugin "%1$s" needs an upgrade (%2$s -> %3$s).', 'wordfence'),
					empty($plugin['Name']) ? $plugin['pluginFile'] : $plugin['Name'],
					$plugin['Version'],
					$plugin['newVersion']
				);
				$added = $this->addIssue('wfPluginUpgrade', $severity, $key, $key, $shortMsg,
					sprintf(
						__("You need to upgrade \"%s\" to the newest version to ensure you have any security fixes the developer has released.", 'wordfence'),
						empty($plugin['Name']) ? $plugin['pluginFile'] : $plugin['Name']
					), $plugin);
				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}

				if (isset($plugin['slug'])) {
					unset($allPlugins[$plugin['slug']]);
				}
			}
		}

		// Theme updates needed
		if (count($this->updateCheck->getThemeUpdates()) > 0) {
			foreach ($this->updateCheck->getThemeUpdates() as $theme) {
				$severity = self::get_record_severity($theme);
				$key = 'wfThemeUpgrade' . ' ' . $theme['Name'] . ' ' . $theme['version'] . ' ' . $theme['newVersion'];
				$shortMsg = sprintf(
				/* translators: 1. Theme name. 2. Software version. 3. Software version. */
					__('The Theme "%1$s" needs an upgrade (%2$s -> %3$s).', 'wordfence'),
					$theme['Name'],
					$theme['version'],
					$theme['newVersion']
				);
				$added = $this->addIssue('wfThemeUpgrade', $severity, $key, $key, $shortMsg, sprintf(
				/* translators: Theme name. */
					__("You need to upgrade \"%s\" to the newest version to ensure you have any security fixes the developer has released.", 'wordfence'),
					esc_html($theme['Name'])
				), $theme);
				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}
			}
		}

		if ($this->isFullScan()) {
			//Abandoned plugins
			foreach ($this->pluginRepoStatus as $slug => $status) {
				if ($status !== false && !is_wp_error($status) && ((is_object($status) && property_exists($status, 'last_updated')) || (is_array($status) && array_key_exists('last_updated', $status)))) {
					$statusArray = (array) $status;
					$hasVersion = array_key_exists('version', $statusArray);
					if (!$hasVersion) {
						$statusArray['version'] = null;
						wordfence::status(3, 'error', sprintf(/* translators: slug */ __('Unable to determine version for plugin %s', 'wordfence'), $slug));
					}
					
					if (array_key_exists('last_updated', $statusArray) && 
						is_string($statusArray['last_updated']) && 
						($lastUpdateTimestamp = strtotime($statusArray['last_updated'])) && 
						(time() - $lastUpdateTimestamp) > 63072000 /* ~2 years */) {
						
						try {
							$statusArray['dateUpdated'] = wfUtils::formatLocalTime(get_option('date_format'), $lastUpdateTimestamp);
						}
						catch (Exception $e) { //DateMalformedStringException in PHP >= 8.3, Exception previously
							wordfence::status(3, 'error', sprintf(
							/* translators: 1. Plugin slug. 2. Malformed date string. */
								__('Encountered bad date string for plugin "%1$s" in abandoned plugin check: %2$s', 'wordfence'),
								$slug, 
								$statusArray['last_updated']));
							continue;
						}
						$severity = wfIssues::SEVERITY_MEDIUM;
						$statusArray['abandoned'] = true;
						$statusArray['vulnerable'] = false;
						$vulnerable = $hasVersion && $this->updateCheck->isPluginVulnerable($slug, $statusArray['version']);
						if ($vulnerable) {
							$severity = wfIssues::SEVERITY_CRITICAL;
							$statusArray['vulnerable'] = true;
							if (is_array($vulnerable) && isset($vulnerable['vulnerabilityLink'])) { $statusArray['vulnerabilityLink'] = $vulnerable['vulnerabilityLink']; }
							if (is_array($vulnerable) && isset($vulnerable['cvssScore'])) { $statusArray['cvssScore'] = $vulnerable['cvssScore']; }
							if (is_array($vulnerable) && isset($vulnerable['cvssVector'])) { $statusArray['cvssVector'] = $vulnerable['cvssVector']; }
						}

						if (isset($allPlugins[$slug]) && isset($allPlugins[$slug]['wpURL'])) {
							$statusArray['wpURL'] = $allPlugins[$slug]['wpURL'];
						}

						$key = "wfPluginAbandoned {$slug} {$statusArray['version']}";
						if (isset($statusArray['tested'])) {
							$shortMsg = sprintf(
							/* translators: 1. Plugin name. 2. Software version. 3. Software version.  */
								__('The Plugin "%1$s" appears to be abandoned (updated %2$s, tested to WP %3$s).', 'wordfence'),
								(empty($statusArray['name']) ? $slug : $statusArray['name']),
								$statusArray['dateUpdated'],
								$statusArray['tested']
							);
							$longMsg = sprintf(
							/* translators: 1. Plugin name. 2. Software version. */
								__('It was last updated %1$s ago and tested up to WordPress %2$s.', 'wordfence'),
								wfUtils::makeTimeAgo(time() - $lastUpdateTimestamp),
								esc_html($statusArray['tested'])
							);
						} else {
							$shortMsg = sprintf(
							/* translators: 1. Plugin name. 2. Software version. */
								__('The Plugin "%1$s" appears to be abandoned (updated %2$s).', 'wordfence'),
								(empty($statusArray['name']) ? $slug : $statusArray['name']),
								$statusArray['dateUpdated']
							);
							$longMsg = sprintf(
							/* translators: Time duration. */
								__('It was last updated %s ago.', 'wordfence'),
								wfUtils::makeTimeAgo(time() - $lastUpdateTimestamp)
							);
						}

						if ($statusArray['vulnerable']) {
							$longMsg .= ' ' . __('It has unpatched security issues and may have compatibility problems with the current version of WordPress.', 'wordfence');
						} else {
							$longMsg .= ' ' . __('It may have compatibility problems with the current version of WordPress or unknown security issues.', 'wordfence');
						}
						$longMsg .= ' ' . sprintf(
							/* translators: Support URL. */
								__('<a href="%s" target="_blank" rel="noopener noreferrer">Get more information.<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_PLUGIN_ABANDONED));
						$added = $this->addIssue('wfPluginAbandoned', $severity, $key, $key, $shortMsg, $longMsg, $statusArray);
						if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
							$haveIssues = wfIssues::STATE_PROBLEM;
						} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
							$haveIssues = wfIssues::STATE_IGNORED;
						}

						unset($allPlugins[$slug]);
					}
				} else if ($status !== false && is_wp_error($status) && isset($status->errors['plugins_api_failed'])) { //The plugin does not exist in the wp.org repo
					$knownFiles = $this->getKnownFilesLoader()->getKnownFiles();
					if (isset($knownFiles['status']) && is_array($knownFiles['status']) && isset($knownFiles['status']['plugins']) && is_array($knownFiles['status']['plugins'])) {
						$requestedPlugins = $this->getPlugins();
						foreach ($requestedPlugins as $key => $data) {
							if ($data['ShortDir'] == $slug && isset($knownFiles['status']['plugins'][$slug]) && $knownFiles['status']['plugins'][$slug] == 'r') { //It existed in the repo at some point and was removed
								$pluginFile = wfUtils::getPluginBaseDir() . $key;
								$pluginData = get_plugin_data($pluginFile);
								$pluginData['wpRemoved'] = true;
								$pluginData['vulnerable'] = false;
								$vulnerable = $this->updateCheck->isPluginVulnerable($slug, $pluginData['Version']);
								if ($vulnerable) {
									$pluginData['vulnerable'] = true;
									if (is_array($vulnerable) && isset($vulnerable['vulnerabilityLink'])) { $statusArray['vulnerabilityLink'] = $vulnerable['vulnerabilityLink']; }
									if (is_array($vulnerable) && isset($vulnerable['cvssScore'])) { $statusArray['cvssScore'] = $vulnerable['cvssScore']; }
									if (is_array($vulnerable) && isset($vulnerable['cvssVector'])) { $statusArray['cvssVector'] = $vulnerable['cvssVector']; }
								}

								$key = "wfPluginRemoved {$slug} {$pluginData['Version']}";
								$shortMsg = sprintf(
								/* translators: Plugin name. */
									__('The Plugin "%s" has been removed from wordpress.org but is still installed on your site.', 'wordfence'), (empty($pluginData['Name']) ? $slug : $pluginData['Name']));
								if ($pluginData['vulnerable']) {
									$longMsg = __('It has unpatched security issues and may have compatibility problems with the current version of WordPress.', 'wordfence');
								} else {
									$longMsg = __('Your site is still using this plugin, but it is not currently available on wordpress.org. Plugins can be removed from wordpress.org for various reasons. This can include benign issues like a plugin author discontinuing development or moving the plugin distribution to their own site, but some might also be due to security issues. In any case, future updates may or may not be available, so it is worth investigating the cause and deciding whether to temporarily or permanently replace or remove the plugin.', 'wordfence');
								}
								$longMsg .= ' ' . sprintf(
									/* translators: Support URL. */
										__('<a href="%s" target="_blank" rel="noopener noreferrer">Get more information.<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_PLUGIN_REMOVED));
								$added = $this->addIssue('wfPluginRemoved', wfIssues::SEVERITY_CRITICAL, $key, $key, $shortMsg, $longMsg, $pluginData);
								if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
									$haveIssues = wfIssues::STATE_PROBLEM;
								} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
									$haveIssues = wfIssues::STATE_IGNORED;
								}

								unset($allPlugins[$slug]);
							}
						}
					}
				}
			}

			//Handle plugins that either do not exist in the repo or do not have updates available
			foreach ($allPlugins as $slug => $plugin) {
				if ($plugin['vulnerable']) {
					$key = implode(' ', array('wfPluginVulnerable', $plugin['pluginFile'], $plugin['Version']));
					$shortMsg = sprintf(/* translators: plugin name */ __('The Plugin "%s" has a security vulnerability.', 'wordfence'), $plugin['Name']);
					$longMsg = sprintf(
						wp_kses(
						/* translators: 1. Plugin name; 2. Support URL */ __('To protect your site from this vulnerability, the safest option is to deactivate and completely remove "%1$s" until a patched version is available. <a href="%2$s" target="_blank" rel="noopener noreferrer">Get more information.<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'),
							array(
								'a' => array(
									'href' => array(),
									'target' => array(),
									'rel' => array(),
								),
								'span' => array(
									'class' => array()
								)
							)
						),
						$plugin['Name'],
						wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_PLUGIN_VULNERABLE)
					);
					if (is_array($plugin['vulnerable']) && isset($plugin['vulnerable']['vulnerabilityLink'])) { $statusArray['vulnerabilityLink'] = $plugin['vulnerable']['vulnerabilityLink']; }
					if (is_array($plugin['vulnerable']) && isset($plugin['vulnerable']['cvssScore'])) { $statusArray['cvssScore'] = $plugin['vulnerable']['cvssScore']; }
					if (is_array($plugin['vulnerable']) && isset($plugin['vulnerable']['cvssVector'])) { $statusArray['cvssVector'] = $plugin['vulnerable']['cvssVector']; }
					$plugin['updatedAvailable'] = false;
					$added = $this->addIssue('wfPluginVulnerable', wfIssues::SEVERITY_CRITICAL, $key, $key, $shortMsg, $longMsg, $plugin);
					if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) { $haveIssues = wfIssues::STATE_PROBLEM; }
					else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) { $haveIssues = wfIssues::STATE_IGNORED; }
					
					unset($allPlugins[$slug]);
				}
			}
		}

		$this->updateCheck = false;
		$this->pluginRepoStatus = array();

		wfIssues::statusEnd($this->statusIDX['oldVersions'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_VULNERABILITY_SCAN, $haveIssues);
	}

	public function scan_suspiciousAdminUsers() {
		$this->statusIDX['suspiciousAdminUsers'] = wfIssues::statusStart(__("Scanning for admin users not created through WordPress", 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_OPTIONS_AUDIT);
		$haveIssues = wfIssues::STATE_SECURE;

		$adminUsers = new wfAdminUserMonitor();
		if ($adminUsers->isEnabled()) {
			try {
				$response = $this->api->call('suspicious_admin_usernames');
				if (is_array($response) && isset($response['ok']) && wfUtils::truthyToBoolean($response['ok']) && !empty($response['patterns'])) {
					wfConfig::set_ser('suspiciousAdminUsernames', $response['patterns']);
				}
			} catch (Exception $e) {
				// Let the rest of the scan continue
			}

			$suspiciousAdmins = $adminUsers->checkNewAdmins();
			if (is_array($suspiciousAdmins)) {
				foreach ($suspiciousAdmins as $userID) {
					$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_USERS);
					$user = new WP_User($userID);
					$key = 'suspiciousAdminUsers-outsideWordPress-' . $userID;
					$added = $this->addIssue('suspiciousAdminUsers', wfIssues::SEVERITY_HIGH, $key, $key,
						sprintf(/* translators: WordPress username. */ __("An admin user with the username %s was created outside of WordPress.", 'wordfence'), $user->user_login),
						sprintf(/* translators: WordPress username. */ __("An admin user with the username %s was created outside of WordPress. It's possible a plugin could have created the account, but if you do not recognize the user, we suggest you remove it.", 'wordfence'), esc_html($user->user_login)),
						array(
							'userID' => $userID,
						));
					if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
						$haveIssues = wfIssues::STATE_PROBLEM;
					} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
						$haveIssues = wfIssues::STATE_IGNORED;
					}
				}
			}

			$admins = $adminUsers->getCurrentAdmins();
			/**
			 * @var WP_User $adminUser
			 */
			foreach ($admins as $userID => $adminUser) {
				$added = false;
				$key = 'suspiciousAdminUsers-username-' . $userID;

				// Check against user name list here.
				$suspiciousAdminUsernames = wfConfig::get_ser('suspiciousAdminUsernames');
				if (is_array($suspiciousAdminUsernames)) {
					foreach ($suspiciousAdminUsernames as $usernamePattern) {
						if (preg_match($usernamePattern, $adminUser->user_login)) {
							$added = $this->addIssue('suspiciousAdminUsers', wfIssues::SEVERITY_HIGH, $key, $key,
								sprintf(/* translators: WordPress username. */ __("An admin user with a suspicious username %s was found.", 'wordfence'), $adminUser->user_login),
								sprintf(/* translators: WordPress username. */ __("An admin user with a suspicious username %s was found. Administrators accounts with usernames similar to this are commonly seen created by hackers. It's possible a plugin could have created the account, but if you do not recognize the user, we suggest you remove it.", 'wordfence'), esc_html($adminUser->user_login)),
								array(
									'userID' => $userID,
								));
						}
					}
				}

				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}
			}
		}

		wfIssues::statusEnd($this->statusIDX['suspiciousAdminUsers'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_OPTIONS_AUDIT, $haveIssues);
	}

	public function scan_suspiciousOptions() {
		$this->statusIDX['suspiciousOptions'] = wfIssues::statusStart(__("Scanning for suspicious site options", 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_OPTIONS_AUDIT);
		$haveIssues = wfIssues::STATE_SECURE;

		$blogsToScan = self::getBlogsToScan('options');
		$wfdb = new wfDB();

		$this->hoover = new wordfenceURLHoover($this->apiKey, $this->wp_version);
		foreach ($blogsToScan as $blog) {
			$excludedHosts = array();
			$homeURL = get_home_url($blog['blog_id']);
			$host = parse_url($homeURL, PHP_URL_HOST);
			if ($host) {
				$excludedHosts[$host] = 1;
			}
			$siteURL = get_site_url($blog['blog_id']);
			$host = parse_url($siteURL, PHP_URL_HOST);
			if ($host) {
				$excludedHosts[$host] = 1;
			}
			$excludedHosts = array_keys($excludedHosts);

			//Newspaper Theme
			if (defined('TD_THEME_OPTIONS_NAME')) {
				$q = $wfdb->querySelect("SELECT option_name, option_value FROM " . $blog['table'] . " WHERE option_name REGEXP '^td_[0-9]+$' OR option_name = '%s'", TD_THEME_OPTIONS_NAME);
			} else {
				$q = $wfdb->querySelect("SELECT option_name, option_value FROM " . $blog['table'] . " WHERE option_name REGEXP '^td_[0-9]+$'");
			}
			foreach ($q as $row) {
				$found = $this->hoover->hoover($blog['blog_id'] . '-' . $row['option_name'], $row['option_value'], $excludedHosts);
				$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_URLS, $found);
			}
		}


		$this->status(2, 'info', __("Examining URLs found in the options we scanned for dangerous websites", 'wordfence'));
		$hooverResults = $this->hoover->getBaddies();
		$this->status(2, 'info', __("Done examining URLs", 'wordfence'));
		if ($this->hoover->errorMsg) {
			wfIssues::statusEndErr();
			throw new Exception($this->hoover->errorMsg);
		}
		$this->hoover->cleanup();
		foreach ($hooverResults as $idString => $hresults) {
			$arr = explode('-', $idString);
			$blogID = $arr[0];
			$optionKey = $arr[1];
			$blog = null;
			foreach ($hresults as $result) {
				if ($result['badList'] != 'goog-malware-shavar' && $result['badList'] != 'googpub-phish-shavar' && $result['badList'] != 'wordfence-dbl') {
					continue; //A list type that may be new and the plugin has not been upgraded yet.
				}

				if ($blog === null) {
					$blogs = self::getBlogsToScan('options', $blogID);
					$blog = array_shift($blogs);
				}

				if ($result['badList'] == 'wordfence-dbl') {
					$shortMsg = sprintf(/* translators: URL. */ __("Option contains a suspected malware URL: %s", 'wordfence'), $optionKey);
					$longMsg = sprintf(/* translators: URL. */ __("This option contains a URL that is currently listed on Wordfence's domain blocklist. It may indicate your site is infected with malware. The URL is: %s", 'wordfence'), esc_html($result['URL']));
				} else {
					//A list type that may be new and the plugin has not been upgraded yet.
					continue;
				}

				$longMsg .= ' - ' . sprintf(/* translators: Support URL. */ __('<a href="%s" target="_blank" rel="noopener noreferrer">Get more information.<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_OPTION_MALWARE_URL));

				$this->status(2, 'info', sprintf(/* translators: Scan result description. */ __("Adding issue: %s", 'wordfence'), $shortMsg));

				if (is_multisite()) {
					switch_to_blog($blogID);
				}

				$ignoreP = $idString;
				$ignoreC = $idString . md5(serialize(get_option($optionKey, '')));
				$added = $this->addIssue('optionBadURL', wfIssues::SEVERITY_HIGH, $ignoreP, $ignoreC, $shortMsg, $longMsg, array(
					'optionKey'   => $optionKey,
					'badURL'      => $result['URL'],
					'isMultisite' => $blog['isMultisite'],
					'domain'      => $blog['domain'],
					'path'        => $blog['path'],
					'blog_id'     => $blogID
				));
				if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
					$haveIssues = wfIssues::STATE_PROBLEM;
				} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
					$haveIssues = wfIssues::STATE_IGNORED;
				}
				if (is_multisite()) {
					restore_current_blog();
				}
			}
		}

		wfIssues::statusEnd($this->statusIDX['suspiciousOptions'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_OPTIONS_AUDIT, $haveIssues);
	}

	public function scan_geoipSupport() {
		$this->statusIDX['geoipSupport'] = wfIssues::statusStart(__("Checking for future GeoIP support", 'wordfence'));
		$this->scanController->startStage(wfScanner::STAGE_SERVER_STATE);
		$haveIssues = wfIssues::STATE_SECURE;

		if (version_compare(phpversion(), '5.4') < 0 && wfConfig::get('isPaid') && wfBlock::hasCountryBlock()) {
			$shortMsg = __('PHP Update Needed for Country Blocking', 'wordfence');
			$longMsg = sprintf(/* translators: Software version. */ __('The GeoIP database that is required for country blocking has been updated to a new format. This new format requires sites to run PHP 5.4 or newer, and this site is on PHP %s. To ensure country blocking continues functioning, please update PHP.', 'wordfence'), wfUtils::cleanPHPVersion());

			$longMsg .= ' ' . sprintf(/* translators: Support URL. */ __('<a href="%s" target="_blank" rel="noopener noreferrer">Get more information.<span class="screen-reader-text"> (opens in new tab)</span></a>', 'wordfence'), wfSupportController::esc_supportURL(wfSupportController::ITEM_SCAN_RESULT_GEOIP_UPDATE));

			$this->status(2, 'info', sprintf(/* translators: Scan result description. */ __("Adding issue: %s", 'wordfence'), $shortMsg));

			$ignoreP = 'geoIPPHPDiscontinuing';
			$ignoreC = $ignoreP;
			$added = $this->addIssue('geoipSupport', wfIssues::SEVERITY_MEDIUM, $ignoreP, $ignoreC, $shortMsg, $longMsg, array());
			if ($added == wfIssues::ISSUE_ADDED || $added == wfIssues::ISSUE_UPDATED) {
				$haveIssues = wfIssues::STATE_PROBLEM;
			} else if ($haveIssues != wfIssues::STATE_PROBLEM && ($added == wfIssues::ISSUE_IGNOREP || $added == wfIssues::ISSUE_IGNOREC)) {
				$haveIssues = wfIssues::STATE_IGNORED;
			}
		}

		wfIssues::statusEnd($this->statusIDX['geoipSupport'], $haveIssues);
		$this->scanController->completeStage(wfScanner::STAGE_SERVER_STATE, $haveIssues);
	}

	public function status($level, $type, $msg) {
		wordfence::status($level, $type, $msg);
	}

	public function addIssue($type, $severity, $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData, $alreadyHashed = false) {
		wfIssues::updateScanStillRunning();
		return $this->i->addIssue($type, $severity, $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData, $alreadyHashed);
	}

	public function addPendingIssue($type, $severity, $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData) {
		wfIssues::updateScanStillRunning();
		return $this->i->addPendingIssue($type, $severity, $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData);
	}

	public function getPendingIssueCount() {
		return $this->i->getPendingIssueCount();
	}

	public function getPendingIssues($offset = 0, $limit = 100) {
		return $this->i->getPendingIssues($offset, $limit);
	}

	public static function requestKill() {
		wfScanMonitor::endMonitoring();
		wfConfig::set('wfKillRequested', time(), wfConfig::DONT_AUTOLOAD);
	}

	public static function checkForKill() {
		$kill = wfConfig::get('wfKillRequested', 0);
		if ($kill && time() - $kill < 600) { //Kill lasts for 10 minutes
			wordfence::status(10, 'info', "SUM_KILLED:" . __('Previous scan was stopped successfully.', 'wordfence'));
			throw new Exception(__("Scan was stopped on administrator request.", 'wordfence'), wfScanEngine::SCAN_MANUALLY_KILLED);
		}
	}

	public static function startScan($isFork = false, $scanMode = false, $isResume = false) {
		if (!defined('DONOTCACHEDB')) {
			define('DONOTCACHEDB', true);
		}

		if ($scanMode === false) {
			$scanMode = wfScanner::shared()->scanType();
		}

		if (!$isFork) { //beginning of scan
			wfConfig::inc('totalScansRun');
			wfConfig::set('wfKillRequested', 0, wfConfig::DONT_AUTOLOAD);
			wordfence::status(4, 'info', __("Entering start scan routine", 'wordfence'));
			if (wfScanner::shared()->isRunning()) {
				return __("A scan is already running. Use the stop scan button if you would like to terminate the current scan.", 'wordfence');
			}
			wfConfig::set('currentCronKey', ''); //Ensure the cron key is cleared
			if (!$isResume)
				wfScanMonitor::handleScanStart($scanMode);
		}
		wfScanMonitor::logLastAttempt($isFork);
		$timeout = self::getMaxExecutionTime() - 2; //2 seconds shorter than max execution time which ensures that only 2 HTTP processes are ever occupied
		$testURL = admin_url('admin-ajax.php?action=wordfence_testAjax');
		$forceIpv4 = wfConfig::get('scan_force_ipv4_start');
		$interceptor = new wfCurlInterceptor($forceIpv4);
		if ($forceIpv4)
			$interceptor->setOption(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		if (!wfConfig::get('startScansRemotely', false)) {
			if ($isFork) {
				$testSuccessful = (bool) wfConfig::get('scanAjaxTestSuccessful');
				wordfence::status(4, 'info', sprintf(/* translators: test result */ __("Cached result for scan start test: %s", 'wordfence'), var_export($testSuccessful, true)));
			}
			else {
				try {
					$testResult = $interceptor->intercept(function () use ($testURL, $timeout) {
						return wp_remote_post($testURL, array(
							'timeout'   => $timeout,
							'blocking'  => true,
							'sslverify' => false,
							'headers'   => array()
						));
					});
				} catch (Exception $e) {
					//Fall through to the remote start test below
				}

				wordfence::status(4, 'info', sprintf(/* translators: Scan start test result data. */ __("Test result of scan start URL fetch: %s", 'wordfence'), var_export($testResult, true)));

				$testSuccessful = !is_wp_error($testResult) && (is_array($testResult) || $testResult instanceof ArrayAccess) && strstr($testResult['body'], 'WFSCANTESTOK') !== false;
				wfConfig::set('scanAjaxTestSuccessful', $testSuccessful);
			}
		}

		$cronKey = wfUtils::bigRandomHex();
		wfConfig::set('currentCronKey', time() . ',' . $cronKey);
		if ((!wfConfig::get('startScansRemotely', false)) && $testSuccessful) {
			//ajax requests can be sent by the server to itself
			$cronURL = self::_localStartURL($isFork, $scanMode, $cronKey);
			$headers = array('Referer' => false/*, 'Cookie' => 'XDEBUG_SESSION=1'*/);
			wordfence::status(4, 'info', sprintf(/* translators: WordPress admin panel URL. */ __("Starting cron with normal ajax at URL %s", 'wordfence'), $cronURL));

			try {
				wfConfig::set('scanStartAttempt', time());
				$response = $interceptor->intercept(function () use ($cronURL, $headers) {
					return wp_remote_get($cronURL, array(
						'timeout'   => 0.01,
						'blocking'  => false,
						'sslverify' => false,
						'headers'   => $headers
					));
				});
				if (wfCentral::isConnected()) {
					wfCentral::updateScanStatus();
				}
			} catch (Exception $e) {
				wfConfig::set('lastScanCompleted', $e->getMessage());
				wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_CALLBACK_TEST_FAILED);
				return false;
			}

			if (is_wp_error($response)) {
				$error_message = $response->get_error_message();
				if ($error_message) {
					$lastScanCompletedMessage = sprintf(/* translators: Error message. */ __("There was an error starting the scan: %s.", 'wordfence'), $error_message);
				} else {
					$lastScanCompletedMessage = __("There was an unknown error starting the scan.", 'wordfence');
				}

				wfConfig::set('lastScanCompleted', $lastScanCompletedMessage);
				wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_CALLBACK_TEST_FAILED);
			}

			wordfence::status(4, 'info', __("Scan process ended after forking.", 'wordfence'));
		} else {
			$cronURL = self::_remoteStartURL($isFork, $scanMode, $cronKey);
			$headers = array();
			wordfence::status(4, 'info', sprintf(/* translators: WordPress admin panel URL. */ __("Starting cron via proxy at URL %s", 'wordfence'), $cronURL));

			try {
				wfConfig::set('scanStartAttempt', time());
				$response = wp_remote_get($cronURL, array(
					'timeout'   => 0.01,
					'blocking'  => false,
					'sslverify' => false,
					'headers'   => $headers
				));
				if (wfCentral::isConnected()) {
					wfCentral::updateScanStatus();
				}
			} catch (Exception $e) {
				wfConfig::set('lastScanCompleted', $e->getMessage());
				wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_CALLBACK_TEST_FAILED);
				return false;
			}

			if (is_wp_error($response)) {
				$error_message = $response->get_error_message();
				if ($error_message) {
					$lastScanCompletedMessage = sprintf(/* translators: Error message. */ __("There was an error starting the scan: %s.", 'wordfence'), $error_message);
				} else {
					$lastScanCompletedMessage = __("There was an unknown error starting the scan.", 'wordfence');
				}
				wfConfig::set('lastScanCompleted', $lastScanCompletedMessage);
				wfConfig::set('lastScanFailureType', wfIssues::SCAN_FAILED_CALLBACK_TEST_FAILED);
			}

			wordfence::status(4, 'info', __("Scan process ended after forking.", 'wordfence'));
		}
		return false; //No error
	}

	public static function verifyStartSignature($signature, $isFork, $scanMode, $cronKey, $remote) {
		$url = self::_baseStartURL($isFork, $scanMode, $cronKey);
		if ($remote) {
			$url = self::_remoteStartURL($isFork, $scanMode, $cronKey);
			$url = remove_query_arg('signature', $url);
		}
		$test = self::_signStartURL($url);
		return hash_equals($signature, $test);
	}

	protected static function _baseStartURL($isFork, $scanMode, $cronKey) {
		$url = admin_url('admin-ajax.php');
		$url .= '?action=wordfence_doScan&isFork=' . ($isFork ? '1' : '0') . '&scanMode=' . urlencode($scanMode) . '&cronKey=' . urlencode($cronKey);
		return $url;
	}

	protected static function _localStartURL($isFork, $scanMode, $cronKey) {
		$url = self::_baseStartURL($isFork, $scanMode, $cronKey);
		return add_query_arg('signature', self::_signStartURL($url), $url);
	}

	protected static function _remoteStartURL($isFork, $scanMode, $cronKey) {
		$url = self::_baseStartURL($isFork, $scanMode, $cronKey);
		$url = preg_replace('/^https?:\/\//i', (wfAPI::SSLEnabled() ? WORDFENCE_API_URL_SEC : WORDFENCE_API_URL_NONSEC) . 'scanp/', $url);
		$url = add_query_arg('k', wfConfig::get('apiKey'), $url);
		$url = add_query_arg('ssl', wfUtils::isFullSSL() ? '1' : '0', $url);
		return add_query_arg('signature', self::_signStartURL($url), $url);
	}

	protected static function _signStartURL($url) {
		$payload = preg_replace('~^https?://[^/]+~i', '', $url);
		return wfCrypt::local_sign($payload);
	}

	public function processResponse($result) {
		return false;
	}

	public static function getMaxExecutionTime($staySilent = false) {
		$config = wfConfig::get('maxExecutionTime');
		if (!$staySilent) {
			wordfence::status(4, 'info', sprintf(/* translators: Time in seconds. */ __("Got value from wf config maxExecutionTime: %s", 'wordfence'), $config));
		}
		if (is_numeric($config) && $config >= WORDFENCE_SCAN_MIN_EXECUTION_TIME) {
			if (!$staySilent) {
				wordfence::status(4, 'info', sprintf(/* translators: Time in seconds. */ __("getMaxExecutionTime() returning config value: %s", 'wordfence'), $config));
			}
			return $config;
		}

		$ini = @ini_get('max_execution_time');
		if (!$staySilent) {
			wordfence::status(4, 'info', sprintf(/* translators: PHP ini value. */ __("Got max_execution_time value from ini: %s", 'wordfence'), $ini));
		}
		if (is_numeric($ini)) {
			$ini = (int) $ini;
			if ($ini >= WORDFENCE_SCAN_MIN_EXECUTION_TIME) {
				if ($ini > WORDFENCE_SCAN_MAX_INI_EXECUTION_TIME) {
					if (!$staySilent) {
						wordfence::status(4, 'info', sprintf(
						/* translators: 1. PHP ini setting. 2. Time in seconds. */
							__('ini value of %1$d is higher than value for WORDFENCE_SCAN_MAX_INI_EXECUTION_TIME (%2$d), reducing', 'wordfence'),
							$ini,
							WORDFENCE_SCAN_MAX_INI_EXECUTION_TIME
						));
					}
					$ini = WORDFENCE_SCAN_MAX_INI_EXECUTION_TIME;
				}
				
				$ini = floor($ini / 2);
				if (!$staySilent) {
					wordfence::status(4, 'info', sprintf(/* translators: PHP ini setting. */ __("getMaxExecutionTime() returning half ini value: %d", 'wordfence'), $ini));
				}
				return $ini;
			}
		}

		if (!$staySilent) {
			wordfence::status(4, 'info', __("getMaxExecutionTime() returning default of: 15", 'wordfence'));
		}
		return 15;
	}

	/**
	 * @return wfScanKnownFilesLoader
	 */
	public function getKnownFilesLoader() {
		if ($this->knownFilesLoader === null) {
			$this->knownFilesLoader = new wfScanKnownFilesLoader($this->api, $this->getPlugins(), $this->getThemes());
		}
		return $this->knownFilesLoader;
	}

	/**
	 * @return array
	 */
	public function getPlugins() {
		static $plugins = null;
		if ($plugins !== null) {
			return $plugins;
		}

		if (!function_exists('get_plugins')) {
			require_once(ABSPATH . '/wp-admin/includes/plugin.php');
		}
		$pluginData = get_plugins();
		$plugins = array();
		foreach ($pluginData as $key => $data) {
			if (preg_match('/^([^\/]+)\//', $key, $matches)) {
				$pluginDir = $matches[1];
				$pluginFullDir = "wp-content/plugins/" . $pluginDir;
				$plugins[$key] = array(
					'Name'     => $data['Name'],
					'Version'  => $data['Version'],
					'ShortDir' => $pluginDir,
					'FullDir'  => $pluginFullDir,
					'UpdateURI' => isset($data['UpdateURI']) ? $data['UpdateURI'] : '', //WP 5.8+
				);
			}
			if (!$this->pluginsCounted) {
				$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_PLUGINS);
			}
		}

		$this->pluginsCounted = true;
		return $plugins;
	}

	/**
	 * @return array
	 */
	public function getThemes() {
		static $themes = null;
		if ($themes !== null) {
			return $themes;
		}

		if (!function_exists('wp_get_themes')) {
			require_once(ABSPATH . '/wp-includes/theme.php');
		}
		$themeData = wp_get_themes();
		$themes = array();
		foreach ($themeData as $themeName => $themeVal) {
			if (preg_match('/\/([^\/]+)$/', $themeVal['Stylesheet Dir'], $matches)) {
				$shortDir = $matches[1]; //e.g. evo4cms
				$fullDir = "wp-content/themes/{$shortDir}"; //e.g. wp-content/themes/evo4cms
				$themes[$themeName] = array(
					'Name'     => $themeVal['Name'],
					'Version'  => $themeVal['Version'],
					'ShortDir' => $shortDir,
					'FullDir'  => $fullDir,
					'UpdateURI' => $themeVal->get('UpdateURI') === false ? '' : $themeVal->get('UpdateURI'), //WP 6.1+
				);
			}
			if (!$this->themesCounted) {
				$this->scanController->incrementSummaryItem(wfScanner::SUMMARY_SCANNED_THEMES);
			}
		}

		$this->themesCounted = true;
		return $themes;
	}

	public function recordMetric($type, $key, $value, $singular = true) {
		if (!isset($this->metrics[$type])) {
			$this->metrics[$type] = array();
		}

		if (!isset($this->metrics[$type][$key])) {
			$this->metrics[$type][$key] = array();
		}

		if ($singular) {
			$this->metrics[$type][$key] = $value;
		} else {
			$this->metrics[$type][$key][] = $value;
		}
	}

	/**
	 * Queries the is_safe_file endpoint. If provided an array, it does a bulk check and returns an array containing the
	 * hashes that were marked as safe. If provided a string, it returns a boolean to indicate the safeness of the file.
	 *
	 * @param string|array $shac
	 * @return array|bool
	 */
	public function isSafeFile($shac) {
		if (is_array($shac)) {
			$result = $this->api->call('is_safe_file', array(), array('multipleSHAC' => json_encode($shac)));
			if (isset($result['isSafe'])) {
				return $result['isSafe'];
			}
			return array();
		}
		$result = $this->api->call('is_safe_file', array(), array('shac' => strtoupper($shac)));
		return isset($result['isSafe']) && $result['isSafe'] == 1;
	}
}

class wfScanKnownFilesLoader {
	/**
	 * @var array
	 */
	private $plugins;

	/**
	 * @var array
	 */
	private $themes;

	/**
	 * @var array
	 */
	private $knownFiles = array();

	/**
	 * @var wfAPI
	 */
	private $api;


	/**
	 * @param wfAPI $api
	 * @param array $plugins
	 * @param array $themes
	 */
	public function __construct($api, $plugins = null, $themes = null) {
		$this->api = $api;
		$this->plugins = $plugins;
		$this->themes = $themes;
	}

	/**
	 * @return bool
	 */
	public function isLoaded() {
		return is_array($this->knownFiles) && count($this->knownFiles) > 0;
	}

	/**
	 * @param $file
	 * @return bool
	 * @throws wfScanKnownFilesException
	 */
	public function isKnownFile($file) {
		if (!$this->isLoaded()) {
			$this->fetchKnownFiles();
		}

		return isset($this->knownFiles['core'][$file]) ||
			isset($this->knownFiles['plugins'][$file]) ||
			isset($this->knownFiles['themes'][$file]);
	}

	/**
	 * @param $file
	 * @return bool
	 * @throws wfScanKnownFilesException
	 */
	public function isKnownCoreFile($file) {
		if (!$this->isLoaded()) {
			$this->fetchKnownFiles();
		}
		return isset($this->knownFiles['core'][$file]);
	}

	/**
	 * @param $file
	 * @return bool
	 * @throws wfScanKnownFilesException
	 */
	public function isKnownPluginFile($file) {
		if (!$this->isLoaded()) {
			$this->fetchKnownFiles();
		}
		return isset($this->knownFiles['plugins'][$file]);
	}

	/**
	 * @param $file
	 * @return bool
	 * @throws wfScanKnownFilesException
	 */
	public function isKnownThemeFile($file) {
		if (!$this->isLoaded()) {
			$this->fetchKnownFiles();
		}
		return isset($this->knownFiles['themes'][$file]);
	}

	/**
	 * @throws wfScanKnownFilesException
	 */
	public function fetchKnownFiles() {
		try {
			$dataArr = $this->api->binCall('get_known_files', json_encode(array(
				'plugins' => $this->plugins,
				'themes'  => $this->themes
			)));

			if ($dataArr['code'] != 200) {
				throw new wfScanKnownFilesException(sprintf(/* translators: 1. HTTP status code. */ __("Got error response from Wordfence servers: %s", 'wordfence'), $dataArr['code']), $dataArr['code']);
			}
			$this->knownFiles = @json_decode($dataArr['data'], true);
			if (!is_array($this->knownFiles)) {
				throw new wfScanKnownFilesException(__("Invalid response from Wordfence servers.", 'wordfence'));
			}
		} catch (Exception $e) {
			throw new wfScanKnownFilesException($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function getKnownPluginData($file) {
		if ($this->isKnownPluginFile($file)) {
			return $this->knownFiles['plugins'][$file];
		}
		return null;
	}

	public function getKnownThemeData($file) {
		if ($this->isKnownThemeFile($file)) {
			return $this->knownFiles['themes'][$file];
		}
		return null;
	}

	/**
	 * @return array
	 */
	public function getPlugins() {
		return $this->plugins;
	}

	/**
	 * @param array $plugins
	 */
	public function setPlugins($plugins) {
		$this->plugins = $plugins;
	}

	/**
	 * @return array
	 */
	public function getThemes() {
		return $this->themes;
	}

	/**
	 * @param array $themes
	 */
	public function setThemes($themes) {
		$this->themes = $themes;
	}

	/**
	 * @return array
	 * @throws wfScanKnownFilesException
	 */
	public function getKnownFiles() {
		if (!$this->isLoaded()) {
			$this->fetchKnownFiles();
		}
		return $this->knownFiles;
	}

	/**
	 * @param array $knownFiles
	 */
	public function setKnownFiles($knownFiles) {
		$this->knownFiles = $knownFiles;
	}

	/**
	 * @return wfAPI
	 */
	public function getAPI() {
		return $this->api;
	}

	/**
	 * @param wfAPI $api
	 */
	public function setAPI($api) {
		$this->api = $api;
	}
}

class wfScanKnownFilesException extends Exception {

}

class wfCommonBackupFileTest {
	const MATCH_EXACT = 'exact';
	const MATCH_REGEX = 'regex';

	/**
	 * @param string $path
	 * @param string $mode
	 * @param bool|string $matcher If $mode is MATCH_REGEX, this will be the regex pattern.
	 * @return wfCommonBackupFileTest
	 */
	public static function createFromRootPath($path, $mode = self::MATCH_EXACT, $matcher = false) {
		return new self(site_url($path), ABSPATH . $path, array(), $mode, $matcher);
	}

	/**
	 * Identical to createFromRootPath except it returns an entry for each file in the index that matches $name
	 *
	 * @param $name
	 * @param string $mode
	 * @param bool|string $matcher
	 * @return array
	 */
	public static function createAllForFile($file, $mode = self::MATCH_EXACT, $matcher = false) {
		global $wpdb;
		$escapedFile = esc_sql(preg_quote($file));
		$table_wfKnownFileList = wfDB::networkTable('wfKnownFileList');
		$files = $wpdb->get_col("SELECT path FROM {$table_wfKnownFileList} WHERE path REGEXP '(^|/){$escapedFile}$'");
		$tests = array();
		foreach ($files as $f) {
			$tests[] = new self(site_url($f), ABSPATH . $f, array(), $mode, $matcher);
		}

		return $tests;
	}

	private $url;
	private $path;
	/**
	 * @var array
	 */
	private $requestArgs;
	private $mode;
	private $matcher;
	private $response;


	/**
	 * @param string $url
	 * @param string $path
	 * @param array $requestArgs
	 */
	public function __construct($url, $path, $requestArgs = array(), $mode = self::MATCH_EXACT, $matcher = false) {
		$this->url = $url;
		$this->path = $path;
		$this->mode = $mode;
		$this->matcher = $matcher;
		$this->requestArgs = $requestArgs;
	}

	/**
	 * @return bool
	 */
	public function fileExists() {
		return file_exists($this->path);
	}

	/**
	 * @return bool
	 */
	public function isPubliclyAccessible() {
		$this->response = wp_remote_get($this->url, $this->requestArgs);
		if ((int) floor(((int) wp_remote_retrieve_response_code($this->response) / 100)) === 2) {
			$handle = @fopen($this->path, 'r');
			if ($handle) {
				$contents = fread($handle, 700);
				fclose($handle);
				$remoteContents = substr(wp_remote_retrieve_body($this->response), 0, 700);
				if ($this->mode == self::MATCH_REGEX) {
					return preg_match($this->matcher, $remoteContents);
				}
				//else MATCH_EXACT
				return $contents === $remoteContents;
			}
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @param string $url
	 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * @return array
	 */
	public function getRequestArgs() {
		return $this->requestArgs;
	}

	/**
	 * @param array $requestArgs
	 */
	public function setRequestArgs($requestArgs) {
		$this->requestArgs = $requestArgs;
	}

	/**
	 * @return mixed
	 */
	public function getResponse() {
		return $this->response;
	}
}

class wfPubliclyAccessibleFileTest extends wfCommonBackupFileTest {

}

class wfScanEngineDurationLimitException extends Exception {
}

class wfScanEngineCoreVersionChangeException extends Exception {
}

class wfScanEngineTestCallbackFailedException extends Exception {
}