<?php

namespace WPML\ST\Rest\MO;

use WPML\ST\TranslationFile\QueueFilter;
use WPML_ST_Translations_File_Queue;

class Import extends \WPML\ST\Rest\Base {

	/**
	 * @return array
	 */
	function get_routes() {
		return [
			[
				'route' => 'import_mo_strings',
				'args'  => [
					'methods'  => 'POST',
					'callback' => [ $this, 'import' ],
					'args'     => [
						'plugins' => [
							'type' => 'array',
						],
						'themes'  => [
							'type' => 'array',
						],
						'other'   => [
							'type' => 'array',
						],
					]
				]
			]
		];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	function get_allowed_capabilities( \WP_REST_Request $request ) {
		return [ 'manage_options' ];
	}

	/**
	 * @return array
	 * @throws \WPML\Auryn\InjectionException
	 */
	public function import( \WP_REST_Request $request ) {
		/** @var WPML_ST_Translations_File_Queue $queue */

		$queue = \WPML\Container\make( \WPML_ST_Translations_File_Scan_Factory::class )->create_queue();
		$queue->import( new QueueFilter(
			$request->get_param( 'plugins' ),
			$request->get_param( 'themes' ),
			$request->get_param( 'other' )
		) );

		return [ 'remaining' => $queue->get_pending() ];
	}
}
