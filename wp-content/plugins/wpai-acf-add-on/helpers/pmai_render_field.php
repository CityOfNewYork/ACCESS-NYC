<?php
if ( ! function_exists('pmai_render_field')){
	
	function pmai_render_field($field, $post = array(), $field_name = ""){

		if (empty($field['multiple'])) $field['multiple'] = false;
		if (empty($field['class'])) $field['class'] = false;
		if (empty($field['id'])) $field['id'] = false;

		$current_field = (!empty($post['fields'][$field['key']])) ? $post['fields'][$field['key']] : false;
		$current_is_multiple_field_value = (isset($post['is_multiple_field_value'][$field['key']])) ? $post['is_multiple_field_value'][$field['key']] : false;
		$current_multiple_value = (isset($post['multiple_value'][$field['key']])) ? $post['multiple_value'][$field['key']] : false;		

		if ( "" != $field_name ){

			$field_keys = str_replace(array('[',']'), array(''), str_replace('][', ':', $field_name));
			
			foreach (explode(":", $field_keys) as $n => $key) {
				$current_field = (!empty($post['fields'][$key])) ? $post['fields'][$key] : $current_field[$key];
				$current_is_multiple_field_value = (isset($post['is_multiple_field_value'][$key])) ? $post['is_multiple_field_value'][$key] : $current_is_multiple_field_value[$key];
				$current_multiple_value = (isset($post['multiple_value'][$key])) ? $post['multiple_value'][$key] : $current_multiple_value[$key];
			}

			$current_field = (!empty($current_field[$field['key']])) ? $current_field[$field['key']] : false;		
			$current_is_multiple_field_value = (isset($current_is_multiple_field_value[$field['key']])) ? $current_is_multiple_field_value[$field['key']] : false;
			$current_multiple_value = (isset($current_multiple_value[$field['key']])) ? $current_multiple_value[$field['key']] : false;

		}	

		global $acf;

		?>
		
		<?php if ( ! in_array($field['type'], array('message')) ): ?>
		
		<div class="field field_type-<?php echo $field['type'];?> field_key-<?php echo $field['key'];?>">			
			<p class="label"><label><?php echo (in_array($field['type'], array('message', 'tab'))) ? $field['type'] : ((empty($field['label']) ? '' : $field['label']));?></label></p>			
			<div class="wpallimport-clear"></div>
			<p class="label" style="display:block; margin:0;"><label></label></p>
			<div class="acf-input-wrap">
				<?php switch ($field['type']) {
					case 'user':
						?>
						<input type="text" placeholder="" value="<?php echo esc_attr( $current_field );?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>]" class="text w95 widefat rad4"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Specify the user ID, username, or user e-mail address. Separate multiple values with commas.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<?php
						break;										
					case 'acf_cf7':
					case 'gravity_forms_field':					
						?>
						<input type="text" placeholder="" value="<?php echo esc_attr( $current_field );?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>]" class="text w95 widefat rad4"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Specify the form ID.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<?php
						break;										
					case 'page_link':
					case 'post_object':
						?>
						<input type="text" placeholder="" value="<?php echo esc_attr( $current_field );?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>]" class="text w95 widefat rad4"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Enter the ID, slug, or Title. Separate multiple entries with commas.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<?php
						break;
					case 'relationship':
						?>
						<div class="input">
							<input type="text" placeholder="" value="<?php echo ( ! is_array($current_field)) ? esc_attr($current_field) : esc_attr( $current_field['value'] );?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][value]" class="text widefat rad4" style="width: 75%;"/>
							<input type="text" style="width:5%; text-align:center;" value="<?php echo (!empty($current_field['delim'])) ? esc_attr( $current_field['delim'] ) : ',';?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][delim]" class="small rad4">
							<a href="#help" class="wpallimport-help" title="<?php _e('Enter the ID, slug, or Title. Separate multiple entries with separator character.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						</div>
						<?php
						break;
					case 'file':
						?>
						<input type="text" placeholder="" value="<?php echo (!is_array($current_field)) ? esc_attr( $current_field ) : esc_attr( $current_field['url'] );?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][url]" class="text w95 widefat rad4"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Specify the URL to the image or file.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<input type="hidden" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][search_in_media]" value="0"/>
						<input type="checkbox" id="<?php echo $field_name . $field['key'] . '_search_in_media';?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][search_in_media]" value="1" <?php echo (!empty($current_field['search_in_media'])) ? 'checked="checked"' : '';?>/>
						<label for="<?php echo $field_name . $field['key'] . '_search_in_media';?>"><?php _e('Search through the Media Library for existing images before importing new images', 'wp_all_import_acf_add_on'); ?></label>
						<a href="#help" class="wpallimport-help" title="<?php _e('If an image with the same file name is found in the Media Library then that image will be attached to this record instead of importing a new image. Disable this setting if your import has different images with the same file name.', 'wp_all_import_acf_add_on') ?>" style="position: relative; top: -2px;">?</a>
						<?php
						break;
					case 'image':
						?>
						<input type="text" placeholder="" value="<?php echo (!is_array($current_field)) ? esc_attr( $current_field ) : esc_attr( $current_field['url'] );?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][url]" class="text w95 widefat rad4"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Specify the URL to the image or file.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<input type="hidden" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][search_in_media]" value="0"/>
						<input type="checkbox" id="<?php echo $field_name . $field['key'] . '_search_in_media';?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][search_in_media]" value="1" <?php echo (!empty($current_field['search_in_media'])) ? 'checked="checked"' : '';?>/>
						<label for="<?php echo $field_name . $field['key'] . '_search_in_media';?>"><?php _e('Search through the Media Library for existing images before importing new images', 'wp_all_import_acf_add_on'); ?></label>
						<a href="#help" class="wpallimport-help" title="<?php _e('If an image with the same file name is found in the Media Library then that image will be attached to this record instead of importing a new image. Disable this setting if your import has different images with the same file name.', 'wp_all_import_acf_add_on') ?>" style="position: relative; top: -2px;">?</a>
						<?php
						break;					
					case 'gallery':
						?>
						<div class="input">
							<label><?php _e('Enter image URL one per line, or separate them with a', 'wp_all_import_acf_add_on'); ?> </label>
							<input type="text" style="width:5%; text-align:center;" value="<?php echo (!empty($current_field['delim'])) ? esc_attr( $current_field['delim'] ) : '';?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][delim]" class="small rad4">
							<textarea placeholder="http://example.com/images/image-1.jpg" style="clear: both; display: block; margin-top: 10px;" class="newline rad4" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][gallery]"><?php echo ( ! is_array($current_field)) ? esc_attr($current_field) : esc_attr( $current_field['gallery'] );?></textarea>			
							<input type="hidden" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][search_in_media]" value="0"/>
							<input type="checkbox" id="<?php echo $field_name . $field['key'] . '_search_in_media';?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][search_in_media]" value="1" <?php echo (!empty($current_field['search_in_media'])) ? 'checked="checked"' : '';?>/>
							<label for="<?php echo $field_name . $field['key'] . '_search_in_media';?>"><?php _e('Search through the Media Library for existing images before importing new images', 'wp_all_import_acf_add_on'); ?></label>
							<a href="#help" class="wpallimport-help" title="<?php _e('If an image with the same file name is found in the Media Library then that image will be attached to this record instead of importing a new image. Disable this setting if your import has different images with the same file name.', 'wp_all_import_acf_add_on') ?>" style="position: relative; top: -2px;">?</a>
							<div class="input">
								<input type="checkbox" id="<?php echo $field_name . $field['key'] . '_only_append_new';?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][only_append_new]" value="1" <?php echo (!empty($current_field['only_append_new'])) ? 'checked="checked"' : '';?>/>
								<label for="<?php echo $field_name . $field['key'] . '_only_append_new';?>"><?php _e('Append only new images and do not touch existing during updating gallery field.', 'wp_all_import_acf_add_on'); ?></label>
							</div>
						</div>
						<?php
						break;					
					case 'color_picker':					
						?>
						<input type="text" placeholder="" value="<?php echo esc_attr( $current_field );?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>]" class="text w95 widefat rad4"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Specify the hex code the color preceded with a # - e.g. #ea5f1a.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<?php
						break;					
					case 'text':					
					case 'number':
					case 'email':
					case 'password':
					case 'url':
					case 'oembed':
					case 'limiter':
						?>
						<input type="text" placeholder="" value="<?php echo esc_attr( $current_field );?>" name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>]" class="text widefat rad4"/>
						<?php
						break;					
					case 'wp_wysiwyg':
					case 'wysiwyg':	
					case 'textarea':
						?>
						<textarea name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>]" class="widefat rad4"><?php echo esc_attr( $current_field );?></textarea>
						<?php
						break;				
					case 'date_picker':
						?>
						<input type="text" placeholder="" value="<?php echo esc_attr( $current_field );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>]" class="text datepicker widefat rad4" style="width:200px;"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Use any format supported by the PHP strtotime function.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<?php
						break;		
					case 'date_time_picker':
						?>
						<input type="text" placeholder="" value="<?php echo esc_attr( $current_field );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>]" class="text datetimepicker widefat rad4" style="width:200px;"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Use any format supported by the PHP strtotime function.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<?php
						break;
					case 'time_picker':
						?>
						<input type="text" placeholder="" value="<?php echo esc_attr( $current_field );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>]" class="text widefat rad4" style="width:200px;"/>
						<a href="#help" class="wpallimport-help" title="<?php _e('Use H:i:s format.', 'wp_all_import_acf_add_on'); ?>" style="top:0;">?</a>
						<?php
						break;
					case 'location-field':
						?>
						<div class="input">
							<label><?php _e("Address"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['address'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address]" class="text widefat rad4"/>												
						</div>												
						<div class="input">
							<label><?php _e("Lat"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['lat'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][lat]" class="text widefat rad4"/>												
						</div>
						<div class="input">
							<label><?php _e("Lng"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['lng'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][lng]" class="text widefat rad4"/>
						</div>
						<?php
						break;
					case 'google_map_extended':
					case 'google_map':					
						?>
						<div class="input">
							<label><?php _e("Address"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['address'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address]" class="text widefat rad4"/>												
						</div>												
						<div class="input">
							<label><?php _e("Lat"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['lat'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][lat]" class="text widefat rad4"/>												
						</div>
						<div class="input">
							<label><?php _e("Lng"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['lng'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][lng]" class="text widefat rad4"/>
						</div>
						<div class="wpallimport-collapsed wpallimport-section wpallimport-sub-options wpallimport-dependent-options">
							<div class="wpallimport-content-section wpallimport-bottom-radius">								
								<div style="padding: 0px; display: block;" class="wpallimport-collapsed-content">										
									<div class="wpallimport-collapsed-content-inner">											
										<label for="realhomes_addonaddress_geocode">Google Geocode API Settings</label>			
										<div class="input">
											<div class="form-field wpallimport-radio-field wpallimport-realhomes_addonaddress_geocode_address_no_key">						
												<input type="radio" <?php if (empty($current_field['address_geocode']) or esc_attr( $current_field['address_geocode'] ) == 'address_no_key'):?>checked="checked"<?php endif;?> value="address_no_key" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_geocode]" class="switcher" id="<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_no_key">
												<label for="<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_no_key">No API Key</label>
												<a style="position: relative; top: -2px;" class="wpallimport-help" href="#help" original-title="Limited number of requests.">?</a>
											</div>
											<div class="form-field wpallimport-radio-field wpallimport-<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_developers">						
												<input type="radio" value="address_google_developers" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_geocode]" class="switcher" id="<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_developers" <?php if (esc_attr( $current_field['address_geocode'] ) == 'address_google_developers'):?>checked="checked"<?php endif;?> >
												<label for="<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_developers">Google Developers API Key - <a href="https://developers.google.com/maps/documentation/geocoding/#api_key">Get free API key</a></label>
												<a style="position: relative; top: -2px;" class="wpallimport-help" href="#help" original-title="Up to 2500 requests per day and 5 requests per second.">?</a>
												<div class="switcher-target-<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_developers" style="display: block;">
													<div class="input sub_input">
														<label for="<?php echo $field_name; ?>_<?php echo $field_name; ?>_google_developers_api_key">API Key</label>			
														<div class="input">
															<input type="text" style="width:100%;" value="<?php echo esc_attr( $current_field['address_google_developers_api_key'] );?>" id="<?php echo $field_name; ?>_<?php echo $field_name; ?>_google_developers_api_key" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_google_developers_api_key]">
														</div>
													</div>
												</div>										
											</div>
											<div class="form-field wpallimport-radio-field wpallimport-<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_for_work">						
												<input type="radio" value="address_google_for_work" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_geocode]" class="switcher" <?php if (esc_attr( $current_field['address_geocode'] ) == 'address_google_for_work'):?>checked="checked"<?php endif;?> id="<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_for_work">
												<label for="<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_for_work">Google for Work Client ID &amp; Digital Signature - <a href="https://developers.google.com/maps/documentation/business">Sign up for Google for Work</a></label>
												<a style="position: relative; top: -2px;" class="wpallimport-help" href="#help" original-title="Up to 100,000 requests per day and 10 requests per second">?</a>
												<div class="switcher-target-<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_for_work" style="display: none;">
													<div class="input sub_input">
														<label for="<?php echo $field_name; ?>_<?php echo $field_name; ?>_google_for_work_client_id">Google for Work Client ID</label>			
														<div class="input">
															<input type="text" style="width:100%;" value="<?php echo esc_attr( $current_field['address_google_for_work_client_id'] );?>" id="<?php echo $field_name; ?>_<?php echo $field_name; ?>_google_for_work_client_id" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_google_for_work_client_id]">
														</div>
														<label for="<?php echo $field_name; ?>_<?php echo $field_name; ?>_google_for_work_digital_signature">Google for Work Digital Signature</label>			
														<div class="input">
															<input type="text" style="width:100%;" value="<?php echo esc_attr( $current_field['address_google_for_work_digital_signature'] );?>" id="<?php echo $field_name; ?>_<?php echo $field_name; ?>_google_for_work_digital_signature" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_google_for_work_digital_signature]">
														</div>
													</div>
												</div>										
											</div>																													
										</div>								
							 		</div>
							 	</div>
							 </div>
						</div>
						<?php if ($field['type'] == 'google_map_extended'): ?>
						<div class="input">
							<label><?php _e("Zoom"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['zoom'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][zoom]" class="text widefat rad4"/>
						</div>
						<div class="input">
							<label><?php _e("Center lat"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['center_lat'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][center_lat]" class="text widefat rad4"/>
						</div>
						<div class="input">
							<label><?php _e("Center lng"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['center_lng'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][center_lng]" class="text widefat rad4"/>
						</div>
						<?php endif;?>
					<?php
						break;					
					case 'paypal_item':
						?>
						<div class="input">
							<label><?php _e("Item Name"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['item_name'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][item_name]" class="text widefat rad4"/>												
						</div>
						<div class="input">
							<label><?php _e("Item Description"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['item_description'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][item_description]" class="text widefat rad4"/>												
						</div>
						<div class="input">
							<label><?php _e("Price"); ?></label>
							<input type="text" placeholder="" value="<?php echo esc_attr( $current_field['price'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][price]" class="text widefat rad4"/>
						</div>
						<?php
						break;
					case 'select':
					case 'checkbox':
					case 'radio':					
					case 'true_false':															
						?>											
						<div class="input">
							<div class="main_choise">
								<input type="radio" id="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="switcher" name="is_multiple_field_value<?php echo $field_name; ?>[<?php echo $field['key'];?>]" value="yes" <?php echo 'no' != $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
								<label for="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="chooser_label"><?php _e("Select value for all records", 'wp_all_import_acf_add_on'); ?></label>
							</div>
							<div class="wpallimport-clear"></div>
							<div class="switcher-target-is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes">
								<div class="input sub_input">
									<div class="input">
										<?php

										if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){
											
											$field_class = 'acf_field_' . $field['type'];										

											$field['other_choice'] = false;
											$tmp_key = $field['key'];
											$field['key'] = 'multiple_value'. $field_name .'[' . $field['key'] . ']';
											$field['value'] = $current_multiple_value;

											acf_render_field( $field );

											$field['key'] = $tmp_key;
											
										}
										else{
											
											$field_class = 'acf_field_' . $field['type'];
											$new_field = new $field_class();

											$field['other_choice'] = false;
											$field['name'] = 'multiple_value'. $field_name .'[' . $field['key'] . ']';
											$field['value'] = $current_multiple_value;// === false ? $current_field : $current_multiple_value;

											$new_field->create_field( $field );

										}

										?>
									</div>
								</div>
							</div>
						</div>											
						
						<div class="clear"></div>

						<div class="input">
							<div class="main_choise">
								<input type="radio" id="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="switcher" name="is_multiple_field_value<?php echo $field_name; ?>[<?php echo $field['key'];?>]" value="no" <?php echo 'no' == $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
								<label for="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="chooser_label"><?php _e('Set with XPath', 'wp_all_import_acf_add_on' )?></label>
							</div>
							<div class="wpallimport-clear"></div>
							<div class="switcher-target-is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no">
								<div class="input sub_input">
									<div class="input">
										<input type="text" class="smaller-text widefat rad4" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>]" style="width:300px;" value="<?php echo esc_attr($current_field); ?>"/>
										<?php										
											if ($field['type']=='select' || $field['type']=='checkbox' || $field['type']=='radio') {
												?>
												<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('Specify the value. For multiple values, separate with commas. If the choices are of the format option : Option, option-2 : Option 2, use option and option-2 for values.', 'wp_all_import_acf_add_on') ?>">?</a>
												<?php
											} else {
												?>
												<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('Specify the 0 for false, 1 for true.', 'wp_all_import_acf_add_on') ?>">?</a>
												<?php
											}
										?>
									</div>
								</div>
							</div>
						</div>
						<?php
						break;		
					case 'taxonomy':
						?>
						<div class="input">
							<div class="main_choise">
								<input type="radio" id="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="switcher" name="is_multiple_field_value<?php echo $field_name; ?>[<?php echo $field['key'];?>]" value="yes" <?php echo 'no' != $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
								<label for="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="chooser_label"><?php _e("Select value for all records"); ?></label>
							</div>
							<div class="wpallimport-clear"></div>
							<div class="switcher-target-is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes">
								<div class="input sub_input">
									<div class="input">
										<?php

										if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

											$field_class = 'acf_field_' . $field['type'];										

											$tmp_key = $field['key'];
											$field['key'] = 'multiple_value'. $field_name .'[' . $field['key'] . ']';
											$field['value'] = $current_multiple_value;

											acf_render_field( $field );

											$field['key'] = $tmp_key;

										} else{
										
											$field_class = 'acf_field_' . $field['type'];
											$new_field = new $field_class();

											$field['other_choice'] = false;
											$field['name'] = 'multiple_value'. $field_name .'[' . $field['key'] . ']';
											$field['value'] = $current_multiple_value;									

											$new_field->create_field( $field );

										}
										?>
									</div>
								</div>
							</div>
						</div>											
						<div class="clear"></div>
						<div class="input" style="overflow:hidden;">
							<div class="main_choise">
								<input type="radio" id="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="switcher" name="is_multiple_field_value<?php echo $field_name; ?>[<?php echo $field['key'];?>]" value="no" <?php echo 'no' == $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
								<label for="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="chooser_label"><?php _e('Set with XPath', 'wp_all_import_acf_add_on' )?></label>
							</div>
							<div class="wpallimport-clear"></div>
							<div class="switcher-target-is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no">
								<div class="input sub_input">
									<div class="input">
										<table class="pmai_taxonomy post_taxonomy">
											<tr>
												<td>
													<div class="col2" style="clear: both;">
														<ol class="sortable no-margin">
															<?php
															if ( ! empty($current_field) ):
																	$taxonomies_hierarchy = json_decode($current_field);																
																
																	if ( ! empty($taxonomies_hierarchy) and is_array($taxonomies_hierarchy)): $i = 0; foreach ($taxonomies_hierarchy as $cat) { $i++;
																		if ( is_null($cat->parent_id) or empty($cat->parent_id) )
																		{
																			?>
																			<li id="item_<?php echo $i; ?>" class="dragging">
																				<div class="drag-element">
																					<input type="text" class="widefat xpath_field rad4" value="<?php echo esc_attr($cat->xpath); ?>"/>
																				</div>
																				<?php if ( $i > 1 ): ?><a href="javascript:void(0);" class="icon-item remove-ico"></a><?php endif; ?>

																				<?php echo reverse_taxonomies_html($taxonomies_hierarchy, $cat->item_id, $i); ?>
																			</li>
																			<?php
																		}
																	}; else:?>
																	<li id="item_1" class="dragging">
																		<div class="drag-element" >
																			<!--input type="checkbox" class="assign_post" checked="checked" title="<?php _e('Assign post to the taxonomy.','wp_all_import_acf_add_on');?>"/-->
																			<input type="text" class="widefat xpath_field rad4" value=""/>
																			<a href="javascript:void(0);" class="icon-item remove-ico"></a>
																		</div>
																	</li>
																	<?php endif;
																  else: ?>
														    <li id="item_1" class="dragging">
														    	<div class="drag-element">
														    		<!--input type="checkbox" class="assign_post" checked="checked" title="<?php _e('Assign post to the taxonomy.','wp_all_import_acf_add_on');?>"/-->
														    		<input type="text" class="widefat xpath_field rad4" value=""/>
														    		<a href="javascript:void(0);" class="icon-item remove-ico"></a>
														    	</div>
														    </li>
															<?php endif;?>
															<li id="item" class="template">
														    	<div class="drag-element">
														    		<!--input type="checkbox" class="assign_post" checked="checked" title="<?php _e('Assign post to the taxonomy.','wp_all_import_acf_add_on');?>"/-->
														    		<input type="text" class="widefat xpath_field rad4" value=""/>
														    		<a href="javascript:void(0);" class="icon-item remove-ico"></a>
														    	</div>
														    </li>
														</ol>
														<input type="hidden" class="hierarhy-output" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>]" value="<?php echo esc_attr($current_field); ?>"/>													
														<?php //$taxonomies_hierarchy = json_decode($current_field, true);?>
														<div class="delim">														
															<a href="javascript:void(0);" class="icon-item add-new-ico"><?php _e('Add more','wp_all_import_acf_add_on');?></a>
														</div>
													</div>
												</td>
											</tr>										
										</table>
									</div>
								</div>
							</div>
						</div>
						<?php
						break;								
					case 'repeater':

						?>
						<div class="repeater">
							
							<div class="input" style="margin-bottom: 10px;">
							
								<div class="input">
									<input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="no" <?php echo 'yes' != $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
									<label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="chooser_label"><?php _e('Fixed Repeater Mode', 'wp_all_import_acf_add_on' )?></label>
								</div>
								<div class="wpallimport-clear"></div>
								<div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no">
									<div class="input sub_input">
										<div class="input">
											<input type="hidden" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_ignore_empties]" value="0"/>
											<input type="checkbox" value="1" id="is_ignore_empties<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_ignore_empties]" <?php if ( ! empty($current_field['is_ignore_empties'])) echo 'checked="checked';?>/>
											<label for="is_ignore_empties<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>"><?php _e('Ignore blank fields', 'wp_all_import_acf_add_on'); ?></label>
											<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('If the value of the element or column in your file is blank, it will be ignored. Use this option when some records in your file have a different number of repeating elements than others.', 'wp_all_import_acf_add_on') ?>">?</a>
										</div>
									</div>
								</div>
								<div class="input">
									<input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="yes" <?php echo 'yes' == $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
									<label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="chooser_label"><?php _e('Variable Repeater Mode (XML)', 'wp_all_import_acf_add_on' )?></label>
								</div>																	
								<div class="input">
									<input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes_csv" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="csv" <?php echo 'csv' == $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
									<label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes_csv" class="chooser_label"><?php _e('Variable Repeater Mode (CSV)', 'wp_all_import_acf_add_on' )?></label>
								</div>																	
								<div class="wpallimport-clear"></div>
								<div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes">
									<div class="input sub_input">
										<div class="input">
											<p>
												<?php printf(__("For each %s do ..."), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][foreach]" value="'. $current_field["foreach"] .'" class="pmai_foreach widefat rad4"/>'); ?>											
												<a href="http://www.wpallimport.com/documentation/advanced-custom-fields/repeater-fields/" target="_blank"><?php _e('(documentation)', 'wp_all_import_acf_add_on'); ?></a>
											</p>
										</div>
									</div>
								</div>
								<div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes_csv">
									<div class="input sub_input">
										<div class="input">
											<p>
												<?php printf(__("Separator Character %s"), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][separator]" value="'. ( (empty($current_field["separator"])) ? '|' : $current_field["separator"] ) .'" class="pmai_variable_separator widefat rad4"/>'); ?>											
												<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('Use this option when importing a CSV file with a column or columns that contains the repeating data, separated by separators. For example, if you had a repeater with two fields - image URL and caption, and your CSV file had two columns, image URL and caption, with values like \'url1,url2,url3\' and \'caption1,caption2,caption3\', use this option and specify a comma as the separator.', 'wp_all_import_acf_add_on') ?>">?</a>
											</p>
										</div>
									</div>
								</div>

							</div>							

							<table class="widefat acf-input-table row_layout">								
								<tbody>
									<?php 																													
									if (!empty($current_field['rows'])) : foreach ($current_field['rows'] as $key => $row): if ("ROWNUMBER" == $key) continue; ?>									
									<tr class="row">							
										<td class="order" style="padding:8px;"><?php echo $key; ?></td>	
										<td class="acf_input-wrap" style="padding:0 !important;">
											<table class="widefat acf_input" style="border:none;">
												<tbody>
													<?php 

													if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){
														
														$parent_field_id = (!empty($field['id'])) ? $field['id'] : $field['ID'];

														if ( empty($parent_field_id) && ! empty($field['key'])){
														    $args=array(
														        'name' => $field['key'],
														        'post_type' => 'acf-field',
														        'post_status' => 'publish',
														        'posts_per_page' => 1
														    );
														    $my_posts = get_posts( $args );
														    if( $my_posts ) {
														    	$parent_field_id = $my_posts[0]->ID;
														    }															
														}														

														if ( ! empty($parent_field_id) )
														{
															if (is_numeric($parent_field_id))
															{
																$sub_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $parent_field_id, 'post_status' => 'publish'));

																if ( ! empty($sub_fields) ){

																	foreach ($sub_fields as $n => $sub_field){
																		$sub_fieldData = (!empty($sub_field->post_content)) ? unserialize($sub_field->post_content) : array();			
																		$sub_fieldData['id'] = $sub_field->ID;
																		$sub_fieldData['label'] = $sub_field->post_title;
																		$sub_fieldData['key'] = $sub_field->post_name;																
																		?>
																		<tr class="field sub_field field_type-<?php echo $sub_fieldData['type'];?> field_key-<?php echo $sub_fieldData['key'];?>">
																			<td class="label">
																				<?php echo $sub_fieldData['label'];?>
																			</td>
																			<td>
																				<div class="inner input">																			
																					<?php echo pmai_render_field($sub_fieldData, $post, $field_name . "[" . $field['key'] . "][rows][" . $key . "]"); ?>
																				</div>
																			</td>
																		</tr>													
																		<?php 
																	}
																}
															}
															else
															{
																$fields = acf_local()->fields;
					
																if (!empty($fields)){
																	foreach ($fields as $sub_field) {
																		if ($sub_field['parent'] == $field['key']){								
																			$sub_fieldData = $sub_field;																	
																			$sub_fieldData['ID'] = $sub_fieldData['id']    = uniqid();																			
																			
																			?>
																			<tr class="field sub_field field_type-<?php echo $sub_fieldData['type'];?> field_key-<?php echo $sub_fieldData['key'];?>">
																				<td class="label">
																					<?php echo $sub_fieldData['label'];?>
																				</td>
																				<td>
																					<div class="inner input">																			
																						<?php echo pmai_render_field($sub_fieldData, $post, $field_name . "[" . $field['key'] . "][rows][" . $key . "]"); ?>
																					</div>
																				</td>
																			</tr>													
																			<?php 
																		}
																	}
																}
															}															
														}

													} else{
														
														foreach ($field['sub_fields'] as $n => $sub_field){ ?>
														<tr class="field sub_field field_type-<?php echo $sub_field['type'];?> field_key-<?php echo $sub_field['key'];?>">
															<td class="label">
																<?php echo $sub_field['label'];?>
															</td>
															<td>
																<div class="inner input">
																	<?php echo pmai_render_field($sub_field, $post, $field_name . "[" . $field['key'] . "][rows][" . $key . "]"); ?>
																</div>
															</td>
														</tr>													
														<?php 
														}
													} 
													?>
												</tbody>
											</table>
										</td>
									</tr>
									<?php endforeach; endif; ?>															
									<tr class="row-clone">							
										<td class="order" style="padding:8px;"></td>		
										<td class="acf_input-wrap" style="padding:0 !important;">
											<table class="widefat acf_input" style="border:none;">
												<tbody>
													<?php 													
													if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

														$parent_field_id = ( ! empty($field['id']) ) ? $field['id'] : $field['ID'];

														if ( empty($parent_field_id) && ! empty($field['key'])){
														    $args=array(
														        'name' => $field['key'],
														        'post_type' => 'acf-field',
														        'post_status' => 'publish',
														        'posts_per_page' => 1
														    );
														    $my_posts = get_posts( $args );
														    if( $my_posts ) {
														    	$parent_field_id = $my_posts[0]->ID;
														    }															
														}

														if (is_numeric($parent_field_id) && $parent_field_id > 0)
														{

															$sub_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => $parent_field_id, 'post_status' => 'publish'));

															if ( ! empty($sub_fields) ){

																foreach ($sub_fields as $key => $sub_field){
																	$sub_fieldData = (!empty($sub_field->post_content)) ? unserialize($sub_field->post_content) : array();
																	$sub_fieldData['ID'] = $sub_field->ID;
																	$sub_fieldData['label'] = $sub_field->post_title;
																	$sub_fieldData['key'] = $sub_field->post_name;
																	?>
																	<tr class="field sub_field field_type-<?php echo $sub_fieldData['type'];?> field_key-<?php echo $sub_fieldData['key'];?>">
																		<td class="label">
																			<?php echo $sub_fieldData['label'];?>
																		</td>
																		<td>
																			<div class="inner">
																				<?php echo pmai_render_field($sub_fieldData, $post, $field_name . "[" . $field['key'] . "][rows][ROWNUMBER]"); ?>
																			</div>
																		</td>
																	</tr>
																	<?php
																}
															}
														}
														else
														{
															$fields = acf_local()->fields;

															if (!empty($fields)){
																foreach ($fields as $sub_field) {
																	if ($sub_field['parent'] == $field['key']){
																		$sub_fieldData = $sub_field;
																		$sub_fieldData['ID'] = $sub_fieldData['id']    = uniqid();

																		?>
																		<tr class="field sub_field field_type-<?php echo $sub_fieldData['type'];?> field_key-<?php echo $sub_fieldData['key'];?>">
																			<td class="label">
																				<?php echo $sub_fieldData['label'];?>
																			</td>
																			<td>
																				<div class="inner">
																					<?php echo pmai_render_field($sub_fieldData, $post, $field_name . "[" . $field['key'] . "][rows][ROWNUMBER]"); ?>
																				</div>
																			</td>
																		</tr>
																		<?php
																	}
																}
															}
														}

													}	
													else { 

														foreach ($field['sub_fields'] as $key => $sub_field){ ?>
														<tr class="field sub_field field_type-<?php echo $sub_field['type'];?> field_key-<?php echo $sub_field['key'];?>">
															<td class="label">
																<?php echo $sub_field['label'];?>
															</td>
															<td>
																<div class="inner">
																	<?php echo pmai_render_field($sub_field, $post, $field_name . "[" . $field['key'] . "][rows][ROWNUMBER]"); ?>
																</div>	
															</td>
														</tr>													
														<?php 
														} 
													} 
													?>
												</tbody>
											</table>
										</td>
									</tr>
								</tbody>
							</table>								
							<div class="wpallimport-clear"></div>
							<div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no">
								<div class="input sub_input">
									<ul class="hl clearfix repeater-footer">
										<li class="right">
											<a href="javascript:void(0);" class="acf-button delete_row" style="margin-left:15px;"><?php _e('Delete Row', 'wp_all_import_acf_add_on'); ?></a>
										</li>
										<li class="right">
											<a class="add-row-end acf-button" href="javascript:void(0);"><?php _e("Add Row", 'wp_all_import_acf_add_on');?></a>
										</li>								
									</ul>							
								</div>							
							</div>							
						</div>
						<?php

						break;
					case 'validated_field':

						if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

							/*$sub_fields = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field', 'post_parent' => ((!empty($field['id'])) ? $field['id'] : $field['ID']), 'post_status' => 'publish'));

							if ( ! empty($sub_fields) ){

								foreach ($sub_fields as $key => $sub_field){
									$sub_fieldData = (!empty($sub_field->post_content)) ? unserialize($sub_field->post_content) : array();			
									$sub_fieldData['ID'] = $sub_field->ID;
									$sub_fieldData['label'] = $sub_field->post_title;
									$sub_fieldData['key'] = $sub_field->post_name;																
									?>
									<tr class="field sub_field field_type-<?php echo $sub_fieldData['type'];?> field_key-<?php echo $sub_fieldData['key'];?>">
										<td class="label">
											<?php echo $sub_fieldData['label'];?>
										</td>
										<td>
											<div class="inner">
												<?php echo pmai_render_field($sub_fieldData, $post, $field_name . "[" . $field['key'] . "][rows][ROWNUMBER]"); ?>
											</div>	
										</td>
									</tr>													
									<?php 
								}
							}*/
						}	
						else { 
							if (!empty($field['sub_fields'])){
								foreach ($field['sub_fields'] as $key => $sub_field){ ?>
								<tr class="field sub_field field_type-<?php echo $sub_field['type'];?> field_key-<?php echo $sub_field['key'];?>">
									<td class="label">
										<?php echo $sub_field['label'];?>
									</td>
									<td>
										<div class="inner">
											<?php echo pmai_render_field($sub_field, $post, $field_name . "[" . $field['key'] . "][rows][ROWNUMBER]"); ?>
										</div>	
									</td>
								</tr>													
								<?php 
								} 
							}
							elseif (!empty($field['sub_field'])){
								?>
								<tr class="field sub_field field_type-<?php echo $field['sub_field']['type'];?> field_key-<?php echo $field['sub_field']['key'];?>">									
									<td>
										<div class="inner">
											<?php echo pmai_render_field($field['sub_field'], $post, $field_name ); ?>
										</div>	
									</td>
								</tr>													
								<?php
							}
						} 
						
						break;

					case 'clone':

						if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

							if (!empty($field['clone'])) {
								$sub_fields = array();
								foreach ($field['clone'] as $sub_field_key) {
									$args = array(
										'name' => $sub_field_key,
										'post_type' => 'acf-field',
										'post_status' => 'publish',
										'posts_per_page' => 1
									);
									$my_posts = get_posts($args);
									if ($my_posts) {
										$sub_fields[] = $my_posts[0];
									}
								}
								if (!empty($sub_fields)) {
									foreach ($sub_fields as $sub_field) {
										$sub_fieldData = (!empty($sub_field->post_content)) ? unserialize($sub_field->post_content) : array();
										$sub_fieldData['ID'] = $sub_field->ID;
										$sub_fieldData['label'] = $sub_field->post_title;
										$sub_fieldData['key'] = $sub_field->post_name;
										?>
										<tr class="field sub_field field_type-<?php echo $sub_fieldData['type']; ?> field_key-<?php echo $sub_fieldData['key']; ?>">
											<td>
												<div class="inner">
													<?php
													echo pmai_render_field($sub_fieldData, $post, $field_name . '[' . $field['key'] . ']'); ?>
												</div>
											</td>
										</tr>
										<?php
									}
								}
							}
						}
						else{
							?>
							<p>
								<?php
								_e('This field type is not supported. E-mail support@soflyy.com with the details of the custom ACF field you are trying to import to, as well as a link to download the plugin to install to add this field type to ACF, and we will investigate the possiblity ot including support for it in the ACF add-on.', 'wp_all_import_acf_add_on');
								?>
							</p>
							<?php
						}

						break;

					case 'flexible_content':						
						?>
						<div class="acf-flexible-content">							
							<div class="clones">
							<?php 		
							if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

								// vars
								$sub_fields = acf_get_fields($field);																
								
								// loop through layouts, sub fields and swap out the field key with the real field
								foreach( array_keys($field['layouts']) as $i ) {
									
									// extract layout
									$layout = acf_extract_var( $field['layouts'], $i );
									
									
									// validate layout
									//$layout = $this->get_valid_layout( $layout );
									
									
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
									$field['layouts'][ $i ] = $layout;
									
								}
							}


							foreach( $field['layouts'] as $i => $layout ){																				

								// vars
								$order = is_numeric($i) ? ($i + 1) : 0;

								?>
								<div class="layout" data-layout="<?php echo $layout['name']; ?>">
											
									<div style="display:none">
										<input type="hidden" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][layouts][ROWNUMBER][acf_fc_layout]" value="<?php echo $layout['name']; ?>" />
									</div>									
										
									<div class="acf-fc-layout-handle">
										<span class="fc-layout-order"><?php echo $order; ?></span>. <?php echo $layout['label']; ?>
									</div>
									
									<table class="widefat acf-input-table <?php if( $layout['display'] == 'row' ): ?>row_layout<?php endif; ?>">
										<?php if( $layout['display'] == 'table' ): ?>
											<thead>
												<tr>

													<?php
													
													foreach( $layout['sub_fields'] as $sub_field_i => $sub_field): 
															
														// add width attr
														$attr = "";
														
														if( count($layout['sub_fields']) > 1 && isset($sub_field['column_width']) && $sub_field['column_width'] )
														{
															$attr = 'width="' . $sub_field['column_width'] . '%"';
														}
														
														// required
														$required_label = "";
														
														if( $sub_field['required'] )
														{
															$required_label = ' <span class="required">*</span>';
														}
														
														?>
														<td class="acf-th-<?php echo $sub_field['name']; ?> field_key-<?php echo $sub_field['key']; ?>" <?php echo $attr; ?>>
															<span><?php echo $sub_field['label'] . $required_label; ?></span>
															<?php if( isset($sub_field['instructions']) ): ?>
																<span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
															<?php endif; ?>
														</td><?php
													endforeach;
													?>													
												</tr>
											</thead>
										<?php endif; ?>
										<tbody>
											<tr>
											<?php

											// layout: Row
											
											if( $layout['display'] == 'row' ): ?>
												<td class="acf_input-wrap">
													<table class="widefat acf_input">
											<?php endif; ?>
																						
											<?php											

											// loop though sub fields
											if( $layout['sub_fields'] ):
												foreach( $layout['sub_fields'] as $sub_field ): ?>
												
													<?php
													
													// attributes (can appear on tr or td depending on $field['layout'])
													$attributes = array(
														'class'				=> "field sub_field field_type-{$sub_field['type']} field_key-{$sub_field['key']}",
														'data-field_type'	=> $sub_field['type'],
														'data-field_key'	=> $sub_field['key'],
														'data-field_name'	=> $sub_field['name']
													);
													
													
													// required
													if( $sub_field['required'] )
													{
														$attributes['class'] .= ' required';
													}
													
													
													// value
													$sub_field['value'] = false;
													
													if( isset($value[ $sub_field['key'] ]) )
													{
														// this is a normal value
														$sub_field['value'] = $value[ $sub_field['key'] ];
													}
													elseif( !empty($sub_field['default_value']) )
													{
														// no value, but this sub field has a default value
														$sub_field['value'] = $sub_field['default_value'];
													}
													
													
													// add name
													$sub_field['name'] = $field['name'] . '[' . $i . '][' . $sub_field['key'] . ']';
													
													
													// clear ID (needed for sub fields to work!)
													//unset( $sub_field['id'] );
													
													
													
													// layout: Row
													
													if( $layout['display'] == 'row' ): ?>
														<tr <?php pmai_join_attr( $attributes ); ?>>
															<td class="label">
																<label>
																	<?php echo $sub_field['label']; ?>
																	<?php if( $sub_field['required'] ): ?><span class="required">*</span><?php endif; ?>
																</label>
																<?php if( isset($sub_field['instructions']) ): ?>
																	<span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
																<?php endif; ?>
															</td>
													<?php endif; ?>
													
													<td <?php if( $layout['display'] != 'row' ){ pmai_join_attr( $attributes ); } ?>>
														<div class="inner">
														<?php
														
														// create field
														echo pmai_render_field($sub_field, $post, $field_name . "[" . $field['key'] . "][layouts][ROWNUMBER]");
														
														?>
														</div>
													</td>
													
													<?php
												
													// layout: Row
													
													if( $layout['display'] == 'row' ): ?>
														</tr>
													<?php endif; ?>
													
												
												<?php endforeach; ?>
											<?php endif; ?>
											<?php	
											// layout: Row
											
											if( $layout['display'] == 'row' ): ?>
													</table>
												</td>
											<?php endif; ?>
																			
											</tr>
										</tbody>
										
									</table>
									
								</div>
								<?php
								
							}
							
							?>
							</div>
							<div class="values ui-sortable">
								<?php if (!empty($current_field['layouts'])) : foreach ($current_field['layouts'] as $key => $layout): if ("ROWNUMBER" == $key) continue; ?>								
								<div class="layout" data-layout="<?php if (!empty($field['layouts'][$key]['name'])) echo $field['layouts'][$key]['name']; ?>">
									
									<div style="display:none">
										<input type="hidden" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][layouts][<?php echo $key;?>][acf_fc_layout]" value="<?php echo $layout['acf_fc_layout']; ?>" />
									</div>									
									<?php
										$current_layout = false;
										foreach ($field['layouts'] as $sub_lay){
											if ($sub_lay['name'] == $layout['acf_fc_layout']){
												$current_layout = $sub_lay;
												break;
											}
										}
									?>
									<div class="acf-fc-layout-handle">
										<span class="fc-layout-order"><?php echo $key; ?></span>. <?php echo $current_layout['label']; ?>
									</div>

									<table class="widefat acf-input-table <?php if( $current_layout['display'] == 'row' ): ?>row_layout<?php endif; ?>">
										<?php if( $current_layout['display'] == 'table' ): ?>
											<thead>
												<tr>
													<?php foreach( $current_layout['sub_fields'] as $sub_field_i => $sub_field): 

														// add width attr
														$attr = "";
														
														if( count($field['layouts'][$key - 1]['sub_fields']) > 1 && isset($sub_field['column_width']) && $sub_field['column_width'] )
														{
															$attr = 'width="' . $sub_field['column_width'] . '%"';
														}
														
														// required
														$required_label = "";
														
														if( $sub_field['required'] )
														{
															$required_label = ' <span class="required">*</span>';
														}
														
														?>
														<td class="acf-th-<?php echo $sub_field['name']; ?> field_key-<?php echo $sub_field['key']; ?>" <?php echo $attr; ?>>
															<span><?php echo $sub_field['label'] . $required_label; ?></span>
															<?php if( isset($sub_field['instructions']) ): ?>
																<span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
															<?php endif; ?>
														</td><?php
													endforeach; ?>
												</tr>
											</thead>
										<?php endif; ?>
										<tbody>
											<tr>
											<?php

											// layout: Row
											
											if( $current_layout['display'] == 'row' ): ?>
												<td class="acf_input-wrap">
													<table class="widefat acf_input">
											<?php endif; ?>
											
											
											<?php

											// loop though sub fields
											if( $current_layout['sub_fields'] ):
											foreach( $current_layout['sub_fields'] as $sub_field ): ?>
											
												<?php
												
												// attributes (can appear on tr or td depending on $field['layout'])
												$attributes = array(
													'class'				=> "field sub_field field_type-{$sub_field['type']} field_key-{$sub_field['key']}",
													'data-field_type'	=> $sub_field['type'],
													'data-field_key'	=> $sub_field['key'],
													'data-field_name'	=> $sub_field['name']
												);
												
												
												// required
												if( $sub_field['required'] )
												{
													$attributes['class'] .= ' required';
												}
												
												
												// value
												$sub_field['value'] = false;
												
												if( isset($value[ $sub_field['key'] ]) )
												{
													// this is a normal value
													$sub_field['value'] = $value[ $sub_field['key'] ];
												}
												elseif( !empty($sub_field['default_value']) )
												{
													// no value, but this sub field has a default value
													$sub_field['value'] = $sub_field['default_value'];
												}
												
												
												// add name
												$sub_field['name'] = $field['name'] . '[' . $i . '][' . $sub_field['key'] . ']';
												
												
												// clear ID (needed for sub fields to work!)
												//unset( $sub_field['id'] );
												
												
												
												// layout: Row
												
												if( $current_layout['display'] == 'row' ): ?>
													<tr <?php pmai_join_attr( $attributes ); ?>>
														<td class="label">
															<label>
																<?php echo $sub_field['label']; ?>
																<?php if( $sub_field['required'] ): ?><span class="required">*</span><?php endif; ?>
															</label>
															<?php if( isset($sub_field['instructions']) ): ?>
																<span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
															<?php endif; ?>
														</td>
												<?php endif; ?>
												
												<td <?php if( empty($field['layouts'][$key - 1]['display']) or $field['layouts'][$key - 1]['display'] != 'row' ){ pmai_join_attr( $attributes ); } ?>>
													<div class="inner">
													<?php
													
													// create field
													echo pmai_render_field($sub_field, $post, $field_name . "[" . $field['key'] . "][layouts][".$key."]");
													
													?>
													</div>
												</td>
												
												<?php
											
												// layout: Row
												
												if( !empty($field['layouts'][$key - 1]['display']) and $field['layouts'][$key - 1]['display'] == 'row' ): ?>
													</tr>
												<?php endif; ?>
												
											
											<?php endforeach; ?>
											<?php endif; ?>
											<?php

											// layout: Row
											
											if( $current_layout['display'] == 'row' ): ?>
													</table>
												</td>
											<?php endif; ?>
																			
											</tr>
										</tbody>
										
									</table>
									
								</div>								
								<?php endforeach; endif; ?>
							</div>
							<div class="add_layout">
								<select>
									<option selected="selected">Select Layout</option>
									<?php foreach ($field['layouts'] as $key => $layout) {
										?>
										<option value="<?php echo $layout['name'];?>"><?php echo $layout['label'];?></option>
										<?php
									}?>
								</select>
								<a href="javascript:void(0);" class="acf-button delete_layout" style="float:right; margin-top: 10px;"><?php _e("Delete Layout", 'wp_all_import_acf_add_on'); ?></a>
							</div>
						</div>
						<?php
						break;
					
					case 'message':

						break;

					default:
						?>
						<p>
							<?php
								_e('This field type is not supported. E-mail support@soflyy.com with the details of the custom ACF field you are trying to import to, as well as a link to download the plugin to install to add this field type to ACF, and we will investigate the possiblity ot including support for it in the ACF add-on.', 'wp_all_import_acf_add_on');
							?>
						</p>
						<?php
						break;
				}
				?>									
			</div>			
		</div>
		<?php endif; 		
	}
}
?>