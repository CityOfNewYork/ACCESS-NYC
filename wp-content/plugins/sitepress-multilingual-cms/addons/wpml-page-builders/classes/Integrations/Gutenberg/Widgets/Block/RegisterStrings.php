<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\Container\make;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class RegisterStrings implements \IWPML_REST_Action, \WPML\PB\Gutenberg\Integration {
	public function add_hooks() {
		$registerStrings = Fns::memorize( function ( $oldValue, $newValue ) {
			$gutenbergIntegration = make( \WPML_Gutenberg_Integration::class );
			$blocks               = $this->getBlocks( $gutenbergIntegration, $newValue );

			$gutenbergIntegration->register_strings_from_widget( $blocks, Strings::createPackage() );
		} );

		Hooks::onAction( 'update_option_widget_block', 10, 2 )
		     ->then( spreadArgs( $registerStrings ) );
	}

	private function getBlocks( $gutenbergIntegration, $options ) {
		$getContent = Logic::ifElse( 'is_scalar', Fns::always( null ), Obj::prop( 'content' ) );

		return wpml_collect( $options )
			->map( $getContent )
			->filter()
			->unique()
			->map( [ $gutenbergIntegration, 'parse_blocks' ] )
			->flatten( 1 )
			->toArray();
	}
}