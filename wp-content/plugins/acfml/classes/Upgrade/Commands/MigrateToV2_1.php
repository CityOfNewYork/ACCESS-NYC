<?php

namespace ACFML\Upgrade\Commands;

use ACFML\Options;
use ACFML\Strings\Factory;
use ACFML\Strings\Translator;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class MigrateToV2_1 implements Command {

	const KEY = 'migrate-to-v2_1';

	const STATUS_DONE = 'done';

	// Our integration runs at acf/init:1 and objects are registered at acf/init:5:
	// we need to include our callbacks in the middle.
	const INIT_PRIORITY = 2;

	// Our translations run at registration hooks with priority 10:
	// we should register here earlier.
	const REGISTRATIONM_PRIORITY = 9;

	public static function run() {
		Hooks::onAction( 'acf/init', self::INIT_PRIORITY )
			->then( function() {
				if ( null === Options::get( self::KEY ) && self::isStActivated() ) {
					$factory    = new Factory();
					$translator = new Translator( $factory );

					Hooks::onFilter( 'acf/post_type/registration_args', self::REGISTRATIONM_PRIORITY, 2 )
						->then( spreadArgs(
							/**
							 * @param  array $args
							 * @param  array $data
							 *
							 * @return array
							 */
							function( $args, $data ) use ( $translator ) {
								$translator->registerCpt( $data );
								return $args;
							}
						) );
					Hooks::onFilter( 'acf/taxonomy/registration_args', self::REGISTRATIONM_PRIORITY, 2 )
						->then( spreadArgs(
							/**
							 * @param  array $args
							 * @param  array $data
							 *
							 * @return array
							 */
							function( $args, $data ) use ( $translator ) {
								$translator->registerTaxonomy( $data );
								return $args;
							}
						) );
					Hooks::onFilter( 'acf/validate_options_page', self::REGISTRATIONM_PRIORITY )
						->then( spreadArgs(
							/**
							 * @param  array $data
							 *
							 * @return array
							 */
							function( $data ) use ( $translator ) {
								$translator->registerOptionsPage( $data );
								return $data;
							}
						) );

					Options::set( self::KEY, self::STATUS_DONE );
				}
			} );
	}

	/**
	 * @return bool
	 */
	public static function isStActivated() {
		return defined( 'WPML_ST_VERSION' );
	}

}
