<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents the fresh install plugin header for standalone installations.
 */
?>
<div id="wfls-onboarding-standalone-modal">
	<div id="wfls-onboarding-standalone-modal-header">
		<div id="wfls-onboarding-standalone-modal-header-title"><?php esc_html_e('Wordfence Login Security Installed', 'wordfence-login-security'); ?></div>
		<div id="wfls-onboarding-standalone-modal-header-accessory"><a href="#" id="wfls-onboarding-standalone-modal-dismiss">&times;</a></div>
	</div>
	<div id="wfls-onboarding-standalone-modal-content">
		<p><?php esc_html_e('You have just installed the Wordfence Login Security plugin. It contains a subset of the functionality found in the full Wordfence plugin: Two-factor Authentication, XML-RPC Protection and Login Page CAPTCHA.', 'wordfence-login-security'); ?></p>
		<p><?php printf(__('If you\'re looking for a more comprehensive solution, the <a href="%s" target="_blank" rel="noopener noreferrer">full Wordfence plugin</a> includes all of the features in this plugin as well as a full-featured WordPress firewall, a security scanner, live traffic, and more. The standard installation includes a robust set of free features that can be upgraded via a Premium license key.', 'wordfence-login-security'), 'https://wordpress.org/plugins/wordfence/'); ?></p>
	</div>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			$('#wfls-onboarding-standalone-modal-dismiss').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				$('#wfls-onboarding-standalone-modal').slideUp(400, function() {
					$('#wfls-onboarding-standalone-modal').remove();
				});
				
				WFLS.setOptions({'dismissed-fresh-install-modal': true});
			});
		});
	})(jQuery);
</script>
