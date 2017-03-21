<?php

class PMAI_Import_Record extends PMAI_Model_Record {		

	/**
	 * Associative array of data which will be automatically available as variables when template is rendered
	 * @var array
	 */
	public $data = array();

	public $parsing_data = array();

	public $articleData = array();

	public $import_type = '';

	/**
	 * Initialize model instance
	 * @param array[optional] $data Array of record data to initialize object with
	 */
	public function __construct($data = array()) { 
		parent::__construct($data);
		$this->setTable(PMXI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}	
	
	/**
	 * Perform import operation
	 * @param string $xml XML string to import
	 * @param callback[optional] $logger Method where progress messages are submmitted
	 * @return pmai_Import_Record
	 * @chainable
	 */
	public function parse($parsing_data = array()) { //$import, $count, $xml, $logger = NULL, $chunk = false, $xpath_prefix = ""

		$this->parsing_data = $parsing_data;		

		add_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // do not perform special filtering for imported content			

		$this->data = array();

		$records = array();

		$this->parsing_data['chunk'] == 1 and $this->parsing_data['logger'] and call_user_func($this->parsing_data['logger'], __('Composing advanced custom fields...', 'wp_all_import_acf_add_on'));

		$acfs = $this->parsing_data['import']->options['acf'];

		if ( ! empty($acfs) ):
			foreach ($acfs as $id => $status) { if ( ! $status ) continue;
        $this->parse_acf_group($id);
			}
		endif;		

		remove_filter('user_has_cap', array($this, '_filter_has_cap_unfiltered_html')); kses_init(); // return any filtering rules back if they has been disabled for import procedure					

		return $this->data;
	}

	public function parse_acf_group( $gid ){

	  global $acf;

    if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

      if (is_numeric($gid)){
        $acf_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $gid, 'post_status' => 'publish'));
        if (!empty($acf_fields)):
          foreach ($acf_fields as $field) {
            $fieldData = (!empty($field->post_content)) ? unserialize($field->post_content) : array();
            $fieldData['ID'] = $field->ID;
            $fieldData['label'] = $field->post_title;
            $fieldData['key'] = $field->post_name;
            $fieldData['name'] = $field->post_excerpt;
            if ($fieldData['type'] == 'flexible_content'){
              // vars
              $sub_fields = acf_get_fields($fieldData);
              // loop through layouts, sub fields and swap out the field key with the real field
              foreach( array_keys($fieldData['layouts']) as $i ) {
                // extract layout
                $layout = acf_extract_var( $fieldData['layouts'], $i );
                // append sub fields
                if( !empty($sub_fields) ) {
                  foreach( array_keys($sub_fields) as $k ) {
                    // check if 'parent_layout' is empty
                    if( empty($sub_fields[ $k ]['parent_layout']) ) {
                      // parent_layout did not save for this field, default it to first layout
                      $sub_fields[ $k ]['parent_layout'] = $layout['key'];
                    }
                    // append sub field to layout,
                    if( $sub_fields[ $k ]['parent_layout'] == $layout['key'] ) {
                      $layout['sub_fields'][] = acf_extract_var( $sub_fields, $k );
                    }
                  }
                }
                // append back to layouts
                $fieldData['layouts'][ $i ] = $layout;
              }
            }
            $this->data[$fieldData['key']] = $this->parse_field($fieldData, $this->parsing_data['import']->options[ 'fields' ][ $fieldData['key'] ]);
          }
        endif;
      }
      else{
        $fields = acf_local()->fields;
        if ( ! empty($fields) ) {
          foreach ($fields as $key => $field) {
            if ($field['parent'] == $gid){
              $fieldData = $field;
              $fieldData['ID']    = uniqid();
              $fieldData['label'] = $field['label'];
              $fieldData['key']   = $field['key'];
              $fieldData['name']   = $field['name'];
              $this->data[$fieldData['key']] = $this->parse_field($fieldData, $this->parsing_data['import']->options[ 'fields' ][ $fieldData['key'] ]);
            }
          }
        }
      }
    }
    else{
      if (is_numeric($gid)){
        foreach (get_post_meta($gid, '') as $cur_meta_key => $cur_meta_val) {
          if (strpos($cur_meta_key, 'field_') !== 0) continue;
          $field = (!empty($cur_meta_val[0])) ? unserialize($cur_meta_val[0]) : array();
          $field_xpath = (!empty($this->parsing_data['import']->options[ 'fields' ][ $field['key'] ])) ? $this->parsing_data['import']->options[ 'fields' ][ $field['key'] ] : "";
          $this->data[$field['key']] = $this->parse_field($field, $field_xpath);
        }
      }
      else{
        global $acf_register_field_group;
        if ( ! empty($acf_register_field_group) ){
          foreach ($acf_register_field_group as $key => $group) {
            if ($group['id'] == $gid){
              foreach ($group['fields'] as $field) {
                $field_xpath = (!empty($this->parsing_data['import']->options[ 'fields' ][ $field['key'] ])) ? $this->parsing_data['import']->options[ 'fields' ][ $field['key'] ] : "";
                $this->data[$field['key']] = $this->parse_field($field, $field_xpath);
              }
            }
          }
        }
      }
    }
  }

	public function parse_field($field, $CurrentFieldXpath, $fieldPath = "", $xpath_suffix = "", $repeater_count_rows = 0, $inside_repeater = false){

		$cxpath = $this->parsing_data['xpath_prefix'] . $this->parsing_data['import']->xpath . $xpath_suffix;		

		$currentIsMultipleField = (isset($this->parsing_data['import']->options['is_multiple_field_value'][ $field['key'] ])) ? $this->parsing_data['import']->options['is_multiple_field_value'][ $field['key'] ] : false;
		$currentMultipleValue   = (isset($this->parsing_data['import']->options['multiple_value'][ $field['key'] ])) ? $this->parsing_data['import']->options['multiple_value'][ $field['key'] ] : false;

		if ( "" != $fieldPath ){

			$fieldKeys = str_replace(array('[',']'), array(''), str_replace('][', ':', $fieldPath));
			
			foreach (explode(":", $fieldKeys) as $n => $key) {
				$CurrentFieldXpath      = (!$n) ? $this->parsing_data['import']->options['fields'][$key] : $CurrentFieldXpath[$key];
				
				$is_multiple_field_value = $this->parsing_data['import']->options['is_multiple_field_value'];
				$currentIsMultipleField = (!$n && isset($is_multiple_field_value[$key])) ? $is_multiple_field_value[$key] : $currentIsMultipleField[$key];

				$is_multiple_value = $this->parsing_data['import']->options['multiple_value'];
				$currentMultipleValue   = (!$n && isset($is_multiple_value[$key])) ? $is_multiple_value[$key] : $currentMultipleValue[$key];
			}

			$CurrentFieldXpath 		= (!empty($CurrentFieldXpath[ $field['key'] ])) ? $CurrentFieldXpath[ $field['key'] ] : false;
			$currentIsMultipleField = (isset($currentIsMultipleField[ $field['key'] ])) ? $currentIsMultipleField[ $field['key'] ] : false;
			$currentMultipleValue   = (isset($currentMultipleValue[ $field['key'] ])) ? $currentMultipleValue[ $field['key'] ] : false;			
			
		}		

		$count_records = ($repeater_count_rows) ? $repeater_count_rows : $this->parsing_data['count'];
		
		$values = array_fill(0, $count_records, "");

		$is_multiple = false;	
		$is_variable = false;
		$is_ignore_empties = false;

		$xml = $this->parsing_data['xml'];		

		$tmp_files	= array();

		if ($field['type'] == 'validated_field' and ! empty($field['sub_field'])){
			$field = $field['sub_field'];
		}

		switch ($field['type']) {
			case 'text':
			case 'textarea':
			case 'number':
			case 'email':
			case 'password':
			case 'wysiwyg':																							
			case 'color_picker':
			case 'message':																																								
			case 'user':			
			case 'limiter':
			case 'wp_wysiwyg':			
			case 'acf_cf7':	
			case 'gravity_forms_field':
			case 'page_link':
			case 'post_object':
			case 'oembed':
			case 'url':
			case 'time_picker':
					if ( "" != $CurrentFieldXpath )
					{
						$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;									
					}
				break;
      case 'relationship':
          if ( is_array($CurrentFieldXpath) ){
              if ( ! empty($CurrentFieldXpath['value']) )
              {
                  $values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['value'], $file)->parse(); $tmp_files[] = $file;
                  foreach ($values as $i => $value) {
                      $explode_delimiter = empty($CurrentFieldXpath['delim']) ? ',' : $CurrentFieldXpath['delim'];
                      $values[$i] = array_map('trim',explode($explode_delimiter, $value));
                  }
              }
          }
          else
          {
              if ( "" != $CurrentFieldXpath ){
                  $values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;
              }
          }
          break;
			case 'image':
			case 'file':
					if ( is_array($CurrentFieldXpath) )
					{
						if ( ! empty($CurrentFieldXpath['url']) )
						{												
							$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['url'], $file)->parse(); $tmp_files[] = $file;	
						}
					}
					else
					{
						if ( "" != $CurrentFieldXpath )
						{
							$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;									
						}
					}
				break;
			case 'gallery':
					if ( is_array($CurrentFieldXpath) ){
						if ( ! empty($CurrentFieldXpath['gallery']) )
						{												
							$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['gallery'], $file)->parse(); $tmp_files[] = $file;									
							foreach ($values as $i => $value) {
								$imgs = array();
								$line_imgs = explode("\n", $value);
								if ( ! empty($line_imgs) ){
									foreach ($line_imgs as $line_img){
										$imgs = array_merge($imgs, ( ! empty($CurrentFieldXpath['delim']) ) ? str_getcsv($line_img, $CurrentFieldXpath['delim']) : array($line_img) );								
									}
								}
								$values[$i] = $imgs;
							}							
						}						
					}
					else
					{
						if ( "" != $CurrentFieldXpath ){
							$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;	
						}
					}
				break;
			case 'date_picker':
					if ( "" != $CurrentFieldXpath )
					{
						$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;
						$warned = array(); // used to prevent the same notice displaying several times
						foreach ($values as $i => $d) {
							if ($d == 'now') $d = current_time('mysql'); // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
							$time = strtotime($d);
							if (FALSE === $time) {									
								$values[$i] = $d;
							}
							else{ 
								$values[$i] = date('Ymd', $time);
							}
						}
					}					
				break;
			case 'date_time_picker':
					if ( "" != $CurrentFieldXpath )
					{
						$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;
						$warned = array(); // used to prevent the same notice displaying several times
						foreach ($values as $i => $d) {
							if ($d == 'now') $d = current_time('mysql'); // Replace 'now' with the WordPress local time to account for timezone offsets (WordPress references its local time during publishing rather than the server’s time so it should use that)
							$time = strtotime($d);
							if (FALSE === $time) {									
								$time = $d;//time();
							}
							$values[$i] = $time;
						}
					}					
				break;
      case 'google_map_extended':
			case 'google_map':
			case 'location-field':
					if ( "" != $CurrentFieldXpath['address'] )
					{
						$addresses = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['address'], $file)->parse(); $tmp_files[] = $file;
					}
					else
					{
						$addresses = array_fill(0, $count_records, "");	
					}
					if ( "" != $CurrentFieldXpath['lat'] )
					{
						$lat = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['lat'], $file)->parse(); $tmp_files[] = $file;
					}
					else
					{
						$lat = array_fill(0, $count_records, "");	
					}
					if ( "" != $CurrentFieldXpath['lng'] )
					{
						$lng = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['lng'], $file)->parse(); $tmp_files[] = $file;
					}
					else
					{
						$lng = array_fill(0, $count_records, "");	
					}

          if ( isset($CurrentFieldXpath['zoom']) && "" != $CurrentFieldXpath['zoom'] ) {
            $zoom = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['zoom'], $file)->parse(); $tmp_files[] = $file;
          }
          else {
            $zoom = array_fill(0, $count_records, "");
          }
          if ( isset($CurrentFieldXpath['center_lat']) && "" != $CurrentFieldXpath['center_lat'] ) {
            $center_lat = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['center_lat'], $file)->parse(); $tmp_files[] = $file;
          }
          else {
            $center_lat = array_fill(0, $count_records, "");
          }
          if ( isset($CurrentFieldXpath['center_lng']) && "" != $CurrentFieldXpath['center_lng'] ) {
            $center_lng = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['center_lng'], $file)->parse(); $tmp_files[] = $file;
          }
          else {
            $center_lng = array_fill(0, $count_records, "");
          }
					
					switch ($CurrentFieldXpath['address_geocode']) 
					{
						case 'address_no_key':
							
							$api = array_fill(0, $count_records, "");
							$client_id = array_fill(0, $count_records, "");
							$signature = array_fill(0, $count_records, "");

							break;

						case 'address_google_developers':

							if ( "" != $CurrentFieldXpath['address_google_developers_api_key'] )
							{
								$api = 	XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['address_google_developers_api_key'], $file)->parse(); $tmp_files[] = $file;
							}
							else
							{
								$api = array_fill(0, $count_records, "");
							}
							

							break;

						case 'address_google_for_work':

							if ( "" != $CurrentFieldXpath['address_google_for_work_client_id'] )
							{
								$client_id = 	XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['address_google_for_work_client_id'], $file)->parse(); $tmp_files[] = $file;
							}
							else
							{
								$client_id = array_fill(0, $count_records, "");
							}

							if ( "" != $CurrentFieldXpath['address_google_for_work_digital_signature'] )
							{
								$signature = 	XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['address_google_for_work_digital_signature'], $file)->parse(); $tmp_files[] = $file;
							}
							else
							{
								$signature = array_fill(0, $count_records, "");
							}

							break;						

						default:
							# code...
							break;
					}
					$values = array(
						'address' => $addresses,
						'lat' => $lat,
						'lng' => $lng,
						'api_key' => $api,
						'client_id' => $client_id,
						'signature' => $signature,
						'address_geocode' => $CurrentFieldXpath['address_geocode'],
            'zoom' => $zoom,
            'center_lat' => $center_lat,
            'center_lng' => $center_lng
					);
				break;
			case 'paypal_item':
					if ( "" != $CurrentFieldXpath['item_name'] )
					{
						$item_names = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['item_name'], $file)->parse(); $tmp_files[] = $file;
					}
					else{
						$item_names = array_fill(0, $count_records, "");	
					}
					if ( "" != $CurrentFieldXpath['item_description'] ){
						$item_descriptions = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['item_description'], $file)->parse(); $tmp_files[] = $file;
					}
					else{
						$item_descriptions = array_fill(0, $count_records, "");	
					}
					if ( "" != $CurrentFieldXpath['price'] ){
						$prices = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath['price'], $file)->parse(); $tmp_files[] = $file;
					}
					else{
						$prices = array_fill(0, $count_records, "");	
					}
					$values = array(
						'item_name' => $item_names,
						'item_description' => $item_descriptions,
						'price' => $prices
					);
				break;
			// field types with possiblity for miltiple values										
			case 'radio':
			case 'true_false':
					
					if ( ! empty($currentIsMultipleField) and "yes" == $currentIsMultipleField){

						if ( ! is_array($currentMultipleValue) ) {
							$values = array_fill(0, $count_records, $currentMultipleValue);									
						}								
						else{
							$values = array();
							foreach ($currentMultipleValue as $single_value) {
								$values[] = array_fill(0, $count_records, $single_value);	
							}									
							$is_multiple = true;
						}
					}
					else{

						if ("" != $CurrentFieldXpath){
							$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;
						}
					}					

				break;			
			case 'checkbox':			
			case 'select':	

					if ( ! empty($currentIsMultipleField) and "yes" == $currentIsMultipleField){

						if ( ! is_array($currentMultipleValue) ) {
							$values = array_fill(0, $count_records, $currentMultipleValue);
						}								
						else{
							
							$values = array_fill(0, $count_records, $currentMultipleValue);

							$is_multiple = true;
						}
					}
					else{
						
						if ( "" != $CurrentFieldXpath ){
							
							if ( empty($field['multiple']) and ( ! in_array($field['type'], array('checkbox')) or ( ! empty($field['field_type']) and ! in_array($field['field_type'], array('checkbox', 'multi_select'))))) {

								$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;

							}
							else {
								
								$values = array();

								$values = XmlImportParser::factory($xml, $cxpath, $CurrentFieldXpath, $file)->parse(); $tmp_files[] = $file;

								foreach ($values as $key => $value) {
									$values[$key] = array_map('trim', explode(",", $value));
								}
								
								$is_multiple = true;
							}
						}
					}
				break;
			case 'taxonomy':

					if ( ! empty($currentIsMultipleField) and "yes" == $currentIsMultipleField){

						if ( ! is_array($currentMultipleValue) ) {
							$values = array_fill(0, $count_records, $currentMultipleValue);									
						}								
						else{
							$values = array();
							foreach ($currentMultipleValue as $single_value) {
								$values[] = array_fill(0, $count_records, $single_value);	
							}									
							$is_multiple = true;
						}

					}
					else{
						
						if ( "" != $CurrentFieldXpath ){																					

							$values = array();							

							$is_multiple = 'nesting';

							$tx_name = $field['taxonomy'];
							$taxonomies_hierarchy = json_decode($CurrentFieldXpath);
							foreach ($taxonomies_hierarchy as $k => $taxonomy){	if ("" == $taxonomy->xpath) continue;								
								$txes_raw =  XmlImportParser::factory($xml, $cxpath, str_replace('\'','"',$taxonomy->xpath), $file)->parse(); $tmp_files[] = $file;						
								$warned = array();
								foreach ($txes_raw as $i => $tx_raw) {
									if (empty($taxonomies_hierarchy[$k]->txn_names[$i])) $taxonomies_hierarchy[$k]->txn_names[$i] = array();
									if (empty($values[$tx_name][$i])) $values[$tx_name][$i] = array();
									$count_cats = count($values[$tx_name][$i]);
									
									$delimeted_taxonomies = $inside_repeater ? array($tx_raw) : explode(',', $tx_raw);

									if ('' != $tx_raw) foreach ($delimeted_taxonomies as $j => $cc) if ('' != $cc) {
																																		
										$cat = get_term_by('name', trim($cc), $tx_name) or $cat = get_term_by('slug', trim($cc), $tx_name) or ctype_digit($cc) and $cat = get_term_by('id', $cc, $tx_name);
										if (!empty($taxonomy->parent_id)) {																			
											foreach ($taxonomies_hierarchy as $key => $value){
												if ($value->item_id == $taxonomy->parent_id and !empty($value->txn_names[$i])){													
													foreach ($value->txn_names[$i] as $parent) {																																																																												
														$values[$tx_name][$i][] = array(
															'name' => trim($cc),
															'parent' => $parent,
															'assign' => 1 //$taxonomy->assign
														);														
													}											
												}
											}
										}
										else {												
											$values[$tx_name][$i][] = array(
												'name' => trim($cc),
												'parent' => false,
												'assign' => 1 //$taxonomy->assign
											);											
										}								
									}
									if ($count_cats < count($values[$tx_name][$i])) $taxonomies_hierarchy[$k]->txn_names[$i][] = $values[$tx_name][$i][count($values[$tx_name][$i]) - 1];
								}
							}								
						}
					}
				break;
      case 'clone':

          global $acf;

          if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0 and ! empty($CurrentFieldXpath)){
              $values = array();
              foreach ($CurrentFieldXpath as $sub_field_key => $sub_field_xpath)
              {
                  $args=array(
                      'name' => $sub_field_key,
                      'post_type' => 'acf-field',
                      'post_status' => 'publish',
                      'posts_per_page' => 1
                  );
                  $my_posts = get_posts( $args );
                  if( $my_posts ) {
                      $sub_field = $my_posts[0];
                      $sub_fieldData = (!empty($sub_field->post_content)) ? unserialize($sub_field->post_content) : array();
                      $sub_fieldData['ID'] = $sub_field->ID;
                      $sub_fieldData['label'] = $sub_field->post_title;
                      $sub_fieldData['key'] = $sub_field->post_name;

                      $values[$sub_field_key] = array(
                          'name' => empty($field['prefix_name']) ? $sub_field->post_excerpt : $field['name'] . '_' . $sub_field->post_excerpt,
                          'values' => $this->parse_field(
                              $sub_fieldData,
                              $sub_field_xpath
                          )
                      );
                  }
              }
          }

          break;
			case 'repeater':

					if ( ! empty($CurrentFieldXpath['rows']) and count($CurrentFieldXpath['rows']) and ! empty($CurrentFieldXpath['rows'][1])){

						global $acf;	
														
						$values = array();

						if ( "yes" == $CurrentFieldXpath['is_variable'] and "" != $CurrentFieldXpath['foreach'] ){		

							$is_variable = 'xml';							

							for ($k = 0; $k < $count_records; $k++) { 
																								
								$base_xpath = '[' . ( $k + 1 ) . ']/'.  ltrim(trim($CurrentFieldXpath['foreach'],'{}!'), '/');																							
								$repeater_rows = XmlImportParser::factory($xml, $cxpath . $base_xpath, "{.}", $file)->parse(); $tmp_files[] = $file;								
								
								foreach ($CurrentFieldXpath['rows'] as $key => $row_fields) 
								{ 
									if ($key == 'ROWNUMBER') continue;																		

									$row_array = array();

									if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

										$sub_fields = array();

										if (is_numeric($field['ID']))
										{	
											$sub_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $field['ID'], 'post_status' => 'publish'));
											
										}
										else
										{
											$fields = acf_local()->fields;
					
											if (!empty($fields)){
												foreach ($fields as $sub_field) {
													if ($sub_field['parent'] == $field['key']){								
														$sub_fieldData = $sub_field;																	
														$sub_fieldData['ID'] = $sub_fieldData['id']    = uniqid();																																
														$sub_fields[] = $sub_fieldData;
													}
												}
											}
											
										}

										if (!empty($sub_fields)):
											foreach ($sub_fields as $n => $sub_field)
											{							
												if (is_object($sub_field))
												{
													$sub_fieldData = (!empty($sub_field->post_content)) ? unserialize($sub_field->post_content) : array();			
													$sub_fieldData['ID'] = $sub_field->ID;
													$sub_fieldData['label'] = $sub_field->post_title;
													$sub_fieldData['key'] = $sub_field->post_name;
													$sub_fieldData['name'] = $sub_field->post_excerpt;									
												}																						
												else
												{
													$sub_fieldData = $sub_field;													
												}		
												
												$row_array[$sub_fieldData['key']] = $this->parse_field(
														$sub_fieldData, 
														$row_fields[$sub_fieldData['key']], 
														$fieldPath . "[" . $field['key'] . "][rows][" . $key . "]",  
														((!is_array($row_fields[$sub_fieldData['key']]) && strpos($row_fields[$sub_fieldData['key']], "!") === 0) ? "" : ( (strpos($CurrentFieldXpath['foreach'], "!") === 0) ? $base_xpath : $xpath_suffix . $base_xpath)),
														count($repeater_rows),
                                                        true
													); 

											}
										endif;	
																																									
									}
									else{

										foreach ($field['sub_fields'] as $n => $sub_field)
										{							
									//		if ( in_array($sub_field['type'], array('repeater', 'flexible_content')) ) $base_xpath = "";										
											$row_array[$sub_field['key']] = $this->parse_field($sub_field, $row_fields[$sub_field['key']], $fieldPath . "[" . $field['key'] . "][rows][" . $key . "]",  ((strpos($row_fields[$sub_field['key']], "!") === 0) ? "" : ( (strpos($CurrentFieldXpath['foreach'], "!") === 0) ? $base_xpath : $xpath_suffix . $base_xpath)), count($repeater_rows), true);

										}

									}																															
									
									$values[] = array(
										'countRows' => count($repeater_rows),
										'vals' => $row_array
									);

									// stop parsing after one repeater row
									break;
								}
							}																					
						}
						else {

							/*if ( "csv" == $CurrentFieldXpath['is_variable'] ){

								foreach ($CurrentFieldXpath['rows'] as $key => $row_fields) 
								{ 
									if ($key == 'ROWNUMBER') continue;	

									if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

										$sub_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $field['ID'], 'post_status' => 'publish'));

										if (!empty($sub_fields)):
											foreach ($sub_fields as $n => $sub_field)
											{			

												$repeater_rows = XmlImportParser::factory($xml, $cxpath, $row_fields[$sub_fieldData['key']], $file)->parse(); $tmp_files[] = $file;		

											}
										endif;
									}

								}

							}
							else{*/
								
								if ( "csv" == $CurrentFieldXpath['is_variable'] ) $is_variable = $CurrentFieldXpath['separator'];

								if ($CurrentFieldXpath['is_ignore_empties']) $is_ignore_empties = true;

								foreach ($CurrentFieldXpath['rows'] as $key => $row_fields) 
								{ 
									if ($key == 'ROWNUMBER') continue;									

									$row_array = array();

									if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

										$sub_fields = array();

										if (is_numeric($field['ID']))
										{
											$sub_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $field['ID'], 'post_status' => 'publish'));
										}
										else
										{
											$fields = acf_local()->fields;
					
											if (!empty($fields)){
												foreach ($fields as $sub_field) {
													if ($sub_field['parent'] == $field['key']){								
														$sub_fieldData = $sub_field;																	
														$sub_fieldData['ID'] = $sub_fieldData['id']    = uniqid();																																
														$sub_fields[] = $sub_fieldData;
													}
												}
											}
										}																				

										if (!empty($sub_fields)):
											foreach ($sub_fields as $n => $sub_field)
											{			
												if (is_object($sub_field))
												{
													$sub_fieldData = (!empty($sub_field->post_content)) ? unserialize($sub_field->post_content) : array();			
													$sub_fieldData['ID'] = $sub_field->ID;
													$sub_fieldData['label'] = $sub_field->post_title;
													$sub_fieldData['key'] = $sub_field->post_name;				
													$sub_fieldData['name'] = $sub_field->post_excerpt;					
												}
												else
												{
													$sub_fieldData = $sub_field;
												}
												
												$row_array[$sub_fieldData['key']] = $this->parse_field($sub_fieldData, $row_fields[$sub_fieldData['key']], $fieldPath . "[" . $field['key'] . "][rows][" . $key . "]", "", 0, true);
                        if ( empty($is_variable) and is_array($row_fields[$sub_fieldData['key']]) and ! empty($row_fields[$sub_fieldData['key']]['separator']) ){
                          $is_variable = $row_fields[$sub_fieldData['key']]['separator'];
                        }
											}
										endif;							
									}
									else{

										foreach ($field['sub_fields'] as $n => $sub_field)
										{							
											$row_array[$sub_field['key']] = $this->parse_field($sub_field, $row_fields[$sub_field['key']], $fieldPath . "[" . $field['key'] . "][rows][" . $key . "]", "", 0, true);
                      if ( empty($is_variable) and is_array($row_fields[$sub_field['key']]) and ! empty($row_fields[$sub_field['key']]['separator']) ){
                        $is_variable = $row_fields[$sub_field['key']]['separator'];
                      }
										}
										
									}							

									$values[] = $row_array;									
								}								
							//}
						}
					}

				break;
			case 'flexible_content':

					if ( ! empty($CurrentFieldXpath['layouts']) and count($CurrentFieldXpath['layouts']) > 1 ){
														
						$values = array();																	

						foreach ($CurrentFieldXpath['layouts'] as $key => $layout_fields) 
						{ 
							if ($key == 'ROWNUMBER') continue;									

							$row_array = array();

							$current_field = false;

							foreach ($field['layouts'] as $layout) {
								if ($layout['name'] == $layout_fields['acf_fc_layout']){
									$current_field = $layout;
									break;
								}
							}

							$row_array['acf_fc_layout'] = $layout_fields['acf_fc_layout'];

							if ( ! empty($current_field['sub_fields']) and is_array($current_field['sub_fields'])) 
							{
								foreach ($current_field['sub_fields'] as $n => $sub_field)
								{							
									
									$row_array['fields'][$sub_field['key']] = $this->parse_field($sub_field, $layout_fields[$sub_field['key']], $fieldPath . "[" . $field['key'] . "][layouts][". $key ."]"); 
																	
								}							
							}
							$values[] = $row_array;
						}
					}

				break;
			default:
				# code...
				break;
		}

		foreach ( (array) $tmp_files as $file) { // remove all temporary files created
			@unlink($file);
		}	

		return array(
			'type'   => $field['type'],
			'name'   => $field['name'],
			'multiple' => isset($field['multiple']) ? $field['multiple'] : false,
			'values' => $values,
			'is_multiple' => $is_multiple,
			'is_variable' => $is_variable,
			'is_ignore_empties' => $is_ignore_empties,
			'xpath' => $CurrentFieldXpath
		);

	}

	public function import($importData = array()){ //$pid, $i, $import, $articleData, $xml, $is_cron = false, $xpath_prefix = ""

		extract($importData);

		$logger and call_user_func($logger, __('<strong>ACF ADD-ON:</strong>', 'wp_all_import_acf_add_on'));

		$this->articleData = $articleData;

		$this->import_type = $import->options['custom_type'];

		$cxpath = $xpath_prefix . $import->xpath;						

		if (!empty($this->data)){
			foreach ((array) $this->data as $key => $field) {

				$this->import_field($pid, $i, $key, $field);
							
			}
		}

	}

	public $parentRepeaters = array();

  public $repeater = array();

	public function import_field($pid, $i, $key, $field, $fieldContainerName = "", $parentRepeater = array()){

		$this->parsing_data['logger'] and call_user_func($this->parsing_data['logger'], sprintf(__('- Importing field `%s`', 'wp_all_import_acf_add_on'), $fieldContainerName . $field['name']));

		// If update is not allowed
		if ( ! empty($this->articleData['ID']) and ! pmai_is_acf_update_allowed( $fieldContainerName . $field['name'], $this->parsing_data['import']->options, $this->parsing_data['import']->id ) and empty($field['xpath']['only_append_new'])){ 
			$this->parsing_data['logger'] and call_user_func($this->parsing_data['logger'], sprintf(__('- Field `%s` is skipped attempted to import options', 'wp_all_import_acf_add_on'), $fieldContainerName . $field['name']));
			return false;
		}

		$this->update_post_meta($pid, "_" . $fieldContainerName . $field['name'], $key);

		switch ($field['type']) {
			case 'text':
			case 'textarea':
			case 'number':
			case 'email':
			case 'password':
			case 'wysiwyg':																							
			case 'color_picker':
			case 'message':																										
			case 'date_picker':
			case 'limiter':
			case 'wp_wysiwyg':
			case 'date_time_picker':
			case 'oembed':
			case 'url':
			case 'time_picker':
					$this->update_post_meta($pid, $fieldContainerName . $field['name'], $field['values'][$i]);			
					//$this->parsing_data['logger'] and call_user_func($this->parsing_data['logger'], sprintf(__('- Field `%s` updated with value `%s`', 'wp_all_import_acf_add_on'), $fieldContainerName . $field['name'], $field['values'][$i]));
				break;
      case 'google_map_extended':
			case 'google_map':			

					// build serach query
					$search = '';
					if (empty($field['values']['address'][$i]) and !empty($field['values']['lat'][$i]) and !empty($field['values']['lng'][$i]))
					{
						$search = 'latlng=' . rawurlencode( $field['values']['lat'][$i] . ',' . $field['values']['lng'][$i] );
					}
					if (!empty($field['values']['address'][$i]) and empty($field['values']['lat'][$i]) and empty($field['values']['lng'][$i]))
					{
						$search = 'address=' . rawurlencode( $field['values']['address'][$i] );
					}
					// build api key
					if ( $field['values']['address_geocode'][$i] == 'address_google_developers' && !empty( $field['values']['address_google_developers_api_key'][$i] ) ) {
        
              $api_key = '&key=' . $field['values']['address_google_developers_api_key'][$i];

          } elseif ( $field['values']['address_geocode'][$i] == 'address_google_for_work' && !empty( $field['values']['address_google_for_work_client_id'][$i] ) && !empty( $field['values']['address_google_for_work_signature'][$i] ) ) {

              $api_key = '&client=' . $field['values']['address_google_for_work_client_id'][$i] . '&signature=' . $field['values']['address_google_for_work_signature'][$i];

          }

				    if (!empty($search))
				    {
				    	// build $request_url for api call
				        $request_url = 'https://maps.googleapis.com/maps/api/geocode/json?' . $search . $api_key;
				        $curl        = curl_init();

				        curl_setopt( $curl, CURLOPT_URL, $request_url );
				        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );				        

				        $json = curl_exec( $curl );
				        curl_close( $curl );
				        
				        // parse api response
				        if ( !empty( $json ) ) {

                  $details = json_decode( $json, true );

                  $address_data = array();

                  foreach ( $details['results'][0]['address_components'] as $type ) {

                    // parse Google Maps output into an array we can use
                    $address_data[ $type['types'][0] ] = $type['long_name'];

                  }

                  $lat  = $details['results'][0]['geometry']['location']['lat'];

                  $lng = $details['results'][0]['geometry']['location']['lng'];

				        	$address = $address_data['street_number'] . ' ' . $address_data['route'];

				        	if (empty($field['values']['address'][$i])) $field['values']['address'][$i] = $address;
				        	if (empty($field['values']['lat'][$i])) $field['values']['lat'][$i] = $lat;
				        	if (empty($field['values']['lng'][$i])) $field['values']['lng'][$i] = $lng;
				        }
				    }

				    if ( $field['type'] == 'google_map_extended' ){
              $this->update_post_meta($pid, $fieldContainerName . $field['name'], array(
                'address' => $field['values']['address'][$i],
                'lat' => $field['values']['lat'][$i],
                'lng' => $field['values']['lng'][$i],
                'zoom' => $field['values']['zoom'][$i],
                'center_lat' => $field['values']['center_lat'][$i],
                'center_lng' => $field['values']['center_lng'][$i]
              ));
            }
            else{
              $this->update_post_meta($pid, $fieldContainerName . $field['name'], array(
                'address' => $field['values']['address'][$i],
                'lat' => $field['values']['lat'][$i],
                'lng' => $field['values']['lng'][$i]
              ));
            }

				break;
			case 'paypal_item':										
					$this->update_post_meta($pid, $fieldContainerName . $field['name'], array(
						'item_name' => $field['values']['item_name'][$i],
						'item_description' => $field['values']['item_description'][$i],
						'price' => $field['values']['price'][$i]
					));													
				break;
			case 'location-field':
					$this->update_post_meta($pid, $fieldContainerName . $field['name'], $field['values']['address'][$i] . "|" . $field['values']['lat'][$i] . "," . $field['values']['lng'][$i]);	
				break;
			case 'gallery':
					//$imgs = explode(",", $field['values'][$i]);	

					$is_append_new = ( ! empty($field['xpath']['only_append_new'])) ? 1 : 0;								

					$gallery_ids = $is_append_new ? $this->get_post_meta( $pid, $fieldContainerName . $field['name']) : array();

					if ( ! empty($field['values'][$i]) )
					{
						$search_in_gallery = ( ! empty($field['xpath']['search_in_media'])) ? 1 : 0;
						foreach ($field['values'][$i] as $url) {							
							if ("" != $url and $attid = $this->import_image(trim($url), $pid, $this->parsing_data['logger'], $search_in_gallery) and ! in_array($attid, $gallery_ids)) $gallery_ids[] = $attid;
						}
					}				

					if ($is_append_new)
					{
						update_post_meta($pid, $fieldContainerName . $field['name'], $gallery_ids);				
					}
					else
					{
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], $gallery_ids);				
					}					

				break;		
			case 'user':				
					if (strpos($field['values'][$i], ",")){
						$users = array_map('trim', explode(",", $field['values'][$i]));
						if ( ! empty($users)):
							foreach ($users as $key => $author) {
								$user = get_user_by('login', $author) or $user = get_user_by('slug', $author) or $user = get_user_by('email', $author) or ctype_digit($author) and $user = get_user_by('id', $author);
								$users[$key] = (!empty($user)) ? $user->ID : "";
							}
						endif;
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], $users);
					}
					else{ 
						$author = $field['values'][$i];
						$user = get_user_by('login', $author) or $user = get_user_by('slug', $author) or $user = get_user_by('email', $author) or ctype_digit($author) and $user = get_user_by('id', $author);
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], (!empty($user)) ? $user->ID : "");
					}
				break;
			case 'image':
					$search_in_gallery = ( ! empty($field['xpath']['search_in_media'])) ? 1 : 0;
					if ("" != $field['values'][$i] and $attid = $this->import_image($field['values'][$i], $pid, $this->parsing_data['logger'], $search_in_gallery)) 
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], $attid);																
				break;
			case 'file':
					$search_in_gallery = ( ! empty($field['xpath']['search_in_media'])) ? 1 : 0;
					if ("" != $field['values'][$i] and $attid = $this->import_file($field['values'][$i], $pid, $this->parsing_data['logger'], $this->parsing_data['import']->options['is_fast_mode'], $search_in_gallery)) 
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], $attid);					
				break;			
			case 'checkbox':				
			case 'select':
			case 'radio':
			case 'true_false':

					if ($field['is_multiple'])
					{						
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], (!empty($field['values'][$i]) and is_array($field['values'][$i])) ? $field['values'][$i] : array());
					}
					else
					{						
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], $field['values'][$i]);
					}									

				break;
			case 'taxonomy':
					
					if ( $field['is_multiple'] !== true and $field['is_multiple'] == 'nesting' )
					{
						if (!empty($field['values'])){	

							foreach ($field['values'] as $tx_name => $txes) {														
								
								$assign_taxes = array();		
								$assign_terms = array();									

								// create term if not exists
								if (!empty($txes[$i])):
									foreach ($txes[$i] as $key => $single_tax) {
										if (is_array($single_tax)){																														

											$parent_id = (!empty($single_tax['parent'])) ? pmxi_recursion_taxes($single_tax['parent'], $tx_name, $txes[$i], $key) : '';
											
											$term = $parent_id ? is_exists_term($single_tax['name'], $tx_name, (int)$parent_id) : is_exists_term($single_tax['name'], $tx_name);		
											
											if ( empty($term) and !is_wp_error($term) ){
												$term_attr = array('parent'=> (!empty($parent_id)) ? $parent_id : 0);
												$term = wp_insert_term(
													$single_tax['name'], // the term 
												  	$tx_name, // the taxonomy
												  	$term_attr
												);
											}
											
											if ( is_wp_error($term) ){									
												
											}
											elseif (!empty($term)) {
												$cat_id = $term['term_id'];
												if ($cat_id and $single_tax['assign']) 
												{					
													if ( !in_array($cat_id, $assign_taxes)) $assign_taxes[] = $cat_id;		
													$term = get_term_by('id', $cat_id, $tx_name);																				
													if ( ! is_wp_error($term) and !in_array($term->term_taxonomy_id, $assign_terms)) $assign_terms[] = $term->term_taxonomy_id;		
												}									
											}									
										}
									}				
								endif;			

								if ( ! empty($assign_taxes) ) $this->update_post_meta($pid, $fieldContainerName . $field['name'], $assign_taxes);	

								if ($this->import_type != 'import_users') $this->associate_terms($pid, ( empty($assign_terms) ? false : $assign_terms ), $tx_name, $this->parsing_data['logger']);
									
							}							
						}
					} 
					elseif ($field['is_multiple'])
					{
						$mult_values = array();
						foreach ($field['values'] as $number => $values) {
							$mult_values[] = trim($values[$i]);	
						}						
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], $mult_values);
					}
					else
					{						
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], $field['values'][$i]);
					}
				break;
			case 'page_link':
				if ( "" != $field['values'][$i] ){
						$post_ids = array();						
						$entries = explode(",", $field['values'][$i]);
						if (!empty($entries) and is_array($entries)){
							foreach ($entries as $ev) {															
								$args = array(
								  'name' => $ev,
								  'post_type' => 'any',								  
								  'post_status' => 'any',
								  'numberposts' => 1
								);
								//$the_query = new WP_Query( $args );
								$my_posts = get_posts($args);
								if ( $my_posts ) {
								  	$post_ids[] = get_permalink($my_posts[0]->ID);
								}
								elseif (ctype_digit($ev)){
									$my_post = get_post($ev);
									if ($my_post)
										$post_ids[] = get_permalink($my_post->ID);
								}			
								//wp_reset_postdata();					
							}
						}
						if (!empty($post_ids)){
							if ($field['multiple']){
								$this->update_post_meta($pid, $fieldContainerName . $field['name'], $post_ids);
							}
							else{
								$this->update_post_meta($pid, $fieldContainerName . $field['name'], array_shift($post_ids));
							}
						}
						else{
							$this->update_post_meta($pid, $fieldContainerName . $field['name'], '');	
						}
					}
					else{
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], '');
					}
				break;
			case 'post_object':					
					if ( "" != $field['values'][$i] ){
						$post_ids = array();						
						$entries = explode(",", $field['values'][$i]);
						if (!empty($entries) and is_array($entries)){
							foreach ($entries as $ev) {
								$args = array(
								  'name' => $ev,
								  'post_type' => 'any',	
								  'post_status' => 'any',							  
								  'numberposts' => 1
								);
								$my_posts = get_posts($args);
								if ( $my_posts ) {
								  	$post_ids[] = $my_posts[0]->ID;
								}
								elseif (ctype_digit($ev)){
									$post_ids[] = $ev;
								}								
							}
						}
						
						if (!empty($post_ids)){
							if ($field['multiple']){
								$this->update_post_meta($pid, $fieldContainerName . $field['name'], $post_ids);
							}
							else{
								$this->update_post_meta($pid, $fieldContainerName . $field['name'], array_shift($post_ids));
							}
						}
						else{
							$this->update_post_meta($pid, $fieldContainerName . $field['name'], '');	
						}
					}
					else{
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], '');
					}					

				break;
			case 'relationship':

					if ( !empty($field['values'][$i]) ){
						$post_ids = array();
            foreach ($field['values'][$i] as $ev) {
                if (ctype_digit($ev)){
                    $post_ids[] = (string) $ev;
                }
                else{
                    $args = array(
                      'name' => $ev,
                      'post_type' => 'any',
                      'post_status' => 'any',
                      'numberposts' => 1
                    );
                    $my_posts = get_posts($args);

                    if ( $my_posts ) {
                        $post_ids[] = (string) $my_posts[0]->ID;
                    }

                    wp_reset_postdata();
                }
            }

						if (!empty($post_ids)){
							
							$this->update_post_meta($pid, $fieldContainerName . $field['name'], $post_ids);
							
						}
						else{
							$this->update_post_meta($pid, $fieldContainerName . $field['name'], '');	
						}
					}
					else{
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], '');
					}	
				break;
			case 'gravity_forms_field':
			case 'acf_cf7':
					if ($field['is_multiple'])
					{
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], explode(",", $field['values'][$i]));
					}
					else{
						$this->update_post_meta($pid, $fieldContainerName . $field['name'], $field['values'][$i]);
					}
				break;
      case 'clone':
          if ( ! empty($field['values']) ):
              foreach ($field['values'] as $sub_field_key => $sub_field_data){
                  $sub_field = $sub_field_data['values'];
                  $sub_field['name'] = $sub_field_data['name'];
                  $this->import_field($pid, $i, $sub_field_key, $sub_field, $fieldContainerName);
              }
              $this->update_post_meta($pid, $fieldContainerName . $field['name'], '');
          endif;
        break;
			case 'repeater':

					if ( ! empty($field['values']) ):						

						if ( $field['is_variable'] == 'xml' ){ // is variable repeater mode enabled
							
							for ($k = 0; $k < $field['values'][$i]['countRows']; $k++) { 
								foreach ($field['values'][$i]['vals'] as $sub_field_key => $sub_field)
								{
									$this->import_field($pid, $k, $sub_field_key, $sub_field, $fieldContainerName . $field['name'] . "_" . $k . "_");		
								}	
							}							
							$this->update_post_meta($pid, $fieldContainerName . $field['name'], $field['values'][$i]['countRows']);

						}
						else{ // is fixed repeater mode enabled

							$countRows = 0;

							foreach ($field['values'] as $row_number => $row)
							{								
								if ( ! empty($row)){
									$countRows++;

									$is_row_import_allowed = true;

									if ( $field['is_ignore_empties'] and $field['is_variable'] === false or $field['is_variable']){
										$is_row_import_allowed = false;										
										foreach ($row as $sub_field_key_check => $sub_field_check){											
											if ( ! empty($sub_field_check['xpath']) and ! empty( $sub_field_check['values'][$i] ) )
											{										
												$is_row_import_allowed = true;
												break;
											}
										}											
									}									

									if ($is_row_import_allowed) {
										if ( $field['is_variable'] !== false and $field['is_variable'] != '' ){
											$countCSVrows = 0;
											foreach ($row as $sub_field_key => $sub_field){												
                        if ($sub_field['type'] != 'repeater'){
                          if ($sub_field['type'] == 'taxonomy'){
                            if (!empty($sub_field['values'])){
                              foreach ($sub_field['values'] as $tx_name => $tx_terms){
                                $is_array = is_array($tx_terms[$i]);
                                if ($is_array)
                                {
                                  foreach ($tx_terms[$i] as $tx_term) {
                                    if (!empty($parentRepeater)){
                                        $parent_tx_rows = explode($parentRepeater['delimiter'], $tx_term['name']);
                                        $tx_rows = explode($field['is_variable'], $parent_tx_rows[$parentRepeater['row']]);
                                    }
                                    else{
                                        $tx_rows = explode($field['is_variable'], $tx_term['name']);
                                    }
                                    if (count($tx_rows) > $countCSVrows)
                                    {
                                      $countCSVrows = count($tx_rows);
                                    }
                                  }
                                }
                              }
                            }
                          }
                          else{
                            if (!empty($parentRepeater)){
                                  $parent_entries = explode($parentRepeater['delimiter'], $sub_field['values'][$i]);
                                  $entries = explode($field['is_variable'], $parent_entries[$parentRepeater['row']]);
                            }
                            else{
                              $entries = explode($field['is_variable'], $sub_field['values'][$i]);
                            }

                            if (count($entries) > $countCSVrows){
                              $countCSVrows = count($entries);
                            }
                          }
                        }
											}											

											for ( $k=0; $k < $countCSVrows; $k++) {
                        foreach ($row as $sub_field_key => $sub_field){
                            if ($sub_field['type'] !== 'repeater'){
                                if ($sub_field['type'] == 'taxonomy'){
                                    if (!empty($sub_field['values'])){
                                        foreach ($sub_field['values'] as $tx_name => $tx_terms){
                                            $is_array = is_array($tx_terms[$i]);
                                            if ($is_array)
                                            {
                                              $entries = array();
                                              foreach ($tx_terms[$i] as $tx_term) {

                                                    $current = $tx_term['name'];

                                                    if (!empty($parentRepeater)){
                                                        $tx_rows = explode($parentRepeater['delimiter'], $current);
                                                        $current = $tx_rows[$parentRepeater['row']];
                                                    }

                                                    $tx_rows = explode($field['is_variable'], $current);
                                                    $current = empty($tx_rows[$k]) ? '' : $tx_rows[$k];

                                            if (!empty($current)){
                                              $entries[] = array(
                                                'name' => $current,
                                                'parent' => $tx_term['parent'],
                                                'assign' => 1
                                              );
                                            }
                                          }
                                                $sub_field['values'][$tx_name][$i] = $entries;
                                            }
                                        }
                                    }
                                }
                                else{
                                    $is_array = is_array($sub_field['values'][$i]);
                                    if ($is_array)
                                    {
                                        $sub_field['values'][$i] = array(implode(",", $sub_field['values'][$i]));
                                    }
                                    else{
                                      if (!empty($parentRepeater)){
                                      $parent_entries = explode($parentRepeater['delimiter'], $sub_field['values'][$i]);
                                      $sub_field['values'][$i] = isset($parent_entries[$parentRepeater['row']]) ? $parent_entries[$parentRepeater['row']] : '';
                                  }
                                    }
                                    $sub_field['values'][$i] = $is_array ? array_shift($sub_field['values'][$i]) : $sub_field['values'][$i];
                                    $entries = explode($field['is_variable'], $sub_field['values'][$i]);
                                    $sub_field['values'][$i] = (!isset($entries[$k])) ? '' : ( $is_array ? explode(",", $entries[$k]) : $entries[$k]);
                                }
                            }

                            $this->import_field($pid, $i, $sub_field_key, $sub_field, $fieldContainerName . $field['name'] . "_" . $k . "_", array(
                                'delimiter' => $field['is_variable'],
                                'row' => $k
                            ));
                        }
											}

											$countRows = $countCSVrows;
											
										}
										else{

											foreach ($row as $sub_field_key => $sub_field){																													
																					
												$this->import_field($pid, $i, $sub_field_key, $sub_field, $fieldContainerName . $field['name'] . "_" . ($countRows - 1) . "_");																				
											}
										}	

									}
									else
										$countRows--;

								}
							}
							$this->update_post_meta($pid, $fieldContainerName . $field['name'], $countRows);
						}

					endif;

				break;
			case 'flexible_content':

				if ( ! empty($field['values']) ):						

					$layouts = array();				

					foreach ($field['values'] as $layout_number => $layout)
					{
						if ( ! empty($layout['fields'])){
							$layouts[] = $layout['acf_fc_layout'];
							foreach ($layout['fields'] as $sub_field_key => $sub_field) 			
								$this->import_field($pid, $i, $sub_field_key, $sub_field, $fieldContainerName . $field['name'] . "_" . $layout_number . "_");														
						}
					}
					$this->update_post_meta($pid, $fieldContainerName . $field['name'], $layouts);

				endif;

				break;
			default:
				# code...
				break;
		}	

		$v = $this->get_post_meta($pid, $fieldContainerName . $field['name']);
					
		$this->parsing_data['logger'] and call_user_func($this->parsing_data['logger'], sprintf(__('- Field `%s` updated with value `%s`', 'wp_all_import_acf_add_on'), $fieldContainerName . $field['name'], esc_attr(maybe_serialize($v))));

	}

	public function update_post_meta($pid, $name, $value){

		$cf_value = apply_filters('pmxi_acf_custom_field', $value, $pid, $name);

        switch ($this->import_type){
            case 'import_users':
                update_user_meta($pid, $name, $cf_value);
                break;
            case 'taxonomies':
                if ( strpos($cf_value, 'field_') === 0 && strpos($name, '_') === 0){
                    update_option( '_' . $this->parsing_data['import']->options['taxonomy_type'] . '_' . $pid . $name, $cf_value);
                }
                else{
                    update_option( $this->parsing_data['import']->options['taxonomy_type'] . '_' . $pid . '_' . $name, $cf_value);
                }
                update_term_meta($pid, $name, $value);
                break;
            default:
                update_post_meta($pid, $name, $cf_value);
                break;
        }

	}

  public function get_post_meta($pid, $name){
      $v = false;
      switch ($this->import_type){
          case 'import_users':
              $v = get_user_meta($pid, $name, true);
              break;
          case 'taxonomies':
              $v = get_option($this->parsing_data['import']->options['taxonomy_type'] . '_' . $pid . '_' . $name);
              break;
          default:
              $v = get_post_meta($pid, $name, true);
              break;
      }
      return $v;
  }

	public function import_image( $img_url, $pid, $logger, $search_in_gallery = false ){
		
		// search image attachment by ID
		if ($search_in_gallery and is_numeric($img_url))
		{			
			if (wp_get_attachment_url( $img_url ))
			{
				return $img_url;
			}
		}

		$uploads = wp_upload_dir();

		// you must first include the image.php file
		// for the function wp_generate_attachment_metadata() to work
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$url = trim($img_url);
		$bn  = wp_all_import_sanitize_filename(urldecode(basename($url)));			
		
		$img_ext = pmxi_getExtensionFromStr($url);									
		$default_extension = pmxi_getExtension($bn);																									

		if ($img_ext == "") 										
			$img_ext = pmxi_get_remote_image_ext($url);																				

		// generate local file name
		$image_name = apply_filters("wp_all_import_image_filename", urldecode(sanitize_file_name((($img_ext) ? str_replace("." . $default_extension, "", $bn) : $bn))) . (("" != $img_ext) ? '.' . $img_ext : ''));

		// if wizard store image data to custom field									
		$create_image = false;
		$download_image = true;

		$image_filename = $image_name;
		$image_filepath = $uploads['path'] . '/' . $image_filename;			
		
		global $wpdb;

		if ($search_in_gallery){

			$attachment = wp_all_import_get_image_from_gallery($image_name, $uploads['path']);

      if (empty($attachment))
      {
          $logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: Image %s not found in media gallery.', 'wp_all_import_acf_add_on'), trim($image_name)));
      }
      else
      {
          $logger and call_user_func($logger, sprintf(__('- Using existing image `%s`...', 'wp_all_import_acf_add_on'), trim($image_name)));
          return $attachment->ID;
      }
		}						

		if ($download_image){

			$image_filename = wp_unique_filename($uploads['path'], $image_name);
			$image_filepath = $uploads['path'] . '/' . $image_filename;			
			
			$request = get_file_curl($url, $image_filepath);

			if ( (is_wp_error($request) or $request === false) and ! @file_put_contents($image_filepath, @file_get_contents($url))) {
				@unlink($image_filepath); // delete file since failed upload may result in empty file created
			} elseif( ($image_info = @getimagesize($image_filepath)) and in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
				$create_image = true;											
			}												
			
			if ( ! $create_image ){

				$url = str_replace(" ", "%20", trim(pmxi_convert_encoding($img_url)));
				
				$request = get_file_curl($url, $image_filepath);

				if ( (is_wp_error($request) or $request === false) and ! @file_put_contents($image_filepath, @file_get_contents($url))) {
					$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s cannot be saved locally as %s', 'wp_all_import_acf_add_on'), $url, $image_filepath));
					@unlink($image_filepath); // delete file since failed upload may result in empty file created										
				} elseif( ! ($image_info = @getimagesize($image_filepath)) or ! in_array($image_info[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
					$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as featured one', 'wp_all_import_acf_add_on'), $url));
					@unlink($image_filepath);
				} else {
					$create_image = true;											
				}
			}
		}				

		if ($create_image){			

			$attachment = array(
				'post_mime_type' => image_type_to_mime_type($image_info[2]),
				'guid' => $uploads['url'] . '/' . $image_filename,
				'post_title' => $image_filename,
				'post_content' => '',
				'post_author' => $this->articleData['post_author'],
			);
			if (($image_meta = wp_read_image_metadata($image_filepath))) {
				if (trim($image_meta['title']) && ! is_numeric(sanitize_title($image_meta['title'])))
					$attachment['post_title'] = $image_meta['title'];
				if (trim($image_meta['caption']))
					$attachment['post_content'] = $image_meta['caption'];
			}

			$attid = wp_insert_attachment($attachment, $image_filepath, $pid);										

			if (is_wp_error($attid)) {
				$logger and call_user_func($logger, __('- <b>WARNING</b>', 'wp_all_import_acf_add_on') . ': ' . $attid->get_error_message());
			} else {
				
				wp_update_attachment_metadata($attid, wp_generate_attachment_metadata($attid, $image_filepath));																															

				do_action( 'pmxi_gallery_image', $pid, $attid, $image_filepath); 

				return $attid;
			}
		}

		return false;

	}

	public function import_file($atch_url, $pid, $logger, $fast = false, $search_in_gallery = false){
		
		// search file attachment by ID
		if ($search_in_gallery and is_numeric($atch_url))
		{			
			if (wp_get_attachment_url( $atch_url ))
			{
				return $atch_url;
			}
		}

		global $wpdb;
		
		$uploads = wp_upload_dir();
		$file_name = sanitize_file_name(basename(parse_url(trim($atch_url), PHP_URL_PATH)));		
		$wpai_uploads = PMXI_Plugin::FILES_DIRECTORY . DIRECTORY_SEPARATOR;
		
		if ( ! preg_match('%^https?://%i', $atch_url)) {
			$file_path = $wpai_uploads . $atch_url;
		}
		else{
			$file_path = $uploads['path'] . '/' . $file_name;
		}
		$attachment_filename = wp_unique_filename($uploads['path'], $file_name);												
		$attachment_filepath = $uploads['path'] . '/' . sanitize_file_name($attachment_filename);
		$download_file = true;
		$create_file = false;
	
		if ($search_in_gallery){
			// searching for existing attachment
			$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE (post_title = %s OR post_title = %s OR post_name = %s) AND post_type = %s;", $file_name, preg_replace('/\\.[^.\\s]{3,4}$/', '', $file_name), sanitize_title(preg_replace('/\\.[^.\\s]{3,4}$/', '', $file_name)), "attachment" ) );		
			if ($attachment_id and ! is_wp_error($attachment_id))
				return $attachment_id;
			
			if ( @file_exists($file_path) ){
				if( ! $wp_filetype = wp_check_filetype(basename($file_name), null )) {
					$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: File %s is not a valid image and cannot be set as ACF image', 'wp_all_import_acf_add_on'), $file_path));
				} else {
					$download_file = false;			
					$create_file = true;										
				}
			}
		}
			
		if ($download_file){
			if ( ! get_file_curl(trim($atch_url), $attachment_filepath) and ! @file_put_contents($attachment_filepath, @file_get_contents(trim($atch_url)))) {												
				$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: Attachment file %s cannot be saved locally as %s', 'wp_all_import_acf_add_on'), trim($atch_url), $attachment_filepath));
				unlink($attachment_filepath); // delete file since failed upload may result in empty file created												
			} elseif( ! $wp_filetype = wp_check_filetype(basename($attachment_filename), null )) {
				$logger and call_user_func($logger, sprintf(__('- <b>WARNING</b>: Can\'t detect attachment file type %s', 'wp_all_import_acf_add_on'), trim($atch_url)));
			} else {
				
				$attachment_data = array(
				    'guid' => $uploads['baseurl'] . '/' . _wp_relative_upload_path( $attachment_filepath ), 
				    'post_mime_type' => $wp_filetype['type'],
				    'post_title' => preg_replace('/\.[^.]+$/', '', basename($attachment_filepath)),
				    'post_content' => '',
				    'post_status' => 'inherit'
				);
				$attach_id = wp_insert_attachment( $attachment_data, $attachment_filepath, $pid );												

				if (is_wp_error($attach_id)) {
					$logger and call_user_func($logger, __('- <b>WARNING</b>', 'wp_all_import_acf_add_on') . ': ' . $pid->get_error_message());
				} else {
					// you must first include the image.php file
					// for the function wp_generate_attachment_metadata() to work
					require_once(ABSPATH . 'wp-admin/includes/image.php');
					
					do_action( 'pmxi_attachment_uploaded', $pid, $attach_id, $attachment_filepath); 
					wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $attachment_filepath));											
					return $attach_id;
				}										
			}
		}	

		if ($create_file){
			$attachment_data = array(
			    'guid' => $uploads['baseurl'] . '/' . _wp_relative_upload_path( $attachment_filepath ), 
			    'post_mime_type' => $wp_filetype['type'],
			    'post_title' => preg_replace('/\.[^.]+$/', '', basename($attachment_filepath)),
			    'post_content' => '',
			    'post_status' => 'inherit'
			);
			
			$attach_id = wp_insert_attachment( $attachment_data, $attachment_filepath, $pid );												

			if (is_wp_error($attach_id)) {
				$logger and call_user_func($logger, __('- <b>WARNING</b>', 'wp_all_import_acf_add_on') . ': ' . $pid->get_error_message());
			} else {
				// you must first include the image.php file
				// for the function wp_generate_attachment_metadata() to work
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				
				do_action( 'pmxi_attachment_uploaded', $pid, $attach_id, $attachment_filepath); 
				wp_update_attachment_metadata($attach_id, wp_generate_attachment_metadata($attach_id, $attachment_filepath));											
				return $attach_id;
			}	
		}
		return false;
	}

	protected function associate_terms($pid, $assign_taxes, $tx_name, $logger = false){
		
		$terms = wp_get_object_terms( $pid, $tx_name );
		$term_ids = array();     

		$assign_taxes = (is_array($assign_taxes)) ? array_filter($assign_taxes) : false;   

		if ( ! empty($terms) ){
			if ( ! is_wp_error( $terms ) ) {				
				foreach ($terms as $term_info) {
					$term_ids[] = $term_info->term_taxonomy_id;
					$this->wpdb->query(  $this->wpdb->prepare("UPDATE {$this->wpdb->term_taxonomy} SET count = count - 1 WHERE term_taxonomy_id = %d", $term_info->term_taxonomy_id) );
				}				
				$in_tt_ids = "'" . implode( "', '", $term_ids ) . "'";
				$this->wpdb->query( $this->wpdb->prepare( "DELETE FROM {$this->wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id IN ($in_tt_ids)", $pid ) );
			}
		}

		if (empty($assign_taxes)) return;

		foreach ($assign_taxes as $tt) {
			$this->wpdb->insert( $this->wpdb->term_relationships, array( 'object_id' => $pid, 'term_taxonomy_id' => $tt ) );
			$this->wpdb->query( "UPDATE {$this->wpdb->term_taxonomy} SET count = count + 1 WHERE term_taxonomy_id = $tt" );
		}

		$values = array();
        $term_order = 0;
		foreach ( $assign_taxes as $tt )			                        	
    		$values[] = $this->wpdb->prepare( "(%d, %d, %d)", $pid, $tt, ++$term_order);
		                					

		if ( $values ){
			if ( false === $this->wpdb->query( "INSERT INTO {$this->wpdb->term_relationships} (object_id, term_taxonomy_id, term_order) VALUES " . join( ',', $values ) . " ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)" ) ){
				$logger and call_user_func($logger, __('<b>ERROR</b> Could not insert term relationship into the database', 'wp_all_import_acf_add_on') . ': '. $this->wpdb->last_error);
			}
		}                        			

		wp_cache_delete( $pid, $tx_name . '_relationships' ); 
	}

	public function _filter_has_cap_unfiltered_html($caps)
	{
		$caps['unfiltered_html'] = true;
		return $caps;
	}
	
	public function filtering($var){
		return ("" == $var) ? false : true;
	}		
}
