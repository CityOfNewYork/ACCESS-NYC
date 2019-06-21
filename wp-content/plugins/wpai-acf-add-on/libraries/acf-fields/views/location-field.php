<div class="input">
    <label><?php _e("Address"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['address'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][address]"
        class="text widefat rad4"/>
</div>
<div class="input">
    <label><?php _e("Lat"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['lat'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][lat]"
        class="text widefat rad4"/>
</div>
<div class="input">
    <label><?php _e("Lng"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['lng'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][lng]"
        class="text widefat rad4"/>
</div>