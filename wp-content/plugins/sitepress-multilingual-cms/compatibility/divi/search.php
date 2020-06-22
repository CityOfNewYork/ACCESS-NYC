<?php

namespace WPML\Compatibility\Divi;

class Search implements \IWPML_Frontend_Action {

	public function add_hooks() {
		add_action( 'et_search_form_fields', [ $this, 'add_language_form_field' ] );
	}

	public function add_language_form_field() {
		do_action( 'wpml_add_language_form_field' );
	}

}

