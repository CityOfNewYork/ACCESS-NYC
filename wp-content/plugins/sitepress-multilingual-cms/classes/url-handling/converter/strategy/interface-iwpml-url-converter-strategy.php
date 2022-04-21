<?php

interface IWPML_URL_Converter_Strategy {

	public function add_hooks();

	public function remove_hooks();

	public function convert_url_string( $source_url, $lang );

	public function convert_admin_url_string( $source_url, $lang );

	public function validate_language( $language, $url );

	public function get_lang_from_url_string( $url );

	public function get_home_url_relative( $url, $lang );

	public function fix_trailingslashit( $source_url );

	public function skip_convert_url_string( $url, $lang_code );

	public function use_wp_login_url_converter();
}
