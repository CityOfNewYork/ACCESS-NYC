<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

$errorMessage = __('Unable to Revoke Grace Period', 'wordfence-login-security');
?>
<div class="wfls-add-top wfls-add-bottom wfls-grace-period-container">
	<div class="wfls-grace-period-button-container">
		<button class="wfls-btn wfls-btn-default" id="wfls-revoke-grace-period">
			<?php esc_html_e('Revoke Grace Period', 'wordfence-login-security') ?>
		</button>

	</div>
</div>
<div>
	<p id="wfls-revoke-grace-period-failed" style="display: none"><strong><?php echo esc_html($errorMessage) ?></strong></p>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			var failureMessage = $('#wfls-revoke-grace-period-failed');
			var button = $('#wfls-revoke-grace-period');
			function revoke2faGracePeriod(userId, success, failure) {
				var ajaxContext = (typeof WFLS === 'undefined' ? GWFLS : WFLS);
				ajaxContext.ajax(
					'wordfence_ls_revoke_2fa_grace_period',
					{
						user_id: userId
					},
					success,
					failure
				);
			}
			function handleError() {
				if (typeof WFLS === 'object') {
					WFLS.panelModal(
						(WFLS.screenSize(500) ? '300px' : '400px'),
						<?php echo json_encode($errorMessage) ?>,
						<?php echo json_encode(__('An unexpected error occurred while attempting to revoke the grace period.', 'wordfence-login-security')) ?>
					);
				}
				else {
					failureMessage.show();
				}
				button.prop('disabled', false);
			}
			button.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				button.prop('disabled', true);
				failureMessage.hide();
				revoke2faGracePeriod(
					<?php echo json_encode($user->ID, true) ?>,
					function(data) {
						if ('error' in data) {
							handleError();
							return;
						}
						if (typeof WFLS === 'undefined')
							window.location.href = '#wfls-user-settings';
						window.location.reload();
					},
					handleError
				);
			});
		});
	})(jQuery);
</script>