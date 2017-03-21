<?php
function pmai_pmxi_custom_field_to_delete( $field_to_delete, $pid, $post_type, $options, $cur_meta_key ){

	if ( ! in_array($cur_meta_key, PMAI_Plugin::$all_acf_fields) ) return $field_to_delete;
	
	return pmai_is_acf_update_allowed($cur_meta_key, $options);
}
?>