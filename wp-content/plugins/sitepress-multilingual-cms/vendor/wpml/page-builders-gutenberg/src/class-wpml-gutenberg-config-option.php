<?php

use WPML\PB\Gutenberg\XPath;

/**
 * Class WPML_Gutenberg_Config_Option
 */
class WPML_Gutenberg_Config_Option {

	const OPTION = 'wpml-gutenberg-config';

	const SEARCH_METHOD_WILDCARD = 'wildcards';
	const SEARCH_METHOD_REGEX    = 'regex';

	/**
	 * @param array $config_data
	 */
	public function update_from_config( $config_data ) {
		$blocks = array();

		if ( isset( $config_data['wpml-config']['gutenberg-blocks']['gutenberg-block'] ) ) {
			foreach ( $config_data['wpml-config']['gutenberg-blocks']['gutenberg-block'] as $block_config ) {
				$blocks[ $block_config['attr']['type'] ] = array();

				if ( '1' === $block_config['attr']['translate'] ) {
					$blocks = $this->add_block_xpaths( $blocks, $block_config );
					$blocks = $this->add_block_attribute_keys( $blocks, $block_config );
				}
			}
		}

		update_option( self::OPTION, $blocks );
	}

	/**
	 * @param array $blocks
	 * @param array $block_config
	 *
	 * @return array
	 */
	private function add_block_xpaths( array $blocks, array $block_config ) {
		if ( isset( $block_config['xpath'] ) ) {
			$block_name                     = $block_config['attr']['type'];
			$blocks[ $block_name ]['xpath'] = array();

			foreach ( $this->normalize_key_data( $block_config['xpath'] ) as $xpaths ) {
				$xpaths                         = XPath::normalize( $xpaths );
				$blocks[ $block_name ]['xpath'] = array_merge( $blocks[ $block_name ]['xpath'], array_values( $xpaths ) );

			}
		}

		return $blocks;
	}

	/**
	 * @param array $blocks
	 * @param array $block_config
	 *
	 * @return array
	 */
	private function add_block_attribute_keys( array $blocks, array $block_config ) {
		if ( isset( $block_config['key'] ) ) {
			$blocks[ $block_config['attr']['type'] ]['key'] = $this->get_keys_recursively( $block_config['key'] );
		}

		return $blocks;
	}

	/**
	 * @param array $keys_config
	 *
	 * @return array
	 */
	private function get_keys_recursively( array $keys_config ) {
		$final_config = array();
		$keys_config  = $this->normalize_key_data( $keys_config );

		foreach ( $keys_config as $key_config ) {

			$partial_config = [];

			if ( isset( $key_config['attr']['search-method'] ) ) {
				$partial_config['search-method'] = $key_config['attr']['search-method'];
			}

			if ( isset( $key_config['key'] ) ) {
				$partial_config['children'] = $this->get_keys_recursively( $key_config['key'] );
			}

			$final_config = array_merge( $final_config, array( $key_config['attr']['name'] => $partial_config ) );
		}

		return $final_config;
	}

	/**
	 * If a sequence has only one element, we will wrap it
	 * in order to have the same data shape as for multiple elements.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function normalize_key_data( array $data ) {
		return isset( $data['value'] ) ? array( $data ) : $data;
	}

	public function get() {
		return get_option( self::OPTION, array() );
	}
}
