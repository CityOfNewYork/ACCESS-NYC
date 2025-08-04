<?php

namespace Gravity_Forms\Gravity_Tools\Model;

use Gravity_Forms\Gravity_Tools\Utils\Common;

class Form_Model {

	/**
	 * @var Common
	 */
	protected $common;

	public function __construct( $common ) {
		$this->common = $common;
	}

	/**
	 * Gets the entry table name, including the site's database prefix
	 *
	 * @since 1.0
	 *
	 * @global $wpdb
	 *
	 * @return string The entry table name
	 */
	public function get_entry_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'gf_entry';
	}

	/**
	 * Returns the current database version.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_database_version() {
		static $db_version = array();
		$blog_id = get_current_blog_id();
		if ( empty( $db_version[ $blog_id ] ) ) {
			$db_version[ $blog_id ] = get_option( 'gf_db_version' );
		}

		return $db_version[ $blog_id ];
	}

	/**
	 * Gets the total, active, inactive, and trashed form counts.
	 *
	 * @since  1.0
	 *
	 * @uses GFFormsModel::get_form_table_name()
	 *
	 * @return array The form counts.
	 */
	public function get_form_count() {
		global $wpdb;
		$form_table_name = $this->get_form_table_name();

		if ( ! $this->common->table_exists( $form_table_name ) ) {
			return array(
				'total'    => 0,
				'active'   => 0,
				'inactive' => 0,
				'trash'    => 0,
			);
		}

		$results = $wpdb->get_results(
			"
            SELECT
            (SELECT count(0) FROM $form_table_name WHERE is_trash = 0) as total,
            (SELECT count(0) FROM $form_table_name WHERE is_active=1 AND is_trash = 0 ) as active,
            (SELECT count(0) FROM $form_table_name WHERE is_active=0 AND is_trash = 0 ) as inactive,
            (SELECT count(0) FROM $form_table_name WHERE is_trash=1) as trash
            "
		);

		return array(
			'total'    => intval( $results[0]->total ),
			'active'   => intval( $results[0]->active ),
			'inactive' => intval( $results[0]->inactive ),
			'trash'    => intval( $results[0]->trash ),
		);
	}

	/**
	 * Gets the form table name, including the site's database prefix.
	 *
	 * @since  1.0
	 *
	 * @return string The form table name.
	 */
	public function get_form_table_name() {
		global $wpdb;

		if ( version_compare( $this->get_database_version(), '2.3-dev-1', '<' ) ) {
			return $wpdb->prefix . 'rg_form';
		}

		return $wpdb->prefix . 'gf_form';
	}

	/**
	 * Returns the entry count for all forms.
	 *
	 * @since 1.0
	 *
	 * @param string $status
	 *
	 * @return null|string
	 */
	public function get_entry_count_all_forms( $status = 'active' ) {
		global $wpdb;

		if ( version_compare( $this->get_database_version(), '2.3-dev-1', '<' ) ) {
			return $this->get_lead_count_all_forms( $status );
		}

		$entry_table_name   = $this->get_entry_table_name();

		if ( ! $this->common->table_exists( $entry_table_name ) ) {
			return 0;
		}

		$sql = $wpdb->prepare( "SELECT count(id)
								FROM $entry_table_name
								WHERE status=%s", $status );

		return $wpdb->get_var( $sql );
	}

	/**
	 * Get entry meta counts.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public function get_entry_meta_counts() {
		global $wpdb;

		$detail_table_name = $this->get_lead_details_table_name();
		$meta_table_name = $this->get_lead_meta_table_name();
		$notes_table_name = $this->get_lead_notes_table_name();

		$results = $wpdb->get_results(
			"
            SELECT
            (SELECT count(0) FROM $detail_table_name) as details,
            (SELECT count(0) FROM $meta_table_name) as meta,
            (SELECT count(0) FROM $notes_table_name) as notes
            "
		);

		return array(
			'details' => intval( $results[0]->details ),
			'meta'    => intval( $results[0]->meta ),
			'notes'   => intval( $results[0]->notes ),
		);

	}

	/**
	 * Get lead counts for all forms.
	 *
	 * @since 1.0
	 *
	 * @param $status
	 *
	 * @return mixed
	 */
	public function get_lead_count_all_forms( $status = 'active' ) {
		global $wpdb;

		$lead_table_name   = $this->get_lead_table_name();

		$result = $wpdb->get_var( "SHOW COLUMNS FROM $lead_table_name LIKE 'status'" );

		if ( $result ) {
			$sql = $wpdb->prepare( "SELECT count(id)
								FROM $lead_table_name
								WHERE status=%s", $status );
		} else {
			$sql = "SELECT count(id) FROM $lead_table_name";
		}

		return $wpdb->get_var( $sql );
	}

	/**
	 * Gets the lead (entries) table name, including the site's database prefix.
	 *
	 * @since  1.0

	 * @global $wpdb
	 *
	 * @return string The lead (entry) table name.
	 */
	public function get_lead_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'rg_lead';
	}

	/**
	 * Gets the lead (entry) meta table name, including the site's database prefix.
	 *
	 * @since  1.0

	 * @global $wpdb
	 *
	 * @return string The lead (entry) meta table name.
	 */
	public function get_lead_meta_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'rg_lead_meta';
	}

	/**
	 * Gets the lead (entry) notes table name, including the site's database prefix.
	 *
	 * @since  1.0
	 *
	 * @global $wpdb
	 *
	 * @return string The lead (entry) notes table name.
	 */
	public function get_lead_notes_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'rg_lead_notes';
	}

	/**
	 * Gets the lead (entry) details table name, including the site's database prefix.
	 *
	 * @since  1.0
	 *
	 * @global $wpdb
	 *
	 * @return string The lead (entry) details table name.
	 */
	public function get_lead_details_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'rg_lead_detail';
	}

	/**
	 * Gets the lead (entry) details long table name, including the site's database prefix.
	 *
	 * @since  1.0
	 *
	 * @global $wpdb
	 *
	 * @return string The lead (entry) details long table name.
	 */
	public function get_lead_details_long_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'rg_lead_detail_long';
	}

	/**
	 * Gets the lead (entry) view table name, including the site's database prefix.
	 *
	 * @since  1.0
	 *
	 * @global $wpdb
	 *
	 * @return string The lead (entry) view table name.
	 */
	public function get_lead_view_name() {
		global $wpdb;

		return $wpdb->prefix . 'rg_lead_view';
	}

}