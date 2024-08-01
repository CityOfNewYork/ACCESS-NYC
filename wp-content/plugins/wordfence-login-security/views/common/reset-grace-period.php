<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

if (!isset($defaultGracePeriod))
	$defaultGracePeriod = \WordfenceLS\Controller_Settings::shared()->get_user_2fa_grace_period();
$defaultGracePeriod = max($defaultGracePeriod, 1);
$errorMessage = $gracePeriod === null ? __('Unable to Activate Grace Period', 'wordfence-login-security') : __('Unable to Reset Grace Period', 'wordfence-login-security');
?>
<div class="wfls-add-top wfls-add-bottom wfls-grace-period-container">
	<div class="wfls-grace-period-input-container">
		<label for="wfls-user-grace-period-override" style="display: none"><?php esc_html_e('Grace Period Override', 'wordfence-login-security') ?></label>
		<input type="text" id="wfls-user-grace-period-override" maxlength="2" pattern="[0-9]+" value="<?php echo (int) $defaultGracePeriod ?>">
		<label for="wfls-user-grace-period-override"><?php esc_html_e('days', 'wordfence-login-security') ?></label>
	</div>
	<div class="wfls-grace-period-button-container">
		<button class="wfls-btn wfls-btn-default" id="wfls-reset-grace-period">
			<?php echo $gracePeriod === null ? esc_html__('Activate Grace Period', 'wordfence-login-security') : esc_html__('Reset Grace Period', 'wordfence-login-security') ?>
		</button>

	</div>
</div>
<div>
	<p id="wfls-reset-grace-period-failed" style="display: none"><strong><?php echo esc_html($errorMessage) ?></strong></p>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			var failureMessage = $('#wfls-reset-grace-period-failed');
			var overrideInput = $('#wfls-user-grace-period-override');
			var button = $('#wfls-reset-grace-period');
			function reset2faGracePeriod(userId, gracePeriodOverride, success, failure) {
				var ajaxContext = (typeof WFLS === 'undefined' ? GWFLS : WFLS);
				ajaxContext.ajax(
					'wordfence_ls_reset_2fa_grace_period',
					{
						user_id: userId,
						grace_period_override: gracePeriodOverride
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
						<?php echo json_encode($gracePeriod === null ? __('An unexpected error occurred while attempting to activate the grace period.', 'wordfence-login-security') : __('An unexpected error occurred while attempting to reset the grace period.', 'wordfence-login-security')) ?>
					);
				}
				else {
					failureMessage.show();
				}
				button.prop('disabled', false);
				overrideInput.prop('disabled', false);
			}
			button.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				button.prop('disabled', true);
				overrideInput.prop('disabled', true);
				failureMessage.hide();
				reset2faGracePeriod(
					<?php echo json_encode($user->ID, true) ?>,
					overrideInput.val(),
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
			overrideInput.on('input', function(e) {
				var value = $(this).val();
				value = value.replace(/[^0-9]/g, '');
				value = parseInt(value);
				if (isNaN(value) || value === 0)
					value = '';
				button.prop('disabled', value < 1);
				$(this).val(value);
			}).trigger('input');
		});
	})(jQuery);
</script>