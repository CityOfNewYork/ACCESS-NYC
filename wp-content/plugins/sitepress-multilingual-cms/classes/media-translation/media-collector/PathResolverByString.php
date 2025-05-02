<?php

namespace WPML\MediaTranslation\MediaCollector;

class PathResolverByString implements PathResolverInterface {
	/** @var string */
	private $string;

	/**
	 * PathResolverByString constructor.
	 *
	 * @param string $string
	 */
	public function __construct( $string ) {
		$this->string = $string;
	}

	/**
	 * @param mixed $data
	 *
	 * @return string|int
	 */
	public function getValue( $data ) {
		if ( ! is_object( $data ) && ! is_array( $data ) ) {
			return '';
		}

		$data = (object) $data;

		if ( ! isset( $data->{$this->string} ) ) {
			return '';
		}

		$data = $data->{$this->string};

		return is_string( $data ) || is_numeric( $data ) ? $data : '';
	}

	/**
	 * @param mixed $data
	 *
	 * @return string|array|object
	 */
	public function resolvePath( $data ) {
		if ( ! is_object( $data ) && ! is_array( $data ) ) {
			return [];
		}

		$data = (array) $data;
		if ( ! isset( $data[ $this->string ] ) ) {
			return [];
		}

		$data = $data[ $this->string ];

		return $data;
	}
}
