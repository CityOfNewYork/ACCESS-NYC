<?php

function pmai_is_acf_update_allowed( $cur_meta_key, $options ){

	if ($options['update_acf_logic'] == 'full_update') return apply_filters('pmai_is_acf_update_allowed', true, $cur_meta_key, $options);	

	global $acf;

	if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

		// Update only these ACF, leave the rest alone
		if ($options['update_all_data'] == 'no' and $options['is_update_acf'] and $options['update_acf_logic'] == 'only'){			

			$is_acf_update_allowed = false;

			if (! empty($options['acf_list']) and is_array($options['acf_list'])){
				foreach ($options['acf_list'] as $key => $acf_field) {				
					$field_name = trim(array_shift(explode(" ", $acf_field)), "[]");												
					if ( $cur_meta_key == $field_name or $cur_meta_key == "_" . $field_name or strpos($cur_meta_key, $field_name . '_') === 0 or strpos($cur_meta_key, '_' . $field_name . '_') === 0 or preg_match('%.*_[0-9]{1,}_'.$field_name.'$%', $cur_meta_key)){
						$is_acf_update_allowed = true;					
						break;
					}				
				}								
			}		
			
			return apply_filters('pmai_is_acf_update_allowed', $is_acf_update_allowed, $cur_meta_key, $options);		

		}

		// Leave these ACF alone, update all other ACF
		if ($options['update_all_data'] == 'no' and $options['is_update_acf'] and $options['update_acf_logic'] == 'all_except'){
			
			if (! empty($options['acf_list']) and is_array($options['acf_list'])){
				foreach ($options['acf_list'] as $key => $acf_field) {
					$field_name = trim(array_shift(explode(" ", $acf_field)), "[]");							
					if ( $cur_meta_key == $field_name or $cur_meta_key == "_" . $field_name or strpos($cur_meta_key, $field_name . '_') === 0 or strpos($cur_meta_key, '_' . $field_name . '_') === 0 or preg_match('%.*_[0-9]{1,}_'.$field_name.'$%', $cur_meta_key)){
						return apply_filters('pmai_is_acf_update_allowed', false, $cur_meta_key, $options);
						break;
					}
				}
			}		
		}

		// Update only mapped ACF fields
		if ($options['update_all_data'] == 'no' and $options['is_update_acf'] and $options['update_acf_logic'] == 'mapped'){
				
			$mapped_acf = $options['acf'];

			if ( ! empty($mapped_acf)){			
				foreach ($mapped_acf as $acf_group_id => $is_mapped) {				
					if ( ! $is_mapped ) continue;				
					$acf_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $acf_group_id, 'post_status' => 'publish'));
					if ( ! empty($acf_fields) ){
						foreach ($acf_fields as $field) {
							if ( $cur_meta_key == $field->post_excerpt or $cur_meta_key == "_" . $field->post_excerpt or strpos($cur_meta_key, $field->post_excerpt . '_') === 0 or strpos($cur_meta_key, '_' . $field->post_excerpt . '_') === 0){
								return apply_filters('pmai_is_acf_update_allowed', true, $cur_meta_key, $options);
								break;
							}
						}				
					}
				}			
			}

			return apply_filters('pmai_is_acf_update_allowed', false, $cur_meta_key, $options);	
		}

		return apply_filters('pmai_is_acf_update_allowed', true, $cur_meta_key, $options);
		
	}
	else{

		// Update only these ACF, leave the rest alone
		if ($options['update_all_data'] == 'no' and $options['is_update_acf'] and $options['update_acf_logic'] == 'only'){
			
			if (! empty($options['acf_list']) and is_array($options['acf_list'])){
				foreach ($options['acf_list'] as $key => $acf_field) {
					$field_parts = explode('---', $acf_field);					
					$parts_temp = explode(" ", $field_parts[0]);
					$field_name = trim(array_shift($parts_temp), "[]");				
					if (!empty($field_parts[1])){
						$sub_field_name = trim($field_parts[1], "[]");
						if (preg_match('%^_{0,1}'.$field_name.'_[0-9]{1,}_'.$sub_field_name.'$%', $cur_meta_key)){
							return apply_filters('pmai_is_acf_update_allowed', true, $cur_meta_key, $options);
							break;
						}
					}
					elseif ( preg_match('%^_{0,1}'.$field_name.'$%', $cur_meta_key) ){
						return apply_filters('pmai_is_acf_update_allowed', true, $cur_meta_key, $options);
						break;
					}				
				}
				return apply_filters('pmai_is_acf_update_allowed', false, $cur_meta_key, $options);
			}					

		}

		// Leave these ACF alone, update all other ACF
		if ($options['update_all_data'] == 'no' and $options['is_update_acf'] and $options['update_acf_logic'] == 'all_except'){
			
			if (! empty($options['acf_list']) and is_array($options['acf_list'])){
				foreach ($options['acf_list'] as $key => $acf_field) {
					$field_parts = explode('---', $acf_field);
					$field_name = trim(array_shift(explode(" ", $field_parts[0])), "[]");
					if (!empty($field_parts[1])){
						$sub_field_name = trim($field_parts[1], "[]");
						if (preg_match('%^_{0,1}'.$field_name.'_[0-9]{1,}_'.$sub_field_name.'$%', $cur_meta_key)){
							return apply_filters('pmai_is_acf_update_allowed', false, $cur_meta_key, $options);
							break;
						}
					}
					elseif ( preg_match('%^_{0,1}'.$field_name.'$%', $cur_meta_key) ){
						return apply_filters('pmai_is_acf_update_allowed', false, $cur_meta_key, $options);
						break;
					}
				}
			}		
		}

		// Update only mapped ACF fields
		if ($options['update_all_data'] == 'no' and $options['is_update_acf'] and $options['update_acf_logic'] == 'mapped'){
				
			$mapped_acf = $options['acf'];

			if ( ! empty($mapped_acf)){			
				
				$all_acf_fields = array();

				foreach ($mapped_acf as $acf_group_id => $is_mapped) {				
					if ( ! $is_mapped ) continue;
					$acf_fields = get_post_meta($acf_group_id, '');
					if (!empty($acf_fields)){
						foreach ($acf_fields as $meta_key => $cur_meta_val){
							
							if (strpos($meta_key, 'field_') !== 0) continue;

							$field = (!empty($cur_meta_val[0])) ? unserialize($cur_meta_val[0]) : array();

							if ( ! in_array($field['name'], $all_acf_fields) ) $all_acf_fields[] = $field['name'];

							if (!empty($field['sub_fields'])){
								foreach ($field['sub_fields'] as $sub_field) {
									if ( ! in_array($sub_field['name'], $all_acf_fields) ) $all_acf_fields[] = $sub_field['name'];		
								}
							}
						}
					}
				}
				if ( in_array($cur_meta_key, $all_acf_fields)){
					foreach ($mapped_acf as $acf_group_id => $is_mapped) {				
						if ( ! $is_mapped ) continue;
						$acf_fields = get_post_meta($acf_group_id, '');
						if (!empty($acf_fields)){
							foreach ($acf_fields as $meta_key => $cur_meta_val){
								
								if (strpos($meta_key, 'field_') !== 0) continue;
								
								$field = (!empty($cur_meta_val[0])) ? unserialize($cur_meta_val[0]) : array();

								if ( preg_match('%^_{0,1}'.$field['name'].'$%', $cur_meta_key) ){
									return apply_filters('pmai_is_acf_update_allowed', true, $cur_meta_key, $options);
									break;
								}

								if (!empty($field['sub_fields'])){
									foreach ($field['sub_fields'] as $sub_field) {
										if (preg_match('%^_{0,1}'.$field['name'].'_[0-9]{1,}_'.$sub_field['name'].'$%', $cur_meta_key)){
											return apply_filters('pmai_is_acf_update_allowed', true, $cur_meta_key, $options);
											break;
										}
									}
								}
							}
						}
					}
					return apply_filters('pmai_is_acf_update_allowed', false, $cur_meta_key, $options);
				}			
			}
		}

		return apply_filters('pmai_is_acf_update_allowed', true, $cur_meta_key, $options);		

	}
	
}

?>