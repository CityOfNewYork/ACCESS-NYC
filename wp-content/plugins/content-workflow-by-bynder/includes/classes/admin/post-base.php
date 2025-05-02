<?php
/**
 * Content Workflow Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer\Admin;

use GatherContent\Importer\General;
use GatherContent\Importer\API;
use GatherContent\Importer\Admin\Mapping\Base as UI_Base;
use GatherContent\Importer\Utils;

/**
 * Because Enqueue is abstract.
 *
 * @since 3.0.0
 */
class Post_Enqueue extends Enqueue {
}

/**
 * Class for managing syncing template items.
 *
 * @since 3.0.0
 */
abstract class Post_Base extends UI_Base {

	/**
	 * GatherContent\Importer\API instance
	 *
	 * @var GatherContent\Importer\API
	 */
	protected $api = null;

	/**
	 * GatherContent\Importer\Admin\Mapping_Wizard instance
	 *
	 * @var GatherContent\Importer\Admin\Mapping_Wizard
	 */
	protected $wizard = null;

	/**
	 * GatherContent\Importer\Admin\Enqueue instance
	 *
	 * @var GatherContent\Importer\Admin\Enqueue
	 */
	protected $enqueue = null;

	/**
	 * A flag to check if this is an ajax request.
	 *
	 * @var boolean
	 */
	protected $doing_ajax = false;

	/** @var array $post_types */
	protected $post_types = [];

	/**
	 * Creates an instance of this class.
	 *
	 * @param API $api API object.
	 * @param Mapping_Wizard $wizard Mapping_Wizard object.
	 *
	 * @since 3.0.0
	 *
	 */
	public function __construct( API $api, Mapping_Wizard $wizard ) {
		$this->api        = $api;
		$this->wizard     = $wizard;
		$this->doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		$this->enqueue    = new Post_Enqueue();
	}

	/**
	 * Get the localizable data array.
	 *
	 * @return array Array of localizable data
	 * @since  3.0.0
	 *
	 */
	protected function get_localize_data() {
		return array(
			'_edit_nonce'  => wp_create_nonce( General::get_instance()->admin->mapping_wizard->option_group . '-options' ),
			'_statuses'    => array(
				'starting' => __( 'Starting Sync', 'content-workflow-by-bynder' ),
				'syncing'  => __( 'Syncing', 'content-workflow-by-bynder' ),
				'complete' => __( 'Sync Complete', 'content-workflow-by-bynder' ),
				'failed'   => __( 'Sync Failed (review?)', 'content-workflow-by-bynder' ),
			),
			'_errors'      => array(
				'unknown' => __( 'There was an unknown error', 'content-workflow-by-bynder' ),
			),
			'_step_labels' => Utils::get_step_label( 'all' ),
		);
	}
}
