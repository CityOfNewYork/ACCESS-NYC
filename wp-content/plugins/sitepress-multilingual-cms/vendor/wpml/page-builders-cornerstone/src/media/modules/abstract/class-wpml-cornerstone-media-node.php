<?php

abstract class WPML_Cornerstone_Media_Node {

	/** @var WPML_Page_Builders_Media_Translate $media_translate */
	protected $media_translate;

	public function __construct( WPML_Page_Builders_Media_Translate $media_translate ) {
		$this->media_translate = $media_translate;
	}

	/**
	 * @param array  $node_data
	 * @param string $target_lang
	 * @param string $source_lang
	 *
	 * @return array
	 */
	abstract function translate( $node_data, $target_lang, $source_lang );
}