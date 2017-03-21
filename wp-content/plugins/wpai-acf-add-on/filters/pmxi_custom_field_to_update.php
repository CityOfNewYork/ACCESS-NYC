<?php
function pmai_pmxi_custom_field_to_update( $field_to_update, $post_type, $options, $m_key ){	

	if ( ! in_array($m_key, PMAI_Plugin::$all_acf_fields) ) return $field_to_update;		

	return pmai_is_acf_update_allowed($m_key, $options);
}
?>