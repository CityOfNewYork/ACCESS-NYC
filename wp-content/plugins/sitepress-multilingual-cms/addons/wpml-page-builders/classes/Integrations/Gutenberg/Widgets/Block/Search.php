<?php


namespace WPML\PB\Gutenberg\Widgets\Block;


use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Search implements \IWPML_Frontend_Action, \WPML\PB\Gutenberg\Integration {

	public function add_hooks() {
		global $sitepress;

		Hooks::onFilter( 'render_block_core/search' )
		     ->then( spreadArgs( [ $sitepress, 'get_search_form_filter' ] ) );
	}

}