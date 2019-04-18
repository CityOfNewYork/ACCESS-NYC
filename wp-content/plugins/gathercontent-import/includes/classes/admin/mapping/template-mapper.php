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

	/**
	 * Field_Types\Types
	 *
	 * @var Field_Types\Types
	 */
	public $field_types;

	public function __construct( array $args ) {
		parent::__construct( $args );
		$this->statuses = $args['statuses'];
		$this->option_name = $args['option_name'];
	}

	/**
	 * The page-specific script ID to enqueue.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	protected function script_id() {
		return 'gathercontent-mapping';
	}

	/**
	 * The mapping page UI callback.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function ui_page() {

		// Output the markup for the JS to build on.
		echo '<div id="mapping-tabs"><span class="gc-loader spinner is-active"></span></div>';

		if ( $this->mapping_id ) {

			echo '<div class="gc-sync-items-descriptions">
			<p class="description"><a href="'. esc_url( add_query_arg( 'sync-items', 1 ) ) .'"><span class="dashicons dashicons-randomize"> </span>' . __( 'Import Items for this template from GatherContent', 'domain' ) . '</a></p>
			</div>';

			$this->view( 'input', array(
				'type'    => 'hidden',
				'id'      => 'gc-existing-id',
				'name'    => $this->option_name .'[existing_mapping_id]',
				'value'   => $this->mapping_id,
			) );
		}

		$project_id  = esc_attr( $this->project->id );
		$template_id = esc_attr( $this->template->id );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-create-map',
			'name'    => $this->option_name .'[create_mapping]',
			'value'   => wp_create_nonce( md5( $project_id . $template_id ) ),
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-template-title',
			'name'    => $this->option_name .'[title]',
			'value'   => esc_attr( isset( $this->template->name ) ? $this->template->name : __( 'Mapped Template', 'gathercontent-import' ) ),
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-structure-uuid',
			'name'    => $this->option_name .'[structure_uuid]',
			'value'   => esc_attr( $this->structure_uuid ),
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-account-id',
			'name'    => $this->option_name .'[account_id]',
			'value'   => $this->account_id,
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-account-slug',
			'name'    => $this->option_name .'[account]',
			'value'   => $this->account_slug,
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-project-id',
			'name'    => $this->option_name .'[project]',
			'value'   => $project_id,
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-template-id',
			'name'    => $this->option_name .'[template]',
			'value'   => $template_id,
		) );

		$this->field_types = $this->initiate_mapped_field_types();
	}

	/**
	 * Get the localizable data array.
	 *
	 * @since  3.0.0
	 *
	 * @return array Array of localizable data
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
						'id' => 'gc-field-th',
						'label' => __( 'GatherContent Field', 'gathercontent-import' ),
					),
					'wp' => array(
						'id' => 'wp-field-th',
						'label' => __( 'Mapped WordPress Field', 'gathercontent-import' ),
					),
				),
				'status' => array(
					'gc' => array(
						'id' => 'gc-status-th',
						'label' => __( 'GatherContent Status', 'gathercontent-import' ),
					),
					'wp' => array(
						'id' => 'wp-status-th',
						'label' => __( 'Mapped WordPress Status', 'gathercontent-import' ),
					),
					'gcafter' => array(
						'id' => 'gcafter-status-th',
						'label' => __( 'On Import, Change GatherContent Status', 'gathercontent-import' ),
					),
				),
			),
		);
	}

	/**
	 * Gets the underscore templates array.
	 *
	 * @since  3.0.0
	 *
	 * @return array
	 */
	protected function get_underscore_templates() {
		$post_status_options = $this->get_default_field_options( 'post_status' );

		return array(
			'tmpl-gc-tabs-wrapper' => array(),
			'tmpl-gc-tab-wrapper' => array(),
			'tmpl-gc-mapping-tab-row' => array(
				'option_base' => $this->option_name,
				'post_types'  => $this->post_types(),
			),
			'tmpl-gc-mapping-defaults-tab' => array(
				'post_author_label'   => $this->post_column_label( 'post_author' ),
				'post_status_options' => $post_status_options,
				'post_status_label'   => __( 'Default Status', 'gathercontent-import' ),
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
			'tmpl-gc-select2-item' => array(),
		);
	}

	/**
	 * Initiates the mapped field types. By default, post fields, taxonomies, and meta fields.
	 * If WP-SEO is installed, that field type will be iniitated as well.
	 *
	 * @since  3.0.0
	 *
	 * @return Field_Types\Types object
	 */
	protected function initiate_mapped_field_types() {
		$core_field_types = array(
			new Field_Types\Post( $this->post_options() ),
			new Field_Types\Taxonomy( $this->post_types() ),
			new Field_Types\Meta(),
			new Field_Types\Media(),
		);

		if ( defined( 'WPSEO_VERSION' ) ) {
			$core_field_types[] = new Field_Types\WPSEO( $this->post_types() );
		}

		$type = new Field_Types\Types( $core_field_types );

		return $type->register();
	}

	/**
	 * Gets the Help Pointers array.
	 *
	 * @since  3.0.0
	 *
	 * @param  bool  $initial Whether we have a mapping ID.
	 *
	 * @return array  Array of Pointers.
	 */
	protected function get_pointers( $initial ) {
		$enqueue = false;
		$dismissed = get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true );
		// $dismissed = preg_replace( array( '~gc_select_tab_how_to,?~', '~gc_map_status_how_to,?~' ), '', $dismissed );
		// update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', $dismissed );
		$dismissed = explode( ',', (string) $dismissed );

		$pointers = array(
			'select_type' => '<h3>'. __( 'Select your Post Type', 'gathercontent-import' ) .'</h3><p>' . __( 'To get started, select your default Post Type for this mapping.', 'gathercontent-import' ) . '</p>',
			'select_tab_how_to' => '',
			'map_status_how_to' => '',
			'refresh_connection' => '',
		);

		if ( $initial ) {

			if ( ! in_array( 'gc_select_tab_how_to', $dismissed, 1 ) ) {
				$content = '<h3>'. __( 'Template Tabs and Fields', 'gathercontent-import' ) .'</h3>';
				$content .= '<p>' . __( 'You\'ll find the tabs from the GatherContent Template here. Select a tab to start mapping the Template fields.', 'gathercontent-import' ) . '</p>';

				$pointers['select_tab_how_to'] = $content;
				$enqueue = true;
			}

			if ( ! in_array( 'gc_map_status_how_to', $dismissed, 1 ) ) {
				$content = '<h3>'. __( 'GatherContent Status &Rarr; WordPress Status', 'gathercontent-import' ) .'</h3>';
				$content .= '<p>' . __( 'Here you\'ll be able to map each individual GatherContent status to a WordPress status, and optionally, change the GatherContent status when your items are imported to WordPress.', 'gathercontent-import' ) . '</p>';

				$pointers['map_status_how_to'] = $content;
				$enqueue = true;
			}
		} else {

			if ( ! in_array( 'gc_refresh_connection', $dismissed, 1 ) ) {
				$content = '<h3>'. __( 'Refresh data from GatherContent', 'gathercontent-import' ) .'</h3>';
				$content .= '<p>' . __( 'To make the plugin more speedy, we cache the requests to GatherContent for 1 day, but if you find that you need to update the data from GatherContent, just hit the "Refresh" button.', 'gathercontent-import' ) . '</p>';
				$content .= '<p>' . __( 'For more help, click the "Help" tab in the upper-right-hand corner.', 'gathercontent-import' ) . '</p>';

				$pointers['refresh_connection'] = $content;
				$enqueue = true;
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
	 * @since  3.0.0
	 *
	 * @return array  Array of tabs.
	 */
	protected function get_tabs() {
		$tabs = array();

		$post_type = $this->get_value( 'post_type', 'esc_attr' );

		foreach ( $this->template->config as $tab ) {

			$rows = array();
			foreach ( $tab->elements as $element ) {
				if ( 'section' === $element->type ) {
					continue;
				}

				if ( $this->get_value( $element->name ) ) {
					$val = $this->get_value( $element->name );
					$element->field_type = isset( $val['type'] ) ? $val['type'] : '';
					$element->field_value = isset( $val['value'] ) ? $val['value'] : '';
				}

				$element->typeName = '';

				if ( isset( $element->type ) ) {
					if ( 'text' === $element->type ) {
						$element->type = isset( $element->plain_text ) && $element->plain_text
							? 'text_plain'
							: 'text_rich';
					}

					$element->typeName = Utils::gc_field_type_name( $element->type );
				}

				$element->post_type = $post_type;
				$rows[] = $element;
			}

			$tab_array = array(
				'id'     => $tab->name,
				'label'  => $tab->label,
				'hidden' => ! empty( $tabs ),
				'rows'   => $rows,
			);

			$tabs[] = $tab_array;
		}

		$default_tab = array(
			'id'          => 'mapping-defaults',
			'label'       => __( 'Mapping Defaults', 'gathercontent-import' ),
			'hidden'      => true,
			'navClasses'  => 'alignright',
			'rows'        => $this->post_options(),
			'post_author' => $this->get_value( 'post_author', 'absint', 1 ),
			'post_status' => $this->get_value( 'post_status', 'esc_attr', 'draft' ),
			'post_type'   => $post_type,
			'gc_status'   => $this->get_gc_statuses(),
		);

		$default_tab[ 'select2:post_author:' . $default_tab['post_author'] ] = $this->get_default_field_options( 'post_author' );

		$tabs[] = $default_tab;

		return $tabs;
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
