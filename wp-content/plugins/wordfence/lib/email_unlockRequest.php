<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<?php echo wp_kses(sprintf(
	/* translators: 1. IP address. 2. Site URL. 3. Site name.  */
		__('Either you or someone else at IP address <b>%1$s</b> requested instructions to regain access to the website <a href="%2$s"><b>%3$s</b></a>.', 'wordfence'), esc_html($IP), esc_attr(wfUtils::getSiteBaseURL()), esc_html($siteName)), array('a'=>array('href'=>array()), 'b'=>array())); ?>
<br><br>
<?php printf(
	/* translators: Localized date.  */
		__('Request was generated at: %s', 'wordfence'), wfUtils::localHumanDate()); ?>
<br><br>
<?php esc_html_e('If you did not request these instructions then you can safely ignore them.', 'wordfence'); ?><br>
<?php echo wp_kses(__('These instructions <b>will be valid for 30 minutes</b> from the time they were sent.', 'wordfence'), array('b'=>array())); ?>
<ul>
	<li>
		<a href="<?php echo $unlockHref; ?>&func=unlockMyIP"><?php esc_html_e('Click here to unlock your ability to sign-in and to access to the site.', 'wordfence'); ?></a> <?php esc_html_e('Do this if you simply need to regain access because you were accidentally locked out. If you received an "Insecure Password" message before getting locked out, you may also need to reset your password.', 'wordfence'); ?> <a href="<?php echo wfSupportController::esc_supportURL(wfSupportController::ITEM_USING_BREACH_PASSWORD); ?>"><?php esc_html_e('Learn More', 'wordfence'); ?></a>
	</li>
	<li>
	<a href="<?php echo $unlockHref; ?>&func=unlockAllIPs"><?php esc_html_e('Click here to unblock all IP addresses.', 'wordfence'); ?></a> <?php esc_html_e('Do this if you still can\'t regain access using the link above. It causes everyone who is blocked or locked out to be able to access your site again.', 'wordfence'); ?>
	</li>
	<li>
	<a href="<?php echo $unlockHref; ?>&func=disableRules"><?php esc_html_e('Click here to unlock all IP addresses and disable the Wordfence Firewall and Wordfence login security for all users', 'wordfence'); ?></a>. <?php esc_html_e('Do this if you keep getting locked out or blocked and can\'t access your site. You can re-enable login security and the firewall once you sign-in to the site by visiting the Wordfence Firewall menu, clicking and then turning on the firewall and login security options. If you use country blocking, you will also need to choose which countries to block.', 'wordfence'); ?>
	</li>
</ul>