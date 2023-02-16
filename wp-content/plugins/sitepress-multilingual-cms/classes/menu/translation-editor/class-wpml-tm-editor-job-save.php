<?php

class WPML_TM_Editor_Job_Save {

	public function save( $data ) {
		$factory = new WPML_TM_Job_Action_Factory( wpml_tm_load_job_factory() );
		$action  = new WPML_TM_Editor_Save_Ajax_Action( $factory, $data );

		return $action->run();
	}
}
