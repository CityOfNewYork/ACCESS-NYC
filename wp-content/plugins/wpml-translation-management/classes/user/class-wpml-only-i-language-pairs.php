<?php

class WPML_TM_Only_I_language_Pairs implements IWPML_Action {

	/** @var SitePress $sitepress */
	private $sitepress;

	/** @var WPML_Language_Pair_Records $language_pair_records */
	private $language_pair_records;

	public function __construct( WPML_Language_Pair_Records $language_pair_records, SitePress $sitepress ) {
		$this->language_pair_records = $language_pair_records;
		$this->sitepress             = $sitepress;
	}

	public function add_hooks() {
		add_action( 'wpml_update_active_languages', array( $this, 'update_language_pairs' ) );
	}

	public function update_language_pairs() {
		$users = get_users( array(
				'meta_key'   => WPML_TM_Wizard_Options::ONLY_I_USER_META,
				'meta_value' => true,
			)
		);

		$all_language_pairs = WPML_All_Language_Pairs::get( $this->sitepress );

		foreach ( $users as $user ) {
			$this->language_pair_records->store(
				$user->ID,
				$all_language_pairs
			);

		}
	}
}