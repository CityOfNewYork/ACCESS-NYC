<?php

namespace WPML\PB;

use WPML\FP\Fns;

/**
 * Class ShortCodesInGutenbergBlocks
 * @package WPML\PB
 *
 * This class is to handle an edge case when there is only one Gutenberg block
 * that contains one or more shortcodes.
 * In this case we need to force the Gutenberg processing as there will be
 * no Gutenberg strings and only shortcode strings.
 *
 */
class ShortCodesInGutenbergBlocks {

	const FORCED_GUTENBERG = 'Forced-Gutenberg';

	public static function recordPackage(
		\WPML_PB_String_Translation_By_Strategy $strategy,
		$strategyKind,
		\WPML_Package $package,
		$language
	) {
		if ( $strategyKind === 'Gutenberg' && $package->kind === 'Page Builder ShortCode Strings' ) {
			$package->kind = self::FORCED_GUTENBERG;
			$strategy->add_package_to_update_list( $package, $language );
		}

	}

	public static function fixupPackage( $package_data ) {
		if ( $package_data['package']->kind === self::FORCED_GUTENBERG ) {
			$package_data['package']->kind = 'Gutenberg';
		}

		return $package_data;
	}

	public static function normalizePackages( array $packagesToUpdate ) {
		if ( count( $packagesToUpdate ) > 1 ) {
			// If we have more than one package then we don't need to 'Force' it.
			// The normal Gutenberg package will update all translations correctly.
			$isForced         = function ( $package ) { return $package['package']->kind !== self::FORCED_GUTENBERG; };
			$packagesToUpdate = array_filter( $packagesToUpdate, $isForced );
		}

		return $packagesToUpdate;
	}
}
