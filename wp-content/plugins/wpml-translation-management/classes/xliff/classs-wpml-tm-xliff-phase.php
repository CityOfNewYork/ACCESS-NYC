<?php
abstract class WPML_TM_XLIFF_Phase {

	public function get() {
		$data = $this->get_data();

		if ( $data ) {
			return array(
				$this->get_phase_name() => array(
					'process-name' => $this->get_process_name(),
					'note'         => $data,
				),
			);
		}

		return array();
	}

	abstract protected function get_data();
	abstract protected function get_phase_name();
	abstract protected function get_process_name();
}