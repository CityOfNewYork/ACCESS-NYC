<?php

namespace WPML\Compatibility\WPBakery\Hooks;

use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;

use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class TranslationJobLabels implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_strip_patterns_from_labels' )
			->then( spreadArgs( pipe(
				Lst::append( '/^_wpb_/' ),
				Lst::append( '/^vc_/' )
			) ) );
	}
}
