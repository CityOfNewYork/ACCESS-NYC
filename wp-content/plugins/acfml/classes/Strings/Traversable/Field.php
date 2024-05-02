<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Config;
use ACFML\Strings\Transformer\Transformer;

class Field extends Entity {

	/**
	 * @return array
	 */
	protected function getConfig() {
		return Config::getForField();
	}

	/**
	 * @param Transformer  $transformer
	 * @param array|string $value
	 * @param array        $config
	 *
	 * @return string
	 */
	protected function transform( Transformer $transformer, $value, $config ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $label ) {
				if ( is_string( $label ) ) {
					$value[ $key ] = $transformer->transform( $label, $config );
				}
			}
		} else {
			$value = $transformer->transform( $value, $config );
		}

		return $value;
	}
}
