<div class="field field_type-<?php echo $field['type'];?> field_key-<?php echo $field['key'];?>">
    <p class="label">
        <label>
            <?php echo (in_array($field['type'], array('message', 'tab', 'accordion'))) ? $field['type'] : ((empty($field['label']) ? '' : $field['label']));?>
        </label>
    </p>
    <div class="wpallimport-clear"></div>
    <div class="acf-input-wrap">