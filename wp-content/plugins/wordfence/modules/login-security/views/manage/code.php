<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WordfenceLS\Model_2faInitializationData $initializationData The initialization data for setting up 2FA for a specific user. Required.
 */
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
	<div class="wfls-block-header wfls-block-header-border-bottom">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title">
				<strong><?php esc_html_e('1. Scan Code or Enter Key', 'wordfence'); ?></strong>
			</div>
		</div>
	</div>
	<div class="wfls-block-content wfls-padding-add-bottom">
		<p><?php esc_html_e('Scan the code below with your authenticator app to add this account. Some authenticator apps also allow you to type in the text version instead.', 'wordfence') ?></p>
		<div id="wfls-qr-code"></div>
		<p class="wfls-center wfls-no-bottom"><input id="wfls-qr-code-text" class="wfls-center" type="text" value="<?php echo esc_attr($initializationData->get_base32_secret()); ?>" onclick="this.select();" size="32" readonly></p>
	</div>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			var narrowPreviously = null;
			function renderQrCode() {
				var narrow = WFLS.screenSize(500);
				if (narrow !== narrowPreviously) {
					$('#wfls-qr-code').empty().qrcode({text: '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js($initializationData->get_otp_url()); ?>', width: (narrow ? 175 : 256), height: (narrow ? 175 : 256)});
					$('#wfls-qr-code-text').css('font-family', narrow ? '' : 'monospace');
				}
				narrowPreviously = narrow;
			}
			$(window).on('resize', renderQrCode);
			renderQrCode();
		});
	})(jQuery);
</script> 