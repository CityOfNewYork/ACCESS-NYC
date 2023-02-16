<?php
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
		if ( ! empty( $this->wpml_acf_field->meta_data['key'] ) && ! empty( $this->wpml_acf_field->meta_data['master_post_id'] ) ) {
			$field_object = get_field_object( $this->wpml_acf_field->meta_data['key'], $this->wpml_acf_field->meta_data['master_post_id'] );
			if ( ! empty( $field_object['taxonomy'] ) ) {
				$this->id = apply_filters( 'wpml_object_id', $this->id, $field_object['taxonomy'], true, $this->wpml_acf_field->target_lang );
			}
		}
		return $this;
	}
}
