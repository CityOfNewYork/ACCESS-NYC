<div class="input">
    <p class="label"><?php _e("Address"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['address'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Lat"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['lat'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][lat]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Lng"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['lng'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][lng]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Zoom"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['zoom'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][zoom]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Street Number"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['street_number'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][street_number]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Street name"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['street_name'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][street_name]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Street short name"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['street_short_name'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][street_short_name]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("City"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['city'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][city]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("State"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['state'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][state]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("State short"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['state_short'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][state_short]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Post code"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['post_code'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][post_code]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Country"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['country'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][country]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Country short"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['country_short'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][country_short]" class="text widefat rad4"/>
    </div>
</div>
<div class="input">
    <p class="label"><?php _e("Place ID"); ?></p>
    <div class="acf-input-wrap">
        <input type="text" placeholder="" value="<?php echo esc_attr( $current_field['place_id'] );?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][place_id]" class="text widefat rad4"/>
    </div>
</div>
<div class="wpallimport-collapsed wpallimport-section wpallimport-sub-options wpallimport-dependent-options">
    <div class="wpallimport-content-section wpallimport-bottom-radius">
        <div style="padding: 0px; display: block;" class="wpallimport-collapsed-content">
            <div class="wpallimport-collapsed-content-inner">
                <label for="realhomes_addonaddress_geocode">Google Geocode API Settings</label>
                <div class="input">
                    <div class="form-field wpallimport-radio-field wpallimport-realhomes_addonaddress_geocode_address_no_key">
                        <input type="radio" <?php if (empty($current_field['address_geocode']) or esc_attr( $current_field['address_geocode'] ) == 'address_no_key'):?>checked="checked"<?php endif;?> value="address_no_key" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_geocode]" class="switcher" id="<?php echo sanitize_key($field_name); ?>_geocode_address_no_key">
                        <label for="<?php echo sanitize_key($field_name); ?>_geocode_address_no_key" class="chooser_label">No API Key</label>
                        <a style="position: relative; top: -2px;" class="wpallimport-help" href="#help" original-title="Limited number of requests.">?</a>
                    </div>
                    <div class="form-field wpallimport-radio-field wpallimport-<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_developers">
                        <input type="radio" value="address_google_developers" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_geocode]" class="switcher" id="<?php echo sanitize_key($field_name); ?>_geocode_address_google_developers" <?php if (esc_attr( $current_field['address_geocode'] ) == 'address_google_developers'):?>checked="checked"<?php endif;?> >
                        <label for="<?php echo sanitize_key($field_name); ?>_geocode_address_google_developers" class="chooser_label">Google Developers API Key - <a href="https://developers.google.com/maps/documentation/geocoding/#api_key">Get free API key</a></label>
                        <a style="position: relative; top: -2px;" class="wpallimport-help" href="#help" original-title="Up to 2500 requests per day and 5 requests per second.">?</a>
                        <div class="switcher-target-<?php echo sanitize_key($field_name); ?>_geocode_address_google_developers" style="display: none;">
                            <div class="input sub_input">
                                <label for="<?php echo sanitize_key($field_name); ?>_google_developers_api_key">API Key</label>
                                <div class="input">
                                    <input type="text" style="width:100%;" value="<?php echo esc_attr( $current_field['address_google_developers_api_key'] );?>" id="<?php echo sanitize_key($field_name); ?>_google_developers_api_key" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_google_developers_api_key]">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-field wpallimport-radio-field wpallimport-<?php echo $field_name; ?>_<?php echo $field_name; ?>_geocode_address_google_for_work">
                        <input type="radio" value="address_google_for_work" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_geocode]" class="switcher" <?php if (esc_attr( $current_field['address_geocode'] ) == 'address_google_for_work'):?>checked="checked"<?php endif;?> id="<?php echo sanitize_key($field_name); ?>_geocode_address_google_for_work">
                        <label for="<?php echo sanitize_key($field_name); ?>_geocode_address_google_for_work" class="chooser_label">Google for Work Client ID &amp; Digital Signature - <a href="https://developers.google.com/maps/documentation/business">Sign up for Google for Work</a></label>
                        <a style="position: relative; top: -2px;" class="wpallimport-help" href="#help" original-title="Up to 100,000 requests per day and 10 requests per second">?</a>
                        <div class="switcher-target-<?php echo sanitize_key($field_name); ?>_geocode_address_google_for_work" style="display: none;">
                            <div class="input sub_input">
                                <label for="<?php echo sanitize_key($field_name); ?>_google_for_work_client_id">Google for Work Client ID</label>
                                <div class="input">
                                    <input type="text" style="width:100%;" value="<?php echo esc_attr( $current_field['address_google_for_work_client_id'] );?>" id="<?php echo sanitize_key($field_name); ?>_google_for_work_client_id" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_google_for_work_client_id]">
                                </div>
                                <label for="<?php echo $field_name; ?>_<?php echo $field_name; ?>_google_for_work_digital_signature">Google for Work Digital Signature</label>
                                <div class="input">
                                    <input type="text" style="width:100%;" value="<?php echo esc_attr( $current_field['address_google_for_work_digital_signature'] );?>" id="<?php echo sanitize_key($field_name); ?>_google_for_work_digital_signature" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address_google_for_work_digital_signature]">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>