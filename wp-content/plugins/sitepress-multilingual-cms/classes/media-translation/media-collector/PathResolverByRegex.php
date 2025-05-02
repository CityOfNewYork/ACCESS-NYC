<?php

namespace WPML\MediaTranslation\MediaCollector;

class PathResolverByRegex implements PathResolverInterface {
	/** @var string */
	private $regex;

	/**
	 * PathResolverByRegex constructor.
	 *
	 * @param string $regex
	 */
	public function __construct( $regex ) {
		$this->regex = $regex;
	}

	/**
	 * @param mixed $data
	 *
	 * @return string|int
	 */
	public function getValue( $data ) {
		if ( ! is_string( $data ) ) {
			return '';
		}

		if ( ! preg_match( $this->regex, $data, $matches ) ) {
			return '';
		}

		return $matches[1];
	}

	/**
	 * @param mixed $data
	 *
	 * @return array
	 */
	public function resolvePath( $data ) {
		if ( ! is_string( $data ) ) {
			return [];
		}

		if ( ! preg_match_all( $this->regex, $data, $matches ) ) {
			return [];
		}

		return $matches[1];
	}
}

