<?php

namespace WordfenceLS\Text;

/**
 * Represents text that is already JavaScript-safe and should not be encoded again.
 * @package Wordfence2FA\Text
 */
class Model_JavaScript {
	private $_javaScript;
	
	/**
	 * Returns a string escaped for use in JavaScript. This is almost identical in behavior to esc_js except that
	 * we don't call _wp_specialchars and keep \r rather than stripping it.
	 * 
	 * @param string|Model_JavaScript $content
	 * @return string
	 */
	public static function esc_js($content) {
		if (is_object($content) && ($content instanceof Model_HTML)) {
			return (string) $content;
		}
		
		$safe_text = wp_check_invalid_utf8($content);
		$safe_text = preg_replace('/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes($safe_text));
		$safe_text = str_replace("\r", '\\r', $safe_text);
		$safe_text = str_replace("\n", '\\n', addslashes($safe_text));
		return apply_filters('js_escape', $safe_text, $content);
	}

	public static function echo_string_literal($string) {
		echo "'" . self::esc_js($string) . "'";
	}

	public function __construct($javaScript) {
		$this->_javaScript = $javaScript;
	}
	
	public function __toString() {
		return $this->_javaScript;
	}
}