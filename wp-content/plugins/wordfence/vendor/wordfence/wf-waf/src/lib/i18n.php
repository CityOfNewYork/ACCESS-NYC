<?php

class wfWAFI18n {

	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @param string $text
	 * @return string
	 */
	public static function __($text) {
		return self::getInstance()->getI18nEngine()->__($text);
	}

	public static function esc_html__($text) {
		return htmlentities(self::__($text), ENT_QUOTES, 'UTF-8');
	}

	public static function esc_html_e($text) {
		echo self::esc_html__($text);
	}

	/**
	 * @return self
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self(new wfWAFI18nEngineDefault());
		}
		return self::$instance;
	}

	/**
	 * @param self $i18nEngine
	 */
	public static function setInstance($i18nEngine) {
		self::$instance = $i18nEngine;
	}

	/** @var wfWAFI18nEngine */
	private $i18nEngine;

	/**
	 * @param wfWAFI18nEngine $i18nEngine
	 */
	public function __construct($i18nEngine) {
		$this->i18nEngine = $i18nEngine;
	}

	/**
	 * @return wfWAFI18nEngine
	 */
	public function getI18nEngine() {
		return $this->i18nEngine;
	}

	/**
	 * @param wfWAFI18nEngine $i18nEngine
	 */
	public function setI18nEngine($i18nEngine) {
		$this->i18nEngine = $i18nEngine;
	}
}

class wfWAFI18nEngineDefault implements wfWAFI18nEngine {

	/**
	 * @param string $text
	 * @return string
	 */
	public function __($text) {
		return $text;
	}
}

interface wfWAFI18nEngine {

	/**
	 * @param string $text
	 * @return string
	 */
	public function __($text);

}