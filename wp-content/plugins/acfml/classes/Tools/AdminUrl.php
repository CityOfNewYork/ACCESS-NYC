<?php

namespace ACFML\Tools;

use WPML\UIPage;

class AdminUrl {

	const DASHBOARD_PARAM_SECTIONS          = 'sections';
	const DASHBOARD_SECTION_STRING          = 'string';
	const DASHBOARD_PARAM_STRING_DOMAIN     = 'predefinedStringDomain';
	const DASHBOARD_SECTION_PACKAGE_BY_SLUG = 'stringPackage/%s';

	/**
	 * @param string[] $sections
	 * @param string   $stringDomain
	 *
	 * @return string
	 */
	private static function getWPMLTMDashboard( array $sections = [], string $stringDomain = '' ) : string {
		$dashboardUrl = admin_url( UIPage::getTMDashboard() );
		if ( empty( $sections ) ) {
			return $dashboardUrl;
		}

		$dashboardUrl = add_query_arg(
			[ self::DASHBOARD_PARAM_SECTIONS => implode( ',', $sections ) ],
			$dashboardUrl
		);

		if ( in_array( self::DASHBOARD_SECTION_STRING, $sections, true ) && $stringDomain ) {
			$dashboardUrl = add_query_arg(
				[ self::DASHBOARD_PARAM_STRING_DOMAIN => $stringDomain ],
				$dashboardUrl
			);
		}

		return $dashboardUrl;
	}

	/**
	 * @param string $packageKindSlug
	 *
	 * @return string
	 */
	public static function getWPMLTMDashboardPackageSection( string $packageKindSlug ) : string {
		$section = sprintf( self::DASHBOARD_SECTION_PACKAGE_BY_SLUG, $packageKindSlug );
		return self::getWPMLTMDashboard( [ $section ] );
	}

}
