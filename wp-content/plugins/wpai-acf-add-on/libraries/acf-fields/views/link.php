<div class="input">
    <label><?php _e("Title"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['title'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][title]"
        class="text widefat rad4"/>
</div>
<div class="input">
    <label><?php _e("URL"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['url'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][url]"
        class="text widefat rad4"/>

    <a
        href="#help"
        class="wpallimport-help"
        title="<?php _e('Use external URL or post ID, slug or title to link to that post.', 'wp_all_import_acf_add_on'); ?>"
        style="top:0;">?</a>

</div>
<div class="input">
    <label><?php _e("Target"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['target'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][target]"
        class="text widefat rad4"/>
</div>