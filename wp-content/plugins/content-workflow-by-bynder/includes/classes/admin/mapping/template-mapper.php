<?php

namespace GatherContent\Importer\Admin\Mapping;

use GatherContent\Importer\Utils;

/**
 * Class for managing/creating template mappings.
 *
 * @since 3.0.0
 */
class Template_Mapper extends Base {

	protected $option_name = '';
	protected $statuses = array();

	const EXCLUDED_FIELDS = array( 'section', 'guidelines' );
	const COMPONENT_FIELD = 'component';

	/**
	 * Field_Types\Types
	 *
	 * @var Field_Types\Types
	 */
	public $field_types;

	public function __construct( array $args ) {
		parent::__construct( $args );
		$this->statuses    = $args['statuses'];
		$this->option_name = $args['option_name'];
	}

	/**
	 * The page-specific script ID to enqueue.
	 *
	 * @return string
	 * @since  3.0.0
	 *
	 */
	protected function script_id() {
		return 'gathercontent-mapping';
	}

	/**
	 * The mapping page UI callback.
	 *
	 * @return void
	 * @since  3.0.0
	 *
	 */
	public function ui_page() {
		// Output the markup for the JS to build on.
		echo '<div id="mapping-tabs"><span class="gc-loader spinner is-active"></span></div>';

		if ( $this->mapping_id ) {

			echo '<div class="gc-sync-items-descriptions">
			<p class="description"><a href="' . esc_url( add_query_arg( 'sync-items', 1 ) ) . '"><span class="dashicons dashicons-randomize"> </span>' . esc_html__( 'Import Items for this template from Content Workflow', 'content-workflow-by-bynder' ) . '</a></p>
			</div>';

			$this->view(
				'input',
				array(
					'type'  => 'hidden',
					'id'    => 'gc-existing-id',
					'name'  => $this->option_name . '[existing_mapping_id]',
					'value' => $this->mapping_id,
				)
			);
		}

		$project_id  = esc_attr( $this->project->id );
		$template_id = esc_attr( $this->template->data->id );

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'gc-create-map',
				'name'  => $this->option_name . '[create_mapping]',
				'value' => wp_create_nonce( md5( $project_id . $template_id ) ),
			)
		);

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'gc-template-title',
				'name'  => $this->option_name . '[title]',
				'value' => esc_attr( isset( $this->template->data->name ) ? $this->template->data->name : __( 'Mapped Template', 'content-workflow-by-bynder' ) ),
			)
		);

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'gc-structure-uuid',
				'name'  => $this->option_name . '[structure_uuid]',
				'value' => esc_attr( $this->structure_uuid ),
			)
		);

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'gc-account-id',
				'name'  => $this->option_name . '[account_id]',
				'value' => $this->account_id,
			)
		);

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'gc-account-slug',
				'name'  => $this->option_name . '[account]',
				'value' => $this->account_slug,
			)
		);

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'gc-project-id',
				'name'  => $this->option_name . '[project]',
				'value' => $project_id,
			)
		);

		$this->view(
			'input',
			array(
				'type'  => 'hidden',
				'id'    => 'gc-template-id',
				'name'  => $this->option_name . '[template]',
				'value' => $template_id,
			)
		);

		$this->field_types = $this->initiate_mapped_field_types();
	}

	/**
	 * Get the localizable data array.
	 *
	 * @return array Array of localizable data
	 * @since  3.0.0
	 *
	 */
	protected function get_localize_data() {
		$initial = ! $this->mapping_id;

		return array(
			'_initial'        => $initial,
			'_pointers'       => $this->get_pointers( $initial ),
			'_tabs'           => $this->get_tabs(),
			'_meta_keys'      => $this->custom_field_keys(),
			'_table_headings' => array(
				'default' => array(
					'gc' => array(
						'id'    => 'gc-field-th',
						'label' => __( 'Content Workflow Field', 'content-workflow-by-bynder' ),
					),
					'wp' => array(
						'id'    => 'wp-field-th',
						'label' => __( 'Mapped WordPress Field', 'content-workflow-by-bynder' ),
					),
				),
				'status'  => array(
					'gc'      => array(
						'id'    => 'gc-status-th',
						'label' => __( 'Content Workflow Status', 'content-workflow-by-bynder' ),
					),
					'wp'      => array(
						'id'    => 'wp-status-th',
						'label' => __( 'Mapped WordPress Status', 'content-workflow-by-bynder' ),
					),
					'gcafter' => array(
						'id'    => 'gcafter-status-th',
						'label' => __( 'On Import, Change Status', 'content-workflow-by-bynder' ),
					),
				),
			),
		);
	}

	/**
	 * Gets the underscore templates array.
	 *
	 * @return array
	 * @since  3.0.0
	 *
	 */
	protected function get_underscore_templates() {
		$post_status_options = $this->get_default_field_options( 'post_status' );

		return array(
			'tmpl-gc-tabs-wrapper'                         => array(),
			'tmpl-gc-tab-wrapper'                          => array(),
			'tmpl-gc-mapping-tab-row'                      => array(
				'option_base' => $this->option_name,
				'post_types'  => $this->post_types(),
			),
			'tmpl-gc-mapping-defaults-tab'                 => array(
				'post_author_label'   => $this->post_column_label( 'post_author' ),
				'post_status_options' => $post_status_options,
				'post_status_label'   => __( 'Default Status', 'content-workflow-by-bynder' ),
				'post_type_label'     => $this->post_column_label( 'post_type' ),
				'post_type_options'   => $this->get_default_field_options( 'post_type' ),
				'gc_status_options'   => $this->statuses,
				'option_base'         => $this->option_name,
			),
			'tmpl-gc-mapping-defaults-tab-status-mappings' => array(
				'option_base'         => $this->option_name,
				'gc_status_options'   => $this->statuses,
				'post_status_options' => $post_status_options,
			),
			'tmpl-gc-select2-item'                         => array(),
		);
	}

	/**
	 * Initiates the mapped field types. By default, post fields, taxonomies, and meta fields.
	 * If WP-SEO is installed, that field type will be iniitated as well.
	 *
	 * @return Field_Types\Types object
	 * @since  3.0.0
	 *
	 */
	protected function initiate_mapped_field_types() {
		$core_field_types = array(
			new Field_Types\Post( $this->post_options() ),
			new Field_Types\Taxonomy( $this->post_types() ),
			new Field_Types\Meta(),
			new Field_Types\Media(),
			new Field_Types\Database(),
		);

		$is_acf_installed = class_exists( 'acf_pro' );
		if ( $is_acf_installed ) {
			$core_field_types[] = new Field_Types\ACF();
		}

		if ( defined( 'WPSEO_VERSION' ) ) {
			$core_field_types[] = new Field_Types\WPSEO( $this->post_types() );
		}

		$type = new Field_Types\Types( $core_field_types );

		return $type->register();
	}

	/**
	 * Gets the Help Pointers array.
	 *
	 * @param bool $initial Whether we have a mapping ID.
	 *
	 * @return array  Array of Pointers.
	 * @since  3.0.0
	 *
	 */
	protected function get_pointers( $initial ) {
		$enqueue   = false;
		$dismissed = get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true );
		// $dismissed = preg_replace( array( '~gc_select_tab_how_to,?~', '~gc_map_status_how_to,?~' ), '', $dismissed );
		// update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', $dismissed );
		$dismissed = explode( ',', (string) $dismissed );

		$pointers = array(
			'select_type'        => '<h3>' . __( 'Select your Post Type', 'content-workflow-by-bynder' ) . '</h3><p>' . __( 'To get started, select your default Post Type for this mapping.', 'content-workflow-by-bynder' ) . '</p>',
			'select_tab_how_to'  => '',
			'map_status_how_to'  => '',
			'refresh_connection' => '',
		);

		if ( $initial ) {

			if ( ! in_array( 'gc_select_tab_how_to', $dismissed, 1 ) ) {
				$content = '<h3>' . __( 'Template Tabs and Fields', 'content-workflow-by-bynder' ) . '</h3>';
				$content .= '<p>' . __( 'You\'ll find the tabs from the Content Workflow Template here. Select a tab to start mapping the Template fields.', 'content-workflow-by-bynder' ) . '</p>';

				$pointers['select_tab_how_to'] = $content;
				$enqueue                       = true;
			}

			if ( ! in_array( 'gc_map_status_how_to', $dismissed, 1 ) ) {
				$content = '<h3>' . __( 'Content Workflow Status &Rarr; WordPress Status', 'content-workflow-by-bynder' ) . '</h3>';
				$content .= '<p>' . __( 'Here you\'ll be able to map each individual Content Workflow status to a WordPress status, and optionally, change the Content Workflow status when your items are imported to WordPress.', 'content-workflow-by-bynder' ) . '</p>';

				$pointers['map_status_how_to'] = $content;
				$enqueue                       = true;
			}
		} else {

			if ( ! in_array( 'gc_refresh_connection', $dismissed, 1 ) ) {
				$content = '<h3>' . __( 'Refresh data from Content Workflow', 'content-workflow-by-bynder' ) . '</h3>';
				$content .= '<p>' . __( 'To make the plugin more speedy, we cache the requests to Content Workflow for 1 day, but if you find that you need to update the data from Content Workflow, just hit the "Refresh" button.', 'content-workflow-by-bynder' ) . '</p>';
				$content .= '<p>' . __( 'For more help, click the "Help" tab in the upper-right-hand corner.', 'content-workflow-by-bynder' ) . '</p>';

				$pointers['refresh_connection'] = $content;
				$enqueue                        = true;
			}
		}

		if ( $enqueue ) {
			wp_enqueue_script( 'wp-pointer' );
			wp_enqueue_style( 'wp-pointer' );
		}

		return $pointers;
	}

	/**
	 * Get's the GC tabs and adds a default tab for universal settings.
	 *
	 * @return array  Array of tabs.
	 * @since  3.0.0
	 *
	 */

	/**
	 * Retrieves and structures tabs with associated fields or components.
	 * Handles tab groups, checks for ACF Pro presence, and constructs tab arrays.
	 * Utilizes switch cases based on ACF Pro installation status.
	 *
	 * @return array Tabs with structured fields or components for display.
	 */

	protected function get_tabs() {
		$tabs                 = [];
		$post_type            = $this->get_value( 'post_type', 'esc_attr' );
		$tab_groups           = $this->template->related->structure->groups ?? [];
		$is_acf_pro_installed = class_exists( 'acf_pro' );

		foreach ( $tab_groups as $tab ) {
			$rows   = [];
			$fields = $tab->fields ?? [];

			foreach ( $fields as $field ) {
				$metadata      = $field->metadata;
				$is_repeatable = ( is_object( $metadata ) && isset( $metadata->repeatable ) ) ? $metadata->repeatable->isRepeatable : false;

				//We use components  as rows and fields in the components as subrows if ACF PRo is installed
				if ( $is_acf_pro_installed ) {

					if ( self::COMPONENT_FIELD !== $field->field_type ) {
						$this->formatAndAddField( $field, $post_type, '', $is_repeatable, $rows );
					}

					if ( self::COMPONENT_FIELD === $field->field_type ) {
						$component_id   = $field->uuid;
						$component_name = $field->label;
						$this->formatAndAddField( $field, $post_type, $component_name, $is_repeatable, $rows, $component_id );
					}
				} else {
					// When ACF Pro is not installed, use the original logic:
					// each row is just as it comes in. (each component field is an individual row)
					$fields_data = ( $field->component->fields ?? [ $field ] );

					foreach ( $fields_data as $field_data ) {
						$formatted_field = $this->format_fields(
							$field_data,
							$post_type,
							$field->label,
							$is_repeatable,
							self::COMPONENT_FIELD === $field->field_type ? $field->uuid : ''
						);

						if ( $formatted_field ) {
							$rows[] = $formatted_field;
						}
					}
				}
			}

			$tabs[] = [
				'id'     => $tab->uuid,
				'label'  => $tab->name,
				'hidden' => ! empty( $tabs ),
				'rows'   => $rows,
			];
		}

		$default_tab = [
			'id'          => 'mapping-defaults',
			'label'       => __( 'Mapping Defaults', 'content-workflow-by-bynder' ),
			'hidden'      => true,
			'navClasses'  => 'alignright',
			'rows'        => $this->post_options(),
			'post_author' => $this->get_value( 'post_author', 'absint', 1 ),
			'post_status' => $this->get_value( 'post_status', 'esc_attr', 'draft' ),
			'post_type'   => $post_type,
			'gc_status'   => $this->get_gc_statuses(),
		];

		$default_tab[ 'select2:post_author:' . $default_tab['post_author'] ] = $this->get_default_field_options( 'post_author' );

		$tabs[] = $default_tab;

		return $tabs;
	}


	private function formatAndAddField( $field, $post_type, $component_name, $is_repeatable, &$rows, $component_id = '' ) {
		$formatted_field = $this->format_fields( $field, $post_type, $component_name, $is_repeatable, $component_id );

		if ( $formatted_field ) {
			$rows[] = $formatted_field;
		}
	}

	/**
	 * Format field object based on the field type
	 *
	 * @param mixed $field
	 * @param string|null $post_type
	 * @param string $component_name
	 * @param bool $is_repeatable
	 * @param string|int $component_id optional
	 *
	 * @return null|mixed formatted field object.
	 * @since 4.0.0
	 *
	 */
	private function format_fields( $field, $post_type, string $component_name = '', bool $is_repeatable = false, string $component_id = '' ) {

		$field_type = $field->field_type ?? '';

		$field->uuid .= ( $component_id ? '_component_' . $component_id : '' );

		// exclude guidelines and section fields
		if ( in_array( $field_type, self::EXCLUDED_FIELDS ) ) {
			return null;
		}

		$field->typeName = '';

		if ( 'text' === $field_type ) {

			$is_plain          = $field->metadata->is_plain;
			$field->type       = $is_plain ? 'text_plain' : 'text_rich';
			$field->plain_text = (bool) $is_plain;

		} else {
			$field->type = $field_type === 'attachment' ? 'files' : $field_type;
		}

		$field->typeName = Utils::cwby_field_type_name( $field_type );

		if ( $val = $this->get_value( $field->uuid ) ) {
			$field->field_type      = isset( $val['type'] ) ? $val['type'] : '';
			$field->field_value     = isset( $val['value'] ) ? $val['value'] : '';
			$field->field_field     = isset( $val['field'] ) ? $val['field'] : '';
			$field->field_subfields = isset( $val['sub_fields'] ) ? (array) $val['sub_fields'] : '';
		}

		$field->is_repeatable = $is_repeatable;
		$field->post_type     = $post_type;
		$field->name          = $field->uuid;
		$field->subtitle      = $component_name ? "($component_name)" : '';

		return $field;
	}

	public function get_gc_statuses() {
		$statuses = $this->get_value( 'gc_status' );
		$statuses = is_array( $statuses ) ? $statuses : array();

		foreach ( $this->statuses as $status ) {
			$statuses[ $status->id ] = isset( $statuses[ $status->id ] ) ? $statuses[ $status->id ] : array();
		}

		return $statuses;
	}

}
