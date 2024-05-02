<?php

namespace WPML\PB\Cornerstone;

class Utils {

	const MODULE_TYPE_PREFIX = 'classic:';
	const LAYOUT_TYPES       = [
		'bar',
		'container',
		'section',
		'row',
		'column',
		'layout-row',
		'layout-column',
		'layout-grid',
		'layout-cell',
		'layout-div',
	];

	/**
	 * @param array $data
	 * @return string
	 */
	public static function getNodeId( $data ) {
		return md5( serialize( $data ) );
	}

	/**
	 * Check if the type is a layout type.
	 *
	 * @param string $type The type to check.
	 * @return bool
	 */
	public static function typeIsLayout( $type ) {
		// Remove the classic prefix before checking.
		$type = preg_replace( '/^' . self::MODULE_TYPE_PREFIX . '/', '', $type );

		return in_array( $type, self::LAYOUT_TYPES, true );
	}

}
