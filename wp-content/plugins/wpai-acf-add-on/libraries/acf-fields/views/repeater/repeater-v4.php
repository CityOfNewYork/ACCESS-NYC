<table class="widefat acf-input-table row_layout">
    <tbody>
    <?php
    if (!empty($current_field['rows'])) : foreach ($current_field['rows'] as $key => $row): if ("ROWNUMBER" == $key) continue; ?>
        <tr class="row">
            <td class="order" style="padding:8px;"><?php echo $key; ?></td>
            <td class="acf_input-wrap" style="padding:0 !important;">
                <table class="widefat acf_input" style="border:none;">
                    <tbody>
                    <?php
                    if (!empty($fields)){
                        /** @var \wpai_acf_add_on\acf\fields\Field $subField */
                        foreach ($fields as $subField){
                            ?>
                            <tr class="field sub_field field_type-<?php echo $subField->getType();?> field_key-<?php echo $subField->getFieldKey();?>">
                                <td class="label">
                                    <?php echo $subField->getFieldLabel();?>
                                </td>
                                <td>
                                    <div class="inner input">
                                        <?php
                                        $subField->setFieldInputName($field_name . "[" . $field['key'] . "][rows][" . $key . "]");
                                        $subField->view();
                                        ?>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </td>
        </tr>
    <?php endforeach; endif; ?>
    <tr class="row-clone">
        <td class="order" style="padding:8px;"></td>
        <td class="acf_input-wrap" style="padding:0 !important;">
            <table class="widefat acf_input" style="border:none;">
                <tbody>
                <?php
                if (!empty($fields)){
                    /** @var \wpai_acf_add_on\acf\fields\Field $subField */
                    foreach ($fields as $subField){
                        ?>
                        <tr class="field sub_field field_type-<?php echo $subField->getType();?> field_key-<?php echo $subField->getFieldKey();?>">
                            <td class="label">
                                <?php echo $subField->getFieldLabel();?>
                            </td>
                            <td>
                                <div class="inner input">
                                    <?php
                                    $subField->setFieldInputName($field_name . "[" . $field['key'] . "][rows][ROWNUMBER]");
                                    $subField->view();
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
