<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

use WPML\FP\Obj;

class Attributes extends Base {

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	public function find( \WP_Block_Parser_Block $block ) {
		$strings = [];
		$attrs   = $this->getAttributes( $block );

		if ( $attrs ) {
			$keys    = $this->getKeyConfig( $block );
			$strings = $this->findStringsRecursively( $attrs, $keys, $block );
		}

		return $strings;
	}

	/**
	 * @param array                  $attrs
	 * @param array                  $config_keys
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	private function findStringsRecursively( array $attrs, array $config_keys, \WP_Block_Parser_Block $block ) {
		$strings = [];

		foreach ( $attrs as $attr_key => $attr_value ) {
			$matching_key = $this->getMatchingConfigKey( $attr_key, $config_keys );

			if ( ! $matching_key ) {
				continue;
			}

			if ( $this->hasJsonEncoding( $attr_key, $config_keys ) ) {
				$attr_value = json_decode( urldecode( $attr_value ), true );
			}

			if ( is_array( $attr_value ) ) {
				$children_config_keys = $this->getChildrenConfigKeys( $config_keys, $matching_key );

				$strings = array_merge(
					$strings,
					$this->findStringsRecursively( $attr_value, $children_config_keys, $block )
				);
			} elseif ( ! is_numeric( $attr_value ) ) {
				$type      = self::get_string_type( $attr_value );
				$string_id = $this->get_string_id( $block->blockName, $attr_value );
				$label     = isset( $config_keys[ $attr_key ]['label'] ) ? $config_keys[ $attr_key ]['label'] : $this->get_block_label( $block );
				$strings[] = $this->build_string( $string_id, $label, $attr_value, $type );
			}
		}

		return $strings;
	}

	/**
	 * @param string $attr_key
	 * @param array  $config_keys
	 *
	 * @return string|null
	 */
	private function getMatchingConfigKey( $attr_key, array $config_keys ) {
		if ( isset( $config_keys[ $attr_key ] ) ) {
			return $attr_key;
		}

		/**
		 * If we don't find an exactly matching key,
		 * we'll try to find a key with a wildcard or a regex.
		 */
		foreach ( $config_keys as $config_key => $key_attrs ) {

			if ( preg_match( $this->getRegex( $config_key, $key_attrs ), $attr_key ) ) {
				return $config_key;
			}
		}

		return null;
	}

	/**
	 * @param array  $config_keys
	 * @param string $matching_key
	 *
	 * @return array
	 */
	private function getChildrenConfigKeys( array $config_keys, $matching_key ) {
		return isset( $config_keys[ $matching_key ]['children'] )
			? $config_keys[ $matching_key ]['children']
			: $this->getMatchAllKey();
	}

	/**
	 * If the config key is not already a regex
	 * we will replace the wildcard (*) and make it a valid regex.
	 *
	 * @param string $config_key
	 * @param array  $key_attrs
	 *
	 * @return string
	 */
	private function getRegex( $config_key, array $key_attrs ) {
		if ( $this->isRegex( $key_attrs ) ) {
			return $config_key;
		}

		return self::getWildcardRegex( $config_key );
	}

	/**
	 * @param string $config_key
	 *
	 * @return string
	 */
	public static function getWildcardRegex( $config_key ) {
		return '/^' . str_replace( '*', 'S+', preg_quote( $config_key, '/' ) ) . '$/';;
	}

	/**
	 * @param array $key_attrs
	 *
	 * @return bool
	 */
	private function isRegex( array $key_attrs ) {
		return isset( $key_attrs['search-method'] )
			   && \WPML_Gutenberg_Config_Option::SEARCH_METHOD_REGEX === $key_attrs['search-method'];
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param array                  $string_translations
	 * @param string                 $lang
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function update( \WP_Block_Parser_Block $block, array $string_translations, $lang ) {
		$attrs = $this->getAttributes( $block );

		if ( $attrs ) {
			$keys         = $this->getKeyConfig( $block );
			$block->attrs = $this->updateStringsRecursively( $attrs, $keys, $string_translations, $lang, $block->blockName );
		}

		return $block;
	}

	/**
	 * @param array  $attrs
	 * @param array  $config_keys
	 * @param array  $translations
	 * @param string $lang
	 * @param string $block_name
	 *
	 * @return array
	 */
	public function updateStringsRecursively( array $attrs, array $config_keys, array $translations, $lang, $block_name ) {
		foreach ( $attrs as $attr_key => $attr_value ) {
			$matching_key = $this->getMatchingConfigKey( $attr_key, $config_keys );

			if ( ! $matching_key ) {
				continue;
			}

			if ( $this->hasJsonEncoding( $attr_key, $config_keys ) ) {
				$attr_value = json_decode( urldecode( $attr_value ), true );
			}

			if ( is_array( $attr_value ) ) {
				$children_config_keys = $this->getChildrenConfigKeys( $config_keys, $matching_key );
				$attrs[ $attr_key ]   = $this->updateStringsRecursively( $attr_value, $children_config_keys, $translations, $lang, $block_name );
			} else {
				$string_id = $this->get_string_id( $block_name, $attr_value );

				if (
					isset( $translations[ $string_id ][ $lang ] ) &&
					ICL_TM_COMPLETE === (int) $translations[ $string_id ][ $lang ]['status']
				) {
					$attrs[ $attr_key ] = $translations[ $string_id ][ $lang ]['value'];
				}
			}

			if ( $this->hasJsonEncoding( $attr_key, $config_keys ) ) {
				$attrs[ $attr_key ] = rawurlencode( wp_json_encode( $attrs[ $attr_key ] ) );
			}
		}

		return $attrs;
	}

	/**
	 * @param array $attr_key
	 * @param array $config_keys
	 *
	 * @retrun bool
	 */
	private function hasJsonEncoding( $attr_key, $config_keys ) {
		return 'json' === Obj::path( [ $attr_key, 'encoding' ], $config_keys );
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	private function getAttributes( \WP_Block_Parser_Block $block ) {
		return is_array( $block->attrs ) && $block->blockName ? $block->attrs : [];
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	private function getKeyConfig( \WP_Block_Parser_Block $block ) {
		$config = $this->get_block_config( $block, 'key' );

		return $config ? $config : [];
	}

	/**
	 * @return array
	 */
	private function getMatchAllKey() {
		return [
			'*' => [
				'search-method' => \WPML_Gutenberg_Config_Option::SEARCH_METHOD_WILDCARD,
			],
		];
	}
}
