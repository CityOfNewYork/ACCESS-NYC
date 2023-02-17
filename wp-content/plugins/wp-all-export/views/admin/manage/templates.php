<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<div class="wrap download-import-templates">
	<h2><?php esc_html_e('Download Import Templates', 'wp_all_export_plugin') ?></h2>
	<p class="description"><?php esc_html_e('Download your import templates and use them to import your exported file to a separate WordPress/WP All Import installation.', 'wp_all_export_plugin'); ?></p>
	<p class="description"><?php esc_html_e('Install these import templates in your separate WP All Import installation from the <i>All Import › Settings</i> page by clicking the "Import Templates" button.', 'wp_all_export_plugin'); ?></p>
	<p class="submit-buttons">
		<a class="button-primary" href='<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'get_template', '_wpnonce' => wp_create_nonce( '_wpnonce-download_template' )), $this->baseUrl));?>'>Download</a>
	</p>
	<img src="<?php echo PMXE_ROOT_URL; ?>/static/img/import-templates.png" width="400px" style="border: 1px solid #aaa;">
	<a href="http://soflyy.com/" target="_blank" class="wpallexport-created-by"><?php esc_html_e('Created by', 'wp_all_export_plugin'); ?> <span></span></a>
</div>