<?php

if (\wpai_acf_add_on\acf\ACFService::isACFNewerThan('5.0.0')){

    if (!empty($field['sub_fields'])){
        foreach ($field['sub_fields'] as $key => $sub_field){ ?>
            <tr class="field sub_field field_type-<?php echo $sub_field['type'];?> field_key-<?php echo $sub_field['key'];?>">
                <td class="label">
                    <?php echo $sub_field['label'];?>
                </td>
                <td>
                    <div class="inner">
                        <?php
                        \wpai_acf_add_on\acf\fields\FieldFactory::create($sub_field, $post, $field_name . "[" . $field['key'] . "][rows][ROWNUMBER]")->view();
                        ?>
                    </div>
                </td>
            </tr>
            <?php
        }
    }
    elseif (!empty($field['sub_field'])){
        ?>
        <tr class="field sub_field field_type-<?php echo $field['sub_field']['type'];?> field_key-<?php echo $field['sub_field']['key'];?>">
            <td>
                <div class="inner">
                    <?php
                    \wpai_acf_add_on\acf\fields\FieldFactory::create($field['sub_field'], $post, $field_name)->view();
                    ?>
                </div>
            </td>
        </tr>
        <?php
    }

}
else {
    ?>
    <p>
        <?php
        _e('This field type is not supported. E-mail support@wpallimport.com with the details of the custom ACF field you are trying to import to, as well as a link to download the plugin to install to add this field type to ACF, and we will investigate the possiblity ot including support for it in the ACF add-on.', 'wp_all_import_acf_add_on');
        ?>
    </p>
    <?php
}