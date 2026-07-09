<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<?php echo wp_kses(sprintf(
	/* translators: 1. IP address. 2. Site URL. 3. Site name.  */
		__('Either you or someone at IP address <b>%1$s</b> requested an alert unsubscribe link for the website <a href="%2$s"><b>%3$s</b></a>.', 'wordfence'), esc_html($IP), esc_attr($siteURL), esc_html($siteName)), array('a'=>array('href'=>array()), 'b'=>array())); ?>
<br><br>
<?php echo esc_html(sprintf(
	/* translators: Localized date.  */
		__('Request was generated at: %s', 'wordfence'), wfUtils::localHumanDate())); ?>
<br><br>
<?php esc_html_e('If you did not request this, you can safely ignore it.', 'wordfence'); ?>
<br><br>
<?php echo wp_kses(sprintf(
	/* translators: URL to WordPress admin panel. */
		__('<a href="%s" target="_blank">Click here<span class="screen-reader-text"> (opens in new tab)</span></a> to stop receiving security alerts.', 'wordfence'), wfUtils::getSiteBaseURL() . '?_wfsf=removeAlertEmail&jwt=' . $jwt), array('a'=>array('href'=>array(), 'target'=>array()))); ?>