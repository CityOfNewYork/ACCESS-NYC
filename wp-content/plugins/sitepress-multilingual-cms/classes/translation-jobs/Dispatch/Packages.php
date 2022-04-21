<?php

namespace WPML\TM\Jobs\Dispatch;

use WPML\Element\API\Languages;

class Packages extends Elements {

	public static function dispatch(
		callable $sendBatch,
		Messages $messages,
		callable $buildBatch,
		$data,
		$type = 'package'
	) {
		parent::dispatch( $sendBatch, $messages, $buildBatch, $data, $type );
	}

	protected static function filterElements( Messages $messages, $packagesData, $targetLanguages ) {

		$ignoredPackagesMessages = [];
		$packagesToTranslation   = [];

		foreach ( $packagesData as $packageId => $packageData ) {
			$packagesToTranslation[ $packageId ] = [
				'type'             => $packageData['type'],
				'target_languages' => []
			];

			$package     = apply_filters( 'wpml_get_translatable_item', null, $packageId, 'package' );
			$packageLang = apply_filters( 'wpml_language_for_element', Languages::getDefaultCode(), $package );

			foreach ( $targetLanguages as $language ) {
				if ( $packageLang === $language ) {
					$ignoredPackagesMessages [] = $messages->ignoreOriginalPackageMessage( $package, $language );
					continue;
				}

				if ( self::hasInProgressJob(
					$package->ID,
					$package->get_element_type_prefix() . '_' . $package->kind_slug,
					$language
				) ) {
					$ignoredPackagesMessages [] = $messages->ignoreInProgressPackageMessage( $package, $language );
					continue;
				}

				$packagesToTranslation[ $packageId ]['target_languages'] [] = $language;
			}
		}

		return [ $packagesToTranslation, $ignoredPackagesMessages ];
	}
}