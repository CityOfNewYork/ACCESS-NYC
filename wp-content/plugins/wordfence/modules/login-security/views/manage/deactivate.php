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
				<strong><?php esc_html_e('Wordfence 2FA Active', 'wordfence'); ?></strong>
			</div>
		</div>
	</div>
	<div class="wfls-block-content wfls-padding-add-bottom">
		<p><?php if ($ownAccount) { esc_html_e('Wordfence two-factor authentication is currently active on your account. You may deactivate it by clicking the button below.', 'wordfence'); } else { echo wp_kses(sprintf(/* translators: Username */ __('Wordfence two-factor authentication is currently active on the account <strong>%s</strong>. You may deactivate it by clicking the button below.', 'wordfence'), esc_html($user->user_login)), array('strong'=>array())); } ?></p>
		<p class="wfls-center wfls-add-top"><a href="#" class="wfls-btn wfls-btn-default" id="wfls-deactivate" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Deactivate', 'wordfence'); ?></a></p>
	</div>
</div>
<div style="display: none;">
	<?php
	echo \WordfenceLS\Model_View::create('common/modal-prompt', array(
		'id' => 'wfls-template-deactivate-prompt',
		'title' => __('Deactivate 2FA', 'wordfence'),
		'message' => __('Are you sure you want to deactivate two-factor authentication?', 'wordfence'),
		'primaryButton' => array('class' => 'wfls-deactivate-prompt-cancel', 'label' => __('Cancel', 'wordfence'), 'link' => '#'),
		'secondaryButtons' => array(array('class' => 'wfls-deactivate-prompt-confirm', 'label' => __('Deactivate', 'wordfence'), 'link' => '#')),
	))->render();
	?>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			$('#wfls-deactivate').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				var content = $("#wfls-template-deactivate-prompt").clone().attr('id', null);
				WFLS.standaloneModalHTML(content, { onOpen: function(modal) {
					$(modal).find('.wfls-deactivate-prompt-cancel').on('click', WFLS.closeStandaloneModal);
					$(modal).find('.wfls-deactivate-prompt-confirm').on('click', function(e) {
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
									WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Deactivating 2FA', 'wordfence')); ?>', response.error);
								}
								else {
									WFLS.closeStandaloneModal();
									$('#wfls-deactivation-controls').crossfade($('#wfls-activation-controls'));
								}
							},
							function(error) {
								WFLS.standaloneModal('<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Deactivating 2FA', 'wordfence')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to deactivate two-factor authentication. Please try again.', 'wordfence')); ?>');
							}
						);
					});
				}});
			});
		});
	})(jQuery);
</script> 