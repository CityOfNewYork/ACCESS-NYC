<?php

namespace WPML\PB\Elementor\Helper;

class Node {

	/**
	 * @param array $element
	 *
	 * @return bool
	 */
	public static function isTranslatable( $element ) {
		return isset( $element['elType'] ) && in_array( $element['elType'], [ 'widget', 'container' ], true );
	}

	/**
	 * @param array $element
	 *
	 * @return bool
	 */
	public static function hasChildren( $element ) {
		return isset( $element['elements'] ) && count( $element['elements'] );
	}
}
