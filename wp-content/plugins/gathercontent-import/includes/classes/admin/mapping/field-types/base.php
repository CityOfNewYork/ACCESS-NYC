<?php
/**
 * GatherContent Plugin, Base Field Type
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Admin\Mapping\Field_Types;
use GatherContent\Importer\Base as Plugin_Base;
use GatherContent\Importer\Views\View;

/**
 * GatherContent Plugin, Base Field Type
 *
 * @since 3.0.0
 */
abstract class Base extends Plugin_Base implements Type {

	/**
	 * Id of field type
	 *
	 * @var string
	 */
	protected $type_id = '';

	/**
	 * Label for type
	 *
	 * @var string
	 */
	protected $option_label = '';

	/**
	 * Array of supported template field types.
	 *
	 * Possibilities include:
	 *
	 * array(
	 * 	'text',
	 * 	'text_rich',
	 * 	'text_plain',
	 * 	'files',
	 * 	'choice_radio',
	 * 	'choice_checkbox',
	 * )
	 *
	 * @var array
	 */
	protected $supported_types = array();

	/**
	 * Returns type_id property
	 *
	 * @since  [since]
	 *
	 * @return [type]  [description]
	 */
	public function type_id() {
		return $this->type_id;
	}

	public function e_type_id() {
		echo $this->type_id;
	}

	public function option_underscore_template( View $view ) {
		$option = '<option <# if ( "'. $this->type_id() .'" === data.field_type ) { #>selected="selected"<# } #> value="'. $this->type_id() .'">' . $this->option_label . '</option>';

		if ( $types = $this->get_supported_types() ) {
			$option = '<# if ( data.type in '. $types .' ) { #>' . $option . '<# } #>';
		}

		echo "\n\t" . $option;
	}

	public function underscore_options( $array ) {
		foreach ( $array as $value => $label ) {
			$this->underscore_option( $value, $label );
		}
	}

	public function underscore_option( $value, $label ) {
		echo '<option <# if ( "'. $value .'" === data.field_value ) { #>selected="selected"<# } #> value="'. $value .'">'. $label .'</option>';
	}

	public function underscore_empty_option( $label ) {
		$this->underscore_option( '', $label );
	}

	protected function get_supported_types() {
		if ( ! empty( $this->supported_types ) && ! is_string( $this->supported_types ) ) {
			$this->supported_types = wp_json_encode( array_flip( $this->supported_types ) );
		}

		return $this->supported_types;
	}

}
