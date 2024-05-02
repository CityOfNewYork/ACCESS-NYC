<?php

class WPML_ACF_Processed_Data {
	/** @var mixed */
	public $meta_value;
	/** @var string */
	public $target_lang;
	/** @var array */
	public $meta_data;
	/** @var bool */
	public $related_acf_field_value;

	// phpcs:disable Squiz.Commenting.FunctionComment.ParamCommentFullStop
	/**
	 * @param mixed  $meta_value
	 * @param string $target_lang
	 * @param array  $meta_data {
	 *   context:        string|null
	 *   attribute:      string|null
	 *   key:            string|null
	 *   type:           string|null
	 *   is_serialized:  bool|null
	 *   post_id:        string|int|null
	 *   master_post_id: string|int|null
	 *   term_id:        string|int|null
	 *   master_term_id: string|int|null
	 * }
	 * @param bool   $related_acf_field_value
	 */
	public function __construct(
		$meta_value = null,
		$target_lang = '',
		$meta_data = [],
		$related_acf_field_value = false
	) {
		$this->meta_value              = $meta_value;
		$this->target_lang             = $target_lang;
		$this->meta_data               = $meta_data;
		$this->related_acf_field_value = $related_acf_field_value;
	}
	//phpcs:enable

}
