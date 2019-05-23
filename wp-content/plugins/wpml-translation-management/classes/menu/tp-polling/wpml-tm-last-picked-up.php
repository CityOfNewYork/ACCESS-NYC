<?php

/**
 * Class WPML_TM_Last_Picked_Up
 */
class WPML_TM_Last_Picked_Up {

	/**
	 * @var Sitepress $sitepress
	 */
	private $sitepress;

	/**
	 * WPML_TM_Last_Picked_Up constructor.
	 *
	 * @param Sitepress $sitepress
	 */
	public function __construct( $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * Get last_picked_up setting.
	 *
	 * @return bool|mixed
	 */
	public function get() {
		return $this->sitepress->get_setting( 'last_picked_up' );
	}

	/**
	 * Get last_picked_up setting as formatted string.
	 *
	 * @param string $format
	 *
	 * @return string
	 */
	public function get_formatted( $format = 'Y, F jS @g:i a' ) {
		$last_picked_up      = $this->get();
		$last_time_picked_up = ! empty( $last_picked_up ) ?
			date_i18n( $format, $last_picked_up ) :
			__( 'never', 'wpml-translation-management' );

		return $last_time_picked_up;
	}

	/**
	 * Set last_picked_up setting.
	 */
	public function set() {
		$this->sitepress->set_setting( 'last_picked_up', current_time( 'timestamp' ), true );
	}
}
