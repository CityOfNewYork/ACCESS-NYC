<?php

namespace WPML\TM\Jobs\Dispatch;

use WPML\Element\API\Languages;
use WPML\TM\API\Jobs;

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

	protected static function filterElements( Messages $messages, $packagesData, $targetLanguages, $howToHandleExisting, $translateAutomatically ) {

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

				$job = Jobs::getElementJob(
					(int) $package->ID,
					(string) $package->get_element_type_prefix() . '_' . $package->kind_slug,
					(string) $language
				);

				if ( $job && ( self::isProgressJob( $job ) || self::shouldJobBeIgnoredBecauseIsCompleted( $job, $howToHandleExisting, $translateAutomatically) ) ) {
					continue;
				}

				$packagesToTranslation[ $packageId ]['target_languages'] [] = $language;
			}
		}

		return [ $packagesToTranslation, $ignoredPackagesMessages ];
	}
}
