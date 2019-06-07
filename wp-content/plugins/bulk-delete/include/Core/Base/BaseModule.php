<?php

namespace BulkWP\BulkDelete\Core\Base;

use BulkWP\BulkDelete\Core\Base\Mixin\Renderer;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Encapsulates the Bulk Delete Meta box Module Logic.
 *
 * All Bulk Delete Meta box Modules should extend this class.
 * This class extends Renderer Mixin class since Bulk Delete still supports PHP 5.3.
 * Once PHP 5.3 support is dropped, Renderer will be implemented as a Trait and this class will `use` it.
 *
 * @since 6.0.0
 */
abstract class BaseModule extends Renderer {
	/**
	 * Item Type. Possible values 'posts', 'pages', 'users' etc.
	 *
	 * @var string
	 */
	protected $item_type;

	/**
	 * The hook suffix of the screen where this meta box would be shown.
	 *
	 * @var string
	 */
	protected $page_hook_suffix;

	/**
	 * Slug of the page where this module will be shown.
	 *
	 * @var string
	 */
	protected $page_slug;

	/**
	 * Slug of the meta box.
	 *
	 * @var string
	 */
	protected $meta_box_slug;

	/**
	 * Action in which the delete operation should be performed.
	 *
	 * @var string
	 */
	protected $action = '';

	/**
	 * Hook for scheduler.
	 *
	 * @var string
	 */
	protected $cron_hook;

	/**
	 * Url of the scheduler addon.
	 *
	 * @var string
	 */
	protected $scheduler_url;

	/**
	 * Messages shown to the user.
	 *
	 * @var array
	 */
	protected $messages = array(
		'box_label'         => '',
		'cron_label'        => '',
		'validation_error'  => '',
		'confirm_deletion'  => '',
		'confirm_scheduled' => '',
		'scheduled'         => '',
		'nothing_to_delete' => '',
		'deleted_one'       => '',
		'deleted_multiple'  => '',
	);

	/**
	 * Initialize and setup variables.
	 *
	 * @return void
	 */
	abstract protected function initialize();

	/**
	 * Render the Modules.
	 *
	 * @return void
	 */
	abstract public function render();

	/**
	 * Process common filters.
	 *
	 * @param array $request Request array.
	 *
	 * @return array User options.
	 */
	abstract protected function parse_common_filters( $request );

	/**
	 * Process user input and create metabox options.
	 *
	 * @param array $request Request array.
	 * @param array $options User options.
	 *
	 * @return array User options.
	 */
	abstract protected function convert_user_input_to_options( $request, $options );

	/**
	 * Perform the deletion.
	 *
	 * @param array $options Array of Delete options.
	 *
	 * @return int Number of items that were deleted.
	 */
	abstract protected function do_delete( $options );

	/**
	 * Create new instances of Modules.
	 */
	public function __construct() {
		$this->initialize();
	}

	/**
	 * Register.
	 *
	 * @param string $hook_suffix Page Hook Suffix.
	 * @param string $page_slug   Page slug.
	 */
	public function register( $hook_suffix, $page_slug ) {
		$this->page_hook_suffix = $hook_suffix;
		$this->page_slug        = $page_slug;

		add_action( "add_meta_boxes_{$this->page_hook_suffix}", array( $this, 'setup_metabox' ) );

		add_filter( 'bd_javascript_array', array( $this, 'filter_js_array' ) );

		if ( ! empty( $this->action ) ) {
			add_action( 'bd_' . $this->action, array( $this, 'process' ) );
		}
	}

	/**
	 * Setup the meta box.
	 */
	public function setup_metabox() {
		add_meta_box(
			$this->meta_box_slug,
			$this->messages['box_label'],
			array( $this, 'render_box' ),
			$this->page_hook_suffix,
			'advanced'
		);
	}

	/**
	 * Render the meta box.
	 */
	public function render_box() {
		if ( $this->is_hidden() ) {
			printf(
				/* translators: 1 Module url */
				__( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ),
				'admin.php?page=' . esc_attr( $this->page_slug )
			);

			return;
		}

		$this->render();
	}

	/**
	 * Is the current meta box hidden by user.
	 *
	 * @return bool True, if hidden. False, otherwise.
	 */
	protected function is_hidden() {
		$current_user    = wp_get_current_user();
		$user_meta_field = $this->get_hidden_box_user_meta_field();
		$hidden_boxes    = get_user_meta( $current_user->ID, $user_meta_field, true );

		return is_array( $hidden_boxes ) && in_array( $this->meta_box_slug, $hidden_boxes, true );
	}

	/**
	 * Get the user meta field that stores the status of the hidden meta boxes.
	 *
	 * @return string Name of the User Meta field.
	 */
	protected function get_hidden_box_user_meta_field() {
		if ( 'posts' === $this->item_type ) {
			return 'metaboxhidden_toplevel_page_bulk-delete-posts';
		} else {
			return 'metaboxhidden_bulk-wp_page_' . $this->page_slug;
		}
	}

	/**
	 * Filter the js array.
	 *
	 * Use `append_to_js_array` function to append any module specific js options.
	 *
	 * @see $this->append_to_js_array
	 *
	 * @param array $js_array JavaScript Array.
	 *
	 * @return array Modified JavaScript Array
	 */
	public function filter_js_array( $js_array ) {
		$js_array['dt_iterators'][] = '_' . $this->field_slug;

		$js_array['pre_delete_msg'][ $this->action ] = $this->action . '_confirm_deletion';
		$js_array['error_msg'][ $this->action ]      = $this->action . '_error';

		$js_array['msg'][ $this->action . '_confirm_deletion' ] = __( 'Are you sure you want to delete all the posts based on the selected option?', 'bulk-delete' );
		$js_array['msg'][ $this->action . '_error' ]            = __( 'Please select posts from at least one option', 'bulk-delete' );

		if ( ! empty( $this->messages['confirm_deletion'] ) ) {
			$js_array['msg'][ $this->action . '_confirm_deletion' ] = $this->messages['confirm_deletion'];
		}

		if ( ! empty( $this->messages['confirm_scheduled'] ) ) {
			$js_array['pre_schedule_msg'][ $this->action ] = $this->action . '_confirm_scheduled';

			$js_array['msg'][ $this->action . '_confirm_scheduled' ] = $this->messages['confirm_scheduled'];
		}

		if ( ! empty( $this->messages['validation_error'] ) ) {
			$js_array['msg'][ $this->action . '_error' ] = $this->messages['validation_error'];
		}

		return $this->append_to_js_array( $js_array );
	}

	/**
	 * Append any module specific options to JS array.
	 *
	 * This function will be overridden by the child classes.
	 *
	 * @param array $js_array JavaScript Array.
	 *
	 * @return array Modified JavaScript Array
	 */
	protected function append_to_js_array( $js_array ) {
		return $js_array;
	}

	/**
	 * Helper function for processing deletion.
	 * Setups up cron and invokes the actual delete method.
	 *
	 * @param array $request Request array.
	 */
	public function process( $request ) {
		$options      = $this->parse_common_filters( $request );
		$options      = $this->convert_user_input_to_options( $request, $options );
		$cron_options = $this->parse_cron_filters( $request );

		/**
		 * Filter the processed delete options.
		 *
		 * @since 6.0.0
		 *
		 * @param array $options Processed options.
		 * @param array $request Request array.
		 * @param \BulkWP\BulkDelete\Core\Base\BaseModule The delete module.
		 */
		$options = apply_filters( 'bd_processed_delete_options', $options, $request, $this );

		if ( $this->is_scheduled( $cron_options ) ) {
			$msg = $this->schedule_deletion( $cron_options, $options );
		} else {
			$items_deleted = $this->delete( $options );
			$msg           = sprintf( $this->get_success_message( $items_deleted ), $items_deleted );
		}

		add_settings_error(
			$this->page_slug,
			$this->action,
			$msg,
			'updated'
		);
	}

	/**
	 * Delete items based on delete options.
	 *
	 * @param array $options Delete Options.
	 *
	 * @return int Number of items deleted.
	 */
	public function delete( $options ) {
		/**
		 * Filter delete options before deleting items.
		 *
		 * @since 6.0.0 Added `Modules` parameter.
		 *
		 * @param array $options Delete options.
		 * @param \BulkWP\BulkDelete\Core\Base\BaseModule Modules that is triggering deletion.
		 */
		$options = apply_filters( 'bd_delete_options', $options, $this );

		return $this->do_delete( $options );
	}

	/**
	 * Get Success Message.
	 *
	 * @param int $items_deleted Number of items that were deleted.
	 *
	 * @return string Success message.
	 */
	protected function get_success_message( $items_deleted ) {
		if ( 0 === $items_deleted ) {
			if ( ! empty( $this->messages['nothing_to_delete'] ) ) {
				return $this->messages['nothing_to_delete'];
			}
		}

		return _n( $this->messages['deleted_one'], $this->messages['deleted_multiple'], $items_deleted, 'bulk-delete' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle, WordPress.WP.I18n.NonSingularStringLiteralPlural
	}

	/**
	 * Getter for cron_hook.
	 *
	 * @return string Cron Hook name.
	 */
	public function get_cron_hook() {
		return $this->cron_hook;
	}

	/**
	 * Getter for field slug.
	 *
	 * @return string Field Slug.
	 */
	public function get_field_slug() {
		return $this->field_slug;
	}

	/**
	 * Getter for action.
	 *
	 * @return string Modules action.
	 */
	public function get_action() {
		return $this->action;
	}

	/**
	 * Is the current deletion request a scheduled request?
	 *
	 * @param array $cron_options Request object.
	 *
	 * @return bool True if it is a scheduled request, False otherwise.
	 */
	protected function is_scheduled( $cron_options ) {
		return $cron_options['is_scheduled'];
	}

	/**
	 * Schedule Deletion of items.
	 *
	 * @param array $cron_options Cron options.
	 * @param array $options      Deletion option.
	 *
	 * @return string Message.
	 */
	protected function schedule_deletion( $cron_options, $options ) {
		$options['cron_label'] = $cron_options['cron_label'];

		if ( '-1' === $cron_options['frequency'] ) {
			wp_schedule_single_event( $cron_options['start_time'], $this->cron_hook, array( $options ) );
		} else {
			wp_schedule_event( $cron_options['start_time'], $cron_options['frequency'], $this->cron_hook, array( $options ) );
		}

		return $this->messages['scheduled'] . ' ' . $this->get_task_list_link();
	}

	/**
	 * Get the link to the page that lists all the scheduled tasks.
	 *
	 * @return string Link to scheduled tasks page.
	 */
	protected function get_task_list_link() {
		return sprintf(
			/* translators: 1 Cron page url */
			__( 'See the full list of <a href = "%s">scheduled tasks</a>', 'bulk-delete' ),
			get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . \Bulk_Delete::CRON_PAGE_SLUG
		);
	}

	/**
	 * Parse request and create cron options.
	 *
	 * @param array $request Request array.
	 *
	 * @return array Parsed cron option.
	 */
	protected function parse_cron_filters( $request ) {
		$cron_options = array(
			'is_scheduled' => false,
		);

		$scheduled = bd_array_get_bool( $request, 'smbd_' . $this->field_slug . '_cron', false );

		if ( $scheduled ) {
			$cron_options['is_scheduled'] = true;
			$cron_options['frequency']    = sanitize_text_field( $request[ 'smbd_' . $this->field_slug . '_cron_freq' ] );
			$cron_options['start_time']   = bd_get_gmt_offseted_time( sanitize_text_field( $request[ 'smbd_' . $this->field_slug . '_cron_start' ] ) );

			$cron_options['cron_label'] = $this->get_cron_label();
		}

		return $cron_options;
	}

	/**
	 * Get the human readable label for the Schedule job.
	 *
	 * @return string Human readable label for schedule job.
	 */
	public function get_cron_label() {
		return $this->messages['cron_label'];
	}

	/**
	 * Get the name of the module.
	 *
	 * This is used as the key to identify the module from page.
	 *
	 * @return string Module name.
	 */
	public function get_name() {
		return bd_get_short_class_name( $this );
	}

	/**
	 * Get the page slug of the module.
	 *
	 * @since 6.0.1
	 *
	 * @return string Page slug.
	 */
	public function get_page_slug() {
		return $this->page_slug;
	}
}
