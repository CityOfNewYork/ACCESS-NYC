<?php

namespace WordfenceLS;

abstract class Model_Settings {
	const AUTOLOAD_YES = 'yes';
	const AUTOLOAD_NO = 'no';
	
	/**
	 * Sets $value to $key.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @param string $autoload Whether or not the key/value pair should autoload in storages that do that.
	 * @param bool $allowOverwrite If false, only sets the value if key does not already exist.
	 */
	abstract public function set($key, $value, $autoload = self::AUTOLOAD_YES, $allowOverwrite = true);
	abstract public function set_multiple($values);
	abstract public function get($key, $default);
	abstract public function remove($key);
}