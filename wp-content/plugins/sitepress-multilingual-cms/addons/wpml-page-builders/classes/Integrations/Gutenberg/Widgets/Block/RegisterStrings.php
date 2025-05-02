<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;

use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class RegisterStrings implements \IWPML_REST_Action, \IWPML_DIC_Action, \WPML\PB\Gutenberg\Integration {

	const AFTER_STRING_CLEANUP = 20;

	/**
	 * @var \WPML_PB_Factory
	 */
	private $pbFactory;

	public function __construct( \WPML_PB_Factory $pbFactory ) {
		$this->pbFactory = $pbFactory;
	}

	public function add_hooks() {
		$registerStrings = Fns::memorize( function ( $oldValue, $newValue ) {
			$gutenbergIntegration = make( \WPML_Gutenberg_Integration::class );
			$blocks               = $this->getBlocks( $gutenbergIntegration, $newValue );

			$gutenbergIntegration->register_strings_from_widget( $blocks, Strings::createPackage() );
		} );

		Hooks::onAction( 'update_option_widget_block', 10, 2 )
			->then( spreadArgs( $registerStrings ) );

		Hooks::onAction( 'wpml_delete_unused_package_strings', self::AFTER_STRING_CLEANUP )
			->then( spreadArgs( [ $this, 'deleteEmptyStringPackage' ] ) );
	}

	private function getBlocks( $gutenbergIntegration, $options ) {
		$getContent = Logic::ifElse( 'is_scalar', Fns::always( null ), Obj::prop( 'content' ) );

		$blocks = wpml_collect( $options )
			->map( $getContent )
			->filter()
			->unique()
			->map( [ $gutenbergIntegration, 'parse_blocks' ] )
			->toArray();

		return array_map( function ( $blocks ) {
			return is_array( $blocks ) && ! empty( $blocks ) ? array_merge( ...$blocks ) : $blocks;
		}, $blocks );
	}

	/**
	 * @param array $packageData
	 */
	public function deleteEmptyStringPackage( $packageData ) {
		if ( isset( $packageData['kind_slug'] ) && Strings::PACKAGE_KIND_SLUG === $packageData['kind_slug'] ) {
			$package        = $this->pbFactory->get_wpml_package( $packageData );
			$packageStrings = $package->get_package_strings( true );

			if ( ! $packageStrings ) {
				do_action( 'wpml_delete_package', $packageData['name'], $packageData['kind'] );
			}
		}
	}
}
