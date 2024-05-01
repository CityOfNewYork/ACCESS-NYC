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
		$groupIds = wpml_collect( acf_get_field_groups( [ 'post_id' => $post->ID ] ) )
			->pluck( 'ID' )
			->toArray();
		$strings  = $this->getUntranslatedStrings( $groupIds, $targetLangCode );

		return $this->buildEntries( $package, $strings );
	}

	/**
	 * @param array $package
	 * @param array $strings
	 *
	 * @return array
	 */
	private function buildEntries( $package, $strings ) {
		foreach ( $strings as $groupId => $groupStrings ) {
			foreach ( $groupStrings as $name => $string ) {
				$package['contents'][ self::getFieldName( $groupId, $name ) ] = [
					'translate' => 1,
					'data'      => base64_encode( $string->value ),
					'format'    => 'base64',
				];
			}
		}

		return $package;
	}

	/**
	 * @param int    $groupId
	 * @param string $stringName
	 *
	 * @return string
	 */
	private static function getFieldName( $groupId, $stringName ) {
		return self::PREFIX . '-' . self::GROUP . '-' . $groupId . '-' . $stringName;
	}

	/**
	 * @param array  $groupIds
	 * @param string $languageCode
	 *
	 * @return array
	 */
	private function getUntranslatedStrings( $groupIds, $languageCode ) {
		$strings = [];

		foreach ( $groupIds as $groupId ) {
			$strings[ $groupId ] = $this->factory->createPackage( $groupId, Package::FIELD_GROUP_PACKAGE_KIND_SLUG )->getUntranslatedStrings( $languageCode );
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
			list( $groupId, $stringName ) = self::parseFieldName( $fieldName );

			if ( $groupId && $stringName ) {
				$allTranslations[ $groupId ][ $stringName ] = $getTranslationEntity( $field['data'] );
			}
		}

		foreach ( $allTranslations as $groupId => $translations ) {
			$this->factory->createPackage( $groupId, Package::FIELD_GROUP_PACKAGE_KIND_SLUG )->setStringTranslations( $translations );
		}
	}

	/**
	 * @param string   $fieldName
	 * @param int|null $groupId
	 *
	 * @return array
	 */
	public static function parseFieldName( $fieldName, $groupId = null ) {
		$mainPattern = '([^-]+)-(?:[^-]+)-([^-]+)-.*';

		if ( $groupId ) { // If the group ID is passed, we can use the short pattern.
			$matches = Str::match( '/^' . $mainPattern . '$/', $fieldName );

			$stringName = $fieldName;
			$namespace  = $matches[1] ?? null;
			$key        = $matches[2] ?? null;
		} else {
			$matches = Str::match( '/^' . self::PREFIX . '-' . self::GROUP . '-(\d+)-(' . $mainPattern . ')$/', $fieldName );

			$groupId    = $matches[1] ?? null;
			$stringName = $matches[2] ?? null;
			$namespace  = $matches[3] ?? null;
			$key        = $matches[4] ?? null;
		}

		return [
			$groupId,
			$stringName,
			$namespace,
			$key,
		];
	}

}
