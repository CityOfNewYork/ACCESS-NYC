<?php

namespace WPML\ST\Rest;

use WPML\ST\MO\File\ManagerFactory;
use WPML\ST\MO\File\Manager;
use function WPML\Container\make;

/**
 * @author OnTheGo Systems
 */
class FactoryLoader implements \IWPML_REST_Action_Loader, \IWPML_Deferred_Action_Loader {
	const REST_API_INIT_ACTION = 'rest_api_init';

	/**
	 * @return string
	 */
	public function get_load_action() {
		return self::REST_API_INIT_ACTION;
	}

	public function create() {
		return [
			MO\Import::class      => make( MO\Import::class ),
			MO\PreGenerate::class => $this->create_pre_generate(),
			Settings::class       => make( Settings::class ),
		];
	}

	private function create_pre_generate() {
		/** @var Manager $manager */
		$manager = ManagerFactory::create();

		return make(
			MO\PreGenerate::class,
			[ ':manager' => $manager ]
		);
	}
}
