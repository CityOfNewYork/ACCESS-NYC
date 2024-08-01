<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WP_User $user The user being edited. Required.
 */

$ownAccount = false;
$ownUser = wp_get_current_user();
if ($ownUser->ID == $user->ID) {
	$ownAccount = true;
}
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
	<div class="wfls-block-header wfls-block-header-border-bottom">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title">
				<strong><?php esc_html_e('Wordfence 2FA Active', 'wordfence-login-security'); ?></strong>
			</div>
		</div>
	</div>
	<div class="wfls-block-content wfls-padding-add-bottom">
		<p><?php if ($ownAccount) { esc_html_e('Wordfence two-factor authentication is currently active on your account. You may deactivate it by clicking the button below.', 'wordfence-login-security'); } else { echo wp_kses(sprintf(__('Wordfence two-factor authentication is currently active on the account <strong>%s</strong>. You may deactivate it by clicking the button below.', 'wordfence-login-security'), esc_html($user->user_login)), array('strong'=>array())); } ?></p>
		<p class="wfls-center wfls-add-top"><a href="#" class="wfls-btn wfls-btn-default" id="wfls-deactivate" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Deactivate', 'wordfence-login-security'); ?></a></p>
	</div>
</div>
<script type="text/x-jquery-template" id="wfls-tmpl-deactivate-prompt">
	<?php
	echo \WordfenceLS\Model_View::create('common/modal-prompt', array(
		'title' => __('Deactivate 2FA', 'wordfence-login-security'),
		'message' => __('Are you sure you want to deactivate two-factor authentication?', 'wordfence-login-security'),
		'primaryButton' => array('id' => 'wfls-deactivate-prompt-cancel', 'label' => __('Cancel', 'wordfence-login-security'), 'link' => '#'),
		'secondaryButtons' => array(array('id' => 'wfls-deactivate-prompt-confirm', 'label' => __('Deactivate', 'wordfence-login-security'), 'link' => '#')),
	))->render();
	?>
</script>
<script type="application/javascript">
	(function($) {
		$(function() {
			$('#wfls-deactivate').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				var prompt = $('#wfls-tmpl-deactivate-prompt').tmpl({});
				var promptHTML = $("<div />").append(prompt).html();
				WFLS.panelHTML((WFLS.screenSize(500) ? '300px' : '400px'), promptHTML, {overlayClose: false, closeButton: false, className: 'wfls-modal', onComplete: function() {
					$('#wfls-deactivate-prompt-cancel').on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();

						WFLS.panelClose();
					});

					$('#wfls-deactivate-prompt-confirm').on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();

						var payload = {
							user: <?php echo (int) $user->ID; ?>,
						};

						WFLS.ajax(
							'wordfence_ls_deactivate',
							payload,
							function(response) {
								if (response.error) {
									WFLS.panelModal((WFLS.screenSize(500) ? '300px' : '400px'), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Deactivating 2FA', 'wordfence-login-security')); ?>', response.error);
								}
								else {
									$('#wfls-deactivation-controls').crossfade($('#wfls-activation-controls'));
								}

								WFLS.panelClose(); //The prompt
							},
							function(error) {
								WFLS.panelModal((WFLS.screenSize(500) ? '300px' : '400px'), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Deactivating 2FA', 'wordfence-login-security')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to deactivate two-factor authentication. Please try again.', 'wordfence-login-security')); ?>');
								WFLS.panelClose(); //The prompt
							}
						);
					});
				}});
			});
		});
	})(jQuery);
</script> 