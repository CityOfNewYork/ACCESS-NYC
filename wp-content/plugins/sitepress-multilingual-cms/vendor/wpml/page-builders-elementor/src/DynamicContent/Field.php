<?php

namespace WPML\PB\Elementor\DynamicContent;

use WPML_PB_String;

class Field {

	/**
	 * e.g. '[elementor-tag id="cc0b6c6" name="post-title" settings="ENCODED_STRING"]'
	 *
	 * @var string $tagValue
	 */
	public $tagValue;

	/**
	 * e.g. 'title'
	 *
	 * @var string $tagKey
	 */
	public $tagKey;

	/**
	 * The node ID.
	 *
	 * @var string $nodeId
	 */
	public $nodeId;

	/**
	 * The item ID inside the node with items.
	 *
	 * @var string $itemId
	 */
	public $itemId;

	/**
	 * @param string $tagValue
	 * @param string $tagKey
	 * @param string $nodeId
	 * @param string $itemId
	 */
	public function __construct( $tagValue, $tagKey, $nodeId, $itemId = '' ) {
		$this->tagValue = $tagValue;
		$this->tagKey   = $tagKey;
		$this->nodeId   = $nodeId;
		$this->itemId   = $itemId;
	}

	/**
	 * @see \WPML_Elementor_Translatable_Nodes::get_string_name()
	 * @see \WPML_Elementor_Module_With_Items::get_string_name()
	 *
	 * @param WPML_PB_String $string
	 *
	 * @return bool
	 */
	public function isMatchingStaticString( WPML_PB_String $string ) {
		$pattern = '/^' . $this->tagKey . '-.*-' . $this->nodeId . '$/';

		if ( $this->itemId ) {
			$pattern = '/^' . $this->tagKey . '-.*-' . $this->nodeId . '-' . $this->itemId . '$/';
		}

		return (bool) preg_match( $pattern, $string->get_name() );
	}
}