<?php

namespace WPML\TM\Jobs;

use WPML\Core\WP\App\Resources;
use WPML\LIB\WP\Hooks;
use WPML\UIPage;

class Loader implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( wpml_is_ajax() ) {
			return;
		}

		if ( UIPage::isTMJobs( $_GET ) || UIPage::isTranslationQueue( $_GET ) ) {
			Hooks::onAction( 'wp_loaded' )
			     ->then( [ $this, 'getData' ] )
			     ->then( Resources::enqueueApp( 'jobs' ) );
		}
	}

	public function getData() {
		$data = ( new \WPML_TM_Jobs_List_Script_Data() )->get();
		$data = ( new \WPML_TM_Scripts_Factory() )->build_localize_script_data( $data );

		return [
			'name' => 'WPML_TM_SETTINGS',
			'data' => $data,
		];
	}
}