<?php

namespace Gravity_Forms\Gravity_SMTP\Experimental_Features;

use Gravity_Forms\Gravity_SMTP\Data_Store\Data_Store_Router;
use Gravity_Forms\Gravity_SMTP\Utils\Booliesh;

class Experiment_Features_Handler {

	const ENABLED_EXPERIMENTS_PARAM = 'enabled_experimental_features';

	protected $experiments = array(
		'alerts_management',
		'email_open_tracking',
	);

	/**
	 * @var Data_Store_Router
	 */
	protected $data_router;

	public function __construct( Data_Store_Router $data_router ) {
		$this->data_router = $data_router;
	}

	public function feature_flag_callback( $is_enabled, $flag_slug ) {
		if ( ! in_array( $flag_slug, $this->experiments ) ) {
			return $is_enabled;
		}

		$enabled_experiments = $this->data_router->get_plugin_setting( self::ENABLED_EXPERIMENTS_PARAM, array() );

		if ( empty( $enabled_experiments ) ) {
			return false;
		}

		if ( ! isset( $enabled_experiments[ $flag_slug ] ) ) {
			return false;
		}

		return Booliesh::get( $enabled_experiments[ $flag_slug ] );
	}

}