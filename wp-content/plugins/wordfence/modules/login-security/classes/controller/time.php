<?php

namespace WordfenceLS;

class Controller_Time {
	const NTP_VERSION = 3; // https://www.ietf.org/rfc/rfc1305.txt
	const NTP_EPOCH_CONVERT = 2208988800; //RFC 5905, page 13
	const FAILURE_LIMIT = 3;
	
	/**
	 * Returns the singleton Controller_Time.
	 *
	 * @return Controller_Time
	 */
	public static function shared() {
		static $_shared = null;
		if ($_shared === null) {
			$_shared = new Controller_Time();
		}
		return $_shared;
	}
	
	public function install() {
		wp_clear_scheduled_hook('wordfence_ls_ntp_cron');
		if (is_main_site()) {
			wp_schedule_event(time() + 10, 'hourly', 'wordfence_ls_ntp_cron');
		}
		Controller_Settings::shared()->reset_ntp_disabled_flag();
	}
	
	public function uninstall() {
		wp_clear_scheduled_hook('wordfence_ls_ntp_cron');
		Controller_Settings::shared()->reset_ntp_disabled_flag();
	}
	
	public function init() {
		$this->_init_actions();
	}
	
	public function _init_actions() {
		add_action('wordfence_ls_ntp_cron', array($this, '_wordfence_ls_ntp_cron'));
	}
	
	public function _wordfence_ls_ntp_cron() {
		if (Controller_Settings::shared()->get_bool(Controller_Settings::OPTION_ALLOW_DISABLING_NTP) && Controller_Settings::shared()->is_ntp_cron_disabled())
			return;
		$ntp = self::ntp_time();
		$time = time();
		
		if ($ntp === false) {
			$failureCount = Controller_Settings::shared()->increment_ntp_failure_count();
			if ($failureCount >= self::FAILURE_LIMIT) {
				Controller_Settings::shared()->set(Controller_Settings::OPTION_USE_NTP, false);
				Controller_Settings::shared()->set(Controller_Settings::OPTION_NTP_OFFSET, 0);
			}
		}
		else {
			Controller_Settings::shared()->reset_ntp_failure_count();
			Controller_Settings::shared()->set(Controller_Settings::OPTION_USE_NTP, true);
			Controller_Settings::shared()->set(Controller_Settings::OPTION_NTP_OFFSET, $ntp - $time);
		}
		Controller_Settings::shared()->set(Controller_Settings::OPTION_ALLOW_DISABLING_NTP, true);
	}
	
	/**
	 * Returns the current UTC timestamp, offset as needed to reflect the time retrieved from an NTP request or (if
	 * running in the complete plugin) offset as needed from the Wordfence server's true time.
	 * 
	 * @param bool|int $time The timestamp to apply any offset to. If `false`, it will use the current timestamp.
	 * @return int
	 */
	public static function time($time = false) {
		if ($time === false) {
			$time = time();
		}
		
		$offset = 0;
		if (Controller_Settings::shared()->is_ntp_enabled()) {
			$offset = Controller_Settings::shared()->get_int(Controller_Settings::OPTION_NTP_OFFSET);
		}
		else if (WORDFENCE_LS_FROM_CORE) {
			$offset = \wfUtils::normalizedTime($time) - $time;
		}
		
		return $time + $offset;
	}
	
	/**
	 * Returns the current timestamp from ntp.org using the NTP protocol. If unable to (e.g., UDP connections are blocked),
	 * it will return false.
	 * 
	 * @return bool|float
	 */
	public static function ntp_time() {
		$servers = array('0.pool.ntp.org', '1.pool.ntp.org', '2.pool.ntp.org', '3.pool.ntp.org');
		
		//Header - RFC 5905, page 18
		$header = '00'; //LI (leap indicator) - 2 bits: 00 for "no warning"
		$header .= sprintf('%03d', decbin(self::NTP_VERSION)); //VN (version number) - 3 bits: 011 for version 3
		$header .= '011'; //Mode (association mode) - 3 bit: 011 for "client"
		
		$packet = chr(bindec($header));
		$packet .= str_repeat("\x0", 39);
		
		foreach ($servers as $s) {
			$socket = @fsockopen('udp://' . $s, 123, $err_no, $err_str, 1);
			if ($socket) {
				stream_set_timeout($socket, 1);
				$remote_originate = microtime(true);
				$secondsNTP = ((int) $remote_originate) + self::NTP_EPOCH_CONVERT;
				$fractional = sprintf('%010d', round(($remote_originate - ((int) $remote_originate)) * 0x100000000));
				$packed = pack('N', $secondsNTP) . pack('N', $fractional);
				
				if (@fwrite($socket, $packet . $packed)) {
					$response = fread($socket, 48);
					$local_transmitted = microtime(true);
				}
				@fclose($socket);
				
				if (isset($response) && Model_Crypto::strlen($response) == 48) {
					break;
				}
			}
		}
		
		if (isset($response) && Model_Crypto::strlen($response) == 48) {
			$longs = unpack("N12", $response);
			
			$remote_originate_seconds = sprintf('%u', $longs[7]) - self::NTP_EPOCH_CONVERT;
			$remote_received_seconds = sprintf('%u', $longs[9]) - self::NTP_EPOCH_CONVERT;
			$remote_transmitted_seconds = sprintf('%u', $longs[11]) - self::NTP_EPOCH_CONVERT;
			
			$remote_originate_fraction = sprintf('%u', $longs[8]) / 0x100000000;
			$remote_received_fraction = sprintf('%u', $longs[10]) / 0x100000000;
			$remote_transmitted_fraction = sprintf('%u', $longs[12]) / 0x100000000;
			
			$remote_originate = $remote_originate_seconds + $remote_originate_fraction;
			$remote_received = $remote_received_seconds + $remote_received_fraction;
			$remote_transmitted = $remote_transmitted_seconds + $remote_transmitted_fraction;
			
			$delay = (($local_transmitted - $remote_originate) / 2)  - ($remote_transmitted - $remote_received);
			
			$ntp_time =  $remote_transmitted - $delay;
			return $ntp_time;
		}
		
		return false;
	}
	
	/**
	 * Formats and returns the given timestamp using the time zone set for the WordPress installation.
	 *
	 * @param string $format See the PHP docs on DateTime for the format options.
	 * @param int|bool $timestamp Assumed to be in UTC. If false, defaults to the current timestamp.
	 * @return string
	 */
	public static function format_local_time($format, $timestamp = false) {
		if ($timestamp === false) {
			$timestamp = self::time();
		}
		
		$utc = new \DateTimeZone('UTC');
		if (!function_exists('date_timestamp_set')) {
			$dtStr = gmdate("c", (int) $timestamp); //Have to do it this way because of PHP 5.2
			$dt = new \DateTime($dtStr, $utc);
		}
		else {
			$dt = new \DateTime('now', $utc);
			$dt->setTimestamp($timestamp);
		}
		
		$tz = get_option('timezone_string');
		if (!empty($tz)) {
			$dt->setTimezone(new \DateTimeZone($tz));
		}
		else {
			$gmt = get_option('gmt_offset');
			if (!empty($gmt)) {
				if (PHP_VERSION_ID < 50510) {
					$dtStr = gmdate("c", (int) ($timestamp + $gmt * 3600)); //Have to do it this way because of < PHP 5.5.10
					$dt = new \DateTime($dtStr, $utc);
				}
				else {
					$direction = ($gmt > 0 ? '+' : '-');
					$gmt = abs($gmt);
					$h = (int) $gmt;
					$m = ($gmt - $h) * 60;
					$dt->setTimezone(new \DateTimeZone($direction . str_pad($h, 2, '0', STR_PAD_LEFT) . str_pad($m, 2, '0', STR_PAD_LEFT)));
				}
			}
		}
		return $dt->format($format);
	}
}