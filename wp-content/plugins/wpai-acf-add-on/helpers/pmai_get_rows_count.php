<?php

if( !function_exists('pmai_get_rows_count') ):
	function pmai_get_rows_count( $field, $sub_field, $i, $parentRepeater ){
        $countCSVrows = 0;
        switch ($sub_field['type']){
            case 'taxonomy':
                if (!empty($sub_field['values'])) {
                    foreach ($sub_field['values'] as $tx_name => $tx_terms) {
                        $is_array = is_array($tx_terms[$i]);
                        if ($is_array) {
                            foreach ($tx_terms[$i] as $tx_term) {
                                if (!empty($parentRepeater)) {
                                    $parent_tx_rows = explode($parentRepeater['delimiter'], $tx_term['name']);
                                    $tx_rows = explode($field['is_variable'], $parent_tx_rows[$parentRepeater['row']]);
                                }
                                else {
                                    $tx_rows = explode($field['is_variable'], $tx_term['name']);
                                }
                                if (count($tx_rows) > $countCSVrows) {
                                    $countCSVrows = count($tx_rows);
                                }
                            }
                        }
                    }
                }
                break;
            case 'google_map':
                if (!empty($sub_field['values'])) {
                    foreach ($sub_field['values'] as $map_setting => $map_setting_values) {
                        if (is_array($map_setting_values)){
                            $countCSVrows = count($map_setting_values);
                            break;
                        }
                    }
                }
                break;
            default:
                if ( ! empty($parentRepeater) ) {
                    $parent_entries = explode($parentRepeater['delimiter'], $sub_field['values'][$i]);
                    $entries = explode($field['is_variable'], $parent_entries[$parentRepeater['row']]);
                }
                else {
                    $entries = explode($field['is_variable'], $sub_field['values'][$i]);
                }

                $entries = array_filter($entries);

                if (count($entries) > $countCSVrows) {
                    $countCSVrows = count($entries);
                }
                break;
        }

        return $countCSVrows;
	}
endif;