<?php

namespace WPML\TM\Jobs;

use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\FP\Relation;
use WPML\FP\Fns;
use function WPML\FP\pipe;

class ExtraFieldDataInEditor implements \IWPML_Backend_Action {
	const MAX_ALLOWED_SINGLE_LINE_LENGTH = 50;

	/** @var \WPML_Custom_Field_Editor_Settings */
	private $customFieldEditorSettings;

	public function __construct( \WPML_Custom_Field_Editor_Settings $customFieldEditorSettings ) {
		$this->customFieldEditorSettings = $customFieldEditorSettings;
	}


	public function add_hooks() {
		add_filter( 'wpml_tm_adjust_translation_fields', [ $this, 'appendTitleAndStyle' ], 1, 3 );
	}

	public function appendTitleAndStyle( array $fields, $job, $originalPost ) {
		$appendTitleAndStyleStrategy = $this->isExternalElement( $job ) ?
			$this->appendToExternalField( $originalPost ) :
			$this->addTitleAndAdjustStyle( $job, $originalPost );

		return Fns::map( pipe( $appendTitleAndStyleStrategy, $this->adjustFieldStyleForUnsafeContent() ), $fields );
	}

	private function addTitleAndAdjustStyle( $job, $originalPost ) {
		return function ( $field ) use ( $job, $originalPost ) {
			if ( FieldId::is_a_custom_field( $field['field_type'] ) ) {
				return $this->appendToCustomField( $field, $job, $originalPost );
			} elseif ( FieldId::is_a_term( $field['field_type'] ) ) {
				return $this->appendToTerm( $field );
			}

			return $this->appendToRegularField( $field );
		};
	}

	private function isExternalElement( $job ) {
		return isset( $job->element_type_prefix ) && wpml_load_core_tm()->is_external_type( $job->element_type_prefix );
	}

	private function appendToExternalField( $originalPost ) {
		return function ( $field ) use ( $originalPost ) {
			$field['title']       = apply_filters( 'wpml_tm_editor_string_name', $field['field_type'], $originalPost );
			$field['field_style'] = $this->applyStyleFilter(
				Obj::propOr( '', 'field_style', $field ),
				$field['field_type'],
				$originalPost
			);

			return $field;
		};
	}

	private function appendToCustomField( $field, $job, $originalPost ) {
		$title = $this->getCustomFieldTitle( $field );
		$style = $this->getCustomFieldStyle( $field );

		$field = (array) apply_filters( 'wpml_editor_cf_to_display', (object) $field, $job );

		$field['title']       = $title;
		$field['field_style'] = $this->getAdjustedFieldStyle( $field, $style );
		$field['field_style'] = $this->applyStyleFilter( $field['field_style'], $field['field_type'], $originalPost );

		return $field;
	}

	private function appendToTerm( $field ) {
		$field['title'] = '';

		return $field;
	}

	private function applyStyleFilter( $style, $type, $originalPost ) {
		return (string) apply_filters( 'wpml_tm_editor_string_style', $style, $type, $originalPost );
	}

	private function appendToRegularField( $field ) {
		$field['title'] = \wpml_collect(
			[
				'title'   => __( 'Title', 'wpml-translation-management' ),
				'body'    => __( 'Body', 'wpml-translation-management' ),
				'excerpt' => __( 'Excerpt', 'wpml-translation-management' ),
			]
		)->get( $field['field_type'], $field['field_type'] );

		if ( $field['field_type'] === 'excerpt' ) {
			$field['field_style'] = '1';
		}

		return $field;
	}

	private function getCustomFieldTitle( $field ) {
		$unfiltered_type    = \WPML_TM_Field_Type_Sanitizer::sanitize( $field['field_type'] );
		$element_field_type = $unfiltered_type;
		/**
		 * @deprecated Use `wpml_editor_custom_field_name` filter instead
		 * @since      3.2
		 */
		$element_field_type = apply_filters( 'icl_editor_cf_name', $element_field_type );
		$element_field_type = apply_filters( 'wpml_editor_custom_field_name', $element_field_type );

		return $this->customFieldEditorSettings->filter_name( $unfiltered_type, $element_field_type );
	}

	private function getCustomFieldStyle( $field ) {
		$type = \WPML_TM_Field_Type_Sanitizer::sanitize( $field['field_type'] );

		$style = Str::includes( "\n", $field['field_data'] ) ? 1 : 0;

		/**
		 * @deprecated Use `wpml_editor_custom_field_style` filter instead
		 * @since      3.2
		 */
		$style = apply_filters( 'icl_editor_cf_style', $style, $type );
		$style = apply_filters( 'wpml_editor_custom_field_style', $style, $type );

		return $this->customFieldEditorSettings->filter_style( $type, $style );
	}

	private function getAdjustedFieldStyle( array $field, $style ) {
		/**
		 * wpml_tm_editor_max_allowed_single_line_length filter
		 *
		 * Filters the value of `\WPML_Translation_Editor_UI::MAX_ALLOWED_SINGLE_LINE_LENGTH`
		 *
		 * @param  int    $max_allowed_single_line_length MAX_ALLOWED_SINGLE_LINE_LENGTH The length of the string, after which it must use a multiline input
		 * @param  array  $field  The generic field data
		 * @param  array  $custom_field_data  The custom field specific data
		 *
		 * @since 2.3.1
		 */
		$maxAllowedLength = (int) apply_filters(
			'wpml_tm_editor_max_allowed_single_line_length',
			self::MAX_ALLOWED_SINGLE_LINE_LENGTH,
			$field,
			[ $field['title'], $style, $field ]
		);

		return 0 === (int) $style && strlen( $field['field_data'] ) > $maxAllowedLength ? '1' : $style;
	}

	private function adjustFieldStyleForUnsafeContent() {
		return function ( array $field ) {
			if ( Relation::propEq( 'field_style', '2', $field ) ) {
				$black_list         = [ 'script', 'style', 'iframe' ];
				$black_list_pattern = '#</?(' . implode( '|', $black_list ) . ')[^>]*>#i';

				if ( preg_replace( $black_list_pattern, '', $field['field_data'] ) !== $field['field_data'] ) {
					$field['field_style'] = '1';
				}
			}

			return $field;
		};
	}
}
