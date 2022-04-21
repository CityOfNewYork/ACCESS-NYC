<?php
$custom_type = get_taxonomy($post['taxonomy_type']);
if (empty($custom_type)){
	$custom_type = new stdClass();
	$custom_type->labels = new stdClass();
	$custom_type->labels->name = __('Taxonomy Terms', 'wp_all_import_plugin');
	$custom_type->labels->singular_name = __('Taxonomy Term', 'wp_all_import_plugin');
}
?>
<div class="wpallimport-collapsed closed wpallimport-section ">
	<div class="wpallimport-content-section ">
		<div class="wpallimport-collapsed-header">
			<h3><?php printf(__('Other %s Options','wp_all_import_plugin'), $custom_type->labels->singular_name);?></h3>	
		</div>
		<div class="wpallimport-collapsed-content" style="padding: 0;">
			<div class="wpallimport-collapsed-content-inner">
				<table class="form-table" style="max-width:none;">
					<?php if ($custom_type->hierarchical): ?>
					<tr>
						<td>
							<h4><?php _e('Parent Term', 'wp_all_import_plugin'); ?> <a class="wpallimport-help" href="#help" style="position:relative; top:-1px;" title="If your taxonomies have parent/child relationships, use this field to set the parent for the imported taxonomy term. Terms can be matched by slug, name, or ID.">?</a></h4>
							<div>
								<input type="text" name="taxonomy_parent" style="width:100%;" value="<?php echo esc_attr($post['taxonomy_parent']); ?>" placeholder="<?php echo __('Parent term', 'wp_all_import_plugin'); ?>"/>
							</div>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td>					
							<input type="hidden" name="encoding" value="<?php echo ($this->isWizard) ? PMXI_Plugin::$session->encoding : $post['encoding']; ?>"/>
							<input type="hidden" name="delimiter" value="<?php echo ($this->isWizard) ? PMXI_Plugin::$session->is_csv : $post['delimiter']; ?>"/>
							
							<h4><?php printf(__('%s Slug', 'wp_all_import_plugin'), $custom_type->labels->singular_name); ?></h4>
							<div class="input">
								<input type="radio" id="taxonomy_slug_auto" name="taxonomy_slug" value="auto" <?php echo 'auto' == $post['taxonomy_slug'] ? 'checked="checked"' : '' ?> class="switcher"/>
								<label for="taxonomy_slug_auto"><?php _e('Set slug automatically', 'wp_all_import_plugin') ?></label>
							</div>
							<div class="input fleft" style="position:relative;width:220px;">
								<input type="radio" id="taxonomy_slug_xpath" class="switcher" name="taxonomy_slug" value="xpath" <?php echo 'xpath' == $post['taxonomy_slug'] ? 'checked="checked"': '' ?>/>
								<label for="taxonomy_slug_xpath"><?php _e('Set slug manually', 'wp_all_import_plugin' )?></label> <br>
								<div class="switcher-target-taxonomy_slug_xpath">
									<div class="input">
										&nbsp;<input type="text" class="smaller-text" name="taxonomy_slug_xpath" style="width:190px;" value="<?php echo esc_attr($post['taxonomy_slug_xpath']) ?>" placeholder="<?php echo __('Term Slug', 'wp_all_import_plugin'); ?>"/>
										<a href="#help" class="wpallimport-help" title="<?php _e('The term slug must be unique. If the slug is already in use by another term, WP All Import will add a number to the end of the slug.', 'wp_all_import_plugin') ?>" style="position:relative; top:13px; float: right;">?</a>
									</div>
								</div>
							</div>								
							<div class="clear"></div>
                            <?php if ($custom_type->name == 'product_cat'): ?>
                                <h4><?php printf(__('%s Display Type', 'wp_all_import_plugin'), $custom_type->labels->singular_name); ?></h4>
                                <div class="input">
                                    <input type="radio" id="taxonomy_display_type_default" name="taxonomy_display_type" value="" <?php echo empty($post['taxonomy_display_type']) ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="taxonomy_display_type_default"><?php _e('Default', 'wp_all_import_plugin') ?></label>
                                </div>
                                <div class="input">
                                    <input type="radio" id="taxonomy_display_type_products" name="taxonomy_display_type" value="products" <?php echo "products" == $post['taxonomy_display_type'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="taxonomy_display_type_products"><?php _e('Products', 'wp_all_import_plugin') ?></label>
                                </div>
                                <div class="input">
                                    <input type="radio" id="taxonomy_display_type_subcategories" name="taxonomy_display_type" value="subcategories" <?php echo "subcategories" == $post['taxonomy_display_type'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="taxonomy_display_type_subcategories"><?php _e('Subcategories', 'wp_all_import_plugin') ?></label>
                                </div>
                                <div class="input">
                                    <input type="radio" id="taxonomy_display_type_both" name="taxonomy_display_type" value="both" <?php echo "both" == $post['taxonomy_display_type'] ? 'checked="checked"' : '' ?> class="switcher"/>
                                    <label for="taxonomy_display_type_both"><?php _e('Both', 'wp_all_import_plugin') ?></label>
                                </div>
                                <div class="input fleft" style="position:relative;width:220px;">
                                    <input type="radio" id="taxonomy_display_type_xpath" class="switcher" name="taxonomy_display_type" value="xpath" <?php echo 'xpath' == $post['taxonomy_display_type'] ? 'checked="checked"': '' ?>/>
                                    <label for="taxonomy_display_type_xpath"><?php _e('Set display type manually', 'wp_all_import_plugin' )?></label> <br>
                                    <div class="switcher-target-taxonomy_display_type_xpath">
                                        <div class="input">
                                            &nbsp;<input type="text" class="smaller-text" name="taxonomy_display_type_xpath" style="width:190px;" value="<?php echo esc_attr($post['taxonomy_display_type_xpath']) ?>" placeholder="<?php echo __('Term Display Type', 'wp_all_import_plugin'); ?>"/>
                                            <a href="#help" class="wpallimport-help" title="<?php _e('The term display type should be empty or one of the following: products, subcategories, both.', 'wp_all_import_plugin') ?>" style="position:relative; top:13px; float: right;">?</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
						</td>
					</tr>
				</table>
			</div>
		</div>
	</div>
</div>