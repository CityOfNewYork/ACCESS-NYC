<?php

/**
 * A dummy WAF implementation that can be used if initialization of the actual WAF failures
 */

class wfDummyWaf extends wfWAF {

	public function __construct() {
		parent::__construct(new wfDummyWafRequest(), new wfDummyWafStorageEngine());
	}

}

class wfDummyWafRequest implements wfWAFRequestInterface {

	public function getBody() {
		return null;
	}

	public function getRawBody() {
		return null;
	}

	public function getMd5Body() {
		return null;
	}

	public function getJsonBody() {
		return null;
	}

	public function getQueryString() {
		return null;
	}

	public function getMd5QueryString() {
		return null;
	}

	public function getHeaders() {
		return null;
	}

	public function getCookies() {
		return null;
	}

	public function getFiles() {
		return null;
	}

	public function getFileNames() {
		return null;
	}

	public function getHost() {
		return null;
	}

	public function getURI() {
		return null;
	}

	public function setMetadata($metadata) {
	}

	public function getMetadata() {
		return null;
	}

	public function getPath() {
		return null;
	}

	public function getIP() {
		return null;
	}

	public function getMethod() {
		return null;
	}

	public function getProtocol() {
		return null;
	}

	public function getAuth() {
		return null;
	}

	public function getTimestamp() {
		return null;
	}

	public function __toString() {
		return '';
	}

}

class wfDummyWafStorageEngine implements wfWAFStorageInterface {

	public function hasPreviousAttackData($olderThan) {
		return false;
	}

	public function hasNewerAttackData($newerThan) {
		return false;
	}

	public function getAttackData() {
		return null;
	}

	public function getAttackDataArray() {
		return array();
	}

	public function getNewestAttackDataArray($newerThan) {
		return array();
	}

	public function truncateAttackData() {
	}

	public function logAttack($failedRules, $failedParamKey, $failedParamValue, $request, $_ = null) {
	}

	public function blockIP($timestamp, $ip) {
	}

	public function isIPBlocked($ip) {
		return false;
	}

	public function purgeIPBlocks($types = wfWAFStorageInterface::IP_BLOCKS_ALL) {
	}

	public function getConfig($key, $default = null, $category = '') {
		if ($key === 'wafStatus')
			return 'disabled';
		return $default;
	}

	public function setConfig($key, $value, $category = '') {
	}

	public function unsetConfig($key, $category = '') {
	}

	public function uninstall() {
	}

	public function isInLearningMode() {
		return false;
	}

	public function isDisabled() {
		return true;
	}

	public function getRulesDSLCacheFile() {
		return null;
	}

	public function isAttackDataFull() {
		return false;
	}

	public function vacuum() {
	}

	public function getRules() {
		return array();
	}

	public function setRules($rules) {
	}

	public function needsInitialRules() {
		return false;
	}

	public function getDescription() {
		return 'Dummy Storage Engine';
	}

}