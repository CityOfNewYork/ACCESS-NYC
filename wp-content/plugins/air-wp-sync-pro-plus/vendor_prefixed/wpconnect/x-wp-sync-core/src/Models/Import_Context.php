<?php
/**
 * Import context.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;

/**
 * Import_Context class
 */
class Import_Context {
	/**
	 * Importer.
	 *
	 * @var Abstract_Importer|null
	 */
	protected $importer = null;

	/**
	 * Existing object id.
	 *
	 * @var mixed|null
	 */
	protected $existing_object_id = null;


	/**
	 * Get importer.
	 *
	 * @return Abstract_Importer|null
	 */
	public function get_importer(): Abstract_Importer {
		return $this->importer;
	}

	/**
	 * Set importer.
	 *
	 * @param Abstract_Importer|null $importer Importer.
	 */
	public function set_importer( Abstract_Importer $importer ) {
		$this->importer = $importer;
		return $this;
	}


	/**
	 * Get existing object id if any.
	 *
	 * @return mixed|null
	 */
	public function get_existing_object_id() {
		return $this->existing_object_id;
	}

	/**
	 * Set existing object id.
	 *
	 * @param mixed|null $existing_object_id Existing object id.
	 */
	public function set_existing_object_id( $existing_object_id ) {
		$this->existing_object_id = $existing_object_id;
		return $this;
	}

	/**
	 * Returns true if the context has an importer set.
	 *
	 * @return bool
	 */
	public function has_importer() {
		return $this->importer instanceof Abstract_Importer;
	}

	/**
	 * Returns true if the context has a source field set.
	 *
	 * @return bool
	 */
	public function has_source_field() {
		return null !== $this->source_field;
	}

	/**
	 * Returns true if the context has an existing field set.
	 *
	 * @return bool
	 */
	public function has_existing_object_id() {
		return null !== $this->existing_object_id;
	}

	/**
	 * Returns true if the context has a taxonomy set.
	 *
	 * @return bool
	 */
	public function has_taxonomy() {
		return null !== $this->taxonomy;
	}
}
