<div class="input">
    <div class="main_choise">
        <input type="radio" id="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="switcher" name="is_multiple_field_value<?php echo $field_name; ?>[<?php echo $field['key'];?>]" value="yes" <?php echo 'no' != $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
        <label for="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="chooser_label"><?php _e("Select value for all records", 'wp_all_import_acf_add_on'); ?></label>
    </div>
    <div class="wpallimport-clear"></div>
