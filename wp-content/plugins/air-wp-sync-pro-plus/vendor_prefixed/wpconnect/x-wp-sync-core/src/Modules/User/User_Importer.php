<?php
/**
 * User Importer.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Modules\User;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Importers\Abstract_Importer;

/**
 * Class User_Importer
 */
class User_Importer extends Abstract_Importer {

	/**
	 * {@inheritDoc}
	 *
	 * @param string $run_id Run id.
	 */
	public function delete_removed_contents( $run_id ) {
		$content_ids = $this->get_run_option( $run_id, 'content_ids', array() );
		if ( ! is_array( $content_ids ) ) {
			$content_ids = array();
		}

		$users = get_users(
			array(
				'exclude'    => $content_ids,
				'fields'     => 'ID',
				'number'     => -1,
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_air_wp_sync_importer_id',
						'value' => $this->infos()->get( 'id' ),
					),
				),
			)
		);

		$count_deleted = 0;
		foreach ( $users as $user_id ) {
			if ( wp_delete_user( $user_id ) ) {
				++$count_deleted;
			}
		}
		update_post_meta( $this->infos()->get( 'id' ), 'count_deleted', $count_deleted );
		return $count_deleted;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param object     $record Remote record.
	 * @param array      $fields Remote record fields.
	 * @param mixed|null $user_id WordPress object id.
	 *
	 * @throws \Exception From wp_update_post / wp_insert_post.
	 */
	protected function import_record( $record, $fields, $user_id = null ) {

		$user_metas = array(
			'_air_wp_sync_record_id'   => $record->id,
			'_air_wp_sync_hash'        => $this->generate_hash( $record ),
			'_air_wp_sync_importer_id' => $this->infos()->get( 'id' ),
			'_air_wp_sync_updated_at'  => gmdate( 'Y-m-d H:i:s' ),
		);

		/**
		 * Filters import user data that will be used by wp_update_user and wp_insert_user.
		 *
		 * @param array $user_data User data.
		 * @param Abstract_Importer $importer Importer.
		 * @param array $fields Fields.
		 * @param \stdClass $record Remote record.
		 * @param mixed|null $user_id WordPress user id.
		 */
		$user_data = apply_filters( 'airwpsync/import_user_data', array(), $this, $fields, $record, $user_id );

		// Make sure we have mandatory data.
		if ( empty( $user_data['user_pass'] ) && ! $user_id ) {
			$user_data['user_pass'] = wp_generate_password();
		}
		if ( empty( $user_data['role'] ) ) {
			$user_data['role'] = $this->config()->get( 'default_role' );
		}
		if ( empty( $user_data['locale'] ) && 'site-default' !== $this->config()->get( 'default_locale' ) ) {
			$user_data['locale'] = $this->config()->get( 'default_locale' );
		}

		$all_roles_keys = User_Helpers::get_available_user_roles_keys();
		if ( ! empty( $user_data['role'] ) && ! in_array( $user_data['role'], $all_roles_keys, true ) ) {
			$this->log( sprintf( '- The role "%s" does not exist, it has been ignored. Allowed list: %s', $user_data['role'], implode( ', ', $all_roles_keys ) ) );
			unset( $user_data['role'] );
		}

		$is_new_user = null === $user_id;

		// Insert or update post.
		if ( $user_id ) {
			$user_id = wp_update_user( array_merge( array( 'ID' => $user_id ), $user_data ), true );
		} else {
			$user_id = wp_insert_user( $user_data, true );
		}

		if ( is_wp_error( $user_id ) ) {
			throw new \Exception( esc_html( $user_id->get_error_message() ) );
		}

		// Handle metas.
		foreach ( $user_metas as $meta_key => $meta_value ) {
			update_user_meta( $user_id, $meta_key, $meta_value );
		}

		/**
		 * Fires after the record has been imported.
		 *
		 * @param Abstract_Importer $importer Importer.
		 * @param array $fields Fields.
		 * @param \stdClass $record Remote record.
		 * @param mixed|null $existing_object_id WordPress object id.
		 */
		do_action( 'airwpsync/import_record_after', $this, $fields, $record, $user_id );

		if ( $is_new_user && $this->config()->get( 'send_user_notification' ) ) {
			do_action( 'edit_user_created_user', $user_id, 'both' );
		}

		return $user_id;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param object $record Remote record.
	 * @param bool   $exclude_other_connections Exclude other connections from the search.
	 *
	 * @return mixed|false
	 */
	protected function get_existing_content_id( $record, $exclude_other_connections = true ) {
		$record       = apply_filters( 'airwpsync/pre_check_existing_content', $record, $this, array( 'user::user_email' ), $this->get_remote_data_provider()->get_import_fields_options() );
		$mapping      = ! empty( $this->config()->get( 'mapping' ) ) ? $this->config()->get( 'mapping' ) : array();
		$remote_value = '';
		foreach ( $mapping as $mapping_field ) {
			if ( 'user::user_email' === $mapping_field['wordpress'] ) {
				$remote_key   = $mapping_field['airtable'];
				$remote_value = isset( $record->fields->$remote_key ) ? $record->fields->$remote_key : '';
			}
		}
		$user = get_user_by( 'email', $remote_value );
		// Historically, all users could be found by this function, whether it has been created by this connection or not (contrary to other module importers) so we need to ignore the $exclude_other_connections param.
		return $user ? $user->ID : false;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $content_id WordPress object id.
	 */
	protected function get_existing_content_hash( $content_id ) {
		return get_user_meta( $content_id, '_air_wp_sync_hash', true );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $object_id WordPress object id.
	 * @return bool true if the content has been deleted.
	 */
	public function delete_created_content( $object_id ) {
		return wp_delete_user( $object_id, apply_filters( 'airwpsync/user_importer/user_id_posts_are_reassigned_to', 1 ) );
	}
}
