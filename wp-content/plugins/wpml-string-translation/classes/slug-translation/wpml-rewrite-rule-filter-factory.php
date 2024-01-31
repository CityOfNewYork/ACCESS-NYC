<?php

class WPML_Rewrite_Rule_Filter_Factory {

	/**
	 * @param SitePress|null $sitepress
	 *
	 * @return WPML_Rewrite_Rule_Filter
	 */
	public function create( $sitepress = null ) {
		if ( ! $sitepress ) {
			global $sitepress;
		}

		$slug_records_factory = new WPML_Slug_Translation_Records_Factory();
		$slug_translations    = new WPML_ST_Slug_Translations();

		$custom_types_repositories = array(
			new WPML_ST_Slug_Translation_Post_Custom_Types_Repository(
				$sitepress,
				new WPML_ST_Slug_Custom_Type_Factory(
					$sitepress,
					$slug_records_factory->create( WPML_Slug_Translation_Factory::POST ),
					$slug_translations
				)
			),
			new WPML_ST_Slug_Translation_Taxonomy_Custom_Types_Repository(
				$sitepress,
				new WPML_ST_Slug_Custom_Type_Factory(
					$sitepress,
					$slug_records_factory->create( WPML_Slug_Translation_Factory::TAX ),
					$slug_translations
				),
				new WPML_ST_Tax_Slug_Translation_Settings()
			),
		);

		return new WPML_Rewrite_Rule_Filter( $custom_types_repositories, new WPML_ST_Slug_New_Match_Finder() );
	}
}
