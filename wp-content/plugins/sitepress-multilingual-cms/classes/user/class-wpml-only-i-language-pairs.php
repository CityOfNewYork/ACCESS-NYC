<?php

class WPML_TM_Only_I_Language_Pairs implements IWPML_AJAX_Action, IWPML_DIC_Action {

	/** @var WPML_Language_Pair_Records $language_pair_records */
	private $language_pair_records;

	public function __construct( WPML_Language_Pair_Records $language_pair_records ) {
		$this->language_pair_records = $language_pair_records;
	}

	public function add_hooks() {
		add_action( 'wpml_update_active_languages', array( $this, 'update_language_pairs' ) );
	}

	public function update_language_pairs() {
		$users = get_users( [
			'meta_key'   => WPML_TM_Wizard_Options::ONLY_I_USER_META,
			'meta_value' => true,
		] );

		$all_language_pairs = WPML_All_Language_Pairs::get();

		foreach ( $users as $user ) {
			$this->language_pair_records->store(
				$user->ID,
				$all_language_pairs
			);

		}
	}
}
