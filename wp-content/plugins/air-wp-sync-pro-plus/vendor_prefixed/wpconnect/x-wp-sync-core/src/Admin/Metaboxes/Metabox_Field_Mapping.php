<?php
/**
 * Manages field mapping.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Admin\Metaboxes;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Plugin_Strings;

/**
 * Class Metabox_Field_Mapping
 */
class Metabox_Field_Mapping {

	/**
	 * Plugin’s strings.
	 *
	 * @var Plugin_Strings
	 */
	private $plugin_strings;

	/**
	 * Constructor
	 *
	 * @param Plugin_Strings $plugin_strings Plugin’s specific strings.
	 */
	public function __construct( $plugin_strings ) {
		$this->plugin_strings = $plugin_strings;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_filter( 'airwpsync/mapping_validation_rules', array( $this, 'add_mapping_validation_rules' ), 10, 1 );
	}

	/**
	 * Add metabox
	 */
	public function add_meta_box() {
		$tooltip_html = '<span class="airwpsync-tooltip" aria-label="' . esc_attr( $this->plugin_strings->get_string( 'admin.metaboxes.metabox_field_mapping.field_mapping_tooltip' ) ) . '">?</span>';
		add_meta_box(
			'airwpsync-mapping',
			__( 'Field Mapping', 'air-wp-sync-pro-plus' ) . $tooltip_html,
			array( $this, 'display' ),
			'airwpsync-connection',
			'normal',
			'high'
		);
	}

	/**
	 * Output metabox HTML
	 */
	public function display() {
		/**
		 * Filters mapping validation rules.
		 *
		 * @param array $rules Mapping validation rules.
		 */
		$mapping_validation_rules = apply_filters( 'airwpsync/mapping_validation_rules', array( 'required' ) );
		$view                     = include_once __DIR__ . '/views/metabox-mapping.php';
		$view( $mapping_validation_rules );
	}

	/**
	 * Add required mapping rule to mapping rules
	 *
	 * @param string[] $rules A list of rules.
	 */
	public function add_mapping_validation_rules( $rules ) {
		$rules[] = 'mappingRequired';
		return $rules;
	}
}
