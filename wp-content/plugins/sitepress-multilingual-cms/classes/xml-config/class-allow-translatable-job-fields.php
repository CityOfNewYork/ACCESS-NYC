<?php

namespace WPML\XMLConfig;

use WPML\FP\Obj;

class AllowTranslatableJobFields extends \WPML_WP_Option implements \IWPML_Backend_Action, \IWPML_AJAX_Action {

	public function get_key() {
		return 'wpml_allow_translatable_job_fields';
	}

	public function get_default() {
		return [];
	}

	public function add_hooks() {
		add_filter( 'wpml_config_array', [ $this, 'wpml_config_filter' ] );
		add_filter( 'wpml_tm_job_field_is_translatable', [ $this, 'filter_job_field' ], 10, 3 );
	}

	/**
	 * @param array $config
	 *
	 * @return array
	 */
	public function wpml_config_filter( $config ) {
		$data = Obj::pathOr( [], [ 'wpml-config', 'allow-translatable-job-fields' ], $config );

		$cleaned = wpml_collect( $data )
			->collapse()
			->map( Obj::prop( 'attr' ) )
			->all();

		$this->set( $cleaned );

		return $config;
	}

	/**
	 * @param bool   $isTranslatable
	 * @param array  $field
	 * @param string $value
	 *
	 * @return bool
	 */
	public function filter_job_field( $isTranslatable, $field, $value ) {
		if ( $isTranslatable ) {
			return $isTranslatable;
		}

		$config = (array) $this->get();

		if ( ! $config ) {
			return $isTranslatable;
		}

		$doesPass = function( $rule ) use ( $field, $value ) {
			try {
				return preg_match( $rule['type'], $field['field_type'] ) && preg_match( $rule['value'], $value );
			} catch ( \Exception $e ) {
				return false;
			}
		};

		return (bool) wpml_collect( $config )
			->first( $doesPass );
	}
}
