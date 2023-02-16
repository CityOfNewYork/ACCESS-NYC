<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<h2><?php esc_html_e('Delete Export', 'pmxe_plugin') ?></h2>

<form method="post">
	<p><?php echo wp_kses_post(sprintf(__('Are you sure you want to delete <strong>%s</strong> export?', 'pmxe_plugin'), wp_all_export_clear_xss(esc_html($item->friendly_name)))); ?></p>
	<p class="submit">
		<?php wp_nonce_field('delete-export', '_wpnonce_delete-export') ?>
		<input type="hidden" name="is_confirmed" value="1" />
		<input type="submit" class="button-primary" value="Delete" />
	</p>
	
</form>