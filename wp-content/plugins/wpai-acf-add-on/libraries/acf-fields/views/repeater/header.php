<div class="repeater">
    <div class="input" style="margin-bottom: 10px;">
        <div class="input">
            <input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="no" <?php echo 'yes' != $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
            <label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="chooser_label"><?php _e('Fixed Repeater Mode', 'wp_all_import_acf_add_on' )?></label>
        </div>
        <div class="input">
            <input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="yes" <?php echo 'yes' == $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
            <label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="chooser_label"><?php _e('Variable Repeater Mode (XML)', 'wp_all_import_acf_add_on' )?></label>
        </div>
        <div class="input">
            <input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes_csv" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="csv" <?php echo 'csv' == $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
            <label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes_csv" class="chooser_label"><?php _e('Variable Repeater Mode (CSV)', 'wp_all_import_acf_add_on' )?></label>
        </div>
        <div class="input sub_input">
            <input type="hidden" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_ignore_empties]" value="0"/>
            <input type="checkbox" value="1" id="is_ignore_empties<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_ignore_empties]" <?php if ( ! empty($current_field['is_ignore_empties'])) echo 'checked="checked';?>/>
            <label for="is_ignore_empties<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>"><?php _e('Ignore blank fields', 'wp_all_import_acf_add_on'); ?></label>
            <a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('If the value of the element or column in your file is blank, it will be ignored. Use this option when some records in your file have a different number of repeating elements than others.', 'wp_all_import_acf_add_on') ?>">?</a>
        </div>
        <div class="wpallimport-clear"></div>
        <div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes">
            <div class="input sub_input">
                <div class="input">
                    <p>
                        <?php printf(__("For each %s do ..."), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][foreach]" value="'. esc_html($current_field["foreach"]) .'" class="pmai_foreach widefat rad4"/>'); ?>
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