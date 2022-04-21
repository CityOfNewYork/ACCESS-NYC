<?php
/**
 * @author OnTheGo Systems
 */
class WPML_TM_XLIFF_Post_Type extends WPML_TM_XLIFF_Phase {

	private $post_type;

	public function __construct( $post_type = '' ) {
		$this->post_type = $post_type;
	}

	/**
	 * @return string
	 */
	protected function get_data() {
		return $this->post_type;
	}

	protected function get_phase_name() {
		return 'post_type';
	}

	protected function get_process_name() {
		return 'Post type';
	}
}
