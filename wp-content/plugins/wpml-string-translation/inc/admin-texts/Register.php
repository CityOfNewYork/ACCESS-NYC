<?php

namespace WPML\Ajax\ST\AdminText;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use function WPML\FP\partial;
use function WPML\FP\pipe;

class Register implements IHandler {

	/** @var \WPML_Admin_Texts */
	private $adminTexts;

	public function __construct( \WPML_Admin_Texts $adminTexts ) {
		$this->adminTexts = $adminTexts;
	}

	/**
	 * Registers or Unregisters an option for translation depending
	 * on the `state` data.
	 *
	 * @param Collection $data
	 *
	 * @return Either
	 */
	public function run( Collection $data ) {
		$state      = $data->get( 'state' ) ? 'on' : '';
		$applyState = partial( [ self::class, 'flatToHierarchical' ], $state );

		$register = pipe(
			'wpml_collect',
			Fns::map( $applyState ),
			Fns::reduce( 'array_replace_recursive', [] ),
			[ $this->adminTexts, 'icl_register_admin_options' ]
		);

		$register( $data->get( 'selected', [] ) );

		return Either::right( true );
	}


	/**
	 * string $state -> string [key1][key2][name] -> array [ key1 => [ key2 => [ name => $state ] ] ]
	 *
	 * @param string $state
	 * @param string $option
	 *
	 * @return array
	 */
	public static function flatToHierarchical( $state, $option ) {

		// string $value -> mixed $key -> array [ $key => $value ]
		$makeArrayWithStringKey = function ( $value, $key ) {
			return [ (string) $key => $value ];
		};

		return \WPML_Admin_Texts::getKeysParts( $option )
								->reverse()
								->reduce( $makeArrayWithStringKey, $state );
	}
}
