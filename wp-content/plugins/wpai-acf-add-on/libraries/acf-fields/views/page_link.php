<input
    type="text"
    placeholder=""
    value="<?php echo esc_attr( $current_field );?>"
    name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>]"
    class="text w95 widefat rad4"/>

<a
    href="#help"
    class="wpallimport-help"
    title="<?php _e('Enter the ID, slug, or Title. Separate multiple entries with commas.', 'wp_all_import_acf_add_on'); ?>"
    style="top:0;">?</a>