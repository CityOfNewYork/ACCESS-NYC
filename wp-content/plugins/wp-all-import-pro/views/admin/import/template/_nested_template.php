<?php $custom_type = get_post_type_object( $post_type ); ?>
<?php

$l10n = array(
	'queue_limit_exceeded' => 'You have attempted to queue too many files.',
	'file_exceeds_size_limit' => 'This file exceeds the maximum upload size for this site.',
	'zero_byte_file' => 'This file is empty. Please try another.',
	'invalid_filetype' => 'This file type is not allowed. Please try another.',
	'default_error' => 'An error occurred in the upload. Please try again later.',
	'missing_upload_url' => 'There was a configuration error. Please contact the server administrator.',
	'upload_limit_exceeded' => 'You may only upload 1 file.',
	'http_error' => 'HTTP Error: Click here for our <a href="http://www.wpallimport.com/documentation/advanced/troubleshooting/" target="_blank">troubleshooting guide</a>, or ask your web host to look in your error_log file for an error that takes place at the same time you are trying to upload a file.',
	'upload_failed' => 'Upload failed.',
	'io_error' => 'IO error.',
	'security_error' => 'Security error.',
	'file_cancelled' => 'File canceled.',
	'upload_stopped' => 'Upload stopped.',
	'dismiss' => 'Dismiss',
	'crunching' => 'Crunching&hellip;',
	'deleted' => 'moved to the trash.',
	'error_uploading' => 'has failed to upload due to an error',
	'cancel_upload' => 'Cancel upload',
	'dismiss' => 'Dismiss'
);

?>
<script type="text/javascript">
	var plugin_url = '<?php echo WP_ALL_IMPORT_ROOT_URL; ?>';
	var swfuploadL10n = <?php echo json_encode($l10n); ?>;
</script>

<div class="wpallimport-collapsed closed nested_options wpallimport-section">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php _e('Nested XML/CSV files','wp_all_import_plugin');?></h3>	
		</div>
		<div class="wpallimport-collapsed-content">
			<table class="form-table" style="max-width:none;">
				<tr>
					<td>						
						<div class="nested_files">
							<ul>
								<?php if ( ! empty($post['nested_files'])): ?>
									<?php 
										$nested_files = json_decode($post['nested_files'], true);
										foreach ($nested_files as $key => $file) {
										?>
										<li rel="<?php echo $key;?>"><?php echo $file;?> <a href="javascript:void(0);" class="unmerge"><?php _e('remove', 'wp_all_import_plugin'); ?></a></li>
										<?php
									}?>
								<?php endif; ?>
							</ul>
							<input type="hidden" value="<?php echo esc_attr($post['nested_files']); ?>" name="nested_files"/>
						</div>				
						<div class="nested_xml">						
							<div class="input" style="margin-left:15px;">							
								<input type="hidden" name="nested_local_path"/>
								<input type="hidden" name="nested_source_path"/>
								<input type="hidden" name="nested_root_element"/>
								<div class="nested_msgs"></div>
							</div>						
						</div>								
						<div class="clear"></div>	
						<div class="add_nested_file">
							
							<div class="msgs"></div>
							
							<div class="file-type-options">
								<label><?php _e('Specify the URL of the nested file to use.', 'wp_all_import_plugin'); ?></label>
								<input type="text" class="regular-text" name="nested_url" value="" style="width:100%; line-height:20px;" placeholder="http(s)://"/>
							</div>

							<a rel="parse" href="javascript:void(0);" class="parse"><?php _e('Add', 'wp_all_import_plugin'); ?></a>
						</div>											
					</td>
				</tr>							
			</table>
		</div>
	</div>
</div>