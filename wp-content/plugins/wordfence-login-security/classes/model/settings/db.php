<?php

namespace WordfenceLS\Settings;

use WordfenceLS\Controller_DB;
use WordfenceLS\Model_Settings;

class Model_DB extends Model_Settings {
	const AUTOLOAD_NO = 'no';
	const AUTOLOAD_YES = 'yes';
	
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
		global $wpdb;
		
		if ($this->_has_cached($key)) {
			return $this->_cached_value($key);
		}
		
		$table = Controller_DB::shared()->settings;
		if (!($setting = $wpdb->get_row($wpdb->prepare("SELECT `name`, `value`, `autoload` FROM `{$table}` WHERE `name` = %s", $key)))) {
			return $default;
		}
		
		if ($setting->autoload != self::AUTOLOAD_NO) {
			$this->_update_cached($key, $setting->value);
		}
		return $setting->value;
	}
	
	public function remove($key) {
		global $wpdb;
		$table = Controller_DB::shared()->settings;
		$wpdb->query($wpdb->prepare("DELETE FROM `{$table}` WHERE `name` = %s", $key));
		$this->_remove_cached($key);
	}
	
	private function _cached() {
		global $wpdb;
		
		$settings = wp_cache_get('allsettings', 'wordfence-ls');
		if (!$settings) {
			$table = Controller_DB::shared()->settings;
			$suppress = $wpdb->suppress_errors();
			$raw = $wpdb->get_results("SELECT `name`, `value` FROM `{$table}` WHERE `autoload` = 'yes'");
			$wpdb->suppress_errors($suppress);
			$settings = array();
			foreach ((array) $raw as $o) {
				$settings[$o->name] = $o->value;
			}
			
			wp_cache_add_non_persistent_groups('wordfence-ls');
			wp_cache_add('allsettings', $settings, 'wordfence-ls');
		}
		
		return $settings;
	}
	
	private function _update_cached($key, $value) {
		$settings = $this->_cached();
		$settings[$key] = $value;
		wp_cache_set('allsettings', $settings, 'wordfence-ls');
	}
	
	private function _remove_cached($key) {
		$settings = $this->_cached();
		if (isset($settings[$key])) {
			unset($settings[$key]);
			wp_cache_set('allsettings', $settings, 'wordfence-ls');
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
			wp_cache_set('allsettings', $settings, 'wordfence-ls');
		}
		return $value;
	}
	public function _has_cached($key) {
		$settings = $this->_cached();
		return isset($settings[$key]);
	}
}