<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\Ajax\IHandler;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\User;
use function WPML\Container\make;
use function WPML\FP\pipe;

class GetManagerRecords implements IHandler {


	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {
		$managers = make( \WPML_Translation_Manager_Records::class )->get_users_with_capability();

		return Either::of(
			[ 'managers' => Fns::map( User::withAvatar(), $managers ) ]
		);
	}

}
