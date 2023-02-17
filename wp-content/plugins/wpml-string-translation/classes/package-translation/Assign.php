<?php

namespace WPML\ST\PackageTranslation;

class Assign {
	/**
	 * Assign all strings from specified domain to existing package.
	 *
	 * @param  string $domainName
	 * @param  int    $packageId
	 *
	 * @since 3.1.0
	 */
	public static function stringsFromDomainToExistingPackage( $domainName, $packageId ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'icl_strings',
			[ 'string_package_id' => $packageId ],
			[ 'context' => $domainName ]
		);
	}

	/**
	 * Assign all strings from specified domain to new package which is created on fly.
	 *
	 * @param  string $domainName
	 * @param  array  $packageData  {
	 *
	 * @type string $kind_slug e.g. toolset_forms
	 * @type string $kind e.g. "Toolset forms"
	 * @type string $name e.g. "1"
	 * @type string $title e.g. "Form 1"
	 * @type string $edit_link URL to edit page of resource
	 * @type string $view_link (Optional) Url to frontend view page of resource
	 * @type int $page_id (optional)
	 * }
	 * @since 3.1.0
	 */
	public static function stringsFromDomainToNewPackage( $domainName, array $packageData ) {
		$packageId = \WPML_Package_Helper::create_new_package( new \WPML_Package( $packageData ) );
		if ( $packageId ) {
			self::stringsFromDomainToExistingPackage( $domainName, $packageId );
		}
	}
}
