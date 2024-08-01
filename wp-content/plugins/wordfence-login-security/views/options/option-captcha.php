<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

$enableOptionName = \WordfenceLS\Controller_Settings::OPTION_ENABLE_AUTH_CAPTCHA;
$currentEnableValue = \WordfenceLS\Controller_Settings::shared()->get_bool($enableOptionName);

$siteKeyOptionName = \WordfenceLS\Controller_Settings::OPTION_RECAPTCHA_SITE_KEY;
$siteKeyValue = \WordfenceLS\Controller_Settings::shared()->get($siteKeyOptionName);

$secretOptionName = \WordfenceLS\Controller_Settings::OPTION_RECAPTCHA_SECRET;
$secretValue = \WordfenceLS\Controller_Settings::shared()->get($secretOptionName);
?>
<ul id="wfls-option-enable-auth-captcha" data-option="<?php echo esc_attr($enableOptionName); ?>" data-enabled-value="1" data-disabled-value="0" data-original-value="<?php echo $currentEnableValue ? '1' : '0'; ?>">
	<li>
		<ul class="wfls-option wfls-padding-add-bottom-small">
			<li id="wfls-enable-auth-captcha" class="wfls-option-checkbox<?php echo ($currentEnableValue ? ' wfls-checked' : ''); ?>" role="checkbox" aria-checked="<?php echo ($currentEnableValue ? 'true' : 'false'); ?>" tabindex="0"><i class="wfls-ion-ios-checkmark-empty" aria-hidden="true" aria-labelledby="wfls-enable-auth-captcha-label"></i></li>
			<li class="wfls-option-title">
				<ul class="wfls-flex-vertical wfls-flex-align-left">
					<li>
						<strong id="wfls-enable-auth-captcha-label"><?php esc_html_e('Enable reCAPTCHA on the login and user registration pages', 'wordfence-login-security'); ?></strong>
					</li>
					<li class="wfls-option-subtitle"><?php printf(__('reCAPTCHA v3 does not make users solve puzzles or click a checkbox like previous versions. The only visible part is the reCAPTCHA logo. If a visitor\'s browser fails the CAPTCHA, Wordfence will send an email to the user\'s address with a link they can click to verify that they are a user of your site. You can read further details <a href="%s" target="_blank" rel="noopener noreferrer">in our documentation</a>.', 'wordfence-login-security'), \WordfenceLS\Controller_Support::esc_supportURL(\WordfenceLS\Controller_Support::ITEM_MODULE_LOGIN_SECURITY_CAPTCHA)); ?></li>
				</ul>
			</li>
		</ul>
	</li>
	<li>
		<ul class="wfls-option wfls-padding-no-top">
			<li class="wfls-option-spacer"></li>
			<li>
				<table>
					<tr class="wfls-option wfls-option-text" data-original-value="<?php echo esc_attr($siteKeyValue); ?>" data-text-option="<?php echo esc_attr($siteKeyOptionName); ?>">
						<th id="wfls-enable-captcha-site-key-label" class="wfls-padding-add-bottom"><?php esc_html_e('reCAPTCHA v3 Site Key', 'wordfence-login-security'); ?></th>
						<td class="wfls-option-text wfls-padding-add-bottom"><input type="text" name="recaptchaSiteKey" id="input-recaptchaSiteKey" class="wfls-form-control" value="<?php echo esc_attr($siteKeyValue); ?>"<?php if (!$currentEnableValue) { echo ' disabled'; } ?>></td>
					</tr>
					<tr class="wfls-option wfls-option-text" data-original-value="<?php echo esc_attr($secretValue); ?>" data-text-option="<?php echo esc_attr($secretOptionName); ?>">
						<th id="wfls-enable-captcha-secret-label"><?php esc_html_e('reCAPTCHA v3 Secret', 'wordfence-login-security'); ?></th>
						<td class="wfls-option-text"><input type="text" name="recaptchaSecret" id="input-recaptchaSecret" class="wfls-form-control" value="<?php echo esc_attr($secretValue); ?>"<?php if (!$currentEnableValue) { echo ' disabled'; } ?>></td>
					</tr>
				</table>
			</li>
		</ul>
		<ul class="wfls-option wfls-padding-no-top">
			<li class="wfls-option-spacer"></li>
			<li class="wfls-option-title">
				<ul class="wfls-flex-vertical wfls-flex-align-left">
					<li class="wfls-option-subtitle"><?php echo wp_kses(__('Note: This feature requires a free site key and secret for the <a href="https://www.google.com/recaptcha/about/" target="_blank" rel="noopener noreferrer">Google reCAPTCHA v3 Service</a>. To set up new reCAPTCHA keys, log into your Google account and go to the <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">reCAPTCHA admin page</a>.', 'wordfence-login-security'), array('a'=>array('href'=>array(), 'target'=>array(), 'rel'=>array()))); ?></li>
				</ul>
			</li>
		</ul>
	</li>
</ul>
<script type="application/javascript">
	(function($) {
		$(function() {
			$('#wfls-enable-auth-captcha').on('keydown', function(e) {
				if (e.keyCode == 32) {
					e.preventDefault();
					e.stopPropagation();

					$(this).trigger('click');
				}
			});

			$('#wfls-enable-auth-captcha').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				var optionElement = $('#wfls-option-enable-auth-captcha');
				if (optionElement.hasClass('wfls-disabled')) {
					return;
				}

				var option = optionElement.data('option');
				var value = false;
				var isActive = $(this).hasClass('wfls-checked');
				if (isActive) {
					$(this).removeClass('wfls-checked').attr('aria-checked', 'false');
					$('#input-recaptchaSiteKey, #input-recaptchaSecret').attr('disabled', true);
					value = optionElement.data('disabledValue');
				}
				else {
					$(this).addClass('wfls-checked').attr('aria-checked', 'true');
					$('#input-recaptchaSiteKey, #input-recaptchaSecret').attr('disabled', false);
					value = optionElement.data('enabledValue');
				}

				var originalValue = optionElement.data('originalValue');
				if (originalValue == value) {
					delete WFLS.pendingChanges[option];
				}
				else {
					WFLS.pendingChanges[option] = value;
				}

				$(optionElement).trigger('change', [false]);
				WFLS.updatePendingChanges();
			});

			$('#wfls-enable-auth-captcha-label').on('click', function(e) {
				var links = $(this).find('a');
				var buffer = 10;
				for (var i = 0; i < links.length; i++) {
					var t = $(links[i]).offset().top;
					var l = $(links[i]).offset().left;
					var b = t + $(links[i]).height();
					var r = l + $(links[i]).width();

					if (e.pageX > l - buffer && e.pageX < r + buffer && e.pageY > t - buffer && e.pageY < b + buffer) {
						return;
					}
				}
				$(this).closest('.wfls-option').find('.wfls-option-checkbox').trigger('click');
			}).css('cursor', 'pointer');

			$('#input-recaptchaSiteKey, #input-recaptchaSecret').on('change paste keyup', function() {
				var e = this;

				setTimeout(function() {
					var optionElement = $(e).closest('.wfls-option');
					var option = optionElement.data('textOption');

					if (typeof option !== 'undefined') {
						var value = $(e).val();

						var originalValue = $(optionElement).data('originalValue');
						if (originalValue == value) {
							delete WFLS.pendingChanges[option];
						}
						else {
							WFLS.pendingChanges[option] = value;
						}

						$(optionElement).trigger('change', [false]);
						WFLS.updatePendingChanges();
					}
				}, 4);
			});

			$(window).on('wflsOptionsReset', function() {
				$('#wfls-enable-auth-captcha').each(function() {
					var enabledValue = $(this).data('enabledValue');
					var disabledValue = $(this).data('disabledValue');
					var originalValue = $(this).data('originalValue');
					if (enabledValue == originalValue) {
						$(this).find('#wfls-enable-auth-captcha.wfls-option-checkbox').addClass('wfls-checked').attr('aria-checked', 'true');
					}
					else {
						$(this).find('#wfls-enable-auth-captcha.wfls-option-checkbox').removeClass('wfls-checked').attr('aria-checked', 'false');
					}
					$(this).trigger('change', [true]);
				});
				$('#input-recaptchaSiteKey, #input-recaptchaSecret').each(function() {
					$(this).val($(this).data('originalValue'));
					$(this).attr('disabled', !$('#wfls-enable-auth-captcha.wfls-option-checkbox').hasClass('wfls-checked'));
				});
			});
		});
	})(jQuery);
</script>