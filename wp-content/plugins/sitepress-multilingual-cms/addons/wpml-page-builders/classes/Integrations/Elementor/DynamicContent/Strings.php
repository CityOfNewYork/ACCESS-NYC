<?php

namespace WPML\PB\Elementor\DynamicContent;

use WPML\Collect\Support\Collection;
use WPML_Elementor_Translatable_Nodes;
use WPML_PB_String;

class Strings {

	const KEY_SETTINGS = WPML_Elementor_Translatable_Nodes::SETTINGS_FIELD;
	const KEY_DYNAMIC  = '__dynamic__';
	const KEY_NODE_ID  = 'id';
	const KEY_ITEM_ID  = '_id';

	const SETTINGS_REGEX        = '/settings="(.*?(?="]))/';
	const NAME_PREFIX           = 'dynamic';
	const DELIMITER             = '-';
	const TRANSLATABLE_SETTINGS = [
		'before',
		'after',
		'fallback',
	];

	/**
	 * Remove the strings overwritten with dynamic content
	 * and add the extra strings "before", "after" and "fallback".
	 *
	 * @param WPML_PB_String[] $strings
	 * @param string           $nodeId
	 * @param array            $element
	 *
	 * @return WPML_PB_String[]
	 */
	public static function filter( array $strings, $nodeId, array $element ) {

		$dynamicFields = self::getDynamicFields( $element );

		$updateFromDynamicFields = function( WPML_PB_String $string ) use ( &$dynamicFields ) {
			$matchingField = $dynamicFields->first(
				function( Field $field ) use ( $string ) {
					return $field->isMatchingStaticString( $string );
				}
			);

			if ( $matchingField ) {
				return self::addBeforeAfterAndFallback( wpml_collect( [ $dynamicFields->pull( $dynamicFields->search( $matchingField ) ) ] ) );
			}

			return $string;
		};

		return wpml_collect( $strings )
			->map( $updateFromDynamicFields )
			->merge( self::addBeforeAfterAndFallback( $dynamicFields ) )
			->flatten()
			->toArray();
	}

	/**
	 * @param array $element
	 *
	 * @return Collection
	 */
	private static function getDynamicFields( array $element ) {
		if ( self::isModuleWithItems( $element ) ) {
			return self::getDynamicFieldsForModuleWithItems( $element );
		} elseif ( isset( $element[ self::KEY_SETTINGS ][ self::KEY_DYNAMIC ] ) ) {
			return self::getFields(
				$element[ self::KEY_SETTINGS ][ self::KEY_DYNAMIC ],
				$element[ self::KEY_NODE_ID ]
			);
		}

		return wpml_collect();
	}

	/**
	 * @param array $element
	 *
	 * @return Collection
	 */
	private static function getDynamicFieldsForModuleWithItems( array $element ) {
		$isDynamic = function( $item ) {
			return isset( $item[ self::KEY_DYNAMIC ] );
		};
		$getFields = function( array $item ) use ( $element ) {
			return self::getFields(
				$item[ self::KEY_DYNAMIC ],
				$element[ self::KEY_NODE_ID ],
				$item[ self::KEY_ITEM_ID ]
			);
		};

		return wpml_collect( reset( $element[ self::KEY_SETTINGS ] ) )
			->filter( $isDynamic )
			->map( $getFields )
			->flatten();
	}

	/**
	 * @param array  $data
	 * @param string $nodeId
	 * @param string $itemId
	 *
	 * @return Collection
	 */
	private static function getFields( array $data, $nodeId, $itemId = '' ) {
		$buildField = function( $tagValue, $tagKey ) use ( $nodeId, $itemId ) {
			return new Field( $tagValue, $tagKey, $nodeId, $itemId );
		};

		return wpml_collect( $data )->map( $buildField );
	}

	/**
	 * @param array $element
	 *
	 * @return bool
	 */
	private static function isModuleWithItems( array $element ) {
		if ( isset( $element[ self::KEY_SETTINGS ] ) ) {
			$firstSettingElement = reset( $element[ self::KEY_SETTINGS ] );
			return is_array( $firstSettingElement ) && 0 === key( $firstSettingElement );
		}

		return false;
	}

	/**
	 * @param Collection $dynamicFields
	 *
	 * @return Collection
	 */
	private static function addBeforeAfterAndFallback( Collection $dynamicFields ) {
		$dynamicFieldToSettingStrings = function( Field $field ) {
			preg_match( self::SETTINGS_REGEX, $field->tagValue, $matches );

			$isTranslatableSetting = function( $value, $settingField ) {
				return $value && is_string( $value ) && in_array( $settingField, self::TRANSLATABLE_SETTINGS, true );
			};

			$buildStringFromSetting = function( $value, $settingField ) use ( $field ) {
				return new WPML_PB_String(
					$value,
					self::getStringName( $field->nodeId, $field->itemId, $field->tagKey, $settingField ),
					sprintf( __( 'Dynamic content string: %s', 'sitepress' ), $field->tagKey ),
					'LINE'
				);
			};

			return wpml_collect( isset( $matches[1] ) ? self::decodeSettings( $matches[1] ) : [] )
				->filter( $isTranslatableSetting )
				->map( $buildStringFromSetting );
		};

		return $dynamicFields->map( $dynamicFieldToSettingStrings );
	}

	/**
	 * @param array          $element
	 * @param WPML_PB_String $string
	 *
	 * @return array
	 */
	public static function updateNode( array $element, WPML_PB_String $string ) {
		$stringNameParts = explode( self::DELIMITER, $string->get_name() );

		if ( count( $stringNameParts ) !== 5 || self::NAME_PREFIX !== $stringNameParts[0] ) {
			return $element;
		}

		list( , , $itemId, $dynamicField, $settingField ) = $stringNameParts;

		if ( $itemId && self::isModuleWithItems( $element ) ) {
			$element = self::updateNodeWithItems( $element, $string, $stringNameParts );
		} elseif ( isset( $element[ self::KEY_SETTINGS ][ self::KEY_DYNAMIC ][ $dynamicField ] ) ) {
			$element[ self::KEY_SETTINGS ][ self::KEY_DYNAMIC ][ $dynamicField ] = self::replaceSettingString(
				$element[ self::KEY_SETTINGS ][ self::KEY_DYNAMIC ][ $dynamicField ],
				$string,
				$settingField
			);
		}

		return $element;
	}

	/**
	 * @param string         $encodedSettings
	 * @param WPML_PB_String $string
	 * @param string         $settingField
	 *
	 * @return string|null
	 */
	private static function replaceSettingString( $encodedSettings, WPML_PB_String $string, $settingField ) {
		$replace = function( array $matches ) use ( $string, $settingField ) {
			$settings                  = self::decodeSettings( $matches[1] );
			$settings[ $settingField ] = $string->get_value();
			$replace                   = urlencode( json_encode( $settings ) );

			return str_replace( $matches[1], $replace, $matches[0] );
		};

		return preg_replace_callback( self::SETTINGS_REGEX, $replace, $encodedSettings );
	}

	/**
	 * @param array          $element
	 * @param WPML_PB_String $string
	 * @param array          $stringNameParts
	 *
	 * @return array
	 */
	private static function updateNodeWithItems( array $element, WPML_PB_String $string, array $stringNameParts ) {
		list( , , $itemId, $dynamicField, $settingField ) = $stringNameParts;

		$items   = wpml_collect( reset( $element[ self::KEY_SETTINGS ] ) );
		$mainKey = key( $element[ self::KEY_SETTINGS ] );

		$replaceStringInItem = function( array $item ) use ( $itemId, $string, $dynamicField, $settingField ) {
			if (
				isset( $item[ self::KEY_DYNAMIC ][ $dynamicField ], $item[ self::KEY_ITEM_ID ] )
				&& $item[ self::KEY_ITEM_ID ] === $itemId
			) {
				$item[ self::KEY_DYNAMIC ][ $dynamicField ] = self::replaceSettingString( $item[ self::KEY_DYNAMIC ][ $dynamicField ], $string, $settingField );
			}

			return $item;
		};

		$element[ self::KEY_SETTINGS ][ $mainKey ] = $items->map( $replaceStringInItem )->toArray();

		return $element;
	}

	/**
	 * @param string $settingsString
	 *
	 * @return array
	 */
	private static function decodeSettings( $settingsString ) {
		return json_decode( urldecode( $settingsString ), true );
	}

	/**
	 * @param string $nodeId
	 * @param string $itemId
	 * @param string $tagKey
	 * @param string $settingField
	 *
	 * @return string
	 */
	public static function getStringName( $nodeId, $itemId, $tagKey, $settingField ) {
		return self::NAME_PREFIX . self::DELIMITER
			. $nodeId . self::DELIMITER
			. $itemId . self::DELIMITER
			. $tagKey . self::DELIMITER
			. $settingField;
	}
}
