<?php

if ( ! function_exists('wp_all_import_ctx_mapping')){
	function wp_all_import_ctx_mapping( $ctx, $mapping_rules, $tx_name ){
		if ( ! empty( $mapping_rules) and $ctx['is_mapping']){
			foreach ($mapping_rules as $rule) {
				foreach ($rule as $key => $value) {
					// Modify the values used to check if something should be mapped by checking the name and key with both being lowercase
					$mappingCheckName = $ctx["name"];
					$mappingCheckKey = $key;
					$mappingCheckLowercase = apply_filters("wpai_is_case_insensitive_taxonomy_mapping", false, $tx_name);
					if ($mappingCheckLowercase) {
						$mappingCheckName = strtolower($mappingCheckName);
						$mappingCheckKey = strtolower($mappingCheckKey);
					}
					if ( trim($mappingCheckName) == trim($mappingCheckKey) || str_replace("&amp;", "&", trim($mappingCheckName)) == str_replace("&amp;", "&", trim($mappingCheckKey)) ){
						$ctx['name'] = trim($value);
						break;
					}
				}
			}
		}
		return apply_filters('pmxi_single_category', $ctx, $tx_name);
	}
}