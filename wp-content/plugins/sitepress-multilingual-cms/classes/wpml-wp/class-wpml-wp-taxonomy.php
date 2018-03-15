<?php
/**
 * Created by PhpStorm.
 * User: bruce
 * Date: 30/10/17
 * Time: 9:09 PM
 */

class WPML_WP_Taxonomy {

	public static function get_linked_post_types( $taxonomy ) {
		global $wp_taxonomies;

		$post_types = array();
		if ( isset( $wp_taxonomies[ $taxonomy ] ) && isset( $wp_taxonomies[ $taxonomy ]->object_type ) ) {
			$post_types = $wp_taxonomies[ $taxonomy ]->object_type;
		}

		return $post_types;
	}

}