<?php

if( !function_exists('wp_all_import_is_base64_encoded')){
	function wp_all_import_is_base64_encoded($data){

		// Only check the base64 portion of image references.
		$matches = [];
		preg_match('@(data:image/.{1,6};base64,)(.*)@', $data, $matches);

		// Set data to only the base64 encoded portion if detected.
		$data = isset($matches[2]) ? $matches[2] : $data;

			$decoded = base64_decode($data, true);

			// Check if there is no invalid character in string
			if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data)) return false;

			// Decode the string in strict mode and send the response
			if (!$decoded) return false;

			// Encode and compare it to original one
			if (base64_encode($decoded) != $data) return false;

			return true;

	}
}