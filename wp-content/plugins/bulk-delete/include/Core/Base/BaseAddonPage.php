<?php

namespace BulkWP\BulkDelete\Core\Base;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Base class for an Add-on page.
 *
 * @since 6.0.0
 */
abstract class BaseAddonPage extends BaseDeletePage {
	/**
	 * Details about the add-on to which this page is added.
	 *
	 * @var \BulkWP\BulkDelete\Core\Addon\AddonInfo
	 */
	protected $addon_info;

	/**
	 * Set the add-on in which this page is part of.
	 *
	 * @param \BulkWP\BulkDelete\Core\Addon\AddonInfo $addon_info Add-on info.
	 */
	public function for_addon( $addon_info ) {
		$this->addon_info = $addon_info;
	}
}
