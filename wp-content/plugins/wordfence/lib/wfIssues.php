<?php
require_once(dirname(__FILE__) . '/wfUtils.php');
class wfIssues {
	//Possible responses from `addIssue`
	const ISSUE_ADDED = 'a';
	const ISSUE_UPDATED = 'u';
	const ISSUE_DUPLICATE = 'd';
	const ISSUE_IGNOREP = 'ip';
	const ISSUE_IGNOREC = 'ic';
	
	//Possible status message states
	const STATE_NONE = 'n'; //Default state before running
	
	const STATE_SKIPPED = 's'; //The scan job was skipped because it didn't need to run
	const STATE_IGNORED = 'i'; //The scan job found an issue, but it matched an entry in the ignore list
	
	const STATE_PROBLEM = 'p'; //The scan job found an issue
	const STATE_SECURE = 'r'; //The scan job found no issues
	
	const STATE_FAILED = 'f'; //The scan job failed
	const STATE_SUCCESS = 'c'; //The scan job succeeded
	
	const STATE_PAIDONLY = 'x';
	
	//Possible scan failure types
	const SCAN_FAILED_GENERAL = 'general';
	const SCAN_FAILED_TIMEOUT = 'timeout';
	const SCAN_FAILED_DURATION_REACHED = 'duration';
	const SCAN_FAILED_VERSION_CHANGE = 'versionchange';
	const SCAN_FAILED_FORK_FAILED = 'forkfailed';
	const SCAN_FAILED_CALLBACK_TEST_FAILED = 'callbackfailed';
	const SCAN_FAILED_START_TIMEOUT = 'starttimeout';
	
	const SCAN_FAILED_API_SSL_UNAVAILABLE = 'sslunavailable';
	const SCAN_FAILED_API_CALL_FAILED = 'apifailed';
	const SCAN_FAILED_API_INVALID_RESPONSE = 'apiinvalid';
	const SCAN_FAILED_API_ERROR_RESPONSE = 'apierror';

	//Severity thresholds
	const SEVERITY_NONE = 0;
	const SEVERITY_LOW = 25;
	const SEVERITY_MEDIUM = 50;
	const SEVERITY_HIGH = 75;
	const SEVERITY_CRITICAL = 100;
	
	//Issue status states
	const STATUS_NEW = 'new';
	const STATUS_IGNOREC = 'ignoreC';
	const STATUS_IGNOREP = 'ignoreP';

	const SCAN_STATUS_UPDATE_INTERVAL = 10; //Seconds

	private $db = false;

	//Properties that are serialized on sleep:
	private $updateCalled = false;
	private $issuesTable = '';
	private $pendingIssuesTable = '';
	private $maxIssues = 0;
	private $newIssues = array();
	public $totalIssues = 0;
	public $totalIgnoredIssues = 0;
	private $totalIssuesBySeverity = array();

	public static $issueSeverities = array(
		'checkGSB' => wfIssues::SEVERITY_CRITICAL,
		'checkSpamIP' => wfIssues::SEVERITY_HIGH,
		'spamvertizeCheck' => wfIssues::SEVERITY_CRITICAL,
		'commentBadURL' => wfIssues::SEVERITY_LOW,
		'postBadTitle' => wfIssues::SEVERITY_HIGH,
		'postBadURL' => wfIssues::SEVERITY_HIGH,
		'file' => wfIssues::SEVERITY_CRITICAL,
		'timelimit' => wfIssues::SEVERITY_HIGH,
		'checkHowGetIPs' => wfIssues::SEVERITY_HIGH,
		'diskSpace' => wfIssues::SEVERITY_HIGH,
		'wafStatus' => wfIssues::SEVERITY_CRITICAL,
		'configReadable' => wfIssues::SEVERITY_CRITICAL,
		'wfPluginVulnerable' => wfIssues::SEVERITY_HIGH,
		'coreUnknown' => wfIssues::SEVERITY_HIGH,
		'easyPasswordWeak' => wfIssues::SEVERITY_HIGH,
		'knownfile' => wfIssues::SEVERITY_HIGH,
		'optionBadURL' => wfIssues::SEVERITY_HIGH,
		'publiclyAccessible' => wfIssues::SEVERITY_HIGH,
		'suspiciousAdminUsers' => wfIssues::SEVERITY_HIGH,
		'wfPluginAbandoned' => wfIssues::SEVERITY_MEDIUM,
		'wfPluginRemoved' => wfIssues::SEVERITY_CRITICAL,
		'wfPluginUpgrade' => wfIssues::SEVERITY_MEDIUM,
		'wfThemeUpgrade' => wfIssues::SEVERITY_MEDIUM,
		'wfUpgradeError' => wfIssues::SEVERITY_MEDIUM,
		'wfUpgrade' => wfIssues::SEVERITY_HIGH,
		'wpscan_directoryList' => wfIssues::SEVERITY_HIGH,
		'wpscan_fullPathDiscl' => wfIssues::SEVERITY_HIGH,
	);

	public static function validIssueTypes() {
		return array('checkHowGetIPs', 'checkSpamIP', 'commentBadURL', 'configReadable', 'coreUnknown', 'database', 'diskSpace', 'wafStatus', 'easyPassword', 'file', 'geoipSupport', 'knownfile', 'optionBadURL', 'postBadTitle', 'postBadURL', 'publiclyAccessible', 'spamvertizeCheck', 'suspiciousAdminUsers', 'timelimit', 'wfPluginAbandoned', 'wfPluginRemoved', 'wfPluginUpgrade', 'wfPluginVulnerable', 'wfThemeUpgrade', 'wfUpgradeError', 'wfUpgrade', 'wpscan_directoryList', 'wpscan_fullPathDiscl', 'skippedPaths', 'wfAssistantPresent');
	}
	
	public static function statusPrep(){
		wfConfig::set_ser('wfStatusStartMsgs', array());
		wordfence::status(10, 'info', "SUM_PREP:Preparing a new scan.");
		wfIssues::updateScanStillRunning();
	}
	
	public static function statusStart($message) {
		$statusStartMsgs = wfConfig::get_ser('wfStatusStartMsgs', array());
		$statusStartMsgs[] = $message;
		wfConfig::set_ser('wfStatusStartMsgs', $statusStartMsgs);
		wordfence::status(10, 'info', 'SUM_START:' . $message);
		wfIssues::updateScanStillRunning();
		return count($statusStartMsgs) - 1;
	}
	
	public static function statusEnd($index, $state) {
		$statusStartMsgs = wfConfig::get_ser('wfStatusStartMsgs', array());
		if ($state == self::STATE_SKIPPED) {
			wordfence::status(10, 'info', 'SUM_ENDSKIPPED:' . $statusStartMsgs[$index]);
		}
		else if ($state == self::STATE_IGNORED) {
			wordfence::status(10, 'info', 'SUM_ENDIGNORED:' . $statusStartMsgs[$index]);
		}
		else if ($state == self::STATE_PROBLEM) {
			wordfence::status(10, 'info', 'SUM_ENDBAD:' . $statusStartMsgs[$index]);
		}
		else if ($state == self::STATE_SECURE) {
			wordfence::status(10, 'info', 'SUM_ENDOK:' . $statusStartMsgs[$index]);
		}
		else if ($state == self::STATE_FAILED) {
			wordfence::status(10, 'info', 'SUM_ENDFAILED:' . $statusStartMsgs[$index]);
		}
		else if ($state == self::STATE_SUCCESS) {
			wordfence::status(10, 'info', 'SUM_ENDSUCCESS:' . $statusStartMsgs[$index]);
		}
		wfIssues::updateScanStillRunning();
		$statusStartMsgs[$index] = '';
		wfConfig::set_ser('wfStatusStartMsgs', $statusStartMsgs);
	}
	
	public static function statusEndErr() {
		$statusStartMsgs = wfConfig::get_ser('wfStatusStartMsgs', array());
		for ($i = 0; $i < count($statusStartMsgs); $i++) {
			if (empty($statusStartMsgs[$i]) === false) {
				wordfence::status(10, 'info', 'SUM_ENDERR:' . $statusStartMsgs[$i]);
				$statusStartMsgs[$i] = '';
			}
		}
		wfIssues::updateScanStillRunning();
	}
	
	public static function statusPaidOnly($message) {
		wordfence::status(10, 'info', "SUM_PAIDONLY:" . $message);
		wfIssues::updateScanStillRunning();
	}
	
	public static function statusDisabled($message) {
		wordfence::status(10, 'info', "SUM_DISABLED:" . $message);
		wfIssues::updateScanStillRunning();
	}
	
	public static function updateScanStillRunning($running = true) {
		static $lastUpdate = 0;
		if ($running) {
			$timestamp = time();
			if ($timestamp - $lastUpdate < self::SCAN_STATUS_UPDATE_INTERVAL)
				return;
			$lastUpdate = $timestamp;
		}
		else {
			$timestamp = 0;
		}
		wfConfig::set('wf_scanLastStatusTime', $timestamp);
	}
	
	/**
	 * Returns false if the scan has not been detected as failed. If it has, returns a constant corresponding to the reason.
	 * 
	 * @return bool|string
	 */
	public static function hasScanFailed() {
		if (wfScanner::shared()->isRunning()) {
			$threshold = WORDFENCE_SCAN_FAILURE_THRESHOLD;
			if (time() - wfConfig::get('wf_scanLastStatusTime', 0) > $threshold) {
				return self::SCAN_FAILED_TIMEOUT;
			}
		}
		
		$scanStartAttempt = wfConfig::get('scanStartAttempt', 0);
		if ($scanStartAttempt && time() - $scanStartAttempt > WORDFENCE_SCAN_START_FAILURE_THRESHOLD) {
			return self::SCAN_FAILED_START_TIMEOUT;
		}
		
		$recordedFailure = wfConfig::get('lastScanFailureType');
		switch ($recordedFailure) {
			case self::SCAN_FAILED_GENERAL:
			case self::SCAN_FAILED_DURATION_REACHED:
			case self::SCAN_FAILED_VERSION_CHANGE:
			case self::SCAN_FAILED_FORK_FAILED:
			case self::SCAN_FAILED_CALLBACK_TEST_FAILED:
			case self::SCAN_FAILED_API_SSL_UNAVAILABLE:
			case self::SCAN_FAILED_API_CALL_FAILED:
			case self::SCAN_FAILED_API_INVALID_RESPONSE:
			case self::SCAN_FAILED_API_ERROR_RESPONSE:
				return $recordedFailure;
		}
		
		return false;
	}
	
	/**
	 * Returns the timestamp of the last status update.
	 *
	 * @return int
	 */
	public static function lastScanStatusUpdate() {
		return wfConfig::get('wf_scanLastStatusTime', 0);
	}
	
	/**
	 * Returns the singleton wfIssues.
	 *
	 * @return wfIssues
	 */
	public static function shared() {
		static $_issues = null;
		if ($_issues === null) {
			$_issues = new wfIssues();
		}
		return $_issues;
	}
	
	public function __sleep(){ //Same order here as vars above
		return array('updateCalled', 'issuesTable', 'pendingIssuesTable', 'maxIssues', 'newIssues', 'totalIssues', 'totalIgnoredIssues', 'totalIssuesBySeverity');
	}
	public function __construct(){
		$this->issuesTable = wfDB::networkTable('wfIssues');
		$this->pendingIssuesTable = wfDB::networkTable('wfPendingIssues');
		$this->maxIssues = wfConfig::get('scan_maxIssues', 0);
	}
	public function __wakeup(){
		$this->db = new wfDB();
	}
	
	public function addIssue($type, $severity,  $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData, $alreadyHashed = false) {
		return $this->_addIssue('issue', $type, $severity, $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData, $alreadyHashed);
	}
	public function addPendingIssue($type, $severity,  $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData) {
		return $this->_addIssue('pending', $type, $severity, $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData);
	}
	
	/**
	 * Create a new issue
	 *
	 * @param string	$group The issue type (e.g., issue or pending
	 * @param string	$type
	 * @param int		$severity
	 * @param string	$ignoreP	string to compare against for permanent ignores
	 * @param string	$ignoreC	string to compare against for ignoring until something changes
	 * @param string	$shortMsg
	 * @param string	$longMsg
	 * @param array		$templateData
	 * @param bool		$alreadyHashed If true, don't re-hash $ignoreP and $ignoreC
	 * @return string	One of the ISSUE_ constants
	 */
	private function _addIssue($group, $type, $severity, $ignoreP, $ignoreC, $shortMsg, $longMsg, $templateData, $alreadyHashed = false) {
		if ($group == 'pending') {
			$table = $this->pendingIssuesTable;
		}
		else {
			$table = $this->issuesTable;
		}
		
		if (!$alreadyHashed) {
			$ignoreP = md5(wfUtils::ifnull($ignoreP));
			$ignoreC = md5(wfUtils::ifnull($ignoreC));
		}
		
		$results = $this->getDB()->querySelect("SELECT id, status, ignoreP, ignoreC FROM {$table} WHERE (ignoreP = '%s' OR ignoreC = '%s')", $ignoreP, $ignoreC);
		foreach ($results as $row) {
			if ($row['status'] == 'new' && ($row['ignoreC'] == $ignoreC || $row['ignoreP'] == $ignoreP)) {
				if ($type != 'file' && $type != 'database') { //Filter out duplicate new issues except for infected files because we want to see all infections even if file contents are identical
					return self::ISSUE_DUPLICATE;
				}
			}
			
			if ($row['status'] == 'ignoreP' && $row['ignoreP'] == $ignoreP) { $this->totalIgnoredIssues++; return self::ISSUE_IGNOREP; } //Always ignore
			else if ($row['status'] == 'ignoreC' && $row['ignoreC'] == $ignoreC) { $this->totalIgnoredIssues++; return self::ISSUE_IGNOREC; } //Unchanged, ignore
			else if ($row['status'] == 'ignoreC') {
				$updateID = $row['id']; //Re-use the existing issue row
				break;
			}
		}
		
		if ($group != 'pending') {
			if (!array_key_exists($severity, $this->totalIssuesBySeverity)) {
				$this->totalIssuesBySeverity[$severity] = 0;
			}
			$this->totalIssuesBySeverity[$severity]++;
			$this->totalIssues++;
			if (empty($this->maxIssues) || $this->totalIssues <= $this->maxIssues)
			{
				$this->newIssues[] = array(
					'type' => $type,
					'severity' => $severity,
					'ignoreP' => $ignoreP,
					'ignoreC' => $ignoreC,
					'shortMsg' => $shortMsg,
					'longMsg' => $longMsg,
					'tmplData' => $templateData
					);
			}
		}

		if (isset($updateID)) {
			if ($group !== 'pending' && wfCentral::isConnected()) {
				wfCentral::sendIssue(array(
					'id' => $updateID,
					'lastUpdated' => time(),
					'type' => $type,
					'severity' => $severity,
					'ignoreP' => $ignoreP,
					'ignoreC' => $ignoreC,
					'shortMsg' => $shortMsg,
					'longMsg' => $longMsg,
					'data' => $templateData,
				));
			}

			$this->getDB()->queryWrite(
				"UPDATE {$table} SET lastUpdated = UNIX_TIMESTAMP(), status = '%s', type = '%s', severity = %d, ignoreP = '%s', ignoreC = '%s', shortMsg = '%s', longMsg = '%s', data = '%s' WHERE id = %d",
				'new',
				$type,
				$severity,
				$ignoreP,
				$ignoreC,
				$shortMsg,
				$longMsg,
				serialize($templateData),
				$updateID);


			return self::ISSUE_UPDATED;
		}
		
		$this->getDB()->queryWrite("INSERT INTO {$table} (time, lastUpdated, status, type, severity, ignoreP, ignoreC, shortMsg, longMsg, data) VALUES (UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s')",
			'new',
			$type,
			$severity,
			$ignoreP,
			$ignoreC,
			$shortMsg,
			$longMsg,
			serialize($templateData));

		if ($group !== 'pending' && wfCentral::isConnected()) {
			global $wpdb;
			wfCentral::sendIssue(array(
				'id' => $wpdb->insert_id,
				'status' => 'new',
				'time' => time(),
				'lastUpdated' => time(),
				'type' => $type,
				'severity' => $severity,
				'ignoreP' => $ignoreP,
				'ignoreC' => $ignoreC,
				'shortMsg' => $shortMsg,
				'longMsg' => $longMsg,
				'data' => $templateData,
			));
		}

		return self::ISSUE_ADDED;
	}
	public function deleteIgnored(){
		if (wfCentral::isConnected()) {
			$result = $this->getDB()->querySelect("SELECT id from " . $this->issuesTable . " where status='ignoreP' or status='ignoreC'");
			$issues = array();
			foreach ($result as $row) {
				$issues[] = $row['id'];
			}
			wfCentral::deleteIssues($issues);
		}

		$this->getDB()->queryWrite("delete from " . $this->issuesTable . " where status='ignoreP' or status='ignoreC'");
	}
	public function deleteNew($types = null) {
		if (!is_array($types)) {
			if (wfCentral::isConnected()) {
				wfCentral::deleteNewIssues();
			}

			$this->getDB()->queryWrite("DELETE FROM {$this->issuesTable} WHERE status = 'new'");
		}
		else {
			if (wfCentral::isConnected()) {
				wfCentral::deleteIssueTypes($types, 'new');
			}

			$query = "DELETE FROM {$this->issuesTable} WHERE status = 'new' AND type IN (" . implode(',', array_fill(0, count($types), "'%s'")) . ")";
			array_unshift($types, $query);
			call_user_func_array(array($this->getDB(), 'queryWrite'), $types);
		}
	}
	public function ignoreAllNew(){
		if (wfCentral::isConnected()) {
			$issues = $this->getDB()->querySelect('SELECT * FROM ' . $this->issuesTable . ' WHERE status=\'new\'');
			if ($issues) {
				wfCentral::sendIssues($issues);
			}
		}

		$this->getDB()->queryWrite("update " . $this->issuesTable . " set status='ignoreC' where status='new'");
	}
	public function emailNewIssues($timeLimitReached = false, $scanController = false){
		$level = wfConfig::getAlertLevel();
		$emails = wfConfig::getAlertEmails();
		if (!count($emails)) {
			return;
		}
		
		$shortSiteURL = preg_replace('/^https?:\/\//i', '', site_url());
		$subject = "[Wordfence Alert] Problems found on $shortSiteURL";

		if(sizeof($emails) < 1){ return; }
		if($level < 1){ return; }
		$needsToAlert = false;
		foreach ($this->totalIssuesBySeverity as $issueSeverity => $totalIssuesBySeverity) {
			if ($issueSeverity >= $level && $totalIssuesBySeverity > 0) {
				$needsToAlert = true;
				break;
			}
		}
		if (!$needsToAlert) {
			return;
		}
		$emailedIssues = wfConfig::get_ser('emailedIssuesList', array());
		if(! is_array($emailedIssues)){
			$emailedIssues = array();
		}
		$overflowCount = $this->totalIssues - count($this->newIssues);
		$finalIssues = array();
		$previousIssues = array();
		foreach($this->newIssues as $newIssue){
			$alreadyEmailed = false;
			foreach($emailedIssues as $emailedIssue){
				if($newIssue['ignoreP'] == $emailedIssue['ignoreP'] || $newIssue['ignoreC'] == $emailedIssue['ignoreC']){
					$alreadyEmailed = true;
					$previousIssues[] = $newIssue;
					break;
				}
			}
			if(! $alreadyEmailed){
				$finalIssues[] = $newIssue;
			}
			else {
				$overflowCount--;
			}
		}
		if(sizeof($finalIssues) < 1){ return; }
		
		$this->newIssues = array();
		$this->totalIssues = 0;

		$totals = array();
		foreach($finalIssues as $i){
			$emailedIssues[] = array( 'ignoreC' => $i['ignoreC'], 'ignoreP' => $i['ignoreP'] );
			if (!array_key_exists($i['severity'], $totals)) {
				$totals[$i['severity']] = 0;
			}
			$totals[$i['severity']]++;
		}
		wfConfig::set_ser('emailedIssuesList', $emailedIssues, false, wfConfig::DONT_AUTOLOAD);
		$needsToAlert = false;
		foreach ($totals as $issueSeverity => $totalIssuesBySeverity) {
			if ($issueSeverity >= $level && $totalIssuesBySeverity > 0) {
				$needsToAlert = true;
				break;
			}
		}
		if (!$needsToAlert) {
			return;
		}

		$content = wfUtils::tmpl('email_newIssues.php', array(
			'isPaid' => wfConfig::get('isPaid'),
			'issues' => $finalIssues,
			'previousIssues' => $previousIssues,
			'totals' => $totals,
			'level' => $level,
			'issuesNotShown' => $overflowCount,
			'adminURL' => get_admin_url(),
			'timeLimitReached' => $timeLimitReached,
			'scanController' => ($scanController ? $scanController : wfScanner::shared()),
			));
		
		foreach ($emails as $email) {
			$uniqueContent = str_replace('<!-- ##UNSUBSCRIBE## -->', wp_kses(sprintf(/* translators: URL to the WordPress admin panel. */ __('No longer an administrator for this site? <a href="%s" target="_blank">Click here</a> to stop receiving security alerts.', 'wordfence'), wfUtils::getSiteBaseURL() . '?_wfsf=removeAlertEmail&jwt=' . wfUtils::generateJWT(array('email' => $email))), array('a'=>array('href'=>array(), 'target'=>array()))), $content);
			wp_mail($email, $subject, $uniqueContent, 'Content-type: text/html');
		}
	}
	public function clearEmailedStatus($issues) {
		if (empty($issues)) { return; }
		
		$emailed_issues = wfConfig::get_ser('emailedIssuesList', array());
		if (!is_array($emailed_issues)) { return; }
		
		$updated = array();
		foreach ($emailed_issues as $ei) {
			$cleared = false;
			foreach ($issues as $issue) {
				if ($issue['ignoreP'] == $ei['ignoreP'] || $issue['ignoreC'] == $ei['ignoreC']) {
					//Discard this one
					$cleared = true;
				}
			}
			if (!$cleared) {
				$updated[] = $ei;
			}
		}
		
		wfConfig::set_ser('emailedIssuesList', $updated, false, wfConfig::DONT_AUTOLOAD);
	}
	
	public function deleteIssue($id) {
		$this->deleteIssues(array($id));
	}
	
	public function deleteIssues($ids) {
		$this->clearEmailedStatus($this->getIssuesByIDs($ids));
		$idString = implode(',', array_map(function($id) { return intval($id); }, $ids));
		$this->getDB()->queryWrite("DELETE FROM `{$this->issuesTable}` WHERE `id` IN ({$idString})");
		if (wfCentral::isConnected()) {
			wfCentral::deleteIssues($ids);
		}
	}

	public function deleteUpdateIssues($type) {
		$issues = $this->getDB()->querySelect("SELECT id, status, ignoreP, ignoreC FROM {$this->issuesTable} WHERE status = 'new' AND type = '%s'", $type);
		$this->clearEmailedStatus($issues);
		
		$this->getDB()->queryWrite("DELETE FROM {$this->issuesTable} WHERE status = 'new' AND type = '%s'", $type);

		if (wfCentral::isConnected()) {
			wfCentral::deleteIssueTypes(array($type));
		}
	}

	public function deleteAllUpdateIssues() {
		$issues = $this->getDB()->querySelect("SELECT id, status, ignoreP, ignoreC FROM {$this->issuesTable} WHERE status = 'new' AND (type = 'wfUpgrade' OR type = 'wfUpgradeError' OR type = 'wfPluginUpgrade' OR type = 'wfThemeUpgrade')");
		$this->clearEmailedStatus($issues);
		
		$this->getDB()->queryWrite("DELETE FROM {$this->issuesTable} WHERE status = 'new' AND (type = 'wfUpgrade' OR type = 'wfUpgradeError' OR type = 'wfPluginUpgrade' OR type = 'wfThemeUpgrade')");

		if (wfCentral::isConnected()) {
			wfCentral::deleteIssueTypes(array('wfUpgrade', 'wfUpgradeError', 'wfPluginUpgrade', 'wfThemeUpgrade'));
		}
	}

	public function updateIssue($id, $status){ //ignoreC, ignoreP, delete or new
		if($status == 'delete'){
			if (wfCentral::isConnected()) {
				wfCentral::deleteIssue($id);
			}
			$this->clearEmailedStatus(array($this->getIssueByID($id)));
			$this->getDB()->queryWrite("delete from " . $this->issuesTable . " where id=%d", $id);
		} else if($status == 'ignoreC' || $status == 'ignoreP' || $status == 'new'){
			$this->getDB()->queryWrite("update " . $this->issuesTable . " set status='%s' where id=%d", $status, $id);

			if (wfCentral::isConnected()) {
				$issue = $this->getDB()->querySelect('SELECT * FROM ' . $this->issuesTable . ' where id=%d', $id);
				if ($issue) {
					wfCentral::sendIssues($issue);
				}
			}
		}
	}
	public function getIssueByID($id) {
		$rec = $this->getDB()->querySingleRec("select * from " . $this->issuesTable . " where id=%d", $id);
		$rec['data'] = unserialize($rec['data']);
		return $rec;
	}
	public function getIssuesByIDs($ids) {
		$result = array();
		$idString = implode(',', array_map(function($id) { return intval($id); }, $ids));
		$rows = $this->getDB()->querySelect("SELECT * FROM `{$this->issuesTable}` WHERE `id` IN ({$idString})");
		foreach ($rows as $r) {
			$result[] = array_merge($r, array('data' => unserialize($r['data'])));
		}
		return $result;
	}
	public function getIssueCounts() {
		global $wpdb;
		$counts = $wpdb->get_results('SELECT COUNT(*) AS c, status FROM ' . $this->issuesTable . ' WHERE status = "new" OR status = "ignoreP" OR status = "ignoreC" GROUP BY status', ARRAY_A);
		$result = array();
		foreach ($counts as $row) {
			$result[$row['status']] = (int) $row['c']; 
		}
		return $result;
	}
	public function getIssues($offset = 0, $limit = 100, $ignoredOffset = 0, $ignoredLimit = 100) {
		/** @var wpdb $wpdb */
		global $wpdb;
		
		$siteCleaningTypes = array('file', 'checkGSB', 'checkSpamIP', 'commentBadURL', 'knownfile', 'optionBadURL', 'postBadTitle', 'postBadURL', 'spamvertizeCheck', 'suspiciousAdminUsers');
		$sortTagging = 'CASE';
		foreach ($siteCleaningTypes as $index => $t) {
			$sortTagging .= ' WHEN type = \'' . esc_sql($t) . '\' THEN ' . ((int) $index);
		}
		$sortTagging .= ' ELSE 999 END';
		
		$ret = array(
			'new' => array(),
			'ignored' => array()
			);
		$userIni = ini_get('user_ini.filename');
		$q1 = $this->getDB()->querySelect("SELECT *, {$sortTagging} AS sortTag FROM " . $this->issuesTable . " WHERE status = 'new' ORDER BY severity DESC, sortTag ASC, type ASC, time DESC LIMIT %d,%d", $offset, $limit);
		$q2 = $this->getDB()->querySelect("SELECT *, {$sortTagging} AS sortTag FROM " . $this->issuesTable . " WHERE status = 'ignoreP' OR status = 'ignoreC' ORDER BY severity DESC, sortTag ASC, type ASC, time DESC LIMIT %d,%d", $ignoredOffset, $ignoredLimit);
		$q = array_merge($q1, $q2);
		foreach ($q as $i) {
			if ($i['status'] == 'new') {
				$ret['new'][] = $this->_hydrateIssue($i, count($ret['new']));
			}
			else if ($i['status'] == 'ignoreP' || $i['status'] == 'ignoreC') {
				$ret['ignored'][] = $this->_hydrateIssue($i, count($ret['ignored']));
			}
			else {
				error_log("Issue has bad status: " . $i['status']);
			}
		}
		return $ret; //array of lists of issues by status
	}
	public function getPendingIssues($offset = 0, $limit = 100){
		/** @var wpdb $wpdb */
		global $wpdb;
		$issues = $this->getDB()->querySelect("SELECT * FROM {$this->pendingIssuesTable} ORDER BY id ASC LIMIT %d,%d", $offset, $limit);
		foreach($issues as &$i){
			$i['data'] = unserialize($i['data']);
		}
		return $issues;
	}
	public function getFixableIssueCount() {
		global $wpdb;
		$issues = $this->getDB()->querySelect("SELECT * FROM {$this->issuesTable} WHERE data LIKE '%s:6:\"canFix\";b:1;%'");
		$count = 0;
		foreach ($issues as $i) {
			$i['data'] = unserialize($i['data']);
			if (isset($i['data']['canFix']) && $i['data']['canFix']) {
				$count++;
			}
		}
		return $count;
	}
	public function getDeleteableIssueCount() {
		global $wpdb;
		$issues = $this->getDB()->querySelect("SELECT * FROM {$this->issuesTable} WHERE data LIKE '%s:9:\"canDelete\";b:1;%'");
		$count = 0;
		foreach ($issues as $i) {
			$i['data'] = unserialize($i['data']);
			if (isset($i['data']['canDelete']) && $i['data']['canDelete']) {
				$count++;
			}
		}
		return $count;
	}
	public function getIssueCount() {
		return (int) $this->getDB()->querySingle("select COUNT(*) from " . $this->issuesTable . " WHERE status = 'new'");
	}
	public function getPendingIssueCount() {
		return (int) $this->getDB()->querySingle("select COUNT(*) from " . $this->pendingIssuesTable . " WHERE status = 'new'");
	}
	public function getLastIssueUpdateTimestamp() {
		return (int) $this->getDB()->querySingle("select MAX(lastUpdated) from " . $this->issuesTable);
	}
	public function reconcileUpgradeIssues($report = null, $useCachedValued = false) {
		if ($report === null) {
			$report = new wfActivityReport();
		}
		
		$updatesNeeded = $report->getUpdatesNeeded($useCachedValued);
		if ($updatesNeeded) {
			if (!$updatesNeeded['core']) {
				$this->deleteUpdateIssues('wfUpgrade');
			}
			
			$issueIDs = array();
			if ($updatesNeeded['plugins']) {
				$upgradeNames = array();
				foreach ($updatesNeeded['plugins'] as $p) {
					$name = $p['Name'];
					$upgradeNames[$name] = 1;
				}
				$upgradeIssues = $this->getDB()->querySelect("SELECT * FROM {$this->issuesTable} WHERE status = 'new' AND type = 'wfPluginUpgrade'");
				foreach ($upgradeIssues as $issue) {
					$data = unserialize($issue['data']);
					$name = $data['Name'];
					if (!isset($upgradeNames[$name])) { //Some plugins don't have a slug associated with them, so we anchor on the name
						$issueIDs[] = $issue['id'];
					}
				}
			}
			else {
				$this->deleteUpdateIssues('wfPluginUpgrade');
			}

			if ($updatesNeeded['themes']) {
				$upgradeNames = array();
				foreach ($updatesNeeded['themes'] as $t) {
					$name = $t['Name'];
					$upgradeNames[$name] = 1;
				}
				$upgradeIssues = $this->getDB()->querySelect("SELECT * FROM {$this->issuesTable} WHERE status = 'new' AND type = 'wfThemeUpgrade'");
				foreach ($upgradeIssues as $issue) {
					$data = unserialize($issue['data']);
					$name = $data['Name'];
					if (!isset($upgradeNames[$name])) { //Some themes don't have a slug associated with them, so we anchor on the name
						$issueIDs[] = $issue['id'];
					}
				}
			}
			else {
				$this->deleteUpdateIssues('wfThemeUpgrade');
			}
			
			if (!empty($issueIDs)) {
				$this->deleteIssues($issueIDs);
			}
		}
		else {
			$this->deleteAllUpdateIssues();
		}
		
		wfScanEngine::refreshScanNotification($this);
	}
	private function getDB(){
		if(! $this->db){
			$this->db = new wfDB();
		}
		return $this->db;
	}

	/**
	 * @return string
	 */
	public function getIssuesTable() {
		return $this->issuesTable;
	}

	private static function getRealFileTokenKey($realFile) {
		return 'wf-real-file-' . base64_encode($realFile);
	}

	private static function generateRealFileToken($realFile) {
		$key = self::getRealFileTokenKey($realFile);
		return wp_create_nonce($key);
	}

	public static function verifyRealFileToken($token, $realFile) {
		$key = self::getRealFileTokenKey($realFile);
		return wp_verify_nonce($token, $key);
	}
	
	/**
	 * Modifies the stored issue data to update any values that may have diverged from the initial state like edit links,
	 * file existence, temporary tokens, etc. It also normalizes values to their correct type instead of the DB
	 * representation.
	 *
	 * @param array $issue The issue data to hydrate
	 * @param int $index The index of the issue in the list to be returned
	 * @return array
	 */
	private function _hydrateIssue($issue, $index) {
		global $wpdb;
		
		$issue['id'] = (int) $issue['id'];
		$issue['time'] = (int) $issue['time'];
		$issue['lastUpdated'] = (int) $issue['lastUpdated'];
		$issue['severity'] = (int) $issue['severity'];
		$issue['sortTag'] = (int) $issue['sortTag'];
		$issue['data'] = unserialize($issue['data']);
		$issue['timeAgo'] = wfUtils::makeTimeAgo(time() - $issue['time']);
		$issue['displayTime'] = wfUtils::formatLocalTime(get_option('date_format') . ' ' . get_option('time_format'), $issue['time']);
		$issue['longMsg'] = wp_kses($issue['longMsg'], 'post');
		$issue['issueIDX'] = $index;
		if (isset($issue['data']['cType'])) {
			$issue['data']['ucType'] = ucwords($issue['data']['cType']);
		}
		
		$userIni = ini_get('user_ini.filename');
		if ($issue['type'] == 'file' || $issue['type'] == 'knownfile') {
			if (array_key_exists('realFile', $issue['data'])) {
				$localFile = $issue['data']['realFile'];
				$issue['data']['realFileToken'] = self::generateRealFileToken($localFile);
			}
			else {
				$localFile = $issue['data']['file'];
				if ($localFile != '.htaccess' && $localFile != $userIni) {
					$localFile = ABSPATH . '/' . preg_replace('/^[\.\/]+/', '', $localFile);
				}
				else {
					$localFile = ABSPATH . '/' . $localFile;
				}
			}
			
			$issue['data']['fileExists'] = '';
			if (file_exists($localFile)) {
				$issue['data']['fileExists'] = true;
			}
		}
		else if ($issue['type'] == 'database') {
			$issue['data']['optionExists'] = false;
			if (!empty($issue['data']['site_id'])) {
				$table_options = wfDB::blogTable('options', $issue['data']['site_id']);
				$issue['data']['optionExists'] = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$table_options} WHERE option_name = %s", $issue['data']['option_name'])) > 0;
			}
		}
		else if ($issue['type'] == 'postBadURL' || $issue['type'] == 'postBadTitle') {
			$postID = $issue['data']['postID'];
			$issue['data']['permalink'] = get_permalink($postID);
			$issue['data']['editPostLink'] = get_edit_post_link($postID, 'raw');
		}
		else if ($issue['type'] == 'commentBadURL') {
			$commentID = $issue['data']['commentID'];
			$comment = get_comment($commentID); //Not using `get_edit_comment_link` directly because it wants to HTML-encode the URL rather than being the real URL, no $context parameter like edit posts has
			
			if ($comment && current_user_can('edit_comment', $comment->comment_ID)) {
				$issue['data']['editCommentLink'] = admin_url( 'comment.php?action=editcomment&c=' ) . $comment->comment_ID;
			}
		}
		
		return $issue;
	}
}