<div class="input">
    <label><?php _e("Item Name"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['item_name'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][item_name]"
        class="text widefat rad4"/>
</div>
<div class="input">
    <label><?php _e("Item Description"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['item_description'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][item_description]"
        class="text widefat rad4"/>
</div>
<div class="input">
    <label><?php _e("Price"); ?></label>
    <input
        type="text"
        placeholder=""
        value="<?php echo esc_attr( $current_field['price'] );?>"
        name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][price]"
        class="text widefat rad4"/>
</div>