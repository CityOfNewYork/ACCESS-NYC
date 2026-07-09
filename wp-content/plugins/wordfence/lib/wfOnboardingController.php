<?php

class wfOnboardingController {
	const ONBOARDING_EMAILS = 'emails'; //New install, part 1 completed
	const ONBOARDING_LICENSE = 'license'; //New install, part 2 completed
	const ONBOARDING_SKIPPED = 'skipped'; //New install, onboarding attempt was skipped
	
	const TOUR_DASHBOARD = 'dashboard';
	const TOUR_FIREWALL = 'firewall';
	const TOUR_SCAN = 'scan';
	const TOUR_BLOCKING = 'blocking';
	const TOUR_LIVE_TRAFFIC = 'livetraffic';
	const TOUR_AUDIT_LOG = 'auditlog';
	const TOUR_LOGIN_SECURITY = 'loginsecurity';
	
	/**
	 * Sets the appropriate initial settings for an existing install so it's not forced through onboarding.
	 */
	public static function migrateOnboarding() {
		$alertEmails = wfConfig::getAlertEmails();
		$onboardingAttempt1 = wfConfig::get('onboardingAttempt1');
		$lastOnboardingVersion = wfConfig::get('onboardingLastVersion');
		if (!empty($alertEmails) && empty($onboardingAttempt1)) { //Wordfence 7.0 migration
			wfConfig::set('onboardingAttempt1', self::ONBOARDING_LICENSE); //Mark onboarding as done
			
			$keys = array(self::TOUR_DASHBOARD, self::TOUR_FIREWALL, self::TOUR_SCAN, self::TOUR_BLOCKING, self::TOUR_LIVE_TRAFFIC, self::TOUR_AUDIT_LOG);
			foreach ($keys as $k) {
				wfConfig::set('needsNewTour_' . $k, 0);
				wfConfig::set('needsUpgradeTour_' . $k, 1);
			}
			wfConfig::set('onboardingLastVersion', WORDFENCE_VERSION);
		}
		else if (!empty($alertEmails) && !empty($onboardingAttempt1) && (empty($lastOnboardingVersion) || 
				version_compare('8.0', $lastOnboardingVersion) == 1)) { //Future new tour steps can copy this block and extend
			$keys = array(self::TOUR_AUDIT_LOG);
			foreach ($keys as $k) {
				wfConfig::set('needsNewTour_' . $k, 0);
				wfConfig::set('needsUpgradeTour_' . $k, 1);
			}
			wfConfig::set('onboardingLastVersion', WORDFENCE_VERSION);
		}
	}
	
	/**
	 * Initializes the onboarding hooks.
	 * 
	 * Only called if (is_admin() && wfUtils::isAdmin()) is true.
	 */
	public static function initialize() {
		$willShowAnyTour = (self::shouldShowNewTour(self::TOUR_DASHBOARD) || self::shouldShowUpgradeTour(self::TOUR_DASHBOARD) ||
							self::shouldShowNewTour(self::TOUR_FIREWALL) || self::shouldShowUpgradeTour(self::TOUR_FIREWALL) ||
							self::shouldShowNewTour(self::TOUR_SCAN) || self::shouldShowUpgradeTour(self::TOUR_SCAN) ||
							self::shouldShowNewTour(self::TOUR_BLOCKING) || self::shouldShowUpgradeTour(self::TOUR_BLOCKING) ||
							self::shouldShowNewTour(self::TOUR_LIVE_TRAFFIC) || self::shouldShowUpgradeTour(self::TOUR_LIVE_TRAFFIC) ||
							self::shouldShowNewTour(self::TOUR_AUDIT_LOG) || self::shouldShowUpgradeTour(self::TOUR_AUDIT_LOG) ||
							self::shouldShowNewTour(self::TOUR_LOGIN_SECURITY) || self::shouldShowUpgradeTour(self::TOUR_LOGIN_SECURITY));
		if (!self::shouldShowAnyAttempt() && !$willShowAnyTour) {
			return;
		}
		
		add_action('in_admin_header', 'wfOnboardingController::_admin_header'); //Called immediately after <div id="wpcontent">
		add_action('pre_current_active_plugins', 'wfOnboardingController::_pre_plugins'); //Called immediately after <hr class="wp-header-end">
		add_action('admin_enqueue_scripts', 'wfOnboardingController::_enqueue_scripts');
	}
	
	/**
	 * Enqueues the scripts and styles we need globally on the backend for onboarding.
	 */
	public static function _enqueue_scripts() {
		$willShowAnyPluginOnboarding = (self::shouldShowAttempt1() || self::shouldShowAttempt2());
		$willShowAnyTour = (self::shouldShowNewTour(self::TOUR_DASHBOARD) || self::shouldShowUpgradeTour(self::TOUR_DASHBOARD) ||
			self::shouldShowNewTour(self::TOUR_FIREWALL) || self::shouldShowUpgradeTour(self::TOUR_FIREWALL) ||
			self::shouldShowNewTour(self::TOUR_SCAN) || self::shouldShowUpgradeTour(self::TOUR_SCAN) ||
			self::shouldShowNewTour(self::TOUR_BLOCKING) || self::shouldShowUpgradeTour(self::TOUR_BLOCKING) ||
			self::shouldShowNewTour(self::TOUR_LIVE_TRAFFIC) || self::shouldShowUpgradeTour(self::TOUR_LIVE_TRAFFIC) ||
			self::shouldShowNewTour(self::TOUR_AUDIT_LOG) || self::shouldShowUpgradeTour(self::TOUR_AUDIT_LOG) ||
			self::shouldShowNewTour(self::TOUR_LOGIN_SECURITY) || self::shouldShowUpgradeTour(self::TOUR_LOGIN_SECURITY));
		
		$page = wfUtils::array_get($_GET, 'page', '');
		if (wfUtils::isAdmin() && 
			(
				(
					$willShowAnyPluginOnboarding && preg_match('~(?:^|/)wp-admin(?:/network)?/plugins\.php~i', $_SERVER['REQUEST_URI'])
				) || 
				(
					!empty($page) && (preg_match('/^Wordfence/', $page) || preg_match('/^WFLS/', $page))
				)
			)
		) {
			self::enqueue_assets();
		}
	}

	public static function enqueue_assets() {
		wp_enqueue_style('wordfence-font', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-roboto-font.css'), '', WORDFENCE_VERSION);
		wp_enqueue_style('wordfence-ionicons-style', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-ionicons.css'), '', WORDFENCE_VERSION);
		wp_enqueue_style('wordfenceOnboardingCSS', wfUtils::getBaseURL() . wfUtils::versionedAsset('css/wf-onboarding.css'), '', WORDFENCE_VERSION);
	}
	
	/**
	 * Outputs the onboarding overlay if it needs to be shown on the plugins page.
	 */
	public static function _admin_header() {
		$willShowAnyTour = (self::shouldShowNewTour(self::TOUR_DASHBOARD) || self::shouldShowUpgradeTour(self::TOUR_DASHBOARD) ||
							self::shouldShowNewTour(self::TOUR_FIREWALL) || self::shouldShowUpgradeTour(self::TOUR_FIREWALL) ||
							self::shouldShowNewTour(self::TOUR_SCAN) || self::shouldShowUpgradeTour(self::TOUR_SCAN) ||
							self::shouldShowNewTour(self::TOUR_BLOCKING) || self::shouldShowUpgradeTour(self::TOUR_BLOCKING) ||
							self::shouldShowNewTour(self::TOUR_LIVE_TRAFFIC) || self::shouldShowUpgradeTour(self::TOUR_LIVE_TRAFFIC) ||
							self::shouldShowNewTour(self::TOUR_AUDIT_LOG) || self::shouldShowUpgradeTour(self::TOUR_AUDIT_LOG) ||
							self::shouldShowNewTour(self::TOUR_LOGIN_SECURITY) || self::shouldShowUpgradeTour(self::TOUR_LOGIN_SECURITY));
		
		$screen = get_current_screen();
		if ($screen->base == 'plugins' && self::shouldShowAttempt1()) {
			register_shutdown_function('wfOnboardingController::_markAttempt1Shown');
			$freshInstall = wfView::create('onboarding/fresh-install')->render(); 
			
			echo wfView::create('onboarding/overlay', array(
				'contentHTML' => $freshInstall,
			))->render();
		}
		else if (preg_match('/wordfence/i', $screen->base) && $willShowAnyTour) {
			echo wfView::create('onboarding/tour-overlay')->render();
		}
	}
	
	public static function _markAttempt1Shown() {
		wfConfig::set('onboardingAttempt1', self::ONBOARDING_SKIPPED); //Only show it once, default to skipped after outputting the first time
	}
	
	public static function shouldShowAttempt1() { //Overlay on plugin page
		if (wfConfig::get('onboardingAttempt3') == self::ONBOARDING_LICENSE) {
			return false;
		}
		
		switch (wfConfig::get('onboardingAttempt1')) {
			case self::ONBOARDING_LICENSE:
			case self::ONBOARDING_SKIPPED:
				return false;
		}
		return true;
	}
	
	public static function _pre_plugins() {
		if (self::shouldShowAttempt2()) {
			echo wfView::create('onboarding/plugin-header')->render();
		}
	}

	private static function needsApiKey() {
		$key = wfConfig::get('apiKey');
		return empty($key);
	}
	
	public static function shouldShowAttempt2() { //Header on plugin page
		if (wfConfig::get('onboardingAttempt3') == self::ONBOARDING_LICENSE) {
			return false;
		}
		
		return !wfConfig::get('onboardingAttempt2') && self::needsApiKey();
	}
	
	public static function shouldShowAttempt3($dismissable = false) {
		if (self::needsApiKey()) {
			if (!$dismissable)
				return true;
			$delayedAt = (int) wfConfig::get('onboardingDelayedAt', 0);
			if (time() - $delayedAt > 43200 /*12 hours in seconds*/)
				return true;
		}
		return false;
	}
	
	/**
	 * Whether or not to pop up attempt 3 at page load or wait for user interaction.
	 * 
	 * @return bool
	 */
	public static function shouldShowAttempt3Automatically() {
		static $_shouldShowAttempt3Automatically = null;
		if ($_shouldShowAttempt3Automatically !== null) { //We cache this so the answer remains the same for the whole request
			return $_shouldShowAttempt3Automatically;
		}
		
		if (!self::shouldShowAttempt3()) {
			$_shouldShowAttempt3Automatically = false;
			return false;
		}
		
		return $_shouldShowAttempt3Automatically = self::shouldShowAttempt3();
	}
	
	public static function willShowNewTour($page) {
		$key = 'needsNewTour_' . $page;
		return wfConfig::get($key);
	}
	
	public static function shouldShowNewTour($page) {
		$key = 'needsNewTour_' . $page;
		return (!self::shouldShowAttempt3Automatically() && !wfConfig::get('touppPromptNeeded') && wfConfig::get($key));
	}
	
	public static function willShowUpgradeTour($page) {
		$key = 'needsUpgradeTour_' . $page;
		return wfConfig::get($key);
	}
	
	public static function shouldShowUpgradeTour($page) {
		$key = 'needsUpgradeTour_' . $page;
		return (!self::shouldShowAttempt3Automatically() && !wfConfig::get('touppPromptNeeded') && wfConfig::get($key));
	}

	public static function shouldShowAnyAttempt() {
		return self::shouldShowAttempt1() || self::shouldShowAttempt2() || self::shouldShowAttempt3();
	}

}