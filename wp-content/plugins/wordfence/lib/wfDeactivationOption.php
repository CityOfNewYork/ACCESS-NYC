<?php

class wfDeactivationOption {

	const RETAIN = 'retain';
	const DELETE_MAIN = 'delete-main';
	const DELETE_LOGIN_SECURITY = 'delete-wfls';
	const DELETE_ALL = 'delete-all';

	private static $options = array();

	private $key;
	private $label;
	private $deleteMain, $deleteLoginSecurity;

	private function __construct($key, $label, $deleteMain, $deleteLoginSecurity) {
		$this->key = $key;
		$this->label = $label;
		$this->deleteMain = $deleteMain;
		$this->deleteLoginSecurity = $deleteLoginSecurity;
	}

	public function getKey() {
		return $this->key;
	}

	public function getLabel() {
		return $this->label;
	}

	public function deletesMain() {
		return $this->deleteMain;
	}

	public function deletesLoginSecurity() {
		return $this->deleteLoginSecurity;
	}

	public function matchesState($deleteMain, $deleteLoginSecurity) {
		return $deleteMain === $this->deleteMain && $deleteLoginSecurity === $this->deleteLoginSecurity;
	}

	private static function registerOption($option) {
		self::$options[$option->getKey()] = $option;
	}

	private static function initializeOptions() {
		if (empty(self::$options)) {
			$options = array(
				new self(self::RETAIN, __('Keep all Wordfence tables and data', 'wordfence'), false, false),
				new self(self::DELETE_MAIN, __('Delete Wordfence tables and data, but keep Login Security tables and 2FA codes', 'wordfence'), true, false),
				new self(self::DELETE_LOGIN_SECURITY, __('Delete Login Security tables and 2FA codes, but keep Wordfence tables and data', 'wordfence'), false, true),
				new self(self::DELETE_ALL, __('Delete all Wordfence tables and data', 'wordfence'), true, true)
			);
			foreach ($options as $option)
				self::registerOption($option);
		}
	}

	public static function getAll() {
		self::initializeOptions();
		return self::$options;
	}

	public static function forKey($key) {
		self::initializeOptions();
		return array_key_exists($key, self::$options) ? self::$options[$key] : null;
	}

	public static function forState($deleteMain, $deleteLoginSecurity) {
		foreach (self::getAll() as $option) {
			if ($option->matchesState($deleteMain, $deleteLoginSecurity))
				return $option;
		}
		return null;
	}

}