<?php

namespace WPML\PB\Cornerstone\Hooks;

use WPML\FP\Cast;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Editor implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_is_editing_translation_with_native_editor', 10, 2 )
			->then( spreadArgs( function( $isTranslationWithNativeEditor, $translatedPostId ) {
				return $isTranslationWithNativeEditor
					|| (
							Str::includes( 'themeco/data/save', Obj::prop( 'REQUEST_URI', $_SERVER ) )
							&& self::getEditedId() === $translatedPostId
				       );
			} ) );
	}

	/**
	 * @return int|null
	 */
	private static function getEditedId() {
		/**
		 * @see \Cornerstone_Routing::process_params
		 * $decodeCornerstoneData :: string -> array
		 */
		$decodeCornerstoneData = function( $data ) {
			$request = Obj::prop( 'request', $data );

			if ( Obj::prop( 'gzip', $data ) ) {
				return (array) json_decode( gzdecode( base64_decode( $request, true ) ), true );
			}

			return (array) $request;
		};

		$key   = version_compare( constant( 'CS_VERSION' ), '7.1.3', '>=' ) ? 'document' : 'builder';
		$getId = Obj::path( [ 'requests', $key, 'id' ] );

		return Maybe::fromNullable( \WP_REST_Server::get_raw_data() )
			->map( 'json_decode' )
			->map( $decodeCornerstoneData )
			->map( $getId )
			->map( Cast::toInt() )
			->getOrElse( null );
	}
}
