<?php

namespace BulkWP\BulkDelete\Core\Addon;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

/**
 * Encapsulates the logic for a Scheduler add-on.
 *
 * All Scheduler add-ons will be extending this class.
 *
 * @since 6.0.0
 */
abstract class SchedulerAddon extends BaseAddon {
	/**
	 * Name of the Scheduler class.
	 *
	 * @var string
	 */
	protected $scheduler_class_name;

	/**
	 * Register and setup the add-on.
	 */
	public function register() {
		$scheduler = new $this->scheduler_class_name();
		$scheduler->register();
	}
}
