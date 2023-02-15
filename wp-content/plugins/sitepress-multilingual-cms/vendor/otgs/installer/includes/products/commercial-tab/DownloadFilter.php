<?php

namespace OTGS\Installer\CommercialTab;

class DownloadFilter {
	public static function shouldDisplayRecord( $productSlug ) {
		return $productSlug !== 'wpml-translation-management'
		       || defined( 'ICL_SITEPRESS_VERSION' )
		          && version_compare( ICL_SITEPRESS_VERSION, '4.5', '<' );
	}
}
