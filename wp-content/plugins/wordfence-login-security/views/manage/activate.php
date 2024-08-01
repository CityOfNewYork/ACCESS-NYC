<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WordfenceLS\Model_2faInitializationData $initializationData The initialization data for setting up 2FA for a specific user. Required.
 */
$user = $initializationData->get_user();
$recovery = $initializationData->get_recovery_codes();
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
	<div class="wfls-block-header wfls-block-header-border-bottom">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title">
				<strong><?php esc_html_e('2. Enter Code from Authenticator App', 'wordfence-login-security'); ?></strong>
			</div>
		</div>
	</div>
	<div class="wfls-block-content wfls-padding-add-bottom">
		<p><?php esc_html_e('Download Recovery Codes', 'wordfence-login-security'); ?> <em class="wfls-text-small"><?php esc_html_e('Optional', 'wordfence-login-security'); ?></em></p>
		<p><?php echo esc_html(sprintf(__('Use one of these %d codes to log in if you lose access to your authenticator device. Codes are %d characters long plus optional spaces. Each one may be used only once.', 'wordfence-login-security'), count($recovery), \WordfenceLS\Model_Crypto::strlen($recovery[0]) * 2)); ?></p>
		<ul class="wfls-recovery-codes">
			<?php
			$recoveryCodeFileContents = sprintf(__('Two-Factor Authentication Recovery Codes - %s (%s)', 'wordfence-login-security'), home_url(), $user->user_login) . "\r\n";
			$recoveryCodeFileContents .= "\r\n" . sprintf(__('Each line of %d letters and numbers is a single recovery code, with optional spaces for readability. To use a recovery code, after entering your username and password, enter the code like "1234 5678 90AB CDEF" at the 2FA prompt. If your site has a custom login prompt and does not show a 2FA prompt, you can use the single-step method by entering your password and the code together in the Password field, like "mypassword1234 5678 90AB CDEF". Your recovery codes are:', 'wordfence-login-security'), \WordfenceLS\Model_Crypto::strlen($recovery[0]) * 2) . "\r\n\r\n";
			foreach ($recovery as $c) {
				$hex = bin2hex($c);
				$blocks = str_split($hex, 4);
				echo '<li>' . implode(' ', $blocks) . '</li>';
				$recoveryCodeFileContents .= implode(' ', $blocks) . "\r\n";
			}
			?>
		</ul>
		<p class="wfls-center"><a href="#" class="wfls-btn wfls-btn-default" id="wfls-recovery-download" target="_blank" rel="noopener noreferrer"><i class="dashicons dashicons-download"></i> <?php esc_html_e('Download', 'wordfence-login-security'); ?></a></p>
		
		<hr class="wfls-half">
		
		<p><?php esc_html_e('Enter the code from your authenticator app below to verify and activate two-factor authentication for this account.', 'wordfence-login-security'); ?></p>
		<p><input type="text" id="wfls-activate-field" value="" size="6" maxlength="6" placeholder="123456" autocomplete="off"></p>
	</div>
	<div class="wfls-block-footer">
		<div class="wfls-block-footer-content">
			<div class="wfls-block-title" id="wfls-activation-help-link-container">
				<a href="<?php echo \WordfenceLS\Controller_Support::esc_supportURL(\WordfenceLS\Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('For help on setting up an app, visit our help article.', 'wordfence-login-security'); ?></a>
			</div>
			<div class="wfls-block-footer-action"><a href="#" id="wfls-activate" class="wfls-btn wfls-btn-default wfls-disabled"><?php esc_html_e('Activate', 'wordfence-login-security'); ?></a></div>
		</div>
	</div>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			$('#wfls-activate-field').on('keyup', function(e) {
				$('#wfls-activate').toggleClass('wfls-disabled', $('#wfls-activate-field').val().length != 6);
				
				if (e.keyCode == 13) {
					$('#wfls-activate').trigger('click');
				}
			});
			
			$('#wfls-recovery-download').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				saveAs(new Blob(["<?php echo str_replace("\n", "\\n", str_replace("\r", "\\r", addslashes($recoveryCodeFileContents))); ?>"], {type: "text/plain;charset=" + document.characterSet}), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(preg_replace('~^https?://~i', '', home_url())) . '_' . \WordfenceLS\Text\Model_JavaScript::esc_js($user->user_login) . '_recoverycodes.txt'; ?>');
				WFLS.savedRecoveryCodes = true;
			});
			
			$('#wfls-activate').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				if (WFLS.userIsActivating) { //Likely a double-click
					return;
				}
				
				WFLS.userIsActivating = true;
				
				var payload = {
					secret: '<?php echo bin2hex($initializationData->get_raw_secret()); ?>',
					recovery: ['<?php echo implode('\', \'', array_map(function($c) { return bin2hex($c); }, $recovery)); ?>'],
					code: $('#wfls-activate-field').val(),
					user: <?php echo $user->ID; ?>,
				};

				WFLS.ajax(
					'wordfence_ls_activate', 
					payload,
					function(response) {
						if (response.error) {
							WFLS.userIsActivating = false;
							WFLS.panelModal((WFLS.screenSize(500) ? '300px' : '400px'), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Activating 2FA', 'wordfence-login-security')); ?>', response.error, {includeDefaultButtons: true});
						}
						else {
							$('#wfls-activation-controls').crossfade($('#wfls-deactivation-controls'));
							$('#wfls-recovery-code-count').text(response.text);
							$('#wfls-activate-field').val('');

							$('.wfls-notice[data-notice-type="wfls-will-be-required"]').find('.wfls-dismiss-link').trigger('click');
							
							if (!WFLS.savedRecoveryCodes) {
								var prompt = $('#wfls-tmpl-recovery-skipped-prompt').tmpl({});
								var promptHTML = $("<div />").append(prompt).html();
								WFLS.panelHTML((WFLS.screenSize(500) ? '300px' : '400px'), promptHTML, { fixed: true, overlayClose: false, closeButton: false, className: 'wfls-modal', onComplete: function() {
									$('#wfls-recovery-skipped-download').on('click', function(e) {
										e.preventDefault();
										e.stopPropagation();
										saveAs(new Blob(["<?php echo str_replace("\n", "\\n", str_replace("\r", "\\r", addslashes($recoveryCodeFileContents))); ?>"], {type: "text/plain;charset=" + document.characterSet}), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(preg_replace('~^https?://~i', '', home_url())) . '_' . \WordfenceLS\Text\Model_JavaScript::esc_js($user->user_login) . '_recoverycodes.txt'; ?>');
										WFLS.panelClose();
									});
									$('#wfls-recovery-skipped-skip').on('click', function(e) {
										e.preventDefault();
										e.stopPropagation();
										WFLS.panelClose();
									});
								}});
							}
							WFLS.savedRecoveryCodes = false;
							WFLS.userIsActivating = false;
						}
					},
					function(error) {
						WFLS.panelModal((WFLS.screenSize(500) ? '300px' : '400px'), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Activating 2FA', 'wordfence-login-security')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to activate two-factor authentication. Please try again.', 'wordfence-login-security')); ?>', {includeDefaultButtons: true});
						WFLS.userIsActivating = false;
					}
				);
			});
		});
	})(jQuery);
</script>
<script type="text/x-jquery-template" id="wfls-tmpl-recovery-skipped-prompt">
	<?php
	echo \WordfenceLS\Model_View::create('common/modal-prompt', array(
		'title' => __('Download Recovery Codes', 'wordfence-login-security'),
		'message' => __('Reminder: If you lose access to your authenticator device, you can use recovery codes to log in. If you have not saved a copy of your recovery codes, we recommend downloading them now.', 'wordfence-login-security'),
		'primaryButton' => array('id' => 'wfls-recovery-skipped-download', 'label' => __('Download', 'wordfence-login-security'), 'link' => '#'),
		'secondaryButtons' => array(array('id' => 'wfls-recovery-skipped-skip', 'label' => __('Skip', 'wordfence-login-security'), 'link' => '#')),
	))->render();
	?>
</script>