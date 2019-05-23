<?php

class WPML_TM_Jobs_List_Services {
	/** @var wpdb */
	private $wpdb;

	/** @var WPML_TM_Rest_Jobs_Translation_Service */
	private $service_names;

	/** @var array|null */
	private $cache;

	public function __construct( WPML_TM_Rest_Jobs_Translation_Service $service_names ) {
		global $wpdb;
		$this->wpdb          = $wpdb;
		$this->service_names = $service_names;
	}

	public function get() {
		if ( $this->cache === null ) {
			$sql = "
			SELECT * 
			FROM 
			(	
				(
					SELECT translation_service
					FROM {$this->wpdb->prefix}icl_translation_status
				) UNION (
					SELECT translation_service
					FROM {$this->wpdb->prefix}icl_string_translations
				)
			) as services
			WHERE translation_service != 'local' AND translation_service != '' 
			";

			$this->cache = array_map( array( $this, 'map' ), $this->wpdb->get_col( $sql ) );
		}

		return $this->cache;
	}

	private function map( $translation_service_id ) {
		return array(
			'value' => $translation_service_id,
			'label' => $this->service_names->get_name( $translation_service_id ),
		);
	}
}