<input
    type="text"
    placeholder=""
    value="<?php echo esc_attr( $current_field );?>"
    name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>]"
    class="text datetimepicker widefat rad4"
    style="width:200px;"/>

<a
    href="#help"
    class="wpallimport-help"
    title="<?php _e('Use any format supported by the PHP strtotime function.', 'wp_all_import_acf_add_on'); ?>"
    style="top:0;">?</a>