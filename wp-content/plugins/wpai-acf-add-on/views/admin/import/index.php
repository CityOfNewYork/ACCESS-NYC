<div class="wpallimport-collapsed closed pmai_options">
	<div class="wpallimport-content-section">
		<div class="wpallimport-collapsed-header">
			<h3><?php _e('Advanced Custom Fields Add-On','wp_all_import_acf_add_on');?></h3>
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<table class="form-table" style="max-width:none;">
					<tr>
						<td colspan="3">
							<?php 

							global $acf;

							$acfs = array(); //apply_filters('acf/get_field_groups', array());							

							if ($acf and version_compare($acf->settings['version'], '5.0.0') >= 0){

								$saved_acfs = get_posts(array('posts_per_page' => -1, 'post_type' => 'acf-field-group'));								

								$acfs = acf_local()->groups;								

							}
							else{

								$acfs = apply_filters('acf/get_field_groups', array());	

							}

							if ( ! empty($saved_acfs)){
								foreach ($saved_acfs as $key => $obj) {
									if ( ! isset($acfs[$obj->post_name]))
									{
										$acfs[] = array(
											'ID' => $obj->ID,
											'title' => $obj->post_title
										);
									}									
								}
							}							

							if ( ! empty($acfs) ){
								foreach ($acfs as $key => $acfObj) {
									if (empty($acfs[$key]['ID']) and ! empty($acfs[$key]['key'])){
										$acfs[$key]['ID'] = $acfs[$key]['key'];								
									}
									elseif (empty($acfs[$key]['ID']) and !empty($acfs[$key]['id'])){
										$acfs[$key]['ID'] = $acfs[$key]['id'];								
									}
								}							

								?>
								<p>
									<strong><?php _e("Please choose your Field Groups.",'wp_all_import_acf_add_on');?></strong>
								</p>								
								<ul>
									<?php 
									foreach ($acfs as $key => $acfObj) {
										$is_show_acf_group = apply_filters('wp_all_import_acf_is_show_group', true, $acfObj);
										?>
										<li>
											<input type="hidden" name="acf[<?php echo $acfObj['ID'];?>]" value="<?php echo $is_show_acf_group ? '0' : '1'?>"/>
											<?php if ($is_show_acf_group): ?>
											<input id="acf_<?php echo $post_type . '_' . $acfObj['ID'];?>" type="checkbox" name="acf[<?php echo $acfObj['ID'];?>]" <?php if ( ! empty($post['acf'][$acfObj['ID']]) ): ?>checked="checked"<?php endif; ?> value="1" rel="<?php echo $acfObj['ID'];?>" class="pmai_acf_group"/>
											<label for="acf_<?php echo $post_type . '_' . $acfObj['ID']; ?>"><?php echo $acfObj['title']; ?></label>
											<?php endif; ?>
										</li>
										<?php
									}

									PMXI_Plugin::$session->set('acf_groups', $acfs);
				    				PMXI_Plugin::$session->save_data();

									?>
								</ul>
								<div class="acf_groups"></div>								
								<?php
							}
							else{
								?>
								<p><strong><?php _e("Please create Field Groups.",'wp_all_import_acf_add_on');?></strong></p>
								<?php	
							}			
							?>					
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>