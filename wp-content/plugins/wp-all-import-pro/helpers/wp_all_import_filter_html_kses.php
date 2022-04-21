<?php

if( !function_exists('wp_all_import_filter_html_kses')){
	function wp_all_import_filter_html_kses($html, $context = 'post'){
		return wp_kses($html, $context);
	}
}