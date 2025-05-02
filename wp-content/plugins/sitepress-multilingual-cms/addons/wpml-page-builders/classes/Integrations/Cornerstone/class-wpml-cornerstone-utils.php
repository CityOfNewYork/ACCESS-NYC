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
		'layout-modal',
		'layout-off-canvas',
		'layout-slide-container',
		'layout-slide',
		'layout-dropdown',
	];

	const NODES_WITH_MODULES = [
		'accordion',
		'accordion-item-elements',
		'tabs',
		'tab-elements',
		'nav-inline',
	];

	/**
	 * @param array $data The data to generate the ID from
	 *
	 * @return string MD5 hash of the serialized data
	 */
	public static function getNodeId( $data ) {
		return md5( serialize( $data ) );
	}

	/**
	 * @param string $type The type to check
	 *
	 * @return bool True if the type is a layout type, false otherwise
	 */
	public static function typeIsLayout( $type ) {
		// Remove the classic prefix before checking.
		$type = preg_replace( '/^' . self::MODULE_TYPE_PREFIX . '/', '', $type );

		return in_array( $type, self::getLayoutTypes(), true );
	}

	/**
	 * Gets the filtered list of layout types.
	 *
	 * @return array List of supported layout types
	 */
	public static function getLayoutTypes() {
		/**
		 * Allows modification of the supported layout types for Cornerstone page builder elements.
		 *
		 * * @since 2.2.2
		 *
		 * @param array $layout_types {
		 *     Array of supported layout type strings.
		 *
		 *     @type string $layout_type Layout type identifier (e.g., 'bar', 'container', 'section', etc.).
		 * }
		 *
		 * @see wpmlpb-376
		 */
		return (array) apply_filters( 'wpml_cornerstone_layout_types', self::LAYOUT_TYPES );
	}

	/**
	 * Gets the filtered list of node types that can contain modules.
	 *
	 * @return array List of node types that can contain modules
	 */
	public static function getNodesWithModules() {
		/**
		 * Filters the list of Cornerstone node types that can contain modules.
		 *
		 * @since 2.2.2
		 *
		 * @param array $nodes_with_modules {
		 *     Array of node type strings that can contain modules.
		 *
		 *     @type string $node_type Node type identifier (e.g., 'accordion', 'tabs', 'nav-inline', etc.).
		 * }
		 *
		 * @see wpmlpb-376
		 */
		return (array) apply_filters( 'wpml_cornerstone_nodes_with_modules', self::NODES_WITH_MODULES );
	}

	/**
	 * @param string $type The node type to check
	 *
	 * @return bool True if the node type should be checked for submodules, false otherwise
	 */
	public static function shouldCheckForSubmodules( $type ) {
		$shouldCheckForSubmodules = in_array( $type, self::getNodesWithModules() );

		/**
		 * Filters whether a Cornerstone node type should be checked for submodules.
		 *
		 * @since 2.2.2
		 *
		 * @param bool   $shouldCheckForSubmodules Whether to check for submodules.
		 * @param string $type                     The node type being checked.
		 *
		 * @see wpmlpb-376
		 */
		return apply_filters( 'wpml_cornerstone_should_check_for_submodules', $shouldCheckForSubmodules, $type );
	}

}
