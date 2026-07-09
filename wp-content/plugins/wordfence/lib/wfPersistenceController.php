<?php

/**
 * wfPersistenceController controls the persistence of disclosure states within the plugin. By default, all states are
 * inactive except those listed in DEFAULT_STATES.
 */
class wfPersistenceController {
	const DEFAULT_STATES = array(
		'audit-log-options' => true,
		'wf-unified-audit-log-options' => true,
	);
	private $_disclosureStates;
	
	public static function shared() {
		static $_shared = false;
		if ($_shared === false) {
			$_shared = new wfPersistenceController();
		}
		return $_shared;
	}
	
	public function __construct() {
		$this->_disclosureStates = wfConfig::get_ser('disclosureStates', array());
	}
	
	/**
	 * Returns whether the options block is in an active state. 
	 * 
	 * @param $key
	 * @return bool
	 */
	public function isActive($key) {
		$default = array_key_exists($key, self::DEFAULT_STATES) && self::DEFAULT_STATES[$key];
		if (!isset($this->_disclosureStates[$key])) {
			return $default;
		}
		return !!$this->_disclosureStates[$key];
	}
	
	/**
	 * Returns whether the options block has been set.
	 *
	 * @param $key
	 * @return bool
	 */
	public function isConfigured($key) {
		return isset($this->_disclosureStates[$key]);
	}
	
	/**
	 * Returns an array of all active disclosure state keys.
	 *
	 * @return string[]
	 */
	public function activeKeys() {
		return array_keys(array_filter(array_merge(self::DEFAULT_STATES, $this->_disclosureStates), function($v) {
			return $v;
		}));
	}
}