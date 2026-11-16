<?php
/**
 * Importer_Remote_Data_Provider interface.
 * Expose get_table_fields & get_records methods to allow the importer to get data from remote sources.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers;

interface Importer_Remote_Data_Provider {
	/**
	 * Return remote source API client class instance.
	 *
	 * @return mixed
	 */
	public function get_api_client();

	/**
	 * Return table fields from remote source based on the importer settings passed as parameter.
	 *
	 * @return array
	 */
	public function get_table_fields();

	/**
	 * Get records from remote source based on the offset.
	 * The offset might be a string, a int, etc... based on the remote source.
	 *
	 * @param mixed $offset Pagination offset.
	 *
	 * @return \stdClass Returns the next offset or false if there is none and the records (props: next_offset & records).
	 */
	public function get_records( $offset );


	/**
	 * Returns import fields options.
	 *
	 * @return array
	 */
	public function get_import_fields_options();
}
