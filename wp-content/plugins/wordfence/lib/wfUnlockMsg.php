<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<p><?php wfI18n::esc_html_e('If you are a WordPress user with administrative privileges on this site please enter your email in the box below and click "Send". You will then receive an email that helps you regain access.', 'wordfence'); ?></p>
<form method="POST" id="unlock-form" action="#">
	<?php require_once(ABSPATH . 'wp-includes/pluggable.php'); ?>
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wf-form'); ?>">
	<input type="text" size="50" name="email" id="unlock-email" value="" maxlength="255" placeholder="email@example.com">&nbsp;&nbsp;<input type="submit" class="wf-btn wf-btn-default" id="unlock-submit" name="s" value="<?php wfI18n::esc_html_e('Send Unlock Email', 'wordfence'); ?>" disabled>
</form>
<script type="application/javascript">
	(function() {
		var textfield = document.getElementById('unlock-email');
		textfield.addEventListener('focus', function() {
			document.getElementById('unlock-form').action = "<?php echo esc_js(wfUtils::getSiteBaseURL()); ?>" + "?_wfsf=unlockEmail";
			document.getElementById('unlock-submit').disabled = false;
		});
	})();
</script>