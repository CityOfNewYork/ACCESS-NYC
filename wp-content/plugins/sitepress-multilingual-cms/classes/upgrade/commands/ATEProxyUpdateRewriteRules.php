<?php

namespace WPML\TM\Upgrade\Commands;

class ATEProxyUpdateRewriteRules implements \IWPML_Upgrade_Command {

	/** @var bool $result */
	private $result = false;

	/**
	 * @return bool
	 */
	public function run_admin() {
		// By doing this, we ensure that the rewrite rules get updated for the `ate/widget/script` endpoint.
		update_option( 'plugin_permalinks_flushed', 0 );
		$this->result = true;

		return $this->result;
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_ajax() {
		return null;
	}

	/**
	 * Unused.
	 *
	 * @return null
	 */
	public function run_frontend() {
		return null;
	}

	/**
	 * @return bool
	 */
	public function get_results() {
		return $this->result;
	}
}
