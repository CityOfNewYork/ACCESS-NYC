<?php

namespace WPML\PB\Gutenberg;

class XPath {

	/**
	 * If a sequence has only one element, we will wrap it
	 * in order to have the same data shape as for multiple elements.
	 * Also check for type attribute
	 *
	 * @param array|string $data
	 *
	 * @return array
	 */
	public static function normalize( $data ) {
		if ( isset( $data['attr']['type'] ) ) {
			$data['value'] = [
				'value' => $data['value'],
				'type' => strtoupper( $data['attr']['type'] )
			];
			unset( $data['attr'] );
		}
		return $data;
	}

	/**
	 * @param string|array $query
	 *
	 * @return array
	 */
	public static function parse( $query ) {
		if ( is_array( $query ) )  {
			return [ $query['value'], $query['type'] ];
		}
		return [ $query, '' ];
	}

}
