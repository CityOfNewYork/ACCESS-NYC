<?php

namespace Gravity_Forms\Gravity_SMTP\Models;

class Notifications_Model {

	protected $table_name = 'gf_form_meta';

	protected function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . $this->table_name;
	}

	public function all() {
		global $wpdb;

		$sql = 'SELECT * FROM %1$s ORDER BY %2$s ASC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), 'form_id' ), ARRAY_A );

		return $results;
	}

	public function slice( $count, $offset = 0 ) {
		global $wpdb;
		$table_name = $this->get_table_name();

		$sql = "SELECT * FROM $table_name LIMIT %d, %d;";
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $offset, $count ), ARRAY_A );

		return $results;
	}

	public function by_service( $service_name ) {
		global $wpdb;

		$like = sprintf( '%%service":"%1$s%%', $service_name );
		$sql = 'SELECT * FROM %1$s WHERE %2$s LIKE \'%3$s\' ORDER BY %4$s ASC;';

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $this->get_table_name(), 'notifications', $like, 'form_id' ), ARRAY_A );

		return $results;
	}

}