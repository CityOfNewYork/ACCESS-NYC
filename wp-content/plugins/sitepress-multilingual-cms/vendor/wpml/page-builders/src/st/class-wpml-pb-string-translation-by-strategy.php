<?php

class WPML_PB_String_Translation_By_Strategy extends WPML_PB_String_Translation {

	/** @var WPML_PB_Factory $factory */
	private $factory;

	/** @var IWPML_PB_Strategy $strategy */
	private $strategy;

	/** @var array $packages_to_update */
	private $packages_to_update = array();

	public function __construct( wpdb $wpdb, WPML_PB_Factory $factory, IWPML_PB_Strategy $strategy ) {
		$this->factory  = $factory;
		$this->strategy = $strategy;
		parent::__construct( $wpdb );
	}

	/** @param int $translated_string_id */
	public function new_translation( $translated_string_id ) {
		list( $package_id, $string_id, $language ) = $this->get_package_for_translated_string( $translated_string_id );
		if ( $package_id ) {
			$package = $this->factory->get_wpml_package( $package_id );
			if ( $package->post_id && $this->strategy->get_package_kind() === $package->kind ) {
				$this->add_package_to_update_list( $package, $language );
			}
		}
	}

	public function save_translations_to_post() {
		foreach ( $this->packages_to_update as $package_data ) {
			if ( $package_data['package']->kind == $this->strategy->get_package_kind() ) {
				$update_post = $this->strategy->get_update_post( $package_data );
				$update_post->update();
			}
		}
	}

	/**
	 * @param int $translated_string_id
	 *
	 * @return array
	 */
	private function get_package_for_translated_string( $translated_string_id ) {
		$sql    = $this->wpdb->prepare(
			"SELECT s.string_package_id, s.id, t.language
			FROM {$this->wpdb->prefix}icl_strings s
			LEFT JOIN {$this->wpdb->prefix}icl_string_translations t
			ON s.id = t.string_id
			WHERE t.id = %d", $translated_string_id );
		$result = $this->wpdb->get_row( $sql );

		if ( $result ) {
			return array( $result->string_package_id, $result->id, $result->language );
		} else {
			return array( null, null, null );
		}
	}

	/**
	 * @param WPML_Package $package
	 * @param string       $language
	 */
	public function add_package_to_update_list( WPML_Package $package, $language ) {
		if ( ! isset( $this->packages_to_update[ $package->ID ] ) ) {
			$this->packages_to_update[ $package->ID ] = array( 'package'   => $package,
			                                                   'languages' => array( $language )
			);
		} else {
			if ( ! in_array( $language, $this->packages_to_update[ $package->ID ]['languages'] ) ) {
				$this->packages_to_update[ $package->ID ]['languages'][] = $language;
			}
		}
	}
}
