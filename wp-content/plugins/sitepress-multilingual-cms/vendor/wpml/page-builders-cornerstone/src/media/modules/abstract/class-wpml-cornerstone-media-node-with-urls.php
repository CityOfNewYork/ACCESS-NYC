<?php

abstract class WPML_Cornerstone_Media_Node_With_URLs extends WPML_Cornerstone_Media_Node {

	/** @return array */
	abstract protected function get_keys();

	/**
	 * @param array  $node_data
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @return array
	 */
	public function translate( $node_data, $target_lang, $source_lang ) {
		foreach ( $this->get_keys() as $key ) {
			if ( ! empty( $node_data[ $key ] ) ) {
				$node_data[ $key ] = $this->media_translate->translate_image_url( $node_data[ $key ], $target_lang, $source_lang );
			}
		}

		return $node_data;
	}
}
