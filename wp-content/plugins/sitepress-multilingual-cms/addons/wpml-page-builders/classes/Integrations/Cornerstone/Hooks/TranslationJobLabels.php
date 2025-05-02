<?php

namespace WPML\PB\Cornerstone\Hooks;

use WPML\FP\Lst;

class TranslationJobLabels implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		add_filter( 'wpml_pb_strip_patterns_from_labels', Lst::append( '/^cs_/' ) );
	}
}
