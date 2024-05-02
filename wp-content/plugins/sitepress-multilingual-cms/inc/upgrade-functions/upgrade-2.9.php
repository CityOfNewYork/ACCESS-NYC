<?php
global $wpdb;

$ms = array(

	'code'           => 'ms',
	'english_name'   => 'Malay',
	'major'          => 0,
	'active'         => 0,
	'default_locale' => 'ms_MY',
	'tag'            => 'ms-MY',
	'encode_url'     => 0,
);

$wpdb->insert( $wpdb->prefix . 'icl_languages', $ms );


$wpdb->insert(
	$wpdb->prefix . 'icl_languages_translations',
	array(
		'language_code'         => 'ms',
		'display_language_code' => 'en',
		'name'                  => 'Malay',
	)
);
$wpdb->insert(
	$wpdb->prefix . 'icl_languages_translations',
	array(
		'language_code'         => 'ms',
		'display_language_code' => 'es',
		'name'                  => 'Malayo',
	)
);
$wpdb->insert(
	$wpdb->prefix . 'icl_languages_translations',
	array(
		'language_code'         => 'ms',
		'display_language_code' => 'de',
		'name'                  => 'Malay',
	)
);
$wpdb->insert(
	$wpdb->prefix . 'icl_languages_translations',
	array(
		'language_code'         => 'ms',
		'display_language_code' => 'fr',
		'name'                  => 'Malay',
	)
);
$wpdb->insert(
	$wpdb->prefix . 'icl_languages_translations',
	array(
		'language_code'         => 'ms',
		'display_language_code' => 'ms',
		'name'                  => 'Melayu',
	)
);

$msFlag = wpml_get_flag_file_name('ms');
$wpdb->insert(
	$wpdb->prefix . 'icl_flags',
	array(
		'lang_code'     => 'ms',
		'flag'          => $msFlag,
		'from_template' => 0,
	)
);
