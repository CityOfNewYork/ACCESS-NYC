<?php

namespace ACFML\Strings;

use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Obj;

class Package {

	// Deprecated! Use one of the constants below.
	const KIND_SLUG = 'acf-field-group';

	const FIELD_GROUP_PACKAGE_KIND_SLUG = 'acf-field-group';
	const CPT_PACKAGE_KIND_SLUG         = 'acf-post-type-labels';
	const TAXONOMY_PACKAGE_KIND_SLUG    = 'acf-taxonomy-labels';
	const OPTION_PAGE_PACKAGE_KIND_SLUG = 'acf-options-page-labels';

	const STATUS_ST_INACTIVE          = 'st_inactive';
	const STATUS_NOT_REGISTERED       = 'not_registered';
	const STATUS_NOT_TRANSLATED       = 'not_translated';
	const STATUS_PARTIALLY_TRANSLATED = 'partially_translated';
	const STATUS_FULLY_TRANSLATED     = 'fully_translated';

	/**
	 * @var string|int $packageId
	 */
	private $packageId;

	/**
	 * @var string $kind
	 */
	private $kind;

	public function __construct( $packageId, $kind = self::FIELD_GROUP_PACKAGE_KIND_SLUG ) {
		$this->packageId = $packageId;
		$this->kind      = $kind;
	}

	/**
	 * The 'kind_slug' entry must be a sanitized version of the 'kind' entry,
	 * because the deletion process gets a 'kind' and builds a 'kind-slug' from it
	 * to identify the package to be deleted.
	 *
	 * @see WPML_Package_Helper::delete_package_action
	 * @see WPML_Package::sanitize_kind
	 *
	 * The 'kind-slug' entry must not match any registered post type;
	 * otherwise, if it is translatable, the Type column in TM will show the post type label
	 * and not the 'kind' entry defined here.
	 *
	 * For backward compatibility reasons, Field Groups match their 'kind' and 'kind-slug'
	 * to their related post types.
	 *
	 * @see WPML_TM_Dashboard_Document_Row::display
	 *
	 * @return array
	 */
	private function getPackageData() {
		switch ( $this->kind ) {
			case self::CPT_PACKAGE_KIND_SLUG:
				return [
					'kind'      => 'ACF Post Type Labels',
					'kind_slug' => self::CPT_PACKAGE_KIND_SLUG,
					'name'      => $this->packageId,
					'title'     => 'Post Type Labels for ' . $this->packageId,
				];
			case self::TAXONOMY_PACKAGE_KIND_SLUG:
				return [
					'kind'      => 'ACF Taxonomy Labels',
					'kind_slug' => self::TAXONOMY_PACKAGE_KIND_SLUG,
					'name'      => $this->packageId,
					'title'     => 'Taxonomy Labels for ' . $this->packageId,
				];
			case self::OPTION_PAGE_PACKAGE_KIND_SLUG:
				return [
					'kind'      => 'ACF Options Page Labels',
					'kind_slug' => self::OPTION_PAGE_PACKAGE_KIND_SLUG,
					'name'      => $this->packageId,
					'title'     => 'Options Page Labels for ' . $this->packageId,
				];
			default:
				return [
					'kind'      => 'ACF Field Group',
					'kind_slug' => self::FIELD_GROUP_PACKAGE_KIND_SLUG,
					'name'      => $this->packageId,
					'title'     => 'Field Group Labels ' . $this->packageId,
				];
		}
	}

	/**
	 * @param string $value
	 * @param array  $stringData
	 *
	 * @return void
	 */
	public function register( $value, $stringData ) {
		if ( $value ) {
			do_action( 'wpml_register_string', $value, self::getStringName( $value, $stringData ), $this->getPackageData(), Obj::prop( 'title', $stringData ), Obj::prop( 'type', $stringData ) );
		}
	}

	/**
	 * @return void
	 */
	public function recordRegisteredStrings() {
		do_action( 'wpml_start_string_package_registration', $this->getPackageData() );
	}

	/**
	 * @return void
	 */
	public function cleanupUnusedStrings() {
		do_action( 'wpml_delete_unused_package_strings', $this->getPackageData() );
	}

	/**
	 * @param string $value
	 * @param array  $stringData
	 *
	 * @return string
	 *
	 * phpcs:disable WordPress.WP.I18n
	 */
	public function translate( $value, $stringData ) {
		if ( $value ) {
			return apply_filters( 'wpml_translate_string', $value, self::getStringName( $value, $stringData ), $this->getPackageData() );
		}

		return $value;
	}

	/**
	 * @return void
	 */
	public function delete() {
		$packageData = $this->getPackageData();
		do_action( 'wpml_delete_package', $packageData['name'], $packageData['kind'] );
	}

	/**
	 * @param string $value
	 * @param array  $meta
	 *
	 * @return string
	 */
	private static function getStringName( $value, $meta ) {
		return $meta['namespace'] . '-' . $meta['id'] . '-' . $meta['key'] . '-' . md5( $value );
	}

	/**
	 * @return \WPML_Package
	 */
	private function getWpmlPackage() {
		return Factory::createWpmlPackage( $this->getPackageData() );
	}

	/**
	 * @param string $languageCode
	 *
	 * @return array
	 */
	public function getUntranslatedStrings( $languageCode ) {
		$package = $this->getWpmlPackage();
		$strings = $package->get_package_strings();
		if ( ! $strings ) {
			return [];
		}

		$translated = $package->get_translated_strings( [] );

		$results = [];

		foreach ( $strings as $string ) {
			if ( ! isset( $translated[ $string->name ][ $languageCode ] ) ) {
				$results[ $string->name ] = $string;
			}
		}

		return $results;
	}

	/**
	 * @param array $translations
	 *
	 * @return void
	 */
	public function setStringTranslations( $translations ) {
		do_action( 'wpml_set_translated_strings', $translations, $this->getPackageData() );
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		// $getPackageStringsCount :: void -> int
		$getPackageStringsCount = Fns::memorize( function() {
			$strings = $this->getWpmlPackage()->get_package_strings();
			return is_array( $strings ) ? count( $strings ) : 0;
		} );

		// $getTranslatedStrings :: void -> array
		$getTranslatedStrings = Fns::memorize( function() {
			return $this->getWpmlPackage()->get_translated_strings( [] );
		} );

		// $isPartiallyTranslated :: void -> bool
		$isPartiallyTranslated = function() use ( $getTranslatedStrings ) {
			$translatedStrings   = $getTranslatedStrings();
			$secondaryLangsCount = count( Languages::getSecondaries() );

			foreach ( $translatedStrings as $translatedStringGroup ) {
				if ( count( $translatedStringGroup ) < $secondaryLangsCount ) {
					return true;
				}
			}

			return false;
		};

		if ( ! defined( 'WPML_ST_VERSION' ) ) {
			return self::STATUS_ST_INACTIVE;
		} elseif ( ! $getPackageStringsCount() ) {
			return self::STATUS_NOT_REGISTERED;
		} elseif ( ! $getTranslatedStrings() ) {
			return self::STATUS_NOT_TRANSLATED;
		} elseif ( $isPartiallyTranslated() ) {
			return self::STATUS_PARTIALLY_TRANSLATED;
		}

		return self::STATUS_FULLY_TRANSLATED;
	}

	/**
	 * @param  string|int $packageId
	 * @param  string     $kind
	 *
	 * @return Package
	 */
	public static function create( $packageId, $kind = self::FIELD_GROUP_PACKAGE_KIND_SLUG ) {
		return new self( $packageId, $kind );
	}

	/**
	 * @param  string $status
	 *
	 * @return string
	 */
	public static function status2text( $status ) {
		switch ( $status ) {
			case self::STATUS_FULLY_TRANSLATED:
				$text = __( 'Complete', 'sitepress' );
				break;
			case self::STATUS_PARTIALLY_TRANSLATED:
				$text = __( 'In progress', 'sitepress' );
				break;
			default:
				$text = __( 'Not translated', 'sitepress' );
		}

		return $text;
	}

}
