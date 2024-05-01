<?php

namespace WPML\TM\Settings\Flags\Endpoints;

use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Str;
use WPML\TM\Settings\Flags\Command\ConvertFlags;
use WPML\TM\Settings\Flags\Options;
use function WPML\Container\make;

class SetFormat {
	public function run( Collection $data ) {
		return Either::of( $data->get( 'format' ) )
		             ->filter( Lst::includes( Fns::__, Options::getAllowedFormats() ) )
		             ->chain( function ( $format ) {
			             /** @var ConvertFlags $convertFlag */
			             $convertFlag = make( ConvertFlags::class );

			             return $convertFlag->run( $format );
		             } )
		             ->chain( function ( $format ) {
			             return Options::saveFormat( $format );
		             } )
		             ->map( Str::concat( 'Flags converted to: ' ) )
		             ->bimap( Str::concat( 'Invalid format: ' ), Fns::identity() );
	}
}