<?php

namespace BulkWP\BulkDelete\Deprecated\Addons;

use BulkWP\BulkDelete\Core\Base\BaseModule;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Base class for all Deprecated Modules.
 *
 * This class just extends all the abstract methods with an empty implementation.
 *
 * @since 6.0.0
 */
abstract class DeprecatedModule extends BaseModule {
	/**
	 * Addon class name of the old add-on that is used to find out whether the old add-on is active or not.
	 *
	 * @var string
	 */
	protected $addon_class_name = '';

	/**
	 * Slug of the old add-on.
	 *
	 * @var string
	 */
	protected $addon_slug = '';

	/**
	 * Load the deprecated module if the old add-on is active.
	 *
	 * @param \BulkWP\BulkDelete\Core\Base\BaseDeletePage $page Page object.
	 */
	public function load_if_needed( $page ) {
		if ( ! class_exists( $this->addon_class_name ) ) {
			return;
		}

		add_filter( 'bd_upsell_addons', array( $this, 'hide_upsell_module' ), 10, 2 );

		$page->add_module( $this );
	}

	/**
	 * Hide the upsell message if the add-on is active.
	 *
	 * @since 6.0.1 Use $page_slug instead of $item_type.
	 *
	 * @param array  $addon_details Addon Details.
	 * @param string $page_slug     Page slug.
	 *
	 * @return array Modified list of Addon Details.
	 */
	public function hide_upsell_module( $addon_details, $page_slug ) {
		if ( ! class_exists( $this->addon_class_name ) ) {
			return $addon_details;
		}

		if ( $this->page_slug !== $page_slug ) {
			return $addon_details;
		}

		$modified_addon_details = array();

		foreach ( $addon_details as $addon_detail ) {
			if ( ! array_key_exists( 'slug', $addon_detail ) ) {
				continue;
			}

			if ( $this->addon_slug === $addon_detail['slug'] ) {
				continue;
			}

			$modified_addon_details[] = $addon_detail;
		}

		return $modified_addon_details;
	}

	/**
	 * Don't do any processing here.
	 *
	 * It is currently handled in the add-on.
	 *
	 * @param array $request Request object.
	 */
	public function process( $request ) {
		// Empty by design. Processing of data happens in the add-on.
	}

	protected function parse_common_filters( $request ) {
		return array();
	}

	protected function convert_user_input_to_options( $request, $options ) {
		return $options;
	}

	protected function do_delete( $options ) {
		// Empty by design.
	}

	protected function get_success_message( $items_deleted ) {
		// Empty by design.
	}
}
