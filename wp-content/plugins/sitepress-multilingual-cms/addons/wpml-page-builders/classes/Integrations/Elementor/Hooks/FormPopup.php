<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class FormPopup implements \IWPML_Frontend_Action, \IWPML_AJAX_Action {

	public function add_hooks() {
		$isElementorFormAjaxSubmit = Relation::propEq( 'action', 'elementor_pro_forms_send_form' );
		if ( $isElementorFormAjaxSubmit( $_POST ) ) {
			Hooks::onAction( 'elementor_pro/forms/new_record', 10, 2 )
			     ->then( spreadArgs( [ $this, 'convertFormPopupIdAjax' ] ) );
		}
	}

	/**
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record  $record      An instance of the form record.
	 * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajaxHandler An instance of the ajax handler.
	 */
	public function convertFormPopupIdAjax ( $record, $ajaxHandler ) {
		if ( Obj::path( [ 'data', 'popup', 'id' ], $ajaxHandler ) ) {
			$popup = Obj::path( [ 'data', 'popup'], $ajaxHandler );
			$getCurrentLanguageFromPost = function () {
				return Obj::prop( 'language_code', apply_filters( 'wpml_post_language_details', null, sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) ) );
			};
			
			$popup['id'] = apply_filters( 'wpml_object_id', $popup['id'], get_post_type( $popup['id'] ), false, $getCurrentLanguageFromPost() );

			$ajaxHandler->add_response_data( 'popup', $popup );
		}
	}
}
