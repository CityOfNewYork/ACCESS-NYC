<?php

class WPML_TM_Job_TS_Status {

	/** @var string */
	private $status;
	/** @var array */
	private $links = array();

	/**
	 * WPML_TM_Job_TS_Status constructor.
	 *
	 * @param string $status
	 * @param array  $links
	 */
	public function __construct( $status, $links ) {
		$this->status = $status;
		$this->links  = $links;
	}

	/**
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * @return array
	 */
	public function get_links() {
		return $this->links;
	}

	public function __toString() {
		if ( $this->status ) {
			return wp_json_encode(
				array(
					'status' => $this->status,
					'links'  => $this->links,
				)
			);
		}
		return '';
	}
}
