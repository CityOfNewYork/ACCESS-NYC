<?php

namespace WPML\PB\Gutenberg;

use WPML\FP\Obj;

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
		if ( isset( $data['attr'] ) ) {
			$data['value'] = array_merge( [ 'value' => $data['value'] ], $data['attr'] );
			if ( isset( $data['value']['type'] ) ) { // @todo This IF will be redundant when I improve `over` function
				$data = Obj::over( Obj::lensPath( [ 'value', 'type' ] ), 'strtoupper', $data );
			}

			unset( $data['attr'] );
		}

		return $data;
	}

	/**
	 * @param string|array $query
	 *
	 * @return array [query, type, label]
	 */
	public static function parse( $query ) {
		if ( is_array( $query ) ) {
			return [
				$query['value'],
				isset( $query['type'] ) ? $query['type'] : '',
				isset( $query['label'] ) ? $query['label'] : '',
			];
		}

		return [ $query, '', '' ];
	}

}
