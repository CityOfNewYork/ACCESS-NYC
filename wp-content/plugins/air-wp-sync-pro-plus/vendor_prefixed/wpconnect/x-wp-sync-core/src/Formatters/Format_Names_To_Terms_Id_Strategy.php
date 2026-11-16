<?php
/**
 * Format names to terms id strategy.
 * Requires a 'taxonomy' arg in the import field context.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

/**
 * Format_Names_To_Terms_Id_Strategy class
 */
class Format_Names_To_Terms_Id_Strategy extends Abstract_Formatter_Strategy {
	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	protected $slug = 'format_names_to_terms_id';

	/**
	 * Formatter.
	 *
	 * @var Formatter
	 */
	protected $formatter;

	/**
	 * Term formatter.
	 *
	 * @var Terms_Formatter
	 */
	protected $term_formatter;

	/**
	 * Constructor
	 *
	 * @param Formatter       $formatter Formatter.
	 * @param Terms_Formatter $term_formatter Term formatter.
	 */
	public function __construct( $formatter, $term_formatter ) {
		parent::__construct();

		$this->formatter      = $formatter;
		$this->term_formatter = $term_formatter;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return array
	 * @throws \Exception Format_To_String_Strategy->format: importer and taxonomy are required in the context.
	 */
	public function format( $value, $context ) {
		if ( ! $context->has_importer() || ! $context->has_arg( 'taxonomy' ) ) {
			throw new \Exception( 'Format_To_String_Strategy->format: importer and taxonomy are required in the context.', 400 );
		}

		$messages = array();
		if ( ! is_array( $value ) ) {
			$value = $this->formatter->format( 'format_to_string', $value, $context );
			if ( ! is_string( $value ) ) {
				return array(
					'success'         => false,
					'formatted_value' => array(),
					'messages'        => array(),
				);
			}
		}

		$split_comma_separated_string_into_terms = $context->has_arg( 'split_comma_separated_string_into_terms' ) && $context->get_arg( 'split_comma_separated_string_into_terms' );

		$value = $this->term_formatter->format( $value, $context->get_importer(), $context->get_arg( 'taxonomy' ), $split_comma_separated_string_into_terms );

		return array(
			'success'         => is_array( $value ),
			'formatted_value' => $value,
			'messages'        => $messages,
		);
	}
}
