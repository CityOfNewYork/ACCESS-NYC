<?php

class wfScanMonitor {

	const CRON_INTERVAL_NAME = 'wf_scan_monitor_interval';
	const CRON_INTERVAL_AMOUNT = 60; //Seconds
	const CRON_HOOK = 'wf_scan_monitor';

	const CONFIG_LAST_ATTEMPT = 'scanMonitorLastAttempt';
	const CONFIG_LAST_ATTEMPT_WAS_FORK = 'scanMonitorLastAttemptWasFork';
	const CONFIG_LAST_ATTEMPT_MODE = 'scanMonitorLastAttemptMode';
	const CONFIG_LAST_SUCCESS = 'scanMonitorLastSuccess';
	const CONFIG_MAX_RESUME_ATTEMPTS = 'scan_max_resume_attempts';
	const CONFIG_REMAINING_RESUME_ATTEMPTS = 'scanMonitorRemainingResumeAttempts';

	const DEFAULT_RESUME_ATTEMPTS = 2;
	const MAX_RESUME_ATTEMPTS = 5;
	const SCAN_START_TIMEOUT = 30; //Seconds

	public static function beginMonitoring() {
		if (wp_next_scheduled(self::CRON_HOOK))
			return;
		wp_schedule_event(time(), self::CRON_INTERVAL_NAME, self::CRON_HOOK);
	}

	public static function endMonitoring() {
		$timestamp = wp_next_scheduled(self::CRON_HOOK);
		if ($timestamp !== false)
			wp_unschedule_event($timestamp, self::CRON_HOOK);
	}

	public static function validateResumeAttempts($attempts, &$valid = null) {
		if ($attempts < 0 || $attempts > self::MAX_RESUME_ATTEMPTS) {
			$valid = false;
			return self::DEFAULT_RESUME_ATTEMPTS;
		}
		$valid = true;
		return $attempts;
	}

	private static function setRemainingResumeAttempts($attempts) {
		wfConfig::set(self::CONFIG_REMAINING_RESUME_ATTEMPTS, $attempts);
	}

	public static function getConfiguredResumeAttempts() {
		$attempts = (int) wfConfig::get(self::CONFIG_MAX_RESUME_ATTEMPTS, self::DEFAULT_RESUME_ATTEMPTS);
		return self::validateResumeAttempts($attempts);
	}

	private static function resetResumeAttemptCounter() {
		$attempts = self::getConfiguredResumeAttempts();
		self::setRemainingResumeAttempts($attempts);
		return $attempts;
	}

	private static function getRemainingResumeAttempts() {
		$attempts = (int) wfConfig::get(self::CONFIG_REMAINING_RESUME_ATTEMPTS, 0);
		return self::validateResumeAttempts($attempts);
	}

	public static function handleScanStart($mode) {
		wfConfig::set(self::CONFIG_LAST_ATTEMPT_MODE, $mode);
		$maxAttempts = self::resetResumeAttemptCounter();
		if ($maxAttempts > 0)
			self::beginMonitoring();
	}

	public static function monitorScan() {
		$remainingAttempts = self::getRemainingResumeAttempts();
		if ($remainingAttempts > 0) {
			$now = time();
			$lastAttempt = wfConfig::get(self::CONFIG_LAST_ATTEMPT);
			if ($lastAttempt === null || $now - $lastAttempt < self::SCAN_START_TIMEOUT)
				return;
			$lastSuccess = wfConfig::get(self::CONFIG_LAST_SUCCESS);
			self::setRemainingResumeAttempts(--$remainingAttempts);
			if ($lastSuccess === null || $lastAttempt > $lastSuccess) {
				wordfence::status(2, 'info', sprintf(/* translators: remaining attempts */ __('Attempting to resume scan stage (%d attempt(s) remaining)...', 'wordfence'), $remainingAttempts));
				self::resumeScan();
			}
		}
		else {
			self::endMonitoring();
		}
	}

	private static function resumeScan() {
		$mode = wfConfig::get(self::CONFIG_LAST_ATTEMPT_MODE);
		if (!wfScanner::isValidScanType($mode))
			$mode = false;
		wfScanEngine::startScan(wfConfig::get(self::CONFIG_LAST_ATTEMPT_WAS_FORK), $mode, true);
	}

	private static function logTimestamp($key) {
		wfConfig::set($key, time());
	}

	public static function logLastAttempt($fork) {
		self::logTimestamp(self::CONFIG_LAST_ATTEMPT);
		wfConfig::set(self::CONFIG_LAST_ATTEMPT_WAS_FORK, $fork);
	}

	public static function logLastSuccess() {
		self::logTimestamp(self::CONFIG_LAST_SUCCESS);
	}

	public static function handleStageStart($fork) {
		if ($fork)
			self::resetResumeAttemptCounter();
	}

	public static function registerCronInterval($schedules) {
		if (!array_key_exists(self::CRON_INTERVAL_NAME, $schedules)) {
			$schedules[self::CRON_INTERVAL_NAME] = array(
				'interval' => self::CRON_INTERVAL_AMOUNT,
				'display' => 'Wordfence Scan Monitor'
			);
		}
		return $schedules;
	}

	public static function registerActions() {
		add_filter('cron_schedules', array(self::class, 'registerCronInterval'));
		add_action(self::CRON_HOOK, array(self::class, 'monitorScan'));
	}

	public static function handleDeactivation() {
		self::endMonitoring();
	}

}