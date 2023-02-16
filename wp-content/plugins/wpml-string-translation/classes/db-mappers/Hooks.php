<?php

namespace WPML\ST\DB\Mappers;

use function WPML\Container\make;
use function WPML\FP\partial;

class Hooks implements \IWPML_Action, \IWPML_Backend_Action, \IWPML_Frontend_Action {
	public function add_hooks() {
		$getStringById      = [ make( \WPML_ST_DB_Mappers_Strings::class ), 'getById' ];
		$moveStringToDomain = partial( [ Update::class, 'moveStringToDomain' ], $getStringById );
		add_action( 'wpml_st_move_string_to_domain', $moveStringToDomain, 10, 2 );

		add_action( 'wpml_st_move_all_strings_to_new_domain', [ Update::class, 'moveAllStringsToNewDomain' ], 10, 2 );
	}
}
