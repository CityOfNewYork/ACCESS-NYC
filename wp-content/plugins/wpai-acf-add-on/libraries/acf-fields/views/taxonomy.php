<div class="input">
    <div class="main_choise">
        <input type="radio" id="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="switcher" name="is_multiple_field_value<?php echo $field_name; ?>[<?php echo $field['key'];?>]" value="yes" <?php echo 'no' != $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
        <label for="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="chooser_label"><?php _e("Select value for all records"); ?></label>
    </div>
    <div class="wpallimport-clear"></div>
    <div class="switcher-target-is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes">
        <div class="input sub_input">
            <div class="input">
                <?php

                if (\wpai_acf_add_on\acf\ACFService::isACFNewerThan('5.0.0')){

                    $field_class = 'acf_field_' . $field['type'];

                    $tmp_key = $field['key'];
                    $field['key'] = 'multiple_value'. $field_name .'[' . $field['key'] . ']';
                    $field['value'] = $current_multiple_value;

                    if( $field['field_type'] == 'select' ) {
                        $field['multiple'] = 0;
                        $field['type'] = 'select';
                        $field['choices'] = array(
                            '' => __('Select term')
                        );
                        $terms = get_terms( array(
                            'taxonomy' => $field['taxonomy'],
                            'hide_empty' => false
                        ) );
                        if (!empty($terms)){
                            foreach ($terms as $term){
                                $field['choices'][$term->term_id] = $term->name;
                            }
                        }
                    } elseif( $field['field_type'] == 'multi_select' ) {
                        $field['multiple'] = 1;
                        $field['type'] = 'select';
                        $field['choices'] = array(
                            '' => __('Select term')
                        );
                        $terms = get_terms( array(
                            'taxonomy' => $field['taxonomy'],
                            'hide_empty' => false
                        ) );
                        if (!empty($terms)){
                            foreach ($terms as $term){
                                $field['choices'][$term->term_id] = $term->name;
                            }
                        }
                    }

                    acf_render_field( $field );

                    $field['key'] = $tmp_key;

                } else{

                    $field_class = 'acf_field_' . $field['type'];
                    $new_field = new $field_class();

                    $field['other_choice'] = false;
                    $field['name'] = 'multiple_value'. $field_name .'[' . $field['key'] . ']';
                    $field['value'] = $current_multiple_value;

                    $new_field->create_field( $field );

                }
                ?>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
<div class="input" style="overflow:hidden;">
    <div class="main_choise">
        <input type="radio" id="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="switcher" name="is_multiple_field_value<?php echo $field_name; ?>[<?php echo $field['key'];?>]" value="no" <?php echo 'no' == $current_is_multiple_field_value ? 'checked="checked"': '' ?>/>
        <label for="is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="chooser_label"><?php _e('Set with XPath', 'wp_all_import_acf_add_on' )?></label>
    </div>
    <div class="wpallimport-clear"></div>
    <div class="switcher-target-is_multiple_field_value_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no">
        <div class="input sub_input">
            <div class="input">
                <table class="pmai_taxonomy post_taxonomy">
                    <tr>
                        <td>
                            <div class="col2" style="clear: both;">
                                <ol class="sortable no-margin">
                                    <?php
                                    if (!is_array($current_field)){
                                        $current_field = array(
                                            'value' => $current_field,
                                            'delim' => ','
                                        );
                                    }
                                    if ( ! empty($current_field['value']) ):
                                        $taxonomies_hierarchy = json_decode($current_field['value']);

                                        if ( ! empty($taxonomies_hierarchy) and is_array($taxonomies_hierarchy)): $i = 0; foreach ($taxonomies_hierarchy as $cat) { $i++;
                                            if ( is_null($cat->parent_id) or empty($cat->parent_id) )
                                            {
                                                ?>
                                                <li id="item_<?php echo $i; ?>" class="dragging">
                                                    <div class="drag-element">
                                                        <input type="text" class="widefat xpath_field rad4" value="<?php echo esc_attr($cat->xpath); ?>"/>
                                                    </div>
                                                    <?php if ( $i > 1 ): ?><a href="javascript:void(0);" class="icon-item remove-ico"></a><?php endif; ?>

                                                    <?php echo reverse_taxonomies_html($taxonomies_hierarchy, $cat->item_id, $i); ?>
                                                </li>
                                                <?php
                                            }
                                        }; else:?>
                                            <li id="item_1" class="dragging">
                                                <div class="drag-element" >
                                                    <input type="text" class="widefat xpath_field rad4" value=""/>
                                                    <a href="javascript:void(0);" class="icon-item remove-ico"></a>
                                                </div>
                                            </li>
                                        <?php endif;
                                    else: ?>
                                        <li id="item_1" class="dragging">
                                            <div class="drag-element">
                                                <input type="text" class="widefat xpath_field rad4" value=""/>
                                                <a href="javascript:void(0);" class="icon-item remove-ico"></a>
                                            </div>
                                        </li>
                                    <?php endif;?>
                                    <li id="item" class="template">
                                        <div class="drag-element">
                                            <input type="text" class="widefat xpath_field rad4" value=""/>
                                            <a href="javascript:void(0);" class="icon-item remove-ico"></a>
                                        </div>
                                    </li>
                                </ol>
                                <input type="hidden" class="hierarhy-output" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][value]" value="<?php echo esc_attr($current_field['value']); ?>"/>
                                <div class="input">
                                    <label for=""><?php _e('Separated by'); ?></label>
                                    <input
                                        type="text"
                                        style="width:5%; text-align:center; padding-left: 25px;"
                                        value="<?php echo (!empty($current_field['delim'])) ? esc_attr( $current_field['delim'] ) : ',';?>"
                                        name="fields<?php echo $field_name;?>[<?php echo $field['key'];?>][delim]"
                                        class="small rad4">
                                </div>
                                <div class="delim">
                                    <a href="javascript:void(0);" class="icon-item add-new-ico"><?php _e('Add more','wp_all_import_acf_add_on');?></a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>