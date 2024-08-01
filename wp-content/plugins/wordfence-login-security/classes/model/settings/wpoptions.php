<?php

namespace WordfenceLS\Settings;

use WordfenceLS\Model_Settings;

class Model_WPOptions extends Model_Settings {
	protected $_prefix;
	
	public function __construct($prefix = '') {
		$this->_prefix = $prefix;
	}
	
	protected function _translate_key($key) {
		return strtolower(preg_replace('/[^a-z0-9]/i', '_', $key));
	}
	
	public function set($key, $value, $autoload = self::AUTOLOAD_YES, $allowOverwrite = true) {
		$key = $this->_translate_key($this->_prefix . $key);
		if (!$allowOverwrite) {
			if (is_multisite()) {
				add_network_option(null, $key, $value);
			}
			else {
				add_option($key, $value, '', $autoload);
			}
		}
		else {
			if (is_multisite()) {
				update_network_option(null, $key, $value);
			}
			else {
				update_option($key, $value, $autoload);
			}
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
		$key = $this->_translate_key($this->_prefix . $key);
		if (is_multisite()) {
			$value = get_network_option($key, $default);
		}
		else {
			$value = get_option($key, $default);
		}
		return $value;
	}
	
	public function remove($key) {
		$key = $this->_translate_key($this->_prefix . $key);
		if (is_multisite()) {
			delete_network_option(null, $key);
		}
		else {
			delete_option($key);
		}
	}
}