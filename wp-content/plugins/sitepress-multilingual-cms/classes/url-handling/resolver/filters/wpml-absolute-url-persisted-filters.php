<?php

class WPML_Absolute_Url_Persisted_Filters implements IWPML_Action {

	/** @var WPML_Absolute_Url_Persisted $url_persisted */
	private $url_persisted;

	public function __construct( WPML_Absolute_Url_Persisted $url_persisted ) {
		$this->url_persisted = $url_persisted;
	}

	public function add_hooks() {
		add_filter( 'wp_insert_post_data', [ $this, 'reset' ] );
		add_action( 'delete_post', [ $this, 'reset' ] );

		add_filter( 'wp_update_term_data', [ $this, 'reset' ] );
		add_action( 'pre_delete_term', [ $this, 'reset' ] );

		add_filter( 'rewrite_rules_array', [ $this, 'reset' ] );
	}

	/**
	 * @param mixed $data
	 *
	 * @return array
	 */
	public function reset( $data = null ) {
		$this->url_persisted->reset();
		return $data;
	}
}
