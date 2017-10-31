<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Settings_Filters {
	/**
	 * @param array $types
	 *
	 * @param array $read_only_cpt_settings
	 *
	 * @return array
	 * @see \WPML_Config::maybe_add_filter
	 *
	 */
	function get_translatable_documents( array $types, array $read_only_cpt_settings ) {
		global $wp_post_types;
		foreach ( $read_only_cpt_settings as $cp => $translate ) {
			if ( $translate && ! isset( $types[ $cp ] ) && isset( $wp_post_types[ $cp ] ) ) {
				$types[ $cp ] = $wp_post_types[ $cp ];
			} elseif ( ! $translate && isset( $types[ $cp ] ) ) {
				unset( $types[ $cp ] );
			}
		}

		return $types;
	}

}