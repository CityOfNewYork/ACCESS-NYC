<?php

namespace WPML\PB\Shortcode;

use WPML\Convert\Ids;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML_PB_Config_Import_Shortcode;
use function WPML\FP\curryN;
use function WPML\FP\spreadArgs;

class AdjustIdsHooks implements \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/**
	 * @var WPML_PB_Config_Import_Shortcode $config
	 */
	private $config;

	public function __construct( WPML_PB_Config_Import_Shortcode $config ) {
		$this->config = $config;
	}

	public function add_hooks() {
		Hooks::onFilter( 'pre_do_shortcode_tag', - PHP_INT_MAX, 4 )
			->then( spreadArgs( Fns::withoutRecursion( Fns::identity(), [ $this, 'convertAttributeIds' ] ) ) );
	}

	/**
	 * @param false|string $bool
	 * @param string       $tag
	 * @param array        $attr
	 * @param array        $m
	 *
	 * @return false|string
	 */
	public function convertAttributeIds( $bool, $tag, $attr, $m ) {
		$tagConfig = $this->getConfig( $tag );

		if ( $tagConfig ) {
			$convert = curryN( 2, function( $type, $arr ) {
				return $arr[1] . Ids::convert( $arr[2], $type, true );
			} );

			foreach ( $tagConfig as $attribute => $type ) {
				$convertTypeIds = $convert( $type );

				$m = preg_replace_callback( '/(' . $attribute . '=[\"\']{1})([^\"\']+)/', $convertTypeIds, $m );
			}

			return do_shortcode_tag( $m );
		}

		return $bool;
	}

	/**
	 * @param string $tag
	 *
	 * @return array|null
	 */
	private function getConfig( $tag ) {
		return Obj::prop( $tag, $this->config->get_id_settings() );
	}
}
