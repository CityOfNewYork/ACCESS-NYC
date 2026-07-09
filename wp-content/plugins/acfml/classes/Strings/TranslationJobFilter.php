<?php

namespace ACFML\Strings;

use WPML\FP\Str;

class TranslationJobFilter {

	const PREFIX = 'acfml';
	const GROUP  = 'group';

	/**
	 * @var Factory $factory
	 */
	private $factory;

	public function __construct( Factory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @param array    $package
	 * @param \WP_Post $post
	 * @param string   $targetLangCode
	 *
	 * @return array
	 */
	public function appendStrings( $package, $post, $targetLangCode ) {
		$groupKeys = wpml_collect( acf_get_field_groups( [ 'post_id' => $post->ID ] ) )
			->pluck( 'key' )
			->toArray();
		$strings   = $this->getUntranslatedStrings( $groupKeys, $targetLangCode );

		return $this->buildEntries( $package, $strings );
	}

	/**
	 * @param array $package
	 * @param array $strings
	 *
	 * @return array
	 */
	private function buildEntries( $package, $strings ) {
		foreach ( $strings as $groupKey => $groupStrings ) {
			foreach ( $groupStrings as $name => $string ) {
				$package['contents'][ self::getFieldName( $groupKey, $name ) ] = [
					'translate' => 1,
					'data'      => base64_encode( $string->value ),
					'format'    => 'base64',
				];
			}
		}

		return $package;
	}

	/**
	 * @param string $groupKey
	 * @param string $stringName
	 *
	 * @return string
	 */
	private static function getFieldName( $groupKey, $stringName ) {
		return self::PREFIX . '-' . self::GROUP . '-' . $groupKey . '-' . $stringName;
	}

	/**
	 * @param array  $groupKeys
	 * @param string $languageCode
	 *
	 * @return array
	 */
	private function getUntranslatedStrings( $groupKeys, $languageCode ) {
		$strings = [];

		foreach ( $groupKeys as $groupKey ) {
			$strings[ $groupKey ] = $this->factory->createPackage( $groupKey, Package::FIELD_GROUP_PACKAGE_KIND_SLUG )->getUntranslatedStrings( $languageCode );
		}

		return $strings;
	}

	/**
	 * @param array     $fields
	 * @param \stdClass $job
	 *
	 * @return void
	 */
	public function saveTranslations( $fields, $job ) {
		$allTranslations = [];

		$getTranslationEntity = function( $translationValue ) use ( $job ) {
			return [
				$job->language_code => [
					'value'  => $translationValue,
					'status' => ICL_STRING_TRANSLATION_COMPLETE,
				],
			];
		};

		foreach ( $fields as $fieldName => $field ) {
			list( $groupKey, $stringName ) = self::parseFieldName( $fieldName );

			if ( $groupKey && $stringName ) {
				$allTranslations[ $groupKey ][ $stringName ] = $getTranslationEntity( $field['data'] );
			}
		}

		foreach ( $allTranslations as $groupKey => $translations ) {
			$this->factory->createPackage( $groupKey, Package::FIELD_GROUP_PACKAGE_KIND_SLUG )->setStringTranslations( $translations );
		}
	}

	/**
	 * @param string      $fieldName
	 * @param string|null $groupKey
	 *
	 * @return array
	 */
	public static function parseFieldName( $fieldName, $groupKey = null ) {
		$mainPattern = '([^-]+)-(?:[^-]+)-([^-]+)-.*';

		if ( $groupKey ) { // If the group key is passed, we can use the short pattern.
			$matches = Str::match( '/^' . $mainPattern . '$/', $fieldName );

			$stringName = $fieldName;
			$namespace  = $matches[1] ?? null;
			$key        = $matches[2] ?? null;
		} else {
			$matches = Str::match( '/^' . self::PREFIX . '-' . self::GROUP . '-([^-]+)-(' . $mainPattern . ')$/', $fieldName );

			$groupKey   = $matches[1] ?? null;
			$stringName = $matches[2] ?? null;
			$namespace  = $matches[3] ?? null;
			$key        = $matches[4] ?? null;
		}

		return [
			$groupKey,
			$stringName,
			$namespace,
			$key,
		];
	}

}
