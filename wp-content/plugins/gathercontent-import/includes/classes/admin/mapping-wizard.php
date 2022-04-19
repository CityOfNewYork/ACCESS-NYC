<?php
namespace GatherContent\Importer\Admin;
use GatherContent\Importer\Utils;
use GatherContent\Importer\Settings\Setting;
use GatherContent\Importer\Settings\Form_Section;
use GatherContent\Importer\Post_Types\Template_Mappings;

/**
 * Class for the template mappings creation wizard.
 *
 * @since 3.0.0
 */
class Mapping_Wizard extends Base {

	const SLUG = 'gathercontent-import-add-new-template';
	const ACCOUNT = 0;
	const PROJECT = 1;
	const TEMPLATE = 2;
	const SYNC = 3;

	protected $slugs = array(
		self::ACCOUNT  => 'gc-account',
		self::PROJECT  => 'gc-project',
		self::TEMPLATE => 'gc-template',
		self::SYNC     => 'gc-sync',
	);

	public $parent_page_slug;
	public $parent_url;
	public $project_items = array();
	public $stored_values = null;
	public $menu_priority = 11; // Puts "New Mapping" after "Template Mappings" CPT menu.

	/**
	 * Template_Mappings
	 *
	 * @var Template_Mappings
	 */
	public $mappings;

	/**
	 * Mapping\Template_Mapper
	 *
	 * @var Mapping\Template_Mapper
	 */
	public $template_mapper;

	/**
	 * Mapping\Items_Sync
	 *
	 * @var Mapping\Items_Sync
	 */
	public $items_sync;

	/**
	 * Default option value (if none is set)
	 *
	 * @var array
	 */
	public $default_options = array();

	/**
	 * Creates an instance of this class.
	 *
	 * @since 3.0.0
	 */
	public function __construct( Admin $parent ) {
		$this->option_name      = $parent->option_name . '_add_new_template';
		$this->option_group     = $parent->option_group . '_add_new_template';
		$this->parent_page_slug = parent::SLUG;
		$this->parent_url       = $parent->url;
		$this->settings         = new Setting( $parent->option_name, $parent->default_options );
		$this->mappings         = new Template_Mappings( parent::SLUG, $this->api() );

		if ( $this->_get_val( 'project' ) ) {
			$this->step = self::PROJECT;

			if ( $this->_get_val( 'template' ) ) {
				$this->step = self::TEMPLATE;

				if ( $this->_get_val( 'sync-items' ) ) {
					$this->step = self::SYNC;
				}
			}
		}

		parent::__construct();

		$this->handle_redirects();
	}

	/**
	 * Registers our menu item and admin page.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function admin_menu() {
		$page = add_submenu_page(
			$this->parent_page_slug,
			$this->get_step_label(),
			__( 'New Mapping', 'gathercontent-import' ),
			\GatherContent\Importer\view_capability(),
			self::SLUG,
			array( $this, 'admin_page' )
		);

		add_filter( 'admin_body_class', array( $this, 'body_class' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_enqueue_style' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_enqueue_script' ) );
		add_action( 'load-' . $page, array( $this, 'add_help_tabs' ) );
	}

	protected function get_step_label() {
		$label = 'GatherContent';
		switch ( $this->step ) {
			case self::ACCOUNT:
				$label = Utils::get_step_label( 'projects' );
				break;
			case self::PROJECT:
				$label = Utils::get_step_label( 'templates' );
				break;
			case self::TEMPLATE:
				$label = $this->_get_val( 'mapping' )
					? $this->mappings->args->labels->edit_item
					: $this->mappings->args->labels->new_item;
				break;
			case self::SYNC:
				$label = Utils::get_step_label( 'import' );
				break;
			default:
				$label = Utils::get_step_label( 'accounts' );
				break;
		}

		return $label;
	}

	public function add_help_tabs() {
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'id'      => 'gc-help-me',
			'title'   => __( 'GatherContent', 'gathercontent-import' ),
			'content' => __( '<p>Thank you for using the GatherContent WordPress plugin!</p>' .
			'<p>To make the plugin more speedy, we cache the requests to GatherContent for 1 day, but if you find that you need to update the data from GatherContent, just hit the "Refresh" button.</p>', 'gathercontent-import' ) . '<p>'. $this->refresh_connection_link() .'</p>',
		) );

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'gathercontent-import' ) . '</strong></p>' .
			'<p><a href="https://gathercontent.com/support/wordpress-integration/" target="_blank">' . __( 'GatherContent WordPress Integration' ) . '</a></p>' .
			'<p><a href="https://wordpress.org/support/plugin/gathercontent-import" target="_blank">' . __( 'Support Forums' ) . '</a></p>'
		);

		if ( self::TEMPLATE === $this->step ) {

			$screen->add_help_tab( array(
				'id'      => 'gc-field-details',
				'title'   => __( 'Mapping Fields', 'gathercontent-import' ),
				'content' => __( '<p><b>Note:</b> If mapping more than one GatherContent field to one WordPress field, you will not be able to "push" that content back to GatherContent, as there is not currently a way to split the fields back to individual fields.</p>', 'gathercontent-import' ),
			) );
		}
	}

	public function admin_page() {
		if ( Utils::doing_ajax() ) {
			return;
		}

		$args = array(
			'logo'                => $this->logo,
			'option_group'        => $this->option_group,
			'settings_sections'   => Form_Section::get_sections( self::SLUG ),
			'go_back_button_text' => __( 'Previous Step', 'gathercontent-import' ),
			'refresh_button'      => $this->refresh_connection_link(),
			'submit_button_text'  => __( 'Next Step', 'gathercontent-import' ),
		);

		switch ( $this->step ) {
			case self::ACCOUNT:
				$args['go_back_button_text'] = __( 'Back to API setup', 'gathercontent-import' );
				$args['go_back_url'] = $this->parent_url;
				break;

			case self::PROJECT:
				$args['go_back_url'] = remove_query_arg( 'project' );
				break;

			case self::TEMPLATE:
				$args['go_back_url'] = remove_query_arg( 'template', remove_query_arg( 'mapping' ) );
				$args['submit_button_text'] = __( 'Save Mapping', 'gathercontent-import' );
				break;

			case self::SYNC:
				$args['submit_button_text'] = __( 'Import Selected Items', 'gathercontent-import' );
				break;
		}

		$this->register_notices();
		$this->view( 'admin-page', $args );
	}

	public function register_notices() {
		$notices = array();

		if ( get_option( 'gc-api-updated' ) ) {
			$notices[] = array(
				'id'      => 'gc-api-connection-reset',
				'message' => __( 'We refreshed the data from the GatherContent API.', 'gathercontent-import' ),
				'type'    => 'updated',
			);
			delete_option( 'gc-api-updated' );
		}

		if ( $this->_get_val( 'updated' ) &&  $this->_get_val( 'project' ) &&  $this->_get_val( 'template' ) ) {

			if ( $this->_get_val( 'sync-items' ) ) {
				$notices[] = array(
					'id'      => 'gc-mapping-updated',
					'message' => __( 'Items Import complete!', 'gathercontent-import' ),
					'type'    => 'updated',
				);

			} else {
				$label = 1 === absint( $this->_get_val( 'updated' ) ) ? 'item_updated' : 'item_saved';

				$notices[] = array(
					'id'      => 'gc-mapping-updated',
					'message' => $this->mappings->args->labels->{$label},
					'type'    => 'updated',
				);
			}

		}

		$notices = apply_filters( 'gc_admin_notices', $notices );
		foreach ( $notices as $notice ) {
			$notice['type'] = isset( $notice['type'] ) ? $notice['type'] : 'error';

			$this->add_settings_error(
				$this->option_name,
				$notice['id'],
				$notice['message'],
				$notice['type']
			);
		}
	}

	public function body_class( $classes ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $classes;
		}

		$screen = get_current_screen();
		if (
			'gathercontent_page_' . self::SLUG !== $screen->id
			&& 'toplevel_page_' . Admin::SLUG !== $screen->id
		) {
			return $classes;
		}

		$classes .= ' gathercontent-admin '. $this->slugs[ $this->step ] .' ';

		if ( isset( $_GET['auth-required'] ) ) {
			$classes .= 'gc-auth-required ';
		}

		return $classes;
	}

	/**
	 * Initializes the plugin's setting, and settings sections/Fields.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function initialize_settings_sections() {

		switch ( $this->step ) {
			case self::PROJECT:
				$this->select_template();
				break;

			case self::SYNC:
			case self::TEMPLATE:
				$this->map_template();
				break;

			default:
				if ( ! $this->step ) {
					$this->select_project();
				}
				break;
		}

		parent::initialize_settings_sections();
	}

	/**
	 * Step one of the mapping wizard, pick a project from list of accounts.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function select_project() {
		$section = new Form_Section(
			'select_project',
			__( 'First, choose a project from an account.', 'gathercontent-import' ),
			'',
			self::SLUG
		);

		$section->add_field(
			'project',
			'',
			array( $this, 'select_project_fields_ui' )
		);
	}

	public function select_project_fields_ui( $field ) {
		$field_id = $field->param( 'id' );
		$my_account_slug =  $this->get_setting( 'platform_url_slug' );

		$accounts = $this->api()->get_accounts();

		if ( ! $accounts ) {
			return $this->add_settings_error( $this->option_name, 'gc-missing-accounts', sprintf( __( 'We couldn\'t find any accounts associated with your GatherContent API credentials. Please <a href="%s">check your settings</a>.', 'gathercontent-import' ), $this->parent_url ) );
		}

		$tabs = array();
		$my_account = false;
		$first = true;

		foreach ( $accounts as $index => $account ) {
			if ( $my_account_slug === $account->slug ) {
				$my_account = $account;
				unset( $accounts[ $index ] );
			}
		}

		if ( $my_account ) {
			array_unshift( $accounts, $my_account );
		}

		foreach ( $accounts as $account ) {
			if ( ! isset( $account->id ) ) {
				continue;
			}

			$options = array();
			$value = '';

			if ( $projects = $this->api()->get_account_projects( $account->id ) ) {
				foreach ( $projects as $project ) {
					$val = esc_attr( $project->id ) . ':' . esc_attr( $account->slug ) . ':' . esc_attr( $account->id );
					$options[ $val ] = esc_attr( $project->name );
					if ( ! $value ) {
						$value = $val;
					}
				}
			}

			$tabs[] = array(
				'id' => $account->id,
				'label' => sprintf( __( '%s', 'gathercontent-import' ), isset( $account->name ) ? $account->name : '' ),
				'nav_class' => $first ? 'nav-tab-active' : '',
				'tab_class' => $first ? '' : 'hidden',
				'content' => $this->view( 'radio', array(
					'id'      => $field_id . '-' . $account->id,
					'name'    => $this->option_name .'['. $field_id .']',
					'value'   => $value,
					'options' => $options,
				), false ),
			);

			$first = false;
		}

		$this->view( 'tabs-wrapper', array(
			'tabs' => $tabs,
		) );

	}

	/**
	 * Step two of the mapping wizard, pick a template in the chosen project.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function select_template() {
		$project = $this->api()->get_project( absint( $this->_get_val( 'project' ) ) );

		$section = new Form_Section(
			'select_template',
			__( 'Next, select a template to map.', 'gathercontent-import' ),
			$this->project_name_and_edit_link( $project ),
			self::SLUG
		);

		$section->add_field(
			'template',
			'',
			array( $this, 'select_template_fields_ui' )
		);

	}

	public function select_template_fields_ui( $field ) {
		$field_id   = $field->param( 'id' );
		$project_id = $this->_get_val( 'project' );
		$options    = array();

		$value = '';
		if ( $templates = $this->api()->get_project_templates( absint( $project_id ) ) ) {

			foreach ( $templates as $template ) {
				$template_id = esc_attr( $template->id );

				$options[ $template_id ] = array(
					'label' => '<span>'. esc_attr( $template->name ) .'</span>',
				);

				$exists = $this->mappings->get_by_project_template( $project_id, $template_id );
				$mapping_id = $exists->have_posts() ? $exists->posts[0] : false;

				if ( $mapping_id ) {

					$options[ $template_id ]['label'] .= sprintf(
						'%s <a href="%s">%s</a>',
						'',
						esc_url( get_permalink( $mapping_id ) ),
						$this->mappings->args->labels->edit_item
					);

					$options[ $template_id ]['disabled'] = 'disabled';

				}
				/*elseif ( $items = $this->get_project_items_list( $project_id, $template_id ) ) {
					$options[ $template_id ]['desc'] = $items;
				}*/

				if ( ! $value ) {
					$value = $template_id;
				}

			}
		}

		$this->view( 'radio', array(
			'id'      => $field_id,
			'name'    => $this->option_name .'['. $field_id .']',
			'value'   => $value,
			'options' => $options,
		) );

		$this->view( 'input', array(
			'type'    => 'hidden',
			'id'      => 'gc-project-id',
			'name'    => $this->option_name .'[project]',
			'value'   => $project_id,
		) );
	}

	/**
	 * Step three, the final step of the mapping wizard. Create a template-mapping.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	public function map_template() {
		$mapping_id  = absint( $this->_get_val( 'mapping' ) );
		$mapping_id  = $mapping_id && get_post( $mapping_id ) ? $mapping_id : false;

		if ( $mapping_id ) {
			$account_id = $this->mappings->get_mapping_account_id( $mapping_id );
			$account_slug = $this->mappings->get_mapping_account_slug( $mapping_id );
		} else {
			$account_id = $this->_get_account_id();
			$account_slug = $this->_get_account_slug();
		}

		$account     = $this->api()->get_account( absint( $account_id ));
		$features 	 = array_flip( $account->features );

		if ( isset( $features['editor:new'] ) ) {
			$template    = $this->api()->get_template( absint( $this->_get_val('template') ), array(
				'headers' => array(
					'Accept' => 'application/vnd.gathercontent.v0.6+json'
				)
			) );

			$structure_uuid = $template->structure_uuid;
		}

		$template    = $this->api()->get_template( absint( $this->_get_val('template') ) );
		$template_id = isset( $template->id ) ? $template->id : null;
		$project     = $this->api()->get_project( absint( $this->_get_val( 'project' ) ) );
		$project_id  = isset( $project->id ) ? $project->id : null;
		$sync_items  = $mapping_id && $this->_get_val( 'sync-items' );
		$notes       = '';

		if ( ! $sync_items && $mapping_id ) {
			$notes .= $this->view( 'existing-mapping-notice', array(
				'name' => $this->mappings->args->labels->singular_name,
				'id'   => $mapping_id,
			), false );
		}

		if ( ! $project_id || ! $template_id ) {
			$notes = $this->view( 'no-mapping-or-template-available', array(), false ) . $notes;
		}

		$title = isset( $template->name )
			? $template->name
			: __( 'Unknown Template', 'gathercontent-import' );

		if ( $sync_items ) {
			$title_prefix = __( 'Import Items for: %s', 'gathercontent-import' );
		} elseif ( $mapping_id ) {
			$title_prefix = __( 'Edit Mapping for: %s', 'gathercontent-import' );
		} else {
			$title_prefix = __( 'Create Mapping for: %s', 'gathercontent-import' );
		}
		$title = sprintf( $title_prefix, $title );

		$desc = '';
		if ( $template && isset( $template->description ) ) {
			$desc .= '<h4 class="description">' . esc_attr( $template->description ) . '</h4>';
		}

		$desc .= $this->project_name_and_edit_link( $project );

		$section = new Form_Section(
			'select_template',
			$notes . $title,
			$desc,
			self::SLUG
		);

		if ( ! $sync_items ) {

			$this->template_mapper = new Mapping\Template_Mapper( array(
				'mapping_id'     => $mapping_id,
				'structure_uuid' => $structure_uuid,
				'account_id'     => $account_id,
				'account_slug'   => $account_slug,
				'project'        => $project,
				'template'       => $template,
				'statuses'       => $this->api()->get_project_statuses( absint( $this->_get_val( 'project' ) ) ),
				'option_name'    => $this->option_name,
			) );

			$callback = $project_id && $template_id
				? array( $this->template_mapper, 'ui' )
				: '__return_empty_string';

			$section->add_field( 'mapping', '', $callback );

		} else {

			$this->items_sync = new Mapping\Items_Sync( array(
				'mapping_id'     => $mapping_id,
				'structure_uuid' => $structure_uuid,
				'account_id'     => $account_id,
				'account_slug'   => $account_slug,
				'project'        => $project,
				'template'       => $template,
				'url'            => $this->platform_url(),
				'mappings'       => $this->mappings,
				'items'          => $this->filter_items_by_template( $project_id, $template_id ),
			) );

			$section->add_field( 'mapping', '', array( $this->items_sync, 'ui' ) );
		}
	}

	/**
	 * Like inception, checks how many levels deep we are in the Template Mapping process,
	 * and performs the appropriate action/redirect.
	 *
	 * Does not actually save fields, but simply uses the field values to determine where
	 * we are in the process (wizard).
	 *
	 * @since  3.0.0
	 *
	 * @param  array  $options Array of options
	 *
	 * @return void
	 */
	public function sanitize_settings( $options ) {
		if ( ! isset( $options['project'] ) ) {
			// Hmmm, this should never happen, but if so, we def. don't want to save.
			return false;
		}

		// Ok, we have a project.
		$project = esc_attr( $options['project'] );

		if ( ! isset( $options['template'] ) ) {
			// Send the user to the template-picker.
			$this->redirect_to_template_picker( $project );
		}

		// Ok, we have a template.
		$template = esc_attr( $options['template'] );

		if ( ! isset( $options['create_mapping'] ) ) {
			// Send the user to the mapping-creator.
			$this->redirect_to_mapping_creation( $project, $template );
		}

		// Ok, we have all we need. Let's attempt to create/update a mapping post.
		$this->save_mapping_post_and_redirect( $project, $template, $options );
	}

	/**
	 * Redirects to the template-picker page of the wizard.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $project GC Project ID.
	 *
	 * @return void
	 */
	protected function redirect_to_template_picker( $project ) {
		wp_safe_redirect( esc_url_raw( add_query_arg( 'project', $project, $this->url ) ) );
		exit;
	}

	/**
	 * Redirects to the (final) mapping-creator page of the wizard.
	 *
	 * @since  3.0.0
	 *
	 * @param  int $project  GC Project ID.
	 * @param  int $template GC Template ID.
	 *
	 * @return void
	 */
	protected function redirect_to_mapping_creation( $project, $template ) {

		// Let's check if we already have a mapped template.
		$exists = $this->mappings->get_by_project_template( $project, $template );

		$args = compact( 'project', 'template' );
		if ( $exists->have_posts() ) {
			// Yep, we found one.
			$args['mapping'] = $exists->posts[0];
			$args['settings-updated'] = 1;
		}

		// Now redirect to the template mapping.
		wp_safe_redirect( esc_url_raw( add_query_arg( $args, $this->url ) ) );
		exit;
	}

	/**
	 * Creates/Saves a mapping post after submission and redirects back
	 * to mapping-creator page to edit new mapping.
	 *
	 * @since  3.0.0
	 *
	 * @param  int   $project  GC Project ID.
	 * @param  int   $template GC Template ID.
	 * @param  array $options Array of options/values submitted.
	 *
	 * @return void
	 */
	protected function save_mapping_post_and_redirect( $project, $template, $options ) {
		if ( ! wp_verify_nonce( $options['create_mapping'], md5( $project . $template ) ) ) {

			// Let check_admin_referer handle the fail.
			check_admin_referer( 'fail', 'fail' );
		}

		$post_id = $this->create_or_update_mapping_post( $options );

		if ( is_wp_error( $post_id ) ) {
			wp_die( $post_id->get_error_message(), __( 'Failed creating mapping!', 'gathercontent-import' ) );
		}

		$edit_url = get_edit_post_link( $post_id, 'raw' );

		$status = isset( $options['existing_mapping_id'] ) && $options['existing_mapping_id'] ? 1 : 2;

		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'updated' => $status ), $edit_url ) ) );
		exit;
	}

	/**
	 * A URL for flushing the cached connection to GC's API
	 *
	 * @since  3.0.0
	 *
	 * @return string URL for flushing cache.
	 */
	public function refresh_connection_link() {
		return \GatherContent\Importer\refresh_connection_link();
	}

	public function project_name_and_edit_link( $project ) {
		$project_name = '';

		if ( isset( $project->name ) ) {
			$url = $this->platform_url( 'templates/' . $project->id );
			$project_name = '<p class="gc-project-name description">' . sprintf( _x( 'Project: %s', 'GatherContent project name', 'gathercontent-import' ), $project->name ) . ' | <a href="'. esc_url( $url ) .'" target="_blank">'. __( 'edit project templates', 'gathercontent-import' ) .'</a></p>';
		}

		return $project_name;
	}

	public function get_project_items_list( $project_id, $template_id, $class = 'gc-radio-desc' ) {
		$items = $this->filter_items_by_template( $project_id, $template_id );

		$list = '';
		if ( ! empty( $items ) ) {
			$list = $this->view( 'gc-items-list', array(
				'class'         => $class,
				'item_base_url' => $this->platform_url( 'item/' ),
				'items'         => array_slice( $items, 0, 5 ),
			), false );
		}

		return $list;
	}

	public function filter_items_by_template( $project_id, $template_id ) {
		$items = $this->get_project_items( $project_id );

		$tmpl_items = is_array( $items )
			? wp_list_filter( $items, array( 'template_id' => $template_id ) )
			: array();

		return $tmpl_items;
	}

	public function get_project_items( $project_id ) {
		if ( isset( $this->project_items[ $project_id ] ) ) {
			return $this->project_items[ $project_id ];
		}

		$this->project_items[ $project_id ] = $this->api()->get_project_items( $project_id );

		return $this->project_items[ $project_id ];
	}

	/**
	 * Create or update a template mapping post using the saved options/values.
	 *
	 * @since  3.0.0
	 *
	 * @param  array $options Array of options from mapping UI.
	 *
	 * @return int|WP_Error The post ID on success. The value 0 or WP_Error on failure.
	 */
	protected function create_or_update_mapping_post( $options ) {
		$post_args = $mapping_args = array();

		$mapping_args = Utils::array_map_recursive( 'sanitize_text_field', $options );

		unset( $options['create_mapping'] );
		unset( $options['title'] );
		unset( $options['account'] );
		unset( $options['account_id'] );
		unset( $options['project'] );
		unset( $options['template'] );

		if ( isset( $options['existing_mapping_id'] ) ) {
			$post_args['ID'] = absint( $options['existing_mapping_id'] );
			unset( $options['existing_mapping_id'] );
		}

		$mapping_args['content'] = $options;

		return $this->mappings->create_mapping( $mapping_args, $post_args, $post_args, 1 );
	}

	/**
	 * Determine if any conditions are met to cause us to redirect.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function handle_redirects() {
		$this->maybe_redirect_to_create_new_mapping();
		$this->maybe_redirect_to_edit_mapping_template();
	}

	/**
	 * Determine if we should redirect to new-mapping settings page
	 * when trying to create a new Template Mapping.
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function maybe_redirect_to_create_new_mapping() {
		global $pagenow;

		if ( 'post-new.php' === $pagenow && $this->get_val_equals( 'post_type', $this->mappings->slug ) ) {
			wp_safe_redirect( $this->url );
			exit;
		}
	}

	/**
	 * Determine if we should redirect to a defined mapping template to edit.
	 * (based on template/project id)
	 *
	 * @since  3.0.0
	 *
	 * @return void
	 */
	protected function maybe_redirect_to_edit_mapping_template() {
		if (
			! $this->get_val_equals( 'page', self::SLUG )
			|| ! $this->_get_val( 'project' )
			|| ! $this->_get_val( 'template' )
		) {
			return;
		}

		$mapping_id = absint( $this->_get_val( 'mapping' ) );

		$exists = $this->mappings->get_by_project_template(
			sanitize_text_field( $this->_get_val( 'project' ) ),
			sanitize_text_field( $this->_get_val( 'template' ) )
		);

		$redirect_id = $exists->have_posts() ? $exists->posts[0] : false;

		// If not mapping id is found to match project/template, get rid of mapping query arg.
		if ( ! $redirect_id && $mapping_id ) {
			wp_safe_redirect( esc_url_raw( remove_query_arg( 'mapping' ) ) );
			exit;
		}

		// Determine if 'mapping' query arg is correct.
		$redirect_id = $redirect_id && $mapping_id !== $redirect_id ? $redirect_id : false;

		if ( ! $redirect_id ) {
			return;
		}

		// Ok, we found a mapping ID, so add that as a query string and redirect.
		$args['mapping'] = $redirect_id;

		wp_safe_redirect( esc_url_raw( add_query_arg( $args ) ) );
		exit;
	}

}
