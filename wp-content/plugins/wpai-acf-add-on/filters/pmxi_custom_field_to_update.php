<?php
/**
 * @param $field_to_update
 * @param $post_type
 * @param $options
 * @param $m_key
 * @return mixed|void
 */
function pmai_pmxi_custom_field_to_update($field_to_update, $post_type, $options, $m_key ){

	if ( ! in_array($m_key, PMAI_Plugin::$all_acf_fields) && ! preg_match('%.*_[0-9]{1,}_%', $m_key)) return $field_to_update;

	return pmai_is_acf_update_allowed($m_key, $options);
}