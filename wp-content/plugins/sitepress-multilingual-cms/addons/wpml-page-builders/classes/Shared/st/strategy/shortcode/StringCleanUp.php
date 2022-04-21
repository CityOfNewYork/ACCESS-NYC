<?php

namespace WPML\PB\Shortcode;

use WPML\FP\Fns;

class StringCleanUp {

	/* @var array */
	private $existingStrings;

	/* @var \WPML_PB_Shortcode_Strategy */
	private $shortcodeStrategy;

	/**
	 * StringCleanUp constructor.
	 *
	 * @param int                         $postId
	 * @param \WPML_PB_Shortcode_Strategy $shortcodeStrategy
	 */
	public function __construct( $postId, \WPML_PB_Shortcode_Strategy $shortcodeStrategy ) {
		$this->shortcodeStrategy = $shortcodeStrategy;
		$this->existingStrings   = $shortcodeStrategy->get_package_strings( $shortcodeStrategy->get_package_key( $postId ) );
	}

	/**
	 * @return array
	 */
	public function get() {
		return $this->existingStrings;
	}

	/**
	 * @param string $value
	 */
	public function remove( $value ) {
		unset( $this->existingStrings[ md5( $value ) ] );
	}

	public function cleanUp() {
		Fns::each( [ $this->shortcodeStrategy, 'remove_string' ], $this->existingStrings );
	}

}
