<?php
/**
 * @author OnTheGo Systems
 */
class WPML_TM_XLIFF_Translator_Notes extends WPML_TM_XLIFF_Phase {

	private $post_id;

	public function __construct( $post_id = 0 ) {
		$this->post_id = $post_id;
	}

	/**
	 * @return string
	 */
	protected function get_data() {
		if ( $this->post_id ) {
			return WPML_TM_Translator_Note::get( $this->post_id );
		} else {
			return '';
		}
	}

	protected function get_phase_name() {
		return 'notes';
	}

	protected function get_process_name() {
		return 'Notes';
	}
}
