<?php

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Str;

class WPML_ACF_Term_Id {
	/**
	 * @var int The term id.
	 */
	public $id;
	/**
	 * @var WPML_ACF_Field WPML representation of ACF field.
	 */
	private $wpml_acf_field;

	/**
	 * WPML_ACF_Term_Id constructor.
	 *
	 * @param int            $id             The term id.
	 * @param WPML_ACF_Field $wpml_acf_field WPML representation of ACF field.
	 */
	public function __construct( $id, WPML_ACF_Field $wpml_acf_field ) {
		$this->id             = $id;
		$this->wpml_acf_field = $wpml_acf_field;
	}

	/**
	 * Replaces taxonomy term id copied from original post with term id of translated version of taxonomy.
	 *
	 * @return WPML_ACF_Term_Id $WPML_ACF_Term_Id Converted term id or original if not translated yet.
	 */
	public function convert() {
		$fieldKey = Obj::prop( 'key', $this->wpml_acf_field->meta_data );
		if ( null === $fieldKey ) {
			return $this;
		}

		$objectId = $this->getObjectId( $this->wpml_acf_field->meta_data );
		if ( null === $objectId ) {
			return $this;
		}

		$field_object = get_field_object( $fieldKey, $objectId );
		if ( ! empty( $field_object['taxonomy'] ) ) {
			$this->id = apply_filters( 'wpml_object_id', $this->id, $field_object['taxonomy'], true, $this->wpml_acf_field->target_lang );
		}

		return $this;
	}

	/**
	 * @param  array $metaData
	 *
	 * @return int|string|null
	 */
	private function getObjectId( $metaData ) {
		$context = Obj::prop( 'context', $metaData );
		switch ( $context ) {
			case \WPML_ACF_Worker::METADATA_CONTEXT_TERM_FIELD:
				// Passing term_XX to get_field_object will get field values for the term with term_id XX.
				return Logic::ifElse( Logic::isNotNull(), Str::concat( 'term_' ), Fns::identity(), Obj::prop( 'master_term_id', $metaData ) );
			case \WPML_ACF_Worker::METADATA_CONTEXT_POST_FIELD:
			default:
				return Obj::prop( 'master_post_id', $metaData );
		}
	}

}
