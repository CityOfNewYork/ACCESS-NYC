<?php
/**
 * Manage Airtable Formatter Strategies: register + format.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter;
use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters\Abstract_Formatter_Strategy;

/**
 * Airtable_Formatter class
 */
class Airtable_Formatter extends Abstract_Formatter {

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
	protected $string_supported_sources = array(
		'autoNumber',
		'barcode.type',
		'barcode.text',
		'checkbox',
		'count',
		'createdTime',
		'createdBy.id',
		'createdBy.email',
		'createdBy.name',
		'currency',
		'date',
		'dateTime',
		'duration',
		'email',
		'externalSyncSource',
		'lastModifiedBy.id',
		'lastModifiedBy.email',
		'lastModifiedBy.name',
		'lastModifiedTime',
		'multipleCollaborators.id',
		'multipleCollaborators.email',
		'multipleCollaborators.name',
		'multipleRecordLinks',
		'multipleSelects',
		'multilineText',
		'number',
		'percent',
		'phoneNumber',
		'rating',
		'richText',
		'rollup',
		'singleCollaborator.id',
		'singleCollaborator.email',
		'singleCollaborator.name',
		'singleLineText',
		'singleSelect',
		'url',
	);

	/**
	 * Phone number supported sources.
	 *
	 * @var string[]
	 */
	protected $phone_number_supported_sources = array( 'phoneNumber' );

	/**
	 * Single line text supported sources.
	 * (expected input text fields that excludes radio buttons, selects, textarea...)
	 *
	 * @var string[]
	 */
	protected $single_line_text_supported_sources = array( 'singleLineText' );

	/**
	 * Multiple lines text supported sources.
	 * (expected long text fields like textarea/html field that excludes radio buttons, single line text fields...).
	 *
	 * @var string[]
	 */
	protected $multiple_lines_text_supported_sources = array( 'richText', 'multilineText' );

	/**
	 * Number supported sources.
	 *
	 * @var string[]
	 */
	protected $number_supported_sources = array( 'number', 'rollup', 'formula' );

	/**
	 * Currency supported sources.
	 *
	 * @var string[]
	 */
	protected $currency_supported_sources = array( 'currency' );

	/**
	 * Boolean supported sources.
	 *
	 * @var string[]
	 */
	protected $boolean_supported_sources = array( 'checkbox' );

	/**
	 * Datetime supported sources.
	 *
	 * @var string[]
	 */
	protected $datetime_supported_sources = array( 'date', 'dateTime', 'createdTime', 'lastModifiedTime' );

	/**
	 * Files supported sources.
	 *
	 * @var string[]
	 */
	protected $files_supported_sources = array( 'multipleAttachments' );

	/**
	 * Email supported sources.
	 *
	 * @var string[]
	 */
	protected $email_supported_sources = array( 'email', 'createdBy.email', 'lastModifiedBy.email', 'multipleCollaborators.email', 'singleCollaborator.email' );

	/**
	 * URL supported sources.
	 *
	 * @var string[]
	 */
	protected $url_supported_sources = array( 'url' );

	/**
	 * Single select supported sources.
	 *
	 * @var string[]
	 */
	protected $single_select_supported_sources = array( 'singleSelect' );

	/**
	 * Multiple select supported sources.
	 *
	 * @var string[]
	 */
	protected $multiple_select_supported_sources = array( 'multipleSelects' );


	/**
	 * Returns relationship supported sources (a field that should return a list of records id from another table).
	 *
	 * @var string[]
	 */
	protected $relationship_supported_sources = array( 'multipleRecordLinks' );
}
