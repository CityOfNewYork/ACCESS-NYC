<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<div class="wpallexport-collapsed wpallexport-section">
	<div class="wpallexport-content-section" style="margin-top:10px;">
		<div class="wpallexport-collapsed-header" style="padding-left: 25px;">
			<h3><?php esc_html_e('Configure Advanced Settings','wp_all_export_plugin');?></h3>
		</div>
		<div class="wpallexport-collapsed-content" style="padding: 0;">
			<div class="wpallexport-collapsed-content-inner">				
				<table class="form-table" style="max-width:none;">
					<tr>
						<td colspan="3">																									
							<div class="input" style="margin:5px 0px;">
								<label for="records_per_request"><?php esc_html_e('In each iteration, process', 'wp_all_export_plugin');?> <input type="text" name="records_per_iteration" class="wp_all_export_sub_input" style="width: 40px;" value="<?php echo esc_attr($post['records_per_iteration']) ?>" /> <?php esc_html_e('records', 'wp_all_export_plugin'); ?></label>
								<a href="#help" class="wpallexport-help" style="position: relative; top: -2px;" title="<?php esc_html_e('WP All Export must be able to process this many records in less than your server\'s timeout settings. If your export fails before completion, to troubleshoot you should lower this number.', 'wp_all_export_plugin'); ?>">?</a>
							</div>
							<div class="input" style="margin:5px 0px;">
								<input type="hidden" name="export_only_new_stuff" value="0" />
								<input type="checkbox" id="export_only_new_stuff" name="export_only_new_stuff" value="1" <?php echo $post['export_only_new_stuff'] ? 'checked="checked"': '' ?> disabled="disabled"/>
								<label for="export_only_new_stuff" disabled="disabled"><?php printf(esc_html__('Only export %s once', 'wp_all_export_plugin'), empty($post['cpt']) ? __('records', 'wp_all_export_plugin') : esc_html(wp_all_export_get_cpt_name($post['cpt']))); ?></label>
								<a href="#help" class="wpallexport-help" style="position: relative; top: -2px;" title="<?php esc_html_e('If re-run, this export will only include records that have not been previously exported.<br><br><strong>Upgrade to the Pro edition of WP All Export to use this option.</strong>', 'wp_all_export_plugin'); ?>">?</a>
							</div>
							<div class="input" style="margin:5px 0px;">
								<input type="hidden" name="export_only_modified_stuff" value="0" />
								<input type="checkbox" id="export_only_modified_stuff" name="export_only_modified_stuff" value="1" <?php echo $post['export_only_modified_stuff'] ? 'checked="checked"': '' ?> disabled="disabled"/>
								<label for="export_only_modified_stuff" disabled="disabled"><?php printf(esc_html__('Only export %s that have been modified since last export', 'wp_all_export_plugin'), empty($post['cpt']) ? __('records', 'wp_all_export_plugin') : esc_html(wp_all_export_get_cpt_name($post['cpt'], 2, $post))); ?></label>
								<a href="#help" class="wpallexport-help" style="position: relative; top: -2px;" title="<?php esc_html_e('If re-run, this export will only include records that have been modified since last export run.<br><br><strong>Upgrade to the Pro edition of WP All Export to use this option.</strong>', 'wp_all_export_plugin'); ?>">?</a>
							</div>
							<div class="input" style="margin:5px 0px;">
								<input type="hidden" name="include_bom" value="0" />
								<input type="checkbox" id="include_bom" name="include_bom" value="1" <?php echo $post['include_bom'] ? 'checked="checked"': '' ?> />
								<label for="include_bom"><?php esc_html_e('Include BOM in export file', 'wp_all_export_plugin') ?></label>
								<a href="#help" class="wpallexport-help" style="position: relative; top: -2px;" title="<?php esc_html_e('The BOM will help some programs like Microsoft Excel read your export file if it includes non-English characters.', 'wp_all_export_plugin'); ?>">?</a>
							</div>
							<div class="input" style="margin:5px 0px;">
								<input type="hidden" name="creata_a_new_export_file" value="0" />
								<input type="checkbox" id="creata_a_new_export_file" name="creata_a_new_export_file" value="1" <?php echo $post['creata_a_new_export_file'] ? 'checked="checked"': '' ?> />
								<label for="creata_a_new_export_file"><?php esc_html_e('Create a new file each time export is run', 'wp_all_export_plugin') ?></label>
								<a href="#help" class="wpallexport-help" style="position: relative; top: -2px;" title="<?php esc_html_e('If disabled, the export file will be overwritten every time this export run.', 'wp_all_export_plugin'); ?>">?</a>
							</div>							
							<div class="input" style="margin:5px 0px;">
								<input type="hidden" name="split_large_exports" value="0" />
								<input type="checkbox" id="split_large_exports" name="split_large_exports" class="switcher" value="1" <?php echo $post['split_large_exports'] ? 'checked="checked"': '' ?> />
								<label for="split_large_exports"><?php esc_html_e('Split large exports into multiple files', 'wp_all_export_plugin') ?></label>
								<span class="switcher-target-split_large_exports pl17" style="display:block; clear: both; width: 100%;">
									<div class="input pl17" style="margin:5px 0px;">							
										<label for="records_per_request"><?php esc_html_e('Limit export to', 'wp_all_export_plugin');?></label> <input type="text" name="split_large_exports_count" class="wp_all_export_sub_input" style="width: 50px;" value="<?php echo esc_attr($post['split_large_exports_count']) ?>" /> <?php esc_html_e('records per file', 'wp_all_export_plugin'); ?>
									</div>																				
								</span>			
							</div>
                            <div class="input" style="margin:5px 0px;">
                                <input type="hidden" name="allow_client_mode" value="0"/>
                                <input type="checkbox" disabled="disabled" id="allow_client_mode" name="allow_client_mode"
                                       value="1" <?php echo (isset($post['allow_client_mode']) && $post['allow_client_mode']) ? 'checked="checked"' : '' ?> />
                                <label for="allow_client_mode"><?php esc_html_e('Allow non-admins to run this export in Client Mode', 'wp_all_export_plugin') ?></label>
                                <span>
                                    <a href="#help" class="wpallexport-help" style="position: relative; top: 0;" title="<?php esc_html_e('When enabled, users with access to Client Mode will be able to run this export and download the export file. Go to All Export > Settings to give users access to Client Mode. <br><br><strong>Upgrade to the Pro edition of WP All Export to use this option.</strong>'); ?>">?</a>
							    </span>
                            </div>
							<br>
							<hr>
							<p style="text-align:right;">
								<div class="input">
									<label for="save_import_as" style="width: 103px;"><?php esc_html_e('Friendly Name:','wp_all_export_plugin');?></label>
									<input type="text" name="friendly_name" title="<?php esc_html_e('Save friendly name...', 'pmxi_plugin') ?>" style="vertical-align:middle; background:#fff !important;" value="<?php echo wp_all_export_clear_xss(esc_attr($post['friendly_name'])); ?>" />
								</div>
							</p>
						</td>
					</tr>											
				</table>
			</div>
		</div>
	</div>
</div>	