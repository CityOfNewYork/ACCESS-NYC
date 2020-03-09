<?php

namespace WPML\ST\Rest\MO;

use WPML\ST\MO\File\Manager;
use \WPML\Rest\Adaptor;
use WPML\ST\MO\Generate\Process\ProcessFactory;
use WPML\ST\MO\Scan\UI\Factory;

class PreGenerate extends \WPML\ST\Rest\Base {
	/** @var Manager */
	private $manager;

	/** @var ProcessFactory */
	private $processFactory;

	public function __construct(
		Adaptor $adaptor,
		Manager $manager,
		ProcessFactory $processFactory
	) {
		parent::__construct( $adaptor );
		$this->manager        = $manager;
		$this->processFactory = $processFactory;
	}


	/**
	 * @return array
	 */
	function get_routes() {
		return [
			[
				'route' => 'pre_generate_mo_files',
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'generate' ],
					'args'     => [],
				]

			]
		];
	}

	function get_allowed_capabilities( \WP_REST_Request $request ) {
		return [ 'manage_options' ];
	}

	public function generate() {
		if ( ! $this->manager->maybeCreateSubdir() ) {
			return [ 'error' => 'no access' ];
		}

		Factory::clearIgnoreWpmlVersion();

		return [ 'remaining' => $this->processFactory->create()->runPage() ];
	}
}
