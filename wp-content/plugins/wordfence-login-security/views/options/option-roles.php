<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

use WordfenceLS\Controller_Settings;
use WordfenceLS\Text\Model_JavaScript;

$states = array(
	Controller_Settings::STATE_2FA_DISABLED => __('Disabled', 'wordfence-login-security'),
	Controller_Settings::STATE_2FA_OPTIONAL => __('Optional', 'wordfence-login-security'),
	Controller_Settings::STATE_2FA_REQUIRED => __('Required', 'wordfence-login-security')
);

$gracePeriod = Controller_Settings::shared()->get_int(Controller_Settings::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD, Controller_Settings::DEFAULT_REQUIRE_2FA_USER_GRACE_PERIOD);
$woocommerceIntegrationEnabled = Controller_Settings::shared()->get_bool(\WordfenceLS\Controller_Settings::OPTION_ENABLE_WOOCOMMERCE_INTEGRATION);

$requiredRoles = array();
foreach ($options as $option) {
	if ($option['state'] === Controller_Settings::STATE_2FA_REQUIRED) {
		$requiredRoles[$option['role']] = $option['title'];
	}
}

$customerRoleWarning = __('Requiring 2FA for customers is not recommended as some customers may experience difficulties setting up or using two-factor authentication. Instead, using the "Optional" mode for users with the customer role is recommended which will allow customers to enable 2FA, but will not require them to do so.', 'wordfence-login-security');

?>
<ul class="wfls-option wfls-option-2fa-roles">
	<li class="wfls-option-title">
		<label><?php esc_html_e('2FA Roles', 'wordfence-login-security') ?></label>
	</li>
	<li class="wfls-option-content">
		<ul>
		<?php foreach ($options as $option): ?>
		<?php $selectId = 'wfls-2fa-role-' . $option['name']; ?>
		<li>
			<label for="<?php echo esc_attr($selectId) ?>"><?php echo esc_html($option['title']) ?></label>
			<select id="<?php echo esc_attr($selectId) ?>" name="<?php echo esc_attr($option['name']) ?>" class="wfls-option-select">
				<?php foreach ($states as $key => $label): ?>
				<?php if (!$option['allow_disabling'] && $key === Controller_Settings::STATE_2FA_DISABLED) continue; ?>
				<option
					value="<?php echo esc_attr($key); ?>"
					<?php if($option['state'] === $key): ?> selected<?php endif ?>
					<?php if(!$option['editable']): ?> disabled<?php endif ?>
				>
					<?php echo esc_html($label) ?>
				</option>
				<?php endforeach ?>
			</select>
		</li>
		<?php endforeach ?>
		</ul>
		<p id="wfls-customer-2fa-required-warning" class="wfls-notice" style="display: none;"><?php echo esc_html($customerRoleWarning) ?></p>
		<?php if ($hasWoocommerce && !$woocommerceIntegrationEnabled): ?>
			<p class="wfls-woocommerce-customer-integration-message"><small><?php esc_html_e('In order to use 2FA with the WooCommerce customer role, you must either enable the "WooCommerce integration" option or use the "wordfence_2fa_management" shortcode to provide customers with access to the 2FA management interface. The default interface is only available through WordPress admin pages which are not accessible to users in the customer role.', 'wordfence-login-security') ?></small></p>
		<?php endif ?>
	</li>
	<li class="wfls-2fa-grace-period-container">
		<label for="wfls-2fa-grace-period" class="wfls-primary-label"><?php esc_html_e('Grace Period', 'wordfence-login-security') ?></label>
		<input id="wfls-2fa-grace-period" type="text" pattern="[0-9]+" value="<?php echo (int)$gracePeriod; ?>" class="wfls-option-input wfls-option-input-required" name="<?php echo esc_html(Controller_Settings::OPTION_REQUIRE_2FA_USER_GRACE_PERIOD) ?>" maxlength="2">
		<label for="wfls-2fa-grace-period"><?php esc_html_e('days', 'wordfence-login-security') ?></label>
		<div id="wfls-grace-period-zero-warning" style="display: none;">
			<strong><?php esc_html_e('Setting the grace period to 0 will prevent users in roles where 2FA is required, including newly created users, from logging in if they have not already enabled two-factor authentication.', 'wordfence-login-security') ?></strong>
			<a href="<?php echo esc_attr(\WordfenceLS\Controller_Support::esc_supportURL(\WordfenceLS\Controller_Support::ITEM_MODULE_LOGIN_SECURITY_ROLES)) ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More', 'wordfence-login-security') ?></a>
		</div>
		<small><?php esc_html_e('For roles that require 2FA, users will have this many days to set up 2FA. Failure to set up 2FA during this period will result in the user losing account access. This grace period will apply to new users from the time of account creation. For existing users, this grace period will apply relative to the time at which the requirement is implemented. This grace period will not automatically apply to admins and must be manually enabled for each admin user.', 'wordfence-login-security') ?></small>
	</li>
	<?php if (!empty($requiredRoles)): ?>
	<li class="wfls-2fa-notification-action">
		<h4><?php esc_html_e('2FA Notifications', 'wordfence-login-security') ?></h4>
		<p>
			<small><?php esc_html_e('Send an email to users with the selected role to notify them of the grace period for enabling 2FA. Select the desired role and optionally specify the URL to be sent in the email to setup 2FA. If left blank, the URL defaults to the standard wordpress login and Wordfence’s Two-Factor Authentication plugin page. For example, if using WooCommerce, input the relative URL of the account page.', 'wordfence-login-security') ?></small>
			<a href="<?php echo \WordfenceLS\Controller_Support::esc_supportURL(\WordfenceLS\Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA_NOTIFICATIONS) ?>" target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="<?php echo \WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o'; ?>" aria-hidden="true"></i></a>
		</p>
		<div>
			<label><?php esc_html_e('2FA Role', 'wordfence-login-security') ?></label>
			<select id="wfls-grace-period-notification-role">
				<?php foreach ($requiredRoles as $role => $label): ?>
				<option value="<?php echo esc_attr($role) ?>"><?php echo esc_html($label) ?></option>
				<?php endforeach ?>
			</select>
		</div>
		<div>
			<label><?php esc_html_e('2FA Relative URL (optional)', 'wordfence-login-security') ?></label>
			<input id="wfls-grace-period-notification-url" type="text" placeholder="ex: /my-account/">
		</div>
		<button class="wfls-btn wfls-btn-default wfls-btn-sm" id="wfls-send-grace-period-notification"><?php esc_html_e('Notify', 'wordfence-login-security') ?></button>
	</li>
	<?php endif ?>
</ul>
<script>
	(function($) {
		function sendGracePeriodNotification(notifyAll) {
			var request = {
				role: $('#wfls-grace-period-notification-role').val(),
				url: $('#wfls-grace-period-notification-url').val(),
			};
			if (typeof notifyAll !== "undefined" && notifyAll)
				request.notify_all = true;
			WFLS.ajax('wordfence_ls_send_grace_period_notification', request, 
				function(response) {
					if (response.error) {
						var settings = {
							additional_buttons: []
						};
						if (response.limit_exceeded) {
							settings.additional_buttons.push({
								label: '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Send Anyway', 'wordfence-login-security')); ?>',
								id: 'wfls-send-grace-period-notification-over-limit'
							});
						}
						WFLS.panelModal((WFLS.screenSize(500) ? '300px' : '400px'), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Sending Notification', 'wordfence-login-security')); ?>', response.error, settings);
					}
					else {
						WFLS.panelModal((WFLS.screenSize(500) ? '300px' : '400px'), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Notification Sent', 'wordfence-login-security')); ?>', response.confirmation);
					}
					if (request.notify_all) {
						WFLS.panelClose();
					}
				},
				function (error) {
					WFLS.panelModal((WFLS.screenSize(500) ? '300px' : '400px'), '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('Error Sending Notification', 'wordfence-login-security')); ?>', '<?php echo \WordfenceLS\Text\Model_JavaScript::esc_js(__('An error was encountered while trying to send the notification. Please try again.', 'wordfence-login-security')); ?>');
					if (request.notify_all) {
						WFLS.panelClose();
					}
				});
		}
		$('#wfls-send-grace-period-notification').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			sendGracePeriodNotification();	
		});
		$(document).on('click', '#wfls-send-grace-period-notification-over-limit', function() {
			sendGracePeriodNotification(true);
			$(this).prop("disabled", true);
		});
		$('#wfls-2fa-grace-period').on('input', function(e) {
			var value = $(this).val();
			value = value.replace(/[^0-9]/g, '');
			value = parseInt(value);
			if (isNaN(value))
				value = '';
			if (value === 0) {
				$("#wfls-grace-period-zero-warning").show();
			}
			else {
				$("#wfls-grace-period-zero-warning").hide();
			}
			$(this).val(value);
		}).trigger('input');
		var customerRoleInput = $('#wfls-2fa-role-enabled-roles\\.customer');
		function isCustomerRoleRequired() {
			return customerRoleInput.val() === 'required';
		}
		function toggleCustomerRoleWarning() {
			$("#wfls-customer-2fa-required-warning").toggle(isCustomerRoleRequired());
		}
		toggleCustomerRoleWarning();
		customerRoleInput.on('change', function(e) {
			toggleCustomerRoleWarning();
			if (isCustomerRoleRequired()) {
				WFLS.displayModalMessage(
					<?php Model_JavaScript::echo_string_literal(__('Not Recommended', 'wordfence-login-security')) ?>,
					<?php Model_JavaScript::echo_string_literal($customerRoleWarning) ?>,
					[
						{
							label: <?php Model_JavaScript::echo_string_literal(__('Make Optional', 'wordfence-login-security')) ?>,
							id: 'wfls-customer-role-warning-revert',
							type: 'primary'
						},
						{
							label: <?php Model_JavaScript::echo_string_literal(__('Proceed', 'wordfence-login-security')) ?>,
							id: 'wfls-generic-modal-close',
							type: 'danger'
						}
					]
				);
			}
		});
		$('body').on('click', '#wfls-customer-role-warning-revert', function() {
			customerRoleInput.val('optional').trigger('change');
			$('#wfls-generic-modal-close').trigger('click');
		});
	})(jQuery);
</script>