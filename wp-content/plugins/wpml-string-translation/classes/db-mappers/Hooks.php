<?php

namespace WPML\ST\DB\Mappers;


class Hooks implements \IWPML_Action, \IWPML_Backend_Action, \IWPML_Frontend_Action {
	public function add_hooks() {
		add_action( 'wpml_st_move_string_to_domain', [ Update::class, 'moveStringToDomain' ], 10, 2 );
		add_action( 'wpml_st_move_all_strings_to_new_domain', [ Update::class, 'moveAllStringsToNewDomain' ], 10, 2 );
	}
}