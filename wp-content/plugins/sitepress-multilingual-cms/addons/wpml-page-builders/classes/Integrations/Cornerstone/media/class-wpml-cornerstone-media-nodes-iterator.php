<?php

class WPML_Cornerstone_Media_Nodes_Iterator implements IWPML_PB_Media_Nodes_Iterator {

	const ITEMS_FIELD = WPML_Cornerstone_Module_With_Items::ITEMS_FIELD;

	/** @var WPML_Cornerstone_Media_Node_Provider $node_provider */
	private $node_provider;
	public function __construct( WPML_Cornerstone_Media_Node_Provider $node_provider ) {
		$this->node_provider = $node_provider;
	}

	/**
	 * @param array $data_array
	 * @param string $lang
	 * @param string $source_lang
	 *
	 * @return array
	 */
	public function translate( $data_array, $lang, $source_lang ) {
		foreach ( $data_array as $key => &$data ) {
			if ( isset( $data[ self::ITEMS_FIELD ] ) && $data[ self::ITEMS_FIELD ] ) {
				$data[ self::ITEMS_FIELD ] = $this->translate( $data[ self::ITEMS_FIELD ], $lang, $source_lang );
			} elseif ( is_numeric( $key ) && isset( $data['_type'] ) ) {
				$data = $this->translate_node( $data, $lang, $source_lang );
			}
		}

		return $data_array;
	}

	/**
	 * @param stdClass $settings
	 * @param string $lang
	 * @param string $source_lang
	 *
	 * @return stdClass
	 */
	private function translate_node( $settings, $lang, $source_lang ) {
		$node = $this->node_provider->get( $settings['_type'] );

		if ( $node ) {
			$settings = $node->translate( $settings, $lang, $source_lang );
		}

		return $settings;
	}
}
