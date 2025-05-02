<?php
namespace WPML\TM\API\ATE;

use WPML\FP\Fns;
use WPML\LIB\WP\WordPress;

use WPML\TM\ATE\API\CachedAMSAPI;
use WPML\TM\ATE\API\CacheStorage\Transient;
use function WPML\Container\make;

class Glossary {

	/** @var \WPML_TM_AMS_API */
	private $amsAPI;

	public function __construct( \WPML_TM_AMS_API $amsAPI ) {
		$this->amsAPI = $amsAPI;
	}

	public function getGlossaryCount() {
		return WordPress::handleError( $this->amsAPI->getGlossaryCount()->getOrElse( [] )  )
		                ->filter( Fns::identity() )
		                ->bimap(
			                function( $response ) {
				                if ( is_wp_error( $response ) ) {
					                return [
						                'error' => $response->get_error_message(),
					                ];
				                }
				                return $response;
			                },
			                Fns::identity()
		                );
	}
}
