<?php

use WPML\FP\Obj;
use WPML\PB\ConvertIds\Helper as ConvertIdsHelper;
use WPML\PB\Gutenberg\XPath;

/**
 * Class WPML_Gutenberg_Config_Option
 */
class WPML_Gutenberg_Config_Option {

	const OPTION               = 'wpml-gutenberg-config';
	const OPTION_IDS_IN_BLOCKS = 'wpml-gutenberg-config-ids-in-blocks';

	const SEARCH_METHOD_WILDCARD = 'wildcards';
	const SEARCH_METHOD_REGEX    = 'regex';

	/**
	 * @param array $config_data
	 */
	public function update_from_config( $config_data ) {
		$blocks        = [];
		$ids_in_blocks = [];

		if ( isset( $config_data['wpml-config']['gutenberg-blocks']['gutenberg-block'] ) ) {
			foreach ( $config_data['wpml-config']['gutenberg-blocks']['gutenberg-block'] as $block_config ) {
				$block_name            = self::get_block_name( $block_config );
				$blocks[ $block_name ] = [];

				if ( '1' === $block_config['attr']['translate'] ) {
					$blocks = $this->add_block_xpaths( $blocks, $block_config );
					$blocks = $this->add_block_attribute_keys( $blocks, $block_config );
					$blocks = $this->add_block_label( $blocks, $block_config );

					if ( ! $blocks[ $block_name ] ) {
						unset( $blocks[ $block_name ] );
					}
				}

				$ids_in_blocks = $this->add_ids_in_block_xpath( $ids_in_blocks, $block_config );
				$ids_in_blocks = $this->add_ids_in_block_keys( $ids_in_blocks, $block_config );
			}
		}

		update_option( self::OPTION, $blocks, 'no' );
		update_option( self::OPTION_IDS_IN_BLOCKS, $ids_in_blocks, 'yes' );
	}

	/**
	 * @param array $block_config
	 *
	 * @return string|null
	 */
	public static function get_block_name( array $block_config ) {
		return Obj::path( [ 'attr', 'type' ], $block_config );
	}

	/**
	 * @param array $blocks
	 * @param array $block_config
	 *
	 * @return array
	 */
	private function add_block_xpaths( array $blocks, array $block_config ) {
		if ( isset( $block_config['xpath'] ) ) {
			$block_name    = self::get_block_name( $block_config );
			$xpaths_config = [];

			foreach ( $this->normalize_key_data( $block_config['xpath'] ) as $xpaths ) {
				if ( self::is_string_type( Obj::path( [ 'attr', 'type' ], $xpaths ) ) ) {
					$xpaths        = XPath::normalize( $xpaths );
					$xpaths_config = array_merge( $xpaths_config, array_values( $xpaths ) );
				}
			}

			if ( $xpaths_config ) {
				$blocks[ $block_name ]['xpath'] = $xpaths_config;
			}
		}

		return $blocks;
	}

	/**
	 * @param array $ids_in_blocks
	 * @param array $block_config
	 *
	 * @return array
	 */
	private function add_ids_in_block_xpath( array $ids_in_blocks, array $block_config ) {
		$xpaths = $this->normalize_key_data( (array) Obj::prop( 'xpath', $block_config ) );

		foreach ( $xpaths as $xpath ) {
			$type = Obj::path( [ 'attr', 'type' ], $xpath );

			if ( ConvertIdsHelper::isValidType( $type ) ) {
				$block_name = self::get_block_name( $block_config );

				$value         = Obj::path( [ 'value' ], $xpath );
				$selected_type = ConvertIdsHelper::selectElementType( Obj::path( [ 'attr', 'sub-type' ], $xpath ), $type );

				$ids_in_blocks[ $block_name ]          = isset( $ids_in_blocks[ $block_name ] ) ? $ids_in_blocks[ $block_name ] : [];
				$ids_in_blocks[ $block_name ]['xpath'] = isset( $ids_in_blocks[ $block_name ]['xpath'] ) ? $ids_in_blocks[ $block_name ]['xpath'] : [];
				$ids_in_blocks[ $block_name ]['xpath'] = array_merge( $ids_in_blocks[ $block_name ]['xpath'], [ $value => $selected_type ] );
			}
		}

		return $ids_in_blocks;
	}

	/**
	 * @param array $blocks
	 * @param array $block_config
	 *
	 * @return array
	 */
	private function add_block_attribute_keys( array $blocks, array $block_config ) {
		if ( isset( $block_config['key'] ) ) {
			$keys = $this->get_keys_recursively( $block_config['key'] );
			$keys = $this->cleanup_to_include_key( $keys );

			if ( $keys ) {
				$blocks[ self::get_block_name( $block_config ) ]['key'] = $keys;
			}
		}

		return $blocks;
	}

	/**
	 * @param array $ids_in_blocks
	 * @param array $block_config
	 *
	 * @return array
	 */
	private function add_ids_in_block_keys( array $ids_in_blocks, array $block_config ) {
		$keys_config = $this->find_convert_ids_key_recursively( $block_config );

		if ( $keys_config ) {
			$block_name = self::get_block_name( $block_config );

			$ids_in_blocks[ $block_name ]        = isset( $ids_in_blocks[ $block_name ] ) ? $ids_in_blocks[ $block_name ] : [];
			$ids_in_blocks[ $block_name ]['key'] = isset( $ids_in_blocks[ $block_name ]['key'] ) ? $ids_in_blocks[ $block_name ]['key'] : [];
			$ids_in_blocks[ $block_name ]['key'] = array_merge( $ids_in_blocks[ $block_name ]['key'], $keys_config );
		}

		return $ids_in_blocks;
	}

	private function find_convert_ids_key_recursively( array $config, array $path = [] ) {
		$current_keys = $this->normalize_key_data( (array) Obj::prop( 'key', $config ) );
		$keys_config  = [];

		if ( $current_keys ) {
			foreach ( $current_keys as $current_key ) {
				$current_name = Obj::path( [ 'attr', 'name' ], $current_key );
				$current_path = array_merge( $path, [ $current_name ] );
				$keys_config  = array_merge( $keys_config, $this->find_convert_ids_key_recursively( $current_key, $current_path ) );
			}
		} else {
			$type = Obj::path( [ 'attr', 'type' ], $config );

			if ( ConvertIdsHelper::isValidType( $type ) ) {
				$path_to_key   = implode( '>', $path );
				$selected_type = ConvertIdsHelper::selectElementType( Obj::path( [ 'attr', 'sub-type' ], $config ), $type );

				$keys_config[ $path_to_key ] = $selected_type;
			}
		}

		return $keys_config;
	}

	private function add_block_label( array $blocks, array $block_config ) {
		if ( isset( $block_config['attr']['label'] ) ) {
			$blocks[ self::get_block_name( $block_config ) ]['label'] = $block_config['attr']['label'];
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

			if ( self::is_string_type( Obj::pathOr( '', [ 'attr', 'type' ], $key_config ) ) ) {
				$partial_config['to_include'] = true;
			}

			if ( isset( $key_config['attr']['search-method'] ) ) {
				$partial_config['search-method'] = $key_config['attr']['search-method'];
			}

			if ( isset( $key_config['attr']['label'] ) ) {
				$partial_config['label'] = $key_config['attr']['label'];
			}

			if ( isset( $key_config['attr']['encoding'] ) ) {
				$partial_config['encoding'] = $key_config['attr']['encoding'];
			}

			if ( isset( $key_config['key'] ) ) {
				$children       = $this->get_keys_recursively( $key_config['key'] );
				$valid_children = $this->filter_string_keys( $children );

				if ( $valid_children ) {
					$partial_config['children'] = $valid_children;
				} else {
					unset( $partial_config['to_include'] );
				}
			}

			if ( $partial_config ) {
				$final_config = array_merge( $final_config, [ $key_config['attr']['name'] => $partial_config ] );
			}
		}

		return $final_config;
	}

	/**
	 * @param array $keys
	 *
	 * @return array
	 */
	private function filter_string_keys( $keys ) {
		return wpml_collect( $keys )
			->filter( Obj::prop( 'to_include' ) )
			->toArray();
	}

	/**
	 * @param array $data
	 *
	 * @return array
	 */
	private function cleanup_to_include_key( $data ) {
		foreach ( $data as &$item ) {
			unset( $item['to_include'] );

			if ( isset( $item['children'] ) ) {
				$item['children'] = $this->cleanup_to_include_key( $item['children'] );
			}
		}

		return $data;
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

	/**
	 * @param string|null $type
	 *
	 * @return bool
	 */
	private static function is_string_type( $type ) {
		return ! ConvertIdsHelper::isValidType( $type );
	}

	public function get() {
		return get_option( self::OPTION, array() );
	}

	/**
	 * @return array
	 */
	public function get_ids_in_blocks() {
		return get_option( self::OPTION_IDS_IN_BLOCKS, [] );
	}
}
