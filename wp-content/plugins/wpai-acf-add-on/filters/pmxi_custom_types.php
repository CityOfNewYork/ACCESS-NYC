<?php
	function pmai_pmxi_custom_types($custom_types){
		if ( ! empty($custom_types['acf']) ) unset($custom_types['acf']);
		return $custom_types;
	}
?>