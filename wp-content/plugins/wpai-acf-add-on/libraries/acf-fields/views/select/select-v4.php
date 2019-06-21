<div class="switcher-target-is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes">
    <div class="input sub_input">
        <div class="input">
            <?php
                $field_class = 'acf_field_' . $field['type'];
                $new_field = new $field_class();
                $field['other_choice'] = false;
                $field['name'] = 'multiple_value'. $field_name .'[' . $field['key'] . ']';
                $field['value'] = $current_multiple_value;
                $field['prefix'] = '';
                $new_field->create_field( $field );
            ?>
        </div>
    </div>
</div>