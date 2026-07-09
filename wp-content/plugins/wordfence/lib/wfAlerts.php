<?php

abstract class wfBaseAlert {

	public abstract function send();
}

class wfBlockAlert extends wfBaseAlert {

	private $IP;
	private $reason;
	private $secsToGo;


	/**
	 * wfBlockAlert constructor.
	 * @param $IP
	 * @param $reason
	 * @param $secsToGo
	 */
	public function __construct($IP, $reason, $secsToGo) {
		$this->IP = $IP;
		$this->reason = $reason;
		$this->secsToGo = $secsToGo;
	}

	public function send() {
		if (wfConfig::get('alertOn_block')) {
			$message = sprintf(/* translators: IP address. */ __('Wordfence has blocked IP address %s.', 'wordfence'), $this->IP) . "\n";
			$message .= sprintf(/* translators: Description of firewall action. */ __('The reason is: "%s".', 'wordfence'), wfWAFBlockI18n::getTranslatedBlockDescription($this->reason));
			if ($this->secsToGo > 0) {
				$message .= "\n" . sprintf(/* translators: Time until. */ __('The duration of the block is %s.', 'wordfence'), wfUtils::makeDuration($this->secsToGo, true));
			}
			wordfence::alert(sprintf(/* translators: IP address. */__('Blocking IP %s', 'wordfence'), $this->IP), $message, $this->IP);
		}
	}

}

class wfAutoUpdatedAlert extends wfBaseAlert {

	private $version;

	/**
	 * @param $version
	 */
	public function __construct($version) {
		$this->version = $version;
	}

	public function send() {
		if (wfConfig::get('alertOn_update') == '1' && $this->version) {
			wordfence::alert(sprintf(/* translators: Software version. */ __("Wordfence Upgraded to version %s", 'wordfence'), $this->version), sprintf(/* translators: Software version. */ __("Your Wordfence installation has been upgraded to version %s", 'wordfence'), $this->version), false);
		}
	}

}

class wfWafDeactivatedAlert extends wfBaseAlert {

	private $username;
	private $IP;

	/**
	 * @param $username
	 * @param $IP
	 */
	public function __construct($username, $IP) {
		$this->username = $username;
		$this->IP = $IP;
	}

	public function send() {
		if (wfConfig::get('alertOn_wafDeactivated')) {
			wordfence::alert(__('Wordfence WAF Deactivated', 'wordfence'), sprintf(/* translators: WP username. */__('A user with username "%s" deactivated the Wordfence Web Application Firewall on your WordPress site.', 'wordfence'), $this->username), $this->IP);
		}
	}

}

class wfWordfenceDeactivatedAlert extends wfBaseAlert {
	private $username;
	private $IP;

	/**
	 * @param $username
	 * @param $IP
	 */
	public function __construct($username, $IP) {
		$this->username = $username;
		$this->IP = $IP;
	}

	public function send() {
		if (wfConfig::get('alertOn_wordfenceDeactivated')) {
			wordfence::alert(__("Wordfence Deactivated", 'wordfence'), sprintf(/* translators: WP username. */ __("A user with username \"%s\" deactivated Wordfence on your WordPress site.", 'wordfence'), $this->username), $this->IP);
		}
	}

}

class wfLostPasswdFormAlert extends wfBaseAlert {

	private $user;
	private $IP;

	/**
	 * @param $user
	 * @param $IP
	 */
	public function __construct($user, $IP) {
		$this->user = $user;
		$this->IP = $IP;
	}

	public function send() {
		if (wfConfig::get('alertOn_lostPasswdForm')) {
			wordfence::alert(__("Password recovery attempted", 'wordfence'), sprintf(/* translators: Email address. */__("Someone tried to recover the password for user with email address: %s", 'wordfence'), wp_kses($this->user->user_email, array())), $this->IP);
		}
	}

}

class wfLoginLockoutAlert extends wfBaseAlert {

	private $IP;
	private $reason;

	/**
	 * @param $IP
	 * @param $reason
	 */
	public function __construct($IP, $reason) {
		$this->IP = $IP;
		$this->reason = $reason;
	}

	public function send() {
		if (wfConfig::get('alertOn_loginLockout')) {
			$message = sprintf(
				/* translators: 1. IP address. 2. Description of firewall action. */
				__('A user with IP address %1$s has been locked out from signing in or using the password recovery form for the following reason: %2$s.', 'wordfence'), $this->IP, wfWAFBlockI18n::getTranslatedBlockDescription($this->reason));
			if (wfBlock::lockoutDuration() > 0) {
				$message .= "\n" . sprintf(/* translators: Time until. */ __('The duration of the lockout is %s.', 'wordfence'), wfUtils::makeDuration(wfBlock::lockoutDuration(), true));
			}
			wordfence::alert(__('User locked out from signing in', 'wordfence'), $message, $this->IP);
		}
	}
}

class wfAdminLoginAlert extends wfBaseAlert {

	private $cookieName;
	private $username;
	private $IP;
	private $cookieValue;

	/**
	 * @param $cookieName
	 * @param $cookieValue
	 * @param $username
	 * @param $IP
	 */
	public function __construct($cookieName, $cookieValue, $username, $IP) {
		$this->cookieName = $cookieName;
		$this->cookieValue = $cookieValue;
		$this->username = $username;
		$this->IP = $IP;
	}

	public function send() {
		if (wfConfig::get('alertOn_adminLogin')) {
			$shouldAlert = true;
			$firstLogin = false;
			if (wfConfig::get('alertOn_firstAdminLoginOnly')) {
				if (isset($_COOKIE[$this->cookieName])) {
					$shouldAlert = !hash_equals($this->cookieValue, $_COOKIE[$this->cookieName]);
				}
				else {
					$firstLogin = true;
				}
			}

			if ($shouldAlert) {
				if ($firstLogin) {
					wordfence::alert(__("Admin Login", 'wordfence'), sprintf(/* translators: WP username. */ __("A user with username \"%s\" who has administrator access signed in to your WordPress site from a new device.", 'wordfence'), $this->username), $this->IP);
				}
				else {
					wordfence::alert(__("Admin Login", 'wordfence'), sprintf(/* translators: WP username. */ __("A user with username \"%s\" who has administrator access signed in to your WordPress site.", 'wordfence'), $this->username), $this->IP);
				}
			}
		}
	}
}

class wfNonAdminLoginAlert extends wfBaseAlert {

	private $cookieName;
	private $username;
	private $IP;
	private $cookieValue;

	/**
	 * @param $cookieName
	 * @param $cookieValue
	 * @param $username
	 * @param $IP
	 */
	public function __construct($cookieName, $cookieValue, $username, $IP) {
		$this->cookieName = $cookieName;
		$this->cookieValue = $cookieValue;
		$this->username = $username;
		$this->IP = $IP;
	}

	public function send() {
		if (wfConfig::get('alertOn_nonAdminLogin')) {
			$shouldAlert = true;
			$firstLogin = false;
			if (wfConfig::get('alertOn_firstNonAdminLoginOnly')) {
				if (isset($_COOKIE[$this->cookieName])) {
					$shouldAlert = !hash_equals($this->cookieValue, $_COOKIE[$this->cookieName]);
				}
				else {
					$firstLogin = true;
				}
			}

			if ($shouldAlert) {
				if ($firstLogin) {
					wordfence::alert(__("User login", 'wordfence'), sprintf(/* translators: WP username. */ __("A non-admin user with username \"%s\" signed in to your WordPress site from a new device.", 'wordfence'), $this->username), $this->IP);
				}
				else {
					wordfence::alert(__("User login", 'wordfence'), sprintf(/* translators: WP username. */ __("A non-admin user with username \"%s\" signed in to your WordPress site.", 'wordfence'), $this->username), $this->IP);
				}
			}
		}
	}
}

class wfBreachLoginAlert extends wfBaseAlert {

	private $username;
	private $lostPasswordUrl;
	private $supportUrl;
	private $IP;

	/**
	 * @param $username
	 * @param $lostPasswordUrl
	 * @param $supportUrl
	 * @param $IP
	 */
	public function __construct($username, $lostPasswordUrl, $supportUrl, $IP) {
		$this->username = $username;
		$this->lostPasswordUrl = $lostPasswordUrl;
		$this->supportUrl = $supportUrl;
		$this->IP = $IP;
	}

	public function send() {
		if (wfConfig::get('alertOn_breachLogin')) {
			wordfence::alert(__('User login blocked for insecure password', 'wordfence'), sprintf(
				/* translators: 1. WP username. 2. Reset password URL. 3. Support URL. */
				__('A user with username "%1$s" tried to sign in to your WordPress site. Access was denied because the password being used exists on lists of passwords leaked in data breaches. Attackers use such lists to break into sites and install malicious code. Please change or reset the password (%2$s) to reactivate this account. Learn More: %3$s', 'wordfence'), $this->username, $this->lostPasswordUrl, $this->supportUrl), $this->IP);
		}
	}
}

class wfIncreasedAttackRateAlert extends wfBaseAlert {

	private $message;

	/**
	 * @param $message
	 */
	public function __construct($message) {
		$this->message = $message;
	}

	public function send() {
		wordfence::alert(__('Increased Attack Rate', 'wordfence'), $this->message, false);
	}
}
