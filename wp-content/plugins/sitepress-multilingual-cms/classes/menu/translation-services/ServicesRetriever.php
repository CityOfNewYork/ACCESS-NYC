<?php

namespace WPML\TM\Menu\TranslationServices;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
use function WPML\FP\curryN;
use function WPML\FP\invoke;
use function WPML\FP\partialRight;
use function WPML\FP\pipe;

class ServicesRetriever {
	public static function get( \WPML_TP_API_Services $servicesAPI, $getUserCountry, $mapService ) {
		$userCountry = $getUserCountry( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null );

		// $buildSection :: $services -> $header -> $showPopularity -> string
		$buildSection = self::buildSection( $mapService );

		// $otherSection :: $services -> $header -> string
		$otherSection = $buildSection( Fns::__, Fns::__, false, true );

		$buildPartnerServicesSections = self::buildPartnerServicesSections( $buildSection, $userCountry );
		$partnerServices              = $servicesAPI->get_translation_services( true );
		$services                     = $buildPartnerServicesSections( $partnerServices );

		$services[] = $otherSection(
			$servicesAPI->get_translation_services( false ),
			__( 'Other Translation Services', 'wpml-translation-management' )
		);

		$services[] = $otherSection(
			$servicesAPI->get_translation_management_systems(),
			__( 'Translation Management System', 'wpml-translation-management' )
		);

		return $services;
	}

	// buildPartnerServicesSections :: \WPML_TP_Services[] -> string[]
	private static function buildPartnerServicesSections( $buildSection, $userCountry ) {
		$headers = [
			'regular'        => __( 'Partner Translation Services', 'wpml-translation-management' ),
			'inCountry'      => __(
				sprintf(
					'Partner Translation Services in %s',
					isset( $userCountry['name'] ) ? $userCountry['name'] : ''
				),
				'wpml-translation-management'
			),
			'otherCountries' => __(
				'Other Partner Translation Services from Around the World',
				'wpml-translation-management'
			),
		];

		// $partnerSection :: $services -> $header -> string
		$partnerSection = $buildSection( Fns::__, Fns::__, true, Fns::__ );

		// $regularPartnerSection :: $services -> string
		$regularPartnerSection = $partnerSection( Fns::__, $headers['regular'], true );

		// $partnersInCountry  :: $services -> string
		$partnersInCountry = $partnerSection( Fns::__, $headers['inCountry'], false );

		// $partnersOther :: $services -> string
		$partnersOther = $partnerSection( Fns::__, $headers['otherCountries'], true );

		// $getServicesFromCountry :: [$servicesFromCountry, $otherServices] -> $servicesFromCountry
		$inUserCountry = Lst::nth( 0 );
		// $getServicesFromOtherCountries :: [$servicesFromCountry, $otherServices] -> $otherServices
		$inOtherCountries = Lst::nth( 1 );

		// $splitSections :: [$servicesFromCountry, $otherServices] -> [string, string]
		$splitSections = Fns::converge(
			Lst::make(),
			[
				pipe( $inUserCountry, $partnersInCountry ),
				pipe( $inOtherCountries, $partnersOther ),
			]
		);

		// $hasUserCountry :: [$servicesFromCountry, $otherServices] -> bool
		$hasUserCountry = pipe( $inUserCountry, Logic::isEmpty(), Logic::not() );

		return pipe(
			Lst::partition( self::belongToUserCountry( $userCountry ) ),
			Logic::ifElse(
				$hasUserCountry,
				$splitSections,
				pipe( $inOtherCountries, $regularPartnerSection, Lst::make() )
			)
		);
	}

	/**
	 * @param  callable $mapService
	 *
	 * @return callable
	 */
	private static function buildSection( $mapService ) {
		return curryN(
			4,
			function ( $services, $header, $showPopularity, $pagination ) use ( $mapService ) {
				return [
					'services'       => Fns::map( Fns::unary( $mapService ), $services ),
					'header'         => $header,
					'showPopularity' => $showPopularity,
					'pagination'     => $pagination,
				];
			}
		);
	}

	// belongToUserCountry :: \WPML_TP_Service -> bool
	private static function belongToUserCountry( $userCountry ) {
		return pipe(
			invoke( 'get_countries' ),
			Fns::map( Obj::prop( 'code' ) ),
			Lst::find( Relation::equals( Obj::prop( 'code', $userCountry ) ) ),
			Logic::isNotNull()
		);
	}
}
