<?php
namespace GatherContent\Importer\Settings;

class Setting {

	protected $option_name;
	public $options;

	public function __construct( $option_name, $default_options = array() ) {
		$this->option_name = $option_name;
		$this->options = get_option( $this->option_name, array() );
		$this->options = is_array( $this->options ) ? $this->options : array();

		if ( false === $this->options && false !== $default_options ) {
			$this->options = $default_options;

			// Initiate the option, and do NOT autoload option.
			add_option(
				$this->option_name,
				apply_filters( "{$this->option_name}_default_options", $this->options ),
				'',
				'no'
			);
		}
	}

	/**
	 * Get option value.
	 *
	 * @since  3.0.0
	 *
	 * @param  [type]  $key [description]
	 *
	 * @return [type]       [description]
	 */
	public function get( $key ) {
		if ( array_key_exists( $key, $this->options ) ) {
			return $this->options[ $key ];
		}

		return false;
	}

	public function update() {
		update_option( $this->option_name, $this->options, false );
	}

	public function all() {
		return $this->options;
	}

	public function sanitize_settings( $options ) {
		if ( is_array( $options ) ) {
			$this->options = array_map( 'sanitize_text_field', $options );
		} else {
			$this->options = is_scalar( $options ) ? sanitize_text_field( $options ) : '';
		}

		return $this->options;
	}
}
