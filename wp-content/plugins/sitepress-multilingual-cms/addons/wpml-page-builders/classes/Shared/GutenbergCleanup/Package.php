<?php

namespace WPML\PB\GutenbergCleanup;

use WPML\FP\Relation;

class Package {

	/**
	 * @param int $postId
	 *
	 * @return \WPML_Package|null
	 */
	public static function get( $postId ) {
		// $isGbPackage :: \WPML_Package -> bool
		$isGbPackage = Relation::propEq( 'kind_slug', 'gutenberg' );

		return wpml_collect( apply_filters( 'wpml_st_get_post_string_packages', [], $postId ) )
			->filter( $isGbPackage )
			->first();
	}

	/**
	 * @param \WPML_Package|null $package
	 */
	public static function delete( $package ) {
		if ( $package ) {
			do_action( 'wpml_delete_package', $package->name, $package->kind );
		}
	}
}
