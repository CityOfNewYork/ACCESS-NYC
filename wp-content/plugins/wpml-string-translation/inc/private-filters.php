<?php

use function WPML\Container\make;

/**
 * @param array $source_languages
 *
 * @return array[]
 */
function filter_tm_source_langs( $source_languages ) {
	global $wpdb, $sitepress;

	static $tm_filter;
	if ( ! $tm_filter ) {
		$tm_filter = new WPML_TM_Filters( $wpdb, $sitepress );
	}

	return $tm_filter->filter_tm_source_langs( $source_languages );
}

/**
 *
 * @param bool       $assigned_correctly
 * @param string     $string_translation_id in the format used by
 *                                          TM functionality as
 *                                          "string|{$string_translation_id}"
 * @param int        $translator_id
 * @param int|string $service
 *
 * @return bool
 */
function wpml_st_filter_job_assignment( $assigned_correctly, $string_translation_id, $translator_id, $service ) {
	global $wpdb, $sitepress;

	$tm_filter = new WPML_TM_Filters( $wpdb, $sitepress );

	return $tm_filter->job_assigned_to_filter( $assigned_correctly, $string_translation_id, $translator_id, $service );
}

add_filter( 'wpml_tm_allowed_source_languages', 'filter_tm_source_langs', 10, 1 );
add_filter( 'wpml_job_assigned_to_after_assignment', 'wpml_st_filter_job_assignment', 10, 4 );

/**
 * @deprecated since WPML ST 3.0.0
 *
 * @param string $val
 *
 * @return string
 * @throws \Auryn\InjectionException
 */
function wpml_st_blog_title_filter( $val ) {
	/** @var WPML_ST_Blog_Name_And_Description_Hooks $filter */
	$filter = make( WPML_ST_Blog_Name_And_Description_Hooks::class );
	return $filter->option_blogname_filter( $val );
}

/**
 * @deprecated since WPML ST 3.0.0
 *
 * @param string $val
 *
 * @return string
 * @throws \Auryn\InjectionException
 */
function wpml_st_blog_description_filter( $val ) {
	/** @var WPML_ST_Blog_Name_And_Description_Hooks $filter */
	$filter = make( WPML_ST_Blog_Name_And_Description_Hooks::class );
	return $filter->option_blogdescription_filter( $val );
}
