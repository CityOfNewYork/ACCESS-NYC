<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
?>
<div id="wf-install-incomplete-overlay"></div>
<div id="wf-install-incomplete-message">
	<div id="wf-install-incomplete-message-inner">
		<p><?php esc_html_e('You must install a license to continue using Wordfence.', 'wordfence'); ?></p>
		<p><a href="<?php echo esc_attr(network_admin_url('admin.php?page=WordfenceSupport')); ?>" class="wf-btn wf-btn-default" role="button"><?php esc_html_e('Resume Installation', 'wordfence'); ?></a></p>
	</div>
</div>
