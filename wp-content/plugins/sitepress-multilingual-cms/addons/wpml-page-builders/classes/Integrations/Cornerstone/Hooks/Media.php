<?php

namespace WPML\PB\Cornerstone\Hooks;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Media implements \IWPML_Frontend_Action, \IWPML_Backend_Action, \IWPML_DIC_Action {

	/**
	 * @var \WPML_Cornerstone_Data_Settings $dataSettings
	 */
	private $dataSettings;

	public function __construct( \WPML_Cornerstone_Data_Settings $dataSettings ) {
		$this->dataSettings = $dataSettings;
	}

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_get_media_updaters', PHP_INT_MAX, 2 )
		     ->then( spreadArgs( [ $this, 'removeUpdaterIfNotHandledByCornerstone' ] ) );
	}

	/**
	 * @param \IWPML_PB_Media_Update[] $updaters
	 * @param \WP_Post                $post
	 *
	 * @return mixed
	 */
	public function removeUpdaterIfNotHandledByCornerstone( $updaters, $post ) {
		if ( ! $this->dataSettings->is_handling_post( $post->ID ) ) {
			unset( $updaters[ \WPML_Cornerstone_Integration_Factory::SLUG ] );
		}

		return $updaters;
	}
}
