<?php

class WPML_TM_Jobs_Needs_Update_Param {

	const INCLUDE_NEEDS_UPDATE = 'include';
	const EXCLUDE_NEEDS_UPDATE = 'exclude';

	/** @var string */
	private $value;

	/**
	 * @param string $value
	 */
	public function __construct( $value ) {
		if ( ! self::is_valid( $value ) ) {
			throw new \InvalidArgumentException( 'Invalid value of NEEDS_UPDATE param: ' . $value );
		}
		$this->value = $value;
	}

	/**
	 * @return bool
	 */
	public function is_needs_update_excluded() {
		return $this->value === self::EXCLUDE_NEEDS_UPDATE;
	}

	/**
	 * @return bool
	 */
	public function is_needs_update_included() {
		return $this->value === self::INCLUDE_NEEDS_UPDATE;
	}

	/**
	 * @param string $value
	 *
	 * @return bool
	 */
	public static function is_valid( $value ) {
		return in_array(
			(string) $value,
			[
				self::INCLUDE_NEEDS_UPDATE,
				self::EXCLUDE_NEEDS_UPDATE,
			],
			true
		);
	}
}
