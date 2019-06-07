<?php

namespace BulkWP\BulkDelete\Core\Base;

use BulkWP\BulkDelete\Core\BulkDelete;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Base class for all Bulk Delete pages that will have modules.
 *
 * @since 6.0.0
 */
abstract class BaseDeletePage extends BasePage {
	/**
	 * Item Type. Possible values 'posts', 'pages', 'users' etc.
	 *
	 * @var string
	 */
	protected $item_type;

	/**
	 * Modules registered to this page.
	 *
	 * @var \BulkWP\BulkDelete\Core\Base\BaseModule[]
	 */
	protected $modules = array();

	/**
	 * Register the modules after the page is registered.
	 */
	public function register() {
		parent::register();

		if ( $this->has_modules() ) {
			$this->register_modules();
		}
	}

	/**
	 * Add a module to the page.
	 *
	 * @param \BulkWP\BulkDelete\Core\Base\BaseModule $module Module to add.
	 */
	public function add_module( $module ) {
		if ( in_array( $module, $this->modules, true ) ) {
			return;
		}

		$this->modules[ $module->get_name() ] = $module;
	}

	/**
	 * Get module object instance by module class name.
	 *
	 * @param string $module_class_name Module class name.
	 *
	 * @return \BulkWP\BulkDelete\Core\Base\BaseModule|null Module object instance or null if no match found.
	 */
	public function get_module( $module_class_name ) {
		$short_class_name = bd_get_short_class_name( $module_class_name );

		if ( isset( $this->modules[ $short_class_name ] ) ) {
			return $this->modules[ $short_class_name ];
		}

		return null;
	}

	protected function register_hooks() {
		parent::register_hooks();

		add_action( 'admin_print_scripts-' . $this->hook_suffix, array( $this, 'enqueue_assets' ) );
		add_action( "load-{$this->hook_suffix}", array( $this, 'on_load_page' ) );
	}

	/**
	 * Enqueue Scripts and Styles.
	 */
	public function enqueue_assets() {
		/**
		 * Runs just before enqueuing scripts and styles in all Bulk WP admin pages.
		 *
		 * This action is primarily for registering or deregistering additional scripts or styles.
		 *
		 * @param \BulkWP\BulkDelete\Core\Base\BaseDeletePage The current page.
		 *
		 * @since 5.5.1
		 * @since 6.0.0 Added $page parameter.
		 */
		do_action( 'bd_before_admin_enqueue_scripts', $this );

		/**
		 * Runs just before enqueuing scripts and styles in a Bulk WP admin pages.
		 *
		 * This action is primarily for registering or deregistering additional scripts or styles.
		 *
		 * @param \BulkWP\BulkDelete\Core\Base\BaseDeletePage The current page.
		 *
		 * @since 6.0.1
		 */
		do_action( "bd_before_enqueue_page_assets_for_{$this->get_page_slug()}", $this );

		wp_enqueue_style( 'jquery-ui-smoothness', $this->get_plugin_dir_url() . 'assets/css/jquery-ui-smoothness.min.css', array(), '1.12.1' );

		wp_enqueue_script(
			'jquery-ui-timepicker-addon',
			$this->get_plugin_dir_url() . 'assets/js/jquery-ui-timepicker-addon.min.js',
			array( 'jquery-ui-slider', 'jquery-ui-datepicker' ),
			'1.6.3',
			true
		);
		wp_enqueue_style( 'jquery-ui-timepicker', $this->get_plugin_dir_url() . 'assets/css/jquery-ui-timepicker-addon.min.css', array( 'jquery-ui-smoothness' ), '1.6.3' );

		wp_enqueue_script( 'select2', $this->get_plugin_dir_url() . 'assets/js/select2.min.js', array( 'jquery' ), '4.0.5', true );
		wp_enqueue_style( 'select2', $this->get_plugin_dir_url() . 'assets/css/select2.min.css', array(), '4.0.5' );

		$postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script(
			'bulk-delete',
			$this->get_plugin_dir_url() . 'assets/js/bulk-delete' . $postfix . '.js',
			array( 'jquery-ui-timepicker-addon', 'jquery-ui-tooltip', 'postbox' ),
			BulkDelete::VERSION,
			true
		);
		wp_enqueue_style(
			'bulk-delete',
			$this->get_plugin_dir_url() . 'assets/css/bulk-delete' . $postfix . '.css',
			array( 'jquery-ui-smoothness', 'jquery-ui-timepicker', 'select2' ),
			BulkDelete::VERSION
		);

		/**
		 * Filter JavaScript array.
		 *
		 * This filter can be used to extend the array that is passed to JavaScript
		 *
		 * @since 5.4
		 */
		$translation_array = apply_filters(
			'bd_javascript_array',
			array(
				'msg'              => array(),
				'validators'       => array(),
				'dt_iterators'     => array(),
				'pre_action_msg'   => array(), // deprecated since 6.0.1.
				'pre_delete_msg'   => array(),
				'pre_schedule_msg' => array(),
				'error_msg'        => array(),
				'pro_iterators'    => array(),
			)
		);
		wp_localize_script( 'bulk-delete', 'BulkWP', $translation_array ); // TODO: Change JavaScript variable to BulkWP.BulkDelete.

		/**
		 * Runs just after enqueuing scripts and styles in all Bulk WP admin pages.
		 *
		 * This action is primarily for registering additional scripts or styles.
		 *
		 * @param \BulkWP\BulkDelete\Core\Base\BaseDeletePage The current page.
		 *
		 * @since 5.5.1
		 * @since 6.0.0 Added $page parameter.
		 */
		do_action( 'bd_after_admin_enqueue_scripts', $this );

		/**
		 * Runs just after enqueuing scripts and styles in a Bulk WP admin pages.
		 *
		 * This action is primarily for registering or deregistering additional scripts or styles.
		 *
		 * @param \BulkWP\BulkDelete\Core\Base\BaseDeletePage The current page.
		 *
		 * @since 6.0.1
		 */
		do_action( "bd_after_enqueue_page_assets_for_{$this->get_page_slug()}", $this );
	}

	/**
	 * Trigger the add_meta_boxes hooks to allow modules to be added when the page is loaded.
	 */
	public function on_load_page() {
		do_action( 'add_meta_boxes_' . $this->hook_suffix, null );
	}

	/**
	 * Add additional nonce fields that are related to modules.
	 */
	protected function render_nonce_fields() {
		parent::render_nonce_fields();

		// Used to save closed meta boxes and their order.
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
	}

	/**
	 * Render meta boxes in body.
	 */
	protected function render_body() {
		do_meta_boxes( '', 'advanced', null );
	}

	/**
	 * Render footer.
	 */
	protected function render_footer() {
		parent::render_footer();

		/**
		 * Runs just before displaying the footer text in the admin page.
		 *
		 * This action is primarily for adding extra content in the footer of admin page.
		 *
		 * @since 5.5.4
		 */
		do_action( "bd_admin_footer_for_{$this->item_type}" );
	}

	/**
	 * Does this page have any modules?
	 *
	 * @return bool True if page has modules, False otherwise.
	 */
	protected function has_modules() {
		return ! empty( $this->modules );
	}

	/**
	 * Load all the registered modules.
	 */
	protected function register_modules() {
		foreach ( $this->modules as $module ) {
			$module->register( $this->hook_suffix, $this->page_slug );
			$this->actions[] = $module->get_action();
		}

		/**
		 * Triggered after all modules are registered.
		 *
		 * @since 6.0.0
		 */
		do_action( "bd_add_meta_box_for_{$this->get_item_type()}" );
	}

	/**
	 * Get the item type of the page.
	 *
	 * @return string Item type of the page.
	 */
	public function get_item_type() {
		return $this->item_type;
	}
}
