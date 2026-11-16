<?php
/**
 * Airtable importer modifier.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Importers;

use Air_WP_Sync_Pro_Vendor\WPConnect\Airtable_WP_Sync_Core\Util\Airtable_Helper;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;

/**
 * Class Airtable_Importer_Modifier.
 */
class Airtable_Importer_Modifier {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'airwpsync/pre_import_record_data', array( $this, 'pre_import_record_filter' ), 10, 2 );
	}

	/**
	 * Checks whether the import started more than two hours ago.
	 * If yes, then refresh record if attachment fields are involved.
	 *
	 * @param  \stdClass         $record Airtable record.
	 * @param Abstract_Importer $importer Importer.
	 */
	public function pre_import_record_filter( $record, $importer ) {
		$start_date = get_option( 'airwpsync-' . $importer->infos()->get( 'id' ) . '-run-' . $importer->get_run_id() . '-start-date' );
		// If we have started the import at least 2 hours ago, attachment URLs could be expired, in that case we should refresh the record.
		if ( Airtable_Helper::should_refresh_attachment_urls( $start_date ) ) {
			$attachment_fields = Airtable_Helper::get_attachment_fields_id( $importer->get_remote_fields() );
			if ( count( $attachment_fields ) > 0 ) {
				try {
					$base_id  = $importer->config()->get( 'app_id' );
					$table_id = $importer->config()->get( 'table' );
					$record   = $importer->get_remote_data_provider()->get_api_client()->get_record( $base_id, $table_id, $record->id, array( 'returnFieldsByFieldId' => true ) );
				} catch ( \Throwable $exception ) {
					/* translators: %s = error message */
					$importer->log( sprintf( __( 'Could not refresh attachment URLs: %s', 'air-wp-sync-pro-plus' ), $exception->getMessage() ) );
				}
			}
		}
		return $record;
	}
}
