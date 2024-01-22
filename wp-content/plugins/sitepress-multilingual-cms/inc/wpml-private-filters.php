<?php

/**
 * @param int|object $element
 *
 * @return string
 */
function wpml_tm_element_md5( $element ) {
	$helper = new WPML_TM_Action_Helper();

	return $helper->post_md5( $element );
}

add_filter( 'wpml_tm_element_md5', 'wpml_tm_element_md5', 10, 1 );

/**
 * Filters the possible target languages for creating a new post translation
 * on the post edit screen.
 *
 * @param string[] $allowed_langs
 * @param int      $element_id
 * @param string   $element_type_prefix
 *
 * @return string[]
 */
function wpml_tm_filter_post_target_langs(
	$allowed_langs,
	$element_id,
	$element_type_prefix
) {
	global $wpml_tm_translation_status, $wpml_post_translations;
	$tm_records = wpml_tm_get_records();

	$allowed_langs_filter = new WPML_TM_Post_Target_Lang_Filter(
		$tm_records,
		$wpml_tm_translation_status,
		$wpml_post_translations
	);

	return $allowed_langs_filter->filter_target_langs(
		$allowed_langs,
		$element_id,
		$element_type_prefix
	);
}

add_filter(
	'wpml_allowed_target_langs',
	'wpml_tm_filter_post_target_langs',
	10,
	3
);
