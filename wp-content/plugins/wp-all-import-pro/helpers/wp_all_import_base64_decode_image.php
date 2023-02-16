<?php
if( !function_exists('wp_all_import_base64_decode_image')){
	function wp_all_import_base64_decode_image( $image ){
		// Only check the base64 portion of image references.
		$matches = [];
		preg_match('@(data:image/.{1,6};base64,)(.*)@', $image, $matches);

		// Set image to only the base64 encoded portion if detected.
		$image = isset($matches[2]) ? $matches[2] : $image;

		return base64_decode($image);
	}
}