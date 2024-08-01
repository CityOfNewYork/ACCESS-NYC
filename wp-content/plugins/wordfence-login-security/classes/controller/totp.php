<?php

namespace WordfenceLS;

class Controller_TOTP {
	const TIME_WINDOW_LENGTH = 30;
	
	/**
	 * Returns the singleton Controller_TOTP.
	 *
	 * @return Controller_TOTP
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_TOTP();
		}
		return $_shared;
	}
	
	public function init() {
		
	}
	
	/**
	 * Activates a user with the given TOTP parameters.
	 * 
	 * @param \WP_User $user
	 * @param string $secret The secret as a hex string.
	 * @param string[] $recovery An array of recovery codes as hex strings.
	 * @param bool|int $vtime The timestamp of the verification code or false to use the current timestamp.
	 */
	public function activate_2fa($user, $secret, $recovery, $vtime = false) {
		if ($vtime === false) {
			$vtime = Controller_Time::time();
		}
		
		global $wpdb;
		$table = Controller_DB::shared()->secrets;
		$wpdb->query($wpdb->prepare("INSERT INTO `{$table}` (`user_id`, `secret`, `recovery`, `ctime`, `vtime`, `mode`) VALUES (%d, %s, %s, UNIX_TIMESTAMP(), %d, 'authenticator')", $user->ID, Model_Compat::hex2bin($secret), implode('', array_map(function($r) { return Model_Compat::hex2bin($r); }, $recovery)), $vtime));
	}
	
	/**
	 * Validates the 2FA (or recovery) code for the given user. This will return `null` if the user does not have 2FA 
	 * enabled. This check will mark the code as used, preventing its use again.
	 * 
	 * @param \WP_User $user
	 * @param string $code
	 * @return bool|null Returns null if the user does not have 2FA enabled, false if the code is invalid, and true if valid.
	 */
	public function validate_2fa($user, $code, $update = true) {
		global $wpdb;
		$table = Controller_DB::shared()->secrets;
		$record = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE `user_id` = %d FOR UPDATE", $user->ID), ARRAY_A);
		if (!$record) {
			return null;
		}
		
		if (preg_match('/^(?:[a-f0-9]{4}\s*){4}$/i', $code)) { //Recovery code
			$code = strtolower(preg_replace('/\s/i', '', $code));
			$recoveryCodes = str_split(strtolower(bin2hex($record['recovery'])), 16);
			
			$index = array_search($code, $recoveryCodes);
			if ($index !== false) {
				if ($update) {
					unset($recoveryCodes[$index]);
					$updatedRecoveryCodes = implode('', $recoveryCodes);
					$wpdb->query($wpdb->prepare("UPDATE `{$table}` SET `recovery` = X%s WHERE `id` = %d", $updatedRecoveryCodes, $record['id']));
				}
				$wpdb->query('COMMIT');
				return true;
			}
		}
		else if (preg_match('/^(?:[0-9]{3}\s*){2}$/i', $code)) { //TOTP code
			$code = preg_replace('/\s/i', '', $code);
			$secret = bin2hex($record['secret']);
			
			$matches = $this->check_code($secret, $code, floor($record['vtime'] / self::TIME_WINDOW_LENGTH));
			if ($matches !== false) {
				if ($update) {
					$wpdb->query($wpdb->prepare("UPDATE `{$table}` SET `vtime` = %d WHERE `id` = %d", $matches, $record['id']));
				}
				$wpdb->query('COMMIT');
				return true;
			}
		}
		
		$wpdb->query('ROLLBACK');
		return false;
	}
	
	/**
	 * Checks whether or not the code is valid for the given secret. If it is, it returns the time window (as a timestamp)
	 * that matched. If no time windows are provided, it checks the current and one on each side.
	 * 
	 * @param string $secret The secret as a hex string.
	 * @param string $code The code.
	 * @param null|int The last-used time window (as a timestamp).
	 * @param null|array $windows An array of time windows or null to use the default.
	 * @return bool|int The time window if matches, otherwise false.
	 */
	public function check_code($secret, $code, $previous = null, $windows = null) {
		$timeCode = floor(Controller_Time::time() / self::TIME_WINDOW_LENGTH);
		
		if ($windows === null) {
			$windows = array();
			$validRange = array(-1, 1); //90 second range for authenticator
			
			$lowRange = $validRange[0];
			$highRange = $validRange[1];
			for ($i = 0; $i >= $lowRange; $i--) {
				$windows[] = $timeCode + $i;
			}
			for ($i = 1; $i <= $highRange; $i++) {
				$windows[] = $timeCode + $i;
			}
		}
		
		foreach ($windows as $w) {
			if ($previous !== null && $previous >= $w) {
				continue;
			}
			
			$expectedCode = $this->_generate_totp($secret, dechex($w));
			if (hash_equals($expectedCode, $code)) {
				return $w * self::TIME_WINDOW_LENGTH;
			}
		}
		
		return false;
	}
	
	/**
	 * Generates a TOTP value using the provided parameters.
	 *
	 * @param $key The key in hex.
	 * @param $time The desired time code in hex.
	 * @param int $digits The number of digits.
	 * @return string The TOTP value.
	 */
	private function _generate_totp($key, $time, $digits = 6)
	{
		$time = Model_Compat::hex2bin(str_pad($time, 16, '0', STR_PAD_LEFT));
		$key = Model_Compat::hex2bin($key);
		$hash = hash_hmac('sha1', $time, $key);
		
		$offset = hexdec(substr($hash, -2)) & 0xf;
		$intermediate = (	((hexdec(substr($hash, $offset * 2, 2)) & 0x7f) << 24) |
			((hexdec(substr($hash, ($offset + 1) * 2, 2)) & 0xff) << 16) |
			((hexdec(substr($hash, ($offset + 2) * 2, 2)) & 0xff) << 8) |
			((hexdec(substr($hash, ($offset + 3) * 2, 2)) & 0xff))
		);
		$otp = $intermediate % pow(10, $digits);
		
		return str_pad("{$otp}", $digits, '0', STR_PAD_LEFT);
	}
}