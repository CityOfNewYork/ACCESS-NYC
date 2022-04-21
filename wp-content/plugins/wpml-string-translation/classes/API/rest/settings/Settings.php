<?php

namespace WPML\ST\Rest;

class Settings extends Base {

	/** @var \WPML\WP\OptionManager $option_manager */
	private $option_manager;

	public function __construct( \WPML\Rest\Adaptor $adaptor, \WPML\WP\OptionManager $option_manager ) {
		parent::__construct( $adaptor );
		$this->option_manager = $option_manager;
	}

	public function get_routes() {
		return [
			[
				'route' => 'settings',
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'set' ],
					'args'     => [
						'group' => [ 'required' => true ],
						'key'   => [ 'required' => true ],
						'data'  => [ 'required' => true ],
					],
				],
			],
		];
	}

	public function get_allowed_capabilities( \WP_REST_Request $request ) {
		return [ 'manage_options' ];
	}

	public function set( \WP_REST_Request $request ) {
		$group = $request->get_param( 'group' );
		$key   = $request->get_param( 'key' );
		$data  = $request->get_param( 'data' );

		$this->option_manager->set( 'ST-' . $group, $key, $data );
	}
}
