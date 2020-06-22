<?php

class WPML_Cache_Factory {

	/** @var array */
	private $valid_caches = [
		'TranslationManagement::get_translation_job_id' => [
			'clear_actions' => [ 'wpml_tm_save_post', 'wpml_cache_clear' ],
		],

		'WPML_Element_Type_Translation::get_language_for_element' => [
			'clear_actions' => [ 'wpml_translation_update' ],
		],

		'WPML_Post_Status::needs_update'                => [
			'clear_actions' => [ 'wpml_translation_status_update' ],
		],
	];

	public function __construct() {
		foreach ( $this->valid_caches as $cache_name => $clear_actions ) {
			$this->init_clear_actions( $cache_name, $clear_actions['clear_actions'] );
		}
	}

	/**
	 * @param string $cache_name
	 *
	 * @return WPML_WP_Cache
	 * @throws InvalidArgumentException Exception.
	 */
	public function get( $cache_name ) {
		if ( isset( $this->valid_caches[ $cache_name ] ) ) {
			return new WPML_WP_Cache( $cache_name );
		} else {
			throw new InvalidArgumentException( $cache_name . ' is not a valid cache for the WPML_Cache_Factory' );
		}
	}

	/**
	 * @param string $cache_name
	 * @param array  $clear_actions
	 */
	public function define( $cache_name, array $clear_actions ) {
		if ( isset( $this->valid_caches[ $cache_name ] ) ) {
			return;
		}

		$this->valid_caches[ $cache_name ] = [
			'clear_actions' => $clear_actions,
		];
		$this->init_clear_actions( $cache_name, $clear_actions );
	}

	private function init_clear_actions( $cache_name, array $clear_actions ) {
		foreach ( $clear_actions as $clear_action ) {
			add_action(
				$clear_action,
				function () use ( $cache_name ) {
					$cache = new WPML_WP_Cache( $cache_name );
					$cache->flush_group_cache();
				}
			);
		}
	}
}
