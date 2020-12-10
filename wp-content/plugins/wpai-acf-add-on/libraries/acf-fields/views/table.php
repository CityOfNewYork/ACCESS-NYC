<div class="repeater">
	<div class="input">
        <div class="input" style="margin-left: 4px;">
            <input type="hidden" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][use_headers]" value="0"/>
            <input type="checkbox" value="1" class="switcher" id="use_headers<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][use_headers]" <?php if ( ! empty($current_field['use_headers'])) echo 'checked="checked';?>/>
            <label for="use_headers<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>"><?php _e('Import headers', 'wp_all_import_acf_add_on'); ?></label>
            <a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('If the value of the element or column in your file is blank, it will be ignored.', 'wp_all_import_acf_add_on') ?>">?</a>
            <div class="wpallimport-clear"></div>
            <div class="switcher-target-use_headers<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>">
                <div class="input sub_input">
                    <div class="input">
                        <p>
					        <?php printf(__("Comma separated table headers %s"), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][headers]" value="'. ( (empty($current_field["headers"])) ? '' : $current_field["headers"] ) .'" class="widefat rad4"/>'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="input" style="margin-bottom: 10px;">
		<div class="input">
			<input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="no" <?php echo 'yes' != $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
			<label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no" class="chooser_label"><?php _e('Fixed Repeater Mode', 'wp_all_import_acf_add_on' )?></label>
		</div>
		<div class="input">
			<input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="yes" <?php echo 'yes' == $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
			<label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes" class="chooser_label"><?php _e('Variable Repeater Mode (XML)', 'wp_all_import_acf_add_on' )?></label>
		</div>
		<div class="input">
			<input type="radio" id="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes_csv" class="switcher variable_repeater_mode" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_variable]" value="csv" <?php echo 'csv' == $current_field['is_variable'] ? 'checked="checked"': '' ?>/>
			<label for="is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes_csv" class="chooser_label"><?php _e('Variable Repeater Mode (CSV)', 'wp_all_import_acf_add_on' )?></label>
		</div>
		<div class="input sub_input">
			<input type="hidden" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_ignore_empties]" value="0"/>
			<input type="checkbox" value="1" id="is_ignore_empties<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>" name="fields<?php echo $field_name; ?>[<?php echo $field['key'];?>][is_ignore_empties]" <?php if ( ! empty($current_field['is_ignore_empties'])) echo 'checked="checked';?>/>
			<label for="is_ignore_empties<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>"><?php _e('Ignore empty rows', 'wp_all_import_acf_add_on'); ?></label>
			<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('If the value of the element or column in your file is blank, it will be ignored. Use this option when some records in your file have a different number of repeating elements than others.', 'wp_all_import_acf_add_on') ?>">?</a>
		</div>
		<div class="wpallimport-clear"></div>
		<div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no">
			<div class="input sub_input">
				<div class="input">
					<p>
						<?php printf(__("Cell separator Character %s"), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][cell_separator]" value="'. ( (empty($current_field["cell_separator"])) ? ',' : $current_field["cell_separator"] ) .'" class="pmai_variable_separator widefat rad4"/>'); ?>
						<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('Use this option when importing a CSV file with a column or columns that contains the repeating data, separated by separators. For example, if you had a repeater with two fields - image URL and caption, and your CSV file had two columns, image URL and caption, with values like \'url1,url2,url3\' and \'caption1,caption2,caption3\', use this option and specify a comma as the separator.', 'wp_all_import_acf_add_on') ?>">?</a>
					</p>
				</div>
			</div>
		</div>
		<div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes">
			<div class="input sub_input">
				<div class="input">
					<p>
						<?php printf(__("For each %s do ..."), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][foreach]" value="'. esc_html($current_field["foreach"]) .'" class="pmai_foreach widefat rad4"/>'); ?>
						<a href="http://www.wpallimport.com/documentation/advanced-custom-fields/repeater-fields/" target="_blank"><?php _e('(documentation)', 'wp_all_import_acf_add_on'); ?></a>
					</p>
				</div>
			</div>
			<div class="input sub_input">
				<div class="input">
					<p>
						<?php printf(__("Cell separator Character %s"), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][cell_separator]" value="'. ( (empty($current_field["cell_separator"])) ? ',' : $current_field["cell_separator"] ) .'" class="pmai_variable_separator widefat rad4"/>'); ?>
						<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('Use this option when importing a CSV file with a column or columns that contains the repeating data, separated by separators. For example, if you had a repeater with two fields - image URL and caption, and your CSV file had two columns, image URL and caption, with values like \'url1,url2,url3\' and \'caption1,caption2,caption3\', use this option and specify a comma as the separator.', 'wp_all_import_acf_add_on') ?>">?</a>
					</p>
				</div>
			</div>
		</div>
		<div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_yes_csv">
			<div class="input sub_input">
				<div class="input">
					<p>
						<?php printf(__("Row separator Character %s"), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][row_separator]" value="'. ( (empty($current_field["row_separator"])) ? '|' : $current_field["row_separator"] ) .'" class="pmai_variable_separator widefat rad4"/>'); ?>
						<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('Use this option when importing a CSV file with a column or columns that contains the repeating data, separated by separators. For example, if you had a repeater with two fields - image URL and caption, and your CSV file had two columns, image URL and caption, with values like \'url1,url2,url3\' and \'caption1,caption2,caption3\', use this option and specify a comma as the separator.', 'wp_all_import_acf_add_on') ?>">?</a>
					</p>
				</div>
			</div>
			<div class="input sub_input">
				<div class="input">
					<p>
						<?php printf(__("Cell separator Character %s"), '<input type="text" name="fields' . $field_name . '[' . $field["key"] . '][cell_separator]" value="'. ( (empty($current_field["cell_separator"])) ? ',' : $current_field["cell_separator"] ) .'" class="pmai_variable_separator widefat rad4"/>'); ?>
						<a href="#help" class="wpallimport-help" style="top:0;" title="<?php _e('Use this option when importing a CSV file with a column or columns that contains the repeating data, separated by separators. For example, if you had a repeater with two fields - image URL and caption, and your CSV file had two columns, image URL and caption, with values like \'url1,url2,url3\' and \'caption1,caption2,caption3\', use this option and specify a comma as the separator.', 'wp_all_import_acf_add_on') ?>">?</a>
					</p>
				</div>
			</div>
		</div>
	</div>

	<table class="widefat acf-input-table row_layout">
		<tbody>
		<?php
		if (!empty($current_field['rows'])) : foreach ($current_field['rows'] as $key => $row): if ("ROWNUMBER" == $key) continue; ?>
			<tr class="row">
				<td class="order" style="padding:8px;"><?php echo $key; ?></td>
				<td class="acf_input-wrap" style="padding:0 !important;">
					<input type="text" name="fields<?php echo $field_name . "[" . $field['key'] . "][rows][" . $key . "]"; ?>" value="<?php echo $row; ?>"/>
				</td>
			</tr>
		<?php endforeach; endif; ?>
		<tr class="row-clone">
			<td class="order" style="padding:8px;"></td>
			<td class="acf_input-wrap" style="padding:0 !important;">
				<input type="text" name="fields<?php echo $field_name . "[" . $field['key'] . "][rows][ROWNUMBER]"; ?>"/>
			</td>
		</tr>
		</tbody>
	</table>


	<div class="wpallimport-clear"></div>
	<div class="switcher-target-is_variable_<?php echo str_replace(array('[',']'), '', $field_name);?>_<?php echo $field['key'];?>_no">
		<div class="input sub_input">
			<ul class="hl clearfix repeater-footer">
				<li class="right">
					<a href="javascript:void(0);" class="acf-button delete_row" style="margin-left:15px;"><?php _e('Delete Row', 'wp_all_import_acf_add_on'); ?></a>
				</li>
				<li class="right">
					<a class="add-row-end acf-button" href="javascript:void(0);"><?php _e("Add Row", 'wp_all_import_acf_add_on');?></a>
				</li>
			</ul>
		</div>
	</div>
</div>