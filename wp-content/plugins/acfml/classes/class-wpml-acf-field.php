<?php

use WPML\FP\Cast;
use WPML\FP\Obj;
use WPML\FP\Type;

abstract class WPML_ACF_Field {
	/** @var mixed */
	public $meta_value;
	/** @var string */
	public $target_lang;
	/** @var array */
	public $meta_data;
	/** @var bool */
	public $related_acf_field_value;
	/** @var \WPML_ACF_Convertable|null */
	public $ids_object;


	public function __construct( $processed_data, $ids = null ) {
		$this->meta_value              = $processed_data->meta_value;
		$this->target_lang             = $processed_data->target_lang;
		$this->meta_data               = $processed_data->meta_data;
		$this->related_acf_field_value = $processed_data->related_acf_field_value;

		$this->ids_object = $ids;
	}

	/**
	 * @return mixed
	 */
	public function convert_ids() {
		if ( null === $this->ids_object ) {
			return null;
		}
		return $this->ids_object->convert( $this );
	}

	/**
	 * @param  bool  $has_element_with_display_translated
	 * @param  mixed $field
	 *
	 * @return bool
	 */
	public function has_element_with_display_translated(
		$has_element_with_display_translated,
		$field
	) {
		global $sitepress_settings;

		if ( ! defined( 'WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED' ) ) {
			return $has_element_with_display_translated;
		}

		if ( ! Type::isArray( Obj::prop( 'post_type', $field ) ) ) {
			return $has_element_with_display_translated;
		}

		foreach ( $field['post_type'] as $type ) {
			if ( WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED === Cast::toInt( Obj::view( Obj::lensPath( [ 'custom_posts_sync_option', $type ] ) )( $sitepress_settings ) ) ) {
				$has_element_with_display_translated = true;
				break;
			}
		}

		return $has_element_with_display_translated;
	}

	/**
	 * @return string
	 */
	abstract public function field_type();
}
