<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents the persistent banner.
 *
 * This banner may be shown on non-Wordfence pages.
 */
?>
<ul id="wf-onboarding-banner">
	<li><?php esc_html_e('Wordfence installation is incomplete', 'wordfence'); ?></li>
	<li>
		<?php if (isset($dismissable) && $dismissable): ?>
			<a href="#" class="wf-onboarding-btn wf-onboarding-btn-default" id="wf-onboarding-delay" data-timestamp="<?php echo time(); ?>"><?php esc_html_e('Remind Me Later', 'wordfence'); ?></a>
		<?php endif ?>
		<a href="<?php echo esc_attr(network_admin_url('admin.php?page=WordfenceSupport')); ?>" class="wf-onboarding-btn wf-onboarding-btn-default" id="wf-onboarding-resume"><?php esc_html_e('Resume Installation', 'wordfence'); ?></a>
	</li>
</ul>
