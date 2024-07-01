<?php
namespace GatherContent\Importer\Admin\Mapping\Field_Types;

use GatherContent\Importer\Views\View;
use WP_Query;

class ACF extends Base implements Type {


	protected $type_id = 'wp-type-acf';

	/**
	 * Array of supported template field types.
	 *
	 * @var array
	 */
	protected $supported_types = array(
		'component',
		'repeater',
		'text_rich',
	);

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->option_label = __( 'ACF Field Groups', 'content-workflow-by-bynder' );
	}

	public function underscore_template( View $view ) {
		global $wpdb;
		global $wp_query;

		// VARIABLES
		$options_acf_groups        = array();
		$options_acf_groups_fields = array();

		// ============= BUILD FIELD GROUP OPTIONS =============
		$group_results = $wpdb->get_results(
			"SELECT * FROM {$wpdb->posts} WHERE post_type = 'acf-field-group' AND post_status = 'publish' AND post_parent = 0"
		);

		// FIELD GROUPS
		if ( $group_results ) {
			// Extract group IDs
			$groupIds            = array_map( function ( $group ) {
				return $group->ID;
			}, $group_results );
			$groupIdPlaceholders = implode( ', ', array_map( function () {
				return '%d';
			}, $groupIds ) );

			// Prepare and execute query to get all fields for all groups
			$wild           = '%' . $wpdb->esc_like( 'repeater' ) . '%';
			$fields_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->posts} WHERE post_type = 'acf-field' AND post_content LIKE %s AND post_parent IN ($groupIdPlaceholders)",
					array_merge( [ $wild ], $groupIds )
				)
			);

			// Group the field results by parent group
			$grouped_field_results = [];
			foreach ( $fields_results as $field ) {
				$grouped_field_results[ $field->post_parent ][] = $field;
			}

			foreach ( $group_results as $group ) {
				// Set the top level field group array of options
				$options_acf_groups[ $group->post_name ] = $group->post_title;
				// Create a blank array based on the group id to define the groups fields
				$options_acf_groups_fields[ $group->post_name ] = array();

				// Get the fields for the current group from grouped results
				$fields_for_group = $grouped_field_results[ $group->ID ] ?? [];

				// Loop fields within each ACF Field Group
				foreach ( $fields_for_group as $field ) {
					// Build array of fields based on parent group
					$options_acf_groups_fields[ $group->post_name ][ $field->post_name ] = $field->post_title;
				}
			}
		}
		// print_r($options_acf_groups);
		// print_r($options_acf_groups_fields);
		?>

		<# if ( '<?php $this->e_type_id(); ?>' === data.field_type ) { #>

		<select id="field-group-select-{{data.name}}" data-set="{{data.name}}"
				class="wp-type-value-select gc-select2 gc-select2-add-new field-select-group <?php $this->e_type_id(); ?>"
				name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][value]">
			<option data-group="" data-set="" value="">Unused</option>
			<?php $this->underscore_options( $options_acf_groups ); ?>
			<?php $this->underscore_empty_option( __( 'Do Not Import', 'content-workflow-by-bynder' ) ); ?>
		</select>
		<span style="display: block; margin: 5px 0;"></span>
		<select id="field-select-{{data.name}}" data-set="{{data.name}}"
				class="wp-type-field-select gc-select2 gc-select2-add-new field-select-field <?php $this->e_type_id(); ?>"
				name="<?php $view->output( 'option_base' ); ?>[mapping][{{ data.name }}][field]">
			<?php /**
			 * If first field has value > populate options from that field group
			 * If field_field, mark selected
			 */ ?>
			<# if ( data.field_value ) { #>
			<?php foreach ( $options_acf_groups_fields as $group_id => $fields ): ?>
				<# if ( '<?php echo esc_attr( $group_id ); ?>' === data.field_value ) { #>
				<option value=""><?php esc_html_e( 'Unused', 'content-workflow-by-bynder' ); ?></option>
				<?php foreach ( $fields as $field_id => $field_name ):
					echo '<option <# if ( "' . esc_attr( $field_id ) . '" === data.field_field ) { #>selected="selected"<# } #> value="' . esc_attr( $field_id ) . '">' . esc_html( $field_name ) . '</option>';
				endforeach; ?>
				<# } #>
			<?php endforeach; ?>
			<# } #>
		</select>

		<# } #>
	<?php }
} ?>
