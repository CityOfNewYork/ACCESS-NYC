<?php

namespace WPML\Options;

use WPML\WP\OptionManager;

class Reset implements \IWPML_Backend_Action {

	public function add_hooks() {
		add_filter( 'wpml_reset_options', [ new OptionManager(), 'reset_options' ] );
	}

}