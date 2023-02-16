<?php

namespace WPML\ST\MO;

use WP_Locale;

class WPLocaleProxy {

	/**
	 * @var WP_Locale|null $wp_locale
	 */
	private $wp_locale;

	/**
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed|null
	 */
	public function __call( $method, array $args ) {
		if ( method_exists( $this->getWPLocale(), $method ) ) {
			return call_user_func_array( [ $this->getWPLocale(), $method ], $args );
		}

		return null;
	}

	/**
	 * @param string $property
	 *
	 * @return bool
	 */
	public function __isset( $property ) {
		if ( property_exists( \WP_Locale::class, $property ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $property
	 *
	 * @return mixed|null
	 */
	public function __get( $property ) {
		if ( $this->__isset( $property ) ) {
			return $this->getWPLocale()->{$property};
		}

		return null;
	}

	/**
	 * @return WP_Locale|null
	 */
	private function getWPLocale() {
		if ( ! $this->wp_locale ) {
			$this->wp_locale = new WP_Locale();
		}

		return $this->wp_locale;
	}
}
