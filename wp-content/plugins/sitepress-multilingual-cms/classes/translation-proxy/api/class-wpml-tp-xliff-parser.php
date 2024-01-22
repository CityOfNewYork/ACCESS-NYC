<?php

class WPML_TP_Xliff_Parser {

	/** @var WPML_TM_Validate_HTML $validate_html */
	private $validate_html;

	/**
	 * WPML_TP_Xliff_Parser constructor.
	 *
	 * @param WPML_TM_Validate_HTML $validate_html
	 */
	public function __construct( WPML_TM_Validate_HTML $validate_html ) {
		$this->validate_html = $validate_html;
	}

	/**
	 * @param SimpleXMLElement $xliff
	 *
	 * @return WPML_TP_Translation_Collection
	 */
	public function parse( SimpleXMLElement $xliff ) {
		$source_lang = (string) $xliff->file->attributes()->{'source-language'};
		$target_lang = (string) $xliff->file->attributes()->{'target-language'};

		$translations = array();
		foreach ( $xliff->file->body->children() as $node ) {
			$source         = $this->get_cdata_value( $node, 'source' );
			$target         = $this->get_cdata_value( $node, 'target' );
			$sourceRestored = $this->validate_html->restore_html( $source );
			$targetRestored = $this->validate_html->restore_html( $target );

			$translations[] = new WPML_TP_Translation(
				(string) $node->attributes()->id,
				( false !== $sourceRestored ) ? $sourceRestored : $source,
				( false !== $targetRestored ) ? $targetRestored : $target
			);
		}

		return new WPML_TP_Translation_Collection(
			$translations,
			$source_lang,
			$target_lang
		);
	}

	/**
	 * @param SimpleXMLElement $xliff_node
	 * @param string           $field
	 *
	 * @return string
	 */
	protected function get_cdata_value( SimpleXMLElement $xliff_node, $field ) {
		$value = '';
		if ( isset( $xliff_node->$field->mrk ) ) {
			$value = (string) $xliff_node->$field->mrk;
		} elseif ( isset( $xliff_node->$field ) ) {
			$value = (string) $xliff_node->$field;
		}

		return self::restore_new_line( $value );
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	public static function restore_new_line( $string ) {
		return preg_replace( '/<br class="xliff-newline"\s*\/>/i', "\n", $string );
	}
}
