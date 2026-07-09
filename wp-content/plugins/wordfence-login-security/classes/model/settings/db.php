<?php

namespace WordfenceLS\Settings;

use WordfenceLS\Controller_DB;
use WordfenceLS\Model_Settings;

class Model_DB extends Model_Settings {
	const AUTOLOAD_NO = 'no';
	const AUTOLOAD_YES = 'yes';
	
	private static $_cache = array();
	
	public function set($key, $value, $autoload = self::AUTOLOAD_YES, $allowOverwrite = true) {
		global $wpdb;
		$table = Controller_DB::shared()->settings;
		if (!$allowOverwrite) {
			if ($this->_has_cached($key)) {
				return;
			}
			
			$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM `{$table}` WHERE `name` = %s", $key), ARRAY_A);
			if (is_array($row)) {
				return;
			}
		}
		
		if ($wpdb->query($wpdb->prepare("INSERT INTO `{$table}` (`name`, `value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `autoload` = VALUES(`autoload`)", $key, $value, $autoload)) !== false && $autoload != self::AUTOLOAD_NO) {
			$this->_update_cached($key, $value);
			do_action('wfls_settings_set', $key, $value);
		}
	}
	
	public function set_multiple($values) {
		foreach ($values as $key => $value) {
			if (is_array($value)) {
				$this->set($key, $value['value'], $value['autoload'], $value['allowOverwrite']);
			}
			else {
				$this->set($key, $value);
			}
		}
	}
	
	public function get($key, $default = false) {
		$results = $this->get_multiple(array($key => $default));
		return $results[$key];
	}
	
	public function get_multiple($keysDefaults) {
		global $wpdb;
		
		$result = array();
		$remaining = array();
		foreach ($keysDefaults as $key => $default) {
			if ($this->_has_cached($key)) {
				$result[$key] = $this->_cached_value($key);
			}
			else {
				$remaining[$key] = $default;
			}
		}
		
		if (!empty($remaining)) {
			$sanitizedKeys = esc_sql(array_keys($remaining));
			$keysINClause = "'" . implode("','", $sanitizedKeys) . "'";
			
			$table = Controller_DB::shared()->settings;
			$rows = $wpdb->get_results("SELECT `name`, `value`, `autoload` FROM `{$table}` WHERE `name` IN ({$keysINClause})", ARRAY_A);
			foreach ($rows as $r) {
				$name = $r['name'];
				$value = $r['value'];
				
				$result[$name] = $value;
				unset($remaining[$name]);
				if ($r['autoload'] != self::AUTOLOAD_NO) {
					$this->_update_cached($name, $value);
				}
			}
		}
		
		return array_merge($remaining, $result);
	}
	
	public function remove($key) {
		global $wpdb;
		$table = Controller_DB::shared()->settings;
		$wpdb->query($wpdb->prepare("DELETE FROM `{$table}` WHERE `name` = %s", $key));
		$this->_remove_cached($key);
	}
	
	private function _cached() {
		global $wpdb;
		
		if (empty(self::$_cache)) {
			$table = Controller_DB::shared()->settings;
			$suppress = $wpdb->suppress_errors();
			$raw = $wpdb->get_results("SELECT `name`, `value` FROM `{$table}` WHERE `autoload` = 'yes'");
			$wpdb->suppress_errors($suppress);
			$settings = array();
			foreach ((array) $raw as $o) {
				$settings[$o->name] = $o->value;
			}
			
			self::$_cache = $settings;
		}
		
		return self::$_cache;
	}
	
	private function _update_cached($key, $value) {
		$settings = $this->_cached();
		$settings[$key] = $value;
		self::$_cache = $settings;
	}
	
	private function _remove_cached($key) {
		$settings = $this->_cached();
		if (isset($settings[$key])) {
			unset($settings[$key]);
			self::$_cache = $settings;
		}
	}
	
	private function _cached_value($key) {
		global $wpdb;
		
		$settings = $this->_cached();
		if (isset($settings[$key])) {
			return $settings[$key];
		}
		
		$table = Controller_DB::shared()->settings;
		$value = $wpdb->get_var($wpdb->prepare("SELECT `value` FROM `{$table}` WHERE name = %s", $key));
		if ($value !== null) {
			$settings[$key] = $value;
			self::$_cache = $settings;
		}
		return $value;
	}
	public function _has_cached($key) {
		$settings = $this->_cached();
		return isset($settings[$key]);
	}
}