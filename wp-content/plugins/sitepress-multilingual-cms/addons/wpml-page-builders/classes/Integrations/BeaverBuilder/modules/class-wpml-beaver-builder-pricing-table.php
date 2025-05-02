<?php
class WPML_Beaver_Builder_Pricing_Table extends WPML_Beaver_Builder_Module_With_Items {


	/**
	 * @param object $settings
	 *
	 * @return array
	 */
	public function &get_items( $settings ) {
		return $settings->pricing_columns;
	}

	/**
	 * @return array
	 */
	public function get_fields() {
		return [ 'title', 'button_text', 'button_url', 'price', 'duration', 'ribbon_text' ];
	}

	/**
	 * @return array
	 */
	private function get_billing_fields() {
		return [ 'billing_option_1', 'billing_option_2' ];
	}

	/**
	 * @return array
	 */
	private function get_extended_features_fields() {
		return [ 'description', 'tooltip' ];
	}

	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'title':
				return esc_html__( 'Pricing table: Title', 'sitepress' );

			case 'button_text':
				return esc_html__( 'Pricing table: Button text', 'sitepress' );

			case 'button_url':
				return esc_html__( 'Pricing table: Button link', 'sitepress' );

			case 'price':
				return esc_html__( 'Pricing table: Price', 'sitepress' );

			case 'duration':
				return esc_html__( 'Pricing table: Duration', 'sitepress' );

			case 'ribbon_text':
				return esc_html__( 'Pricing table: Ribbon Text', 'sitepress' );

			case 'billing_option_1':
				return esc_html__( 'Pricing table: Billing Option 1', 'sitepress' );

			case 'billing_option_2':
				return esc_html__( 'Pricing table: Billing Option 2', 'sitepress' );

			case 'description':
				return esc_html__( 'Pricing table: Feature Description', 'sitepress' );

			case 'tooltip':
				return esc_html__( 'Pricing table: Feature Tooltip', 'sitepress' );

			default:
				return '';

		}
	}


	/**
	 * @param string $field
	 *
	 * @return string
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'title':
			case 'button_text':
			case 'price':
			case 'duration':
			case 'description':
			case 'tooltip':
			case 'ribbon_text':
			case 'billing_option_1':
			case 'billing_option_2':
				return 'LINE';

			case 'button_url':
				return 'LINK';

			default:
				return '';
		}
	}

	/**
	 * @param string $node_id
	 * @param mixed  $value
	 * @param string $field
	 *
	 * @return string
	 */
	private function get_string_name( $node_id, $value, $field ) {
		return md5( $value . '-' . $field . '-' . $node_id );
	}

	/**
	 * @param string $node_id
	 * @param object $settings
	 * @param array  $strings
	 *
	 * @return array
	 */
	public function get( $node_id, $settings, $strings ) {
		$strings = parent::get( $node_id, $settings, $strings );

		$strings = $this->add_billing_fields( $strings, $node_id, $settings );
		$strings = $this->add_extended_features_fields( $strings, $node_id, $settings );

		return $strings;
	}

	/**
	 * @param string $node_id
	 * @param object $settings
	 * @param array  $strings
	 *
	 * @return array
	 */
	private function add_billing_fields( $strings, $node_id, $settings ) {

		foreach ( $this->get_billing_fields() as $billing_field ) {
			if ( ! isset( $settings->$billing_field ) || ! is_string( $settings->$billing_field ) ) {
				continue;
			}

			$strings[] = new WPML_PB_String(
				$settings->$billing_field,
				$this->get_string_name( $node_id, $settings->$billing_field, $billing_field ),
				$this->get_title( $billing_field ),
				$this->get_editor_type( $billing_field )
			);
		}

		return $strings;
	}

	/**
	 *
	 * @param array  $strings
	 * @param string $node_id
	 * @param object $settings
	 *
	 * @return array
	 */
	private function add_extended_features_fields( $strings, $node_id, $settings ) {
		if ( ! isset( $settings->pricing_columns ) || ! is_array( $settings->pricing_columns ) ) {
			return $strings;
		}

		foreach ( $settings->pricing_columns as $column ) {
			if ( ! $this->contain_valid_extended_features( $column ) ) {
				continue;
			}

			$extendedFeatures = $column->extended_features;

			foreach ( $extendedFeatures as $feature ) {
				foreach ( $this->get_extended_features_fields() as $field ) {
					if ( isset( $feature->$field ) && is_string( $feature->$field ) ) {
						$strings[] = new WPML_PB_String(
							$feature->$field,
							$this->get_string_name( $node_id, $feature->$field, $field ),
							$this->get_title( $field ),
							$this->get_editor_type( $field )
						);
					}
				}
			}
		}

		return $strings;
	}

	/**
	 * @param object $column
	 *
	 * @return bool
	 */
	private function contain_valid_extended_features( $column ) {
		return isset( $column->extended_features )
		&& ( is_array( $column->extended_features ) || is_object( $column->extended_features ) );
	}

	/**
	 * @param string         $node_id
	 * @param object         $settings
	 * @param WPML_PB_String $string
	 */
	public function update( $node_id, $settings, WPML_PB_String $string ) {
		parent::update( $node_id, $settings, $string );

		$this->update_billing_fields( $node_id, $settings, $string );
		$this->update_extended_features_fields( $node_id, $settings, $string );

		return null;
	}

	/**
	 * @param string         $node_id
	 * @param object|mixed   $settings
	 * @param WPML_PB_String $string
	 */
	private function update_billing_fields( $node_id, $settings, $string ) {
		if ( ! is_object( $settings ) ) {
			return;
		}

		foreach ( $this->get_billing_fields() as $billing_field ) {
			if ( ! is_string( $settings->$billing_field ) ) {
				continue;
			}

			if ( $this->get_string_name( $node_id, $settings->$billing_field, $billing_field ) === $string->get_name() ) {
				$settings->$billing_field = $string->get_value();
			}
		}
	}

	/**
	 * @param string         $node_id
	 * @param object         $settings
	 * @param WPML_PB_String $string
	 */
	private function update_extended_features_fields( $node_id, $settings, $string ) {
		if ( ! is_array( $settings->pricing_columns ) ) {
			return;
		}

		foreach ( $settings->pricing_columns as &$column ) {
			if ( ! $this->contain_valid_extended_features( $column ) ) {
				continue;
			}

			foreach ( $column->extended_features as &$feature ) {
				foreach ( $this->get_extended_features_fields() as $field ) {
					if ( isset( $feature->$field ) && $this->get_string_name( $node_id, $feature->$field, $field ) === $string->get_name() ) {
						$feature->$field = $string->get_value();
					}
				}
			}
		}
	}

}
