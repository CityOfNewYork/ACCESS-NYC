<?php

namespace WPML\TranslationRoles;

use WPML\Collect\Support\Collection;
use WPML\Ajax\IHandler;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\LIB\WP\User;
use function WPML\Container\make;

class GetTranslatorRecords implements IHandler {


	/**
	 * @inheritDoc
	 */
	public function run( Collection $data ) {
		$translators = make( \WPML_Translator_Records::class )->get_users_with_capability();

		return Either::of(
			[ 'translators' => Fns::map( User::withAvatar(), $translators ) ]
		);
	}

}
