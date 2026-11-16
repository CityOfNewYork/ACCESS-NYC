<?php
/**
 * Manage Formatter Strategies: register + format.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Abstract_Formatter class
 */
class Abstract_Formatter {

	/**
	 * Formatter strategies.
	 *
	 * @var Abstract_Formatter_Strategy[]
	 */
	protected $strategies = array();


	/**
	 * Register strategy.
	 *
	 * @param Abstract_Formatter_Strategy $strategy Formatter strategy to register.
	 *
	 * @return self
	 */
	public function register_strategy( $strategy ) {
		$this->strategies[ $strategy->get_slug() ] = $strategy;
		return $this;
	}

	/**
	 * String supported sources.
	 *
	 * @var string[]
	 */
	protected $string_supported_sources;

	/**
	 * Phone number supported sources.
	 *
	 * @var string[]
	 */
	protected $phone_number_supported_sources;

	/**
	 * Single line text supported sources.
	 * (expected input text fields that excludes radio buttons, selects, textarea...)
	 *
	 * @var string[]
	 */
	protected $single_line_text_supported_sources;

	/**
	 * Multiple lines text supported sources.
	 * (expected long text fields like textarea/html field that excludes radio buttons, single line text fields...).
	 *
	 * @var string[]
	 */
	protected $multiple_lines_text_supported_sources;

	/**
	 * Number supported sources.
	 *
	 * @var string[]
	 */
	protected $number_supported_sources;

	/**
	 * Currency supported sources.
	 *
	 * @var string[]
	 */
	protected $currency_supported_sources;

	/**
	 * Boolean supported sources.
	 *
	 * @var string[]
	 */
	protected $boolean_supported_sources;

	/**
	 * Datetime supported sources.
	 *
	 * @var string[]
	 */
	protected $datetime_supported_sources;

	/**
	 * Files supported sources.
	 *
	 * @var string[]
	 */
	protected $files_supported_sources;

	/**
	 * Email supported sources.
	 *
	 * @var string[]
	 */
	protected $email_supported_sources;

	/**
	 * URL supported sources.
	 *
	 * @var string[]
	 */
	protected $url_supported_sources;

	/**
	 * Single select supported sources.
	 *
	 * @var string[]
	 */
	protected $single_select_supported_sources;

	/**
	 * Multiple select supported sources.
	 *
	 * @var string[]
	 */
	protected $multiple_select_supported_sources;

	/**
	 * Returns relationship supported sources (a field that should return a list of records id from another table).
	 *
	 * @var string[]
	 */
	protected $relationship_supported_sources;


	/**
	 * Constructor
	 */
	public function __construct() {
	}

	/**
	 * Format value then return result.
	 *
	 * @param string               $strategy_slug Strategy’s slug.
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return mixed
	 * @throws \Exception The strategy’s "%s" is not registered.
	 */
	public function format( $strategy_slug, $value, $context ) {
		$map = false;
		if ( strpos( $strategy_slug, 'map:' ) === 0 ) {
			$strategy_slug_parts = explode( ':', $strategy_slug );
			$map                 = $strategy_slug_parts[0];
			$strategy_slug       = implode( ':', $strategy_slug_parts );
		}

		if ( $map ) {
			if ( empty( $value ) ) {
				$value = array();
			}
			if ( ! is_array( $value ) ) {
				throw new \Exception( esc_html( sprintf( '"map:" modifier on strategy slug "%s" is meant to be used on array', $strategy_slug ) ), 400 );
			}
			// Composed maps?
			if ( strpos( $strategy_slug, 'map:' ) === 0 ) {
				$format              = array( $this, 'format' );
				$strategy_slug_parts = explode( ':', $strategy_slug );
				array_shift( $strategy_slug_parts );
				$strategy_slug = implode( ':', $strategy_slug_parts );
				return array_map(
					function ( $sub_value ) use ( $format, $strategy_slug, $context ) {
						return call_user_func( $format, $strategy_slug, $sub_value, $context );
					},
					$value
				);
			}

			$strategy = $this->get_strategy( $strategy_slug );

			// Change source field type, we will convert with the map modifier the value from an array to single field value.
			$source_field = $context->get_source_field();
			if ( $source_field && strpos( $source_field->type, 'airwpsyncProxyRecordLinks|' ) === 0 ) {
				$source_field->type = str_replace( 'airwpsyncProxyRecordLinks|', '', $source_field->type );
				$context->set_source_field( $source_field );
			}
			$result = array(
				'success'         => true,
				'formatted_value' => array(),
				'messages'        => array(),
			);
			foreach ( $value as $sub_value ) {
				$sub_result                  = $strategy->format( $sub_value, $context );
				$result['success']           = $result['success'] && $sub_result['success'];
				$result['formatted_value'][] = $sub_result['formatted_value'];
				$result['messages']          = array_merge( $result['messages'], $sub_result['messages'] );
			}
		} else {
			$strategy = $this->get_strategy( $strategy_slug );
			$result   = $strategy->format( $value, $context );
		}

		if ( ! empty( $result['messages'] ) && $context->has_importer() ) {
			$log_level = $result['success'] ? 'log' : 'error';
			foreach ( $result['messages'] as $message ) {
				$context->get_importer()->log( $message, $log_level );
			}
		}

		return $result['formatted_value'];
	}

	/**
	 * Return the registered strategy if it exists.
	 *
	 * @param string $strategy_slug Strategy’s slug.
	 *
	 * @return Abstract_Formatter_Strategy
	 * @throws \Exception The strategy’s "%s" is not registered.
	 */
	protected function get_strategy( $strategy_slug ) {
		if ( ! isset( $this->strategies[ $strategy_slug ] ) ) {
			throw new \Exception( esc_html( sprintf( 'The strategy’s "%s" is not registered', $strategy_slug ) ), 400 );
		}
		return $this->strategies[ $strategy_slug ];
	}

	/**
	 * Returns string supported sources from remote.
	 *
	 * @return string[]
	 */
	public function get_string_supported_sources() {
		return $this->string_supported_sources;
	}

	/**
	 * Single line text supported sources.
	 * (expected input text fields that excludes radio buttons, selects, textarea...)
	 *
	 * @return string[]
	 */
	public function get_single_line_text_supported_sources(): array {
		return $this->single_line_text_supported_sources;
	}

	/**
	 * Multiple lines text supported sources.
	 * (expected long text fields like textarea/html field that excludes radio buttons, single line text fields...).
	 *
	 * @return string[]
	 */
	public function get_multiple_lines_text_supported_sources(): array {
		return $this->multiple_lines_text_supported_sources;
	}

	/**
	 * Returns number supported sources from remote.
	 *
	 * @return string[]
	 */
	public function get_number_supported_sources() {
		return $this->number_supported_sources;
	}

	/**
	 * Returns boolean supported sources from remote.
	 *
	 * @return string[]
	 */
	public function get_boolean_supported_sources() {
		return $this->boolean_supported_sources;
	}

	/**
	 * Returns datetime supported sources from remote.
	 *
	 * @return string[]
	 */
	public function get_datetime_supported_sources() {
		return $this->datetime_supported_sources;
	}

	/**
	 * Returns files supported sources from remote.
	 *
	 * @return string[]
	 */
	public function get_files_supported_sources() {
		return $this->files_supported_sources;
	}

	/**
	 * Return email supported sources from remote.
	 *
	 * @return string[]
	 */
	public function get_email_supported_sources(): array {
		return $this->email_supported_sources;
	}

	/**
	 * Return URL supported sources from remote.
	 *
	 * @return string[]
	 */
	public function get_url_supported_sources(): array {
		return $this->url_supported_sources;
	}

	/**
	 * Returns single select supported sources.
	 *
	 * @return string[]
	 */
	public function get_single_select_supported_sources(): array {
		return $this->single_select_supported_sources;
	}

	/**
	 * Returns multiple select supported sources.
	 *
	 * @return string[]
	 */
	public function get_multiple_select_supported_sources(): array {
		return $this->multiple_select_supported_sources;
	}

	/**
	 * Returns relationship supported sources (a field that should return a list of records id from another table).
	 *
	 * @return string[]
	 */
	public function get_relationship_supported_sources(): array {
		return $this->relationship_supported_sources;
	}

	/**
	 * Returns currency supported sources.
	 *
	 * @return string[]
	 */
	public function get_currency_supported_sources(): array {
		return $this->currency_supported_sources;
	}

	/**
	 * Returns phone number supported sources.
	 *
	 * @return string[]
	 */
	public function get_phone_number_supported_sources(): array {
		return $this->phone_number_supported_sources;
	}

	/**
	 * Prefix types with "airwpsyncProxyRecordLinks|".
	 *
	 * @param array $types Field types.
	 *
	 * @return string[]
	 */
	public function prefix_proxy_fields_types( $types ) {
		if ( ! is_array( $types ) ) {
			return array();
		}
		return array_map(
			function ( $type ) {
				return 'airwpsyncProxyRecordLinks|' . $type;
			},
			$types
		);
	}
}
