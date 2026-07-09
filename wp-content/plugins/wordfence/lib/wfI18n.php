<?php

class wfI18nException extends Exception {
}

class wfI18n {

	const WAF_CLASS = "wfWAFI18n";

	private static function invokeWafMethod($method, ...$arguments) {
		if (!class_exists(self::WAF_CLASS))
			throw new wfI18nException("WAF I18n class does not exist");
		if (!method_exists(self::WAF_CLASS, $method))
			throw new wfI18nException("WAF method does not exist: {$method}");
		return call_user_func([self::WAF_CLASS, $method], ...$arguments);
	}

	public static function __($text) {
		try {
			return self::invokeWafMethod("__", $text);
		}
		catch (wfI18nException $e) {
			// Fall back to returning original text if WAF functionality is not present
			return $text;
		}
	}

	public static function esc_html__($text) {
		return htmlentities(self::__($text), ENT_QUOTES, 'UTF-8');
	}

	public static function esc_html_e($text) {
		echo self::esc_html__($text);
	}

}