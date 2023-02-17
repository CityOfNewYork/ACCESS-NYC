<?php

class WPML_TM_Rest_Jobs_Columns {
	/**
	 * @return array
	 */
	public static function get_columns() {
		return array(
			'id'         => __( 'ID', 'wpml-translation-management' ),
			'title'      => __( 'Title', 'wpml-translation-management' ),
			'languages'  => __( 'Languages', 'wpml-translation-management' ),
			'batch_name' => __( 'Batch name', 'wpml-translation-management' ),
			'translator' => __( 'Translated by', 'wpml-translation-management' ),
			'sent_date'  => __( 'Sent on', 'wpml-translation-management' ),
			'deadline'   => __( 'Deadline', 'wpml-translation-management' ),
			'status'     => __( 'Status', 'wpml-translation-management' ),
		);
	}

	/**
	 * @return array
	 */
	public static function get_sortable() {
		return array(
			'id'            => __( 'ID', 'wpml-translation-management' ),
			'title'         => __( 'Title', 'wpml-translation-management' ),
			'batch_name'    => __( 'Batch name', 'wpml-translation-management' ),
			'language'      => __( 'Language', 'wpml-translation-management' ),
			'sent_date'     => __( 'Sent on', 'wpml-translation-management' ),
			'deadline_date' => __( 'Deadline', 'wpml-translation-management' ),
			'status'        => __( 'Status', 'wpml-translation-management' ),
		);
	}
}
