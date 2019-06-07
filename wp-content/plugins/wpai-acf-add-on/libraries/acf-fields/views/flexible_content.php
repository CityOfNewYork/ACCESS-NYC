<div class="acf-flexible-content">
    <div class="clones">
        <?php
        foreach( $field['layouts'] as $i => $layout ){

            // vars
            $order = is_numeric($i) ? ($i + 1) : 0;

            ?>
            <div class="layout" data-layout="<?php echo sanitize_title($layout['name']); ?>">

                <div style="display:none">
                    <input type="hidden" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][layouts][ROWNUMBER][acf_fc_layout]" value="<?php echo $layout['name']; ?>" />
                </div>

                <div class="acf-fc-layout-handle">
                    <span class="fc-layout-order"><?php echo $order; ?></span>. <?php echo $layout['label']; ?>
                </div>

                <table class="widefat acf-input-table <?php if( $layout['display'] == 'row' ): ?>row_layout<?php endif; ?>">
                    <?php if( $layout['display'] == 'table' ): ?>
                        <thead>
                        <tr>

                            <?php

                            foreach( $layout['sub_fields'] as $sub_field_i => $sub_field):

                                // add width attr
                                $attr = "";

                                if( count($layout['sub_fields']) > 1 && isset($sub_field['column_width']) && $sub_field['column_width'] )
                                {
                                    $attr = 'width="' . $sub_field['column_width'] . '%"';
                                }

                                // required
                                $required_label = "";

                                if( $sub_field['required'] )
                                {
                                    $required_label = ' <span class="required">*</span>';
                                }

                                ?>
                            <td class="acf-th-<?php echo $sub_field['name']; ?> field_key-<?php echo $sub_field['key']; ?>" <?php echo $attr; ?>>
                                <span><?php echo $sub_field['label'] . $required_label; ?></span>
                                <?php if( isset($sub_field['instructions']) ): ?>
                                <span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
                            <?php endif; ?>
                                </td><?php
                            endforeach;
                            ?>
                        </tr>
                        </thead>
                    <?php endif; ?>
                    <tbody>
                    <tr>
                        <?php

                        // layout: Row

                        if( $layout['display'] == 'row' ): ?>
                        <td class="acf_input-wrap">
                            <table class="widefat acf_input">
                                <?php endif; ?>

                                <?php

                                // loop though sub fields
                                if( $layout['sub_fields'] ):
                                    foreach( $layout['sub_fields'] as $sub_field ): ?>

                                        <?php

                                        // attributes (can appear on tr or td depending on $field['layout'])
                                        $attributes = array(
                                            'class'				=> "field sub_field field_type-{$sub_field['type']} field_key-{$sub_field['key']}",
                                            'data-field_type'	=> $sub_field['type'],
                                            'data-field_key'	=> $sub_field['key'],
                                            'data-field_name'	=> $sub_field['name']
                                        );


                                        // required
                                        if( $sub_field['required'] )
                                        {
                                            $attributes['class'] .= ' required';
                                        }


                                        // value
                                        $sub_field['value'] = false;

                                        if( isset($value[ $sub_field['key'] ]) )
                                        {
                                            // this is a normal value
                                            $sub_field['value'] = $value[ $sub_field['key'] ];
                                        }
                                        elseif( !empty($sub_field['default_value']) )
                                        {
                                            // no value, but this sub field has a default value
                                            $sub_field['value'] = $sub_field['default_value'];
                                        }


                                        // add name
                                        $sub_field['name'] = $field['name'] . '[' . $i . '][' . $sub_field['key'] . ']';


                                        // clear ID (needed for sub fields to work!)
                                        //unset( $sub_field['id'] );



                                        // layout: Row

                                        if( $layout['display'] == 'row' ): ?>
                                            <tr <?php pmai_join_attr( $attributes ); ?>>
                                            <td class="label">
                                                <label>
                                                    <?php echo $sub_field['label']; ?>
                                                    <?php if( $sub_field['required'] ): ?><span class="required">*</span><?php endif; ?>
                                                </label>
                                                <?php if( isset($sub_field['instructions']) ): ?>
                                                    <span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>

                                        <td <?php if( $layout['display'] != 'row' ){ pmai_join_attr( $attributes ); } ?>>
                                            <div class="inner">
                                                <?php
                                                \wpai_acf_add_on\acf\fields\FieldFactory::create($sub_field, $post, $field_name . "[" . $field['key'] . "][layouts][ROWNUMBER]")->view();
                                                ?>
                                            </div>
                                        </td>

                                        <?php

                                        // layout: Row

                                        if( $layout['display'] == 'row' ): ?>
                                            </tr>
                                        <?php endif; ?>


                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php
                                // layout: Row

                                if( $layout['display'] == 'row' ): ?>
                            </table>
                        </td>
                    <?php endif; ?>

                    </tr>
                    </tbody>

                </table>

            </div>
            <?php

        }

        ?>
    </div>
    <div class="values ui-sortable">
        <?php if (!empty($current_field['layouts'])) : foreach ($current_field['layouts'] as $key => $layout): if ("ROWNUMBER" == $key) continue; ?>
            <div class="layout" data-layout="<?php if (!empty($field['layouts'][$key]['name'])) echo $field['layouts'][$key]['name']; ?>">

                <div style="display:none">
                    <input type="hidden" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][layouts][<?php echo $key;?>][acf_fc_layout]" value="<?php echo $layout['acf_fc_layout']; ?>" />
                </div>
                <?php
                $current_layout = false;
                foreach ($field['layouts'] as $sub_lay){
                    if ($sub_lay['name'] == $layout['acf_fc_layout']){
                        $current_layout = $sub_lay;
                        break;
                    }
                }
                ?>
                <div class="acf-fc-layout-handle">
                    <span class="fc-layout-order"><?php echo $key; ?></span>. <?php echo $current_layout['label']; ?>
                </div>

                <table class="widefat acf-input-table <?php if( $current_layout['display'] == 'row' ): ?>row_layout<?php endif; ?>">
                    <?php if( $current_layout['display'] == 'table' ): ?>
                        <thead>
                        <tr>
                            <?php foreach( $current_layout['sub_fields'] as $sub_field_i => $sub_field):

                                // add width attr
                                $attr = "";

                                if( count($field['layouts'][$key - 1]['sub_fields']) > 1 && isset($sub_field['column_width']) && $sub_field['column_width'] )
                                {
                                    $attr = 'width="' . $sub_field['column_width'] . '%"';
                                }

                                // required
                                $required_label = "";

                                if( $sub_field['required'] )
                                {
                                    $required_label = ' <span class="required">*</span>';
                                }

                                ?>
                            <td class="acf-th-<?php echo $sub_field['name']; ?> field_key-<?php echo $sub_field['key']; ?>" <?php echo $attr; ?>>
                                <span><?php echo $sub_field['label'] . $required_label; ?></span>
                                <?php if( isset($sub_field['instructions']) ): ?>
                                <span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
                            <?php endif; ?>
                                </td><?php
                            endforeach; ?>
                        </tr>
                        </thead>
                    <?php endif; ?>
                    <tbody>
                    <tr>
                        <?php

                        // layout: Row

                        if( $current_layout['display'] == 'row' ): ?>
                        <td class="acf_input-wrap">
                            <table class="widefat acf_input">
                                <?php endif; ?>


                                <?php

                                // loop though sub fields
                                if( $current_layout['sub_fields'] ):
                                    foreach( $current_layout['sub_fields'] as $sub_field ): ?>

                                        <?php

                                        // attributes (can appear on tr or td depending on $field['layout'])
                                        $attributes = array(
                                            'class'				=> "field sub_field field_type-{$sub_field['type']} field_key-{$sub_field['key']}",
                                            'data-field_type'	=> $sub_field['type'],
                                            'data-field_key'	=> $sub_field['key'],
                                            'data-field_name'	=> $sub_field['name']
                                        );


                                        // required
                                        if( $sub_field['required'] )
                                        {
                                            $attributes['class'] .= ' required';
                                        }


                                        // value
                                        $sub_field['value'] = false;

                                        if( isset($value[ $sub_field['key'] ]) )
                                        {
                                            // this is a normal value
                                            $sub_field['value'] = $value[ $sub_field['key'] ];
                                        }
                                        elseif( !empty($sub_field['default_value']) )
                                        {
                                            // no value, but this sub field has a default value
                                            $sub_field['value'] = $sub_field['default_value'];
                                        }


                                        // add name
                                        $sub_field['name'] = $field['name'] . '[' . $i . '][' . $sub_field['key'] . ']';


                                        // clear ID (needed for sub fields to work!)
                                        //unset( $sub_field['id'] );



                                        // layout: Row

                                        if( $current_layout['display'] == 'row' ): ?>
                                            <tr <?php pmai_join_attr( $attributes ); ?>>
                                            <td class="label">
                                                <label>
                                                    <?php echo $sub_field['label']; ?>
                                                    <?php if( $sub_field['required'] ): ?><span class="required">*</span><?php endif; ?>
                                                </label>
                                                <?php if( isset($sub_field['instructions']) ): ?>
                                                    <span class="sub-field-instructions"><?php echo $sub_field['instructions']; ?></span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>

                                        <td <?php if( empty($field['layouts'][$key - 1]['display']) or $field['layouts'][$key - 1]['display'] != 'row' ){ pmai_join_attr( $attributes ); } ?>>
                                            <div class="inner">
                                                <?php
                                                \wpai_acf_add_on\acf\fields\FieldFactory::create($sub_field, $post, $field_name . "[" . $field['key'] . "][layouts][".$key."]")->view();
                                                ?>
                                            </div>
                                        </td>

                                        <?php

                                        // layout: Row

                                        if( !empty($field['layouts'][$key - 1]['display']) and $field['layouts'][$key - 1]['display'] == 'row' ): ?>
                                            </tr>
                                        <?php endif; ?>


                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php

                                // layout: Row

                                if( $current_layout['display'] == 'row' ): ?>
                            </table>
                        </td>
                    <?php endif; ?>

                    </tr>
                    </tbody>

                </table>

            </div>
        <?php endforeach; endif; ?>
    </div>
    <div class="add_layout">
        <select>
            <option selected="selected">Select Layout</option>
            <?php foreach ($field['layouts'] as $key => $layout) {
                ?>
                <option value="<?php echo sanitize_title($layout['name']);?>"><?php echo $layout['label'];?></option>
                <?php
            }?>
        </select>
        <a href="javascript:void(0);" class="acf-button delete_layout_button" style="float:right; margin-top: 10px;"><?php _e("Delete Layout", 'wp_all_import_acf_add_on'); ?></a>
        <a href="javascript:void(0);" class="acf-button add_layout_button" style="float:right; margin-top: 10px; margin-right: 10px"><?php _e("Add Layout", 'wp_all_import_acf_add_on'); ?></a>
    </div>
</div>