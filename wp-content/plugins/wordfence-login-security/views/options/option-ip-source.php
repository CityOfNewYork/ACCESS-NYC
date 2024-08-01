<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents the global option OPTION_IP_SOURCE with a value select menu and text area (hidden by default) for trusted proxies.
 */

$selectOptions = array(
	array('value' => \WordfenceLS\Model_Request::IP_SOURCE_AUTOMATIC, 'label' => esc_html__('Use the most secure method to get visitor IP addresses. Prevents spoofing and works with most sites.', 'wordfence-login-security') . ' <strong>' . esc_html__('(Recommended)', 'wordfence-login-security') . '</strong>'),
	array('value' => \WordfenceLS\Model_Request::IP_SOURCE_REMOTE_ADDR, 'label' => esc_html__('Use PHP\'s built in REMOTE_ADDR and don\'t use anything else. Very secure if this is compatible with your site.', 'wordfence-login-security')),
	array('value' => \WordfenceLS\Model_Request::IP_SOURCE_X_FORWARDED_FOR, 'label' => esc_html__('Use the X-Forwarded-For HTTP header. Only use if you have a front-end proxy or spoofing may result.', 'wordfence-login-security')),
	array('value' => \WordfenceLS\Model_Request::IP_SOURCE_X_REAL_IP, 'label' => esc_html__('Use the X-Real-IP HTTP header. Only use if you have a front-end proxy or spoofing may result.', 'wordfence-login-security')),
);
?>
<ul class="wfls-flex-vertical wfls-flex-full-width">
	<li>
		<ul id="wfls-option-ip-source" class="wfls-option wfls-option-ip-source" data-option="<?php echo esc_attr(\WordfenceLS\Controller_Settings::OPTION_IP_SOURCE); ?>" data-original-value="<?php echo esc_attr(\WordfenceLS\Controller_Settings::shared()->get(\WordfenceLS\Controller_Settings::OPTION_IP_SOURCE)); ?>" data-text-area-option="<?php echo esc_attr(\WordfenceLS\Controller_Settings::OPTION_IP_TRUSTED_PROXIES); ?>" data-original-text-area-value="<?php echo esc_attr(\WordfenceLS\Controller_Settings::shared()->get(\WordfenceLS\Controller_Settings::OPTION_IP_TRUSTED_PROXIES)); ?>">
			<li class="wfls-option-content wfls-no-right">
				<ul class="wfls-flex-vertical wfls-flex-align-left">
					<li class="wfls-option-title"><strong><?php esc_html_e('How to get IPs', 'wordfence-login-security'); ?></strong></li>
					<li>
						<ul class="wfls-flex-vertical wfls-flex-align-left">
							<li class="wfls-padding-add-left">
								<ul class="wfls-flex-vertical wfls-flex-align-left" role="radiogroup">
									<?php foreach ($selectOptions as $o): ?>
										<li class="wfls-padding-add-top-small"><input type="radio" class="wfls-option-radio" name="wfls-ip-source" value="<?php echo esc_attr($o['value']); ?>" id="wfls-ip-source-<?php echo esc_attr(preg_replace('/[^a-z0-9]/i', '-', $o['value'])); ?>"<?php if ($o['value'] == \WordfenceLS\Controller_Settings::shared()->get(\WordfenceLS\Controller_Settings::OPTION_IP_SOURCE)) { echo ' checked'; } ?>><label for="wfls-ip-source-<?php echo esc_attr(preg_replace('/[^a-z0-9]/i', '-', $o['value'])); ?>">&nbsp;&nbsp;</label><?php echo $o['label']; ?></li>
									<?php endforeach; ?>
								</ul>
							</li>
							<li class="wfls-option-ip-source-details wfls-padding-add-top">
								<div class="wfls-left">Detected IP(s): <span id="wfls-ip-source-preview-all"><?php echo \WordfenceLS\Model_Request::current()->detected_ip_preview(); ?></span></div>
								<div class="wfls-left">Your IP with this setting: <span id="wfls-ip-source-preview-single"><?php echo esc_html(\WordfenceLS\Model_Request::current()->ip()); ?></span></div>
								<div class="wfls-left"><a href="#" id="wfls-ip-source-trusted-proxies-show">+ Edit trusted proxies</a></div>
							</li>
						</ul>
					</li>
				</ul>
			</li>
		</ul>
	</li>
	<li id="wfls-ip-source-trusted-proxies">
		<ul id="wfls-option-ip-source-trusted-proxies" class="wfls-option wfls-option-textarea" data-text-option="<?php echo esc_attr(\WordfenceLS\Controller_Settings::OPTION_IP_TRUSTED_PROXIES); ?>" data-original-text-value="<?php echo esc_attr(\WordfenceLS\Controller_Settings::shared()->get(\WordfenceLS\Controller_Settings::OPTION_IP_TRUSTED_PROXIES)); ?>">
			<li class="wfls-option-spacer"></li>
			<li class="wfls-option-content wfls-no-right">
				<ul>
					<li class="wfls-option-title">
						<ul class="wfls-flex-vertical wfls-flex-align-left">
							<li><?php esc_html_e('Trusted Proxies', 'wordfence-login-security'); ?></li>
							<li class="wfls-option-subtitle"><?php esc_html_e('These IPs (or CIDR ranges) will be ignored when determining the requesting IP via the X-Forwarded-For HTTP header. Enter one IP or CIDR range per line.', 'wordfence-login-security'); ?></li>
						</ul>
					</li>
					<li class="wfls-option-textarea">
						<textarea spellcheck="false" autocapitalize="none" autocomplete="off" name="howGetIPs_trusted_proxies"><?php echo esc_html(\WordfenceLS\Controller_Settings::shared()->get(\WordfenceLS\Controller_Settings::OPTION_IP_TRUSTED_PROXIES)); ?></textarea>
					</li>
				</ul>
			</li>
		</ul>
	</li>
</ul>
<script type="application/javascript">
	(function($) {
		$(function() {
			var updateIPPreview = function() {
				WFLS.updateIPPreview({ip_source: $('input[name="wfls-ip-source"]:checked').val(), ip_source_trusted_proxies: $('#wfls-ip-source-trusted-proxies textarea').val()}, function(ret) {
					if (ret && ret.ip) {
						$('#wfls-ip-source-preview-all').html(ret.preview);
						$('#wfls-ip-source-preview-single').html(ret.ip);
					}
				});
			};

			$('input[name="wfls-ip-source"]').on('change', function() {
				var optionElement = $(this).closest('.wfls-option.wfls-option-ip-source');
				var option = optionElement.data('option');
				var value = $('input[name="wfls-ip-source"]:checked').val();

				var originalValue = optionElement.data('originalValue');
				if (originalValue == value) {
					delete WFLS.pendingChanges[option];
				}
				else {
					WFLS.pendingChanges[option] = value;
				}

				WFLS.updatePendingChanges();

				updateIPPreview();
			});

			var coalescingUpdateTimer;
			$('#wfls-ip-source-trusted-proxies textarea').on('change paste keyup', function() {
				var e = this;

				setTimeout(function() {
					clearTimeout(coalescingUpdateTimer);
					coalescingUpdateTimer = setTimeout(updateIPPreview, 1000);

					var optionElement = $(e).closest('.wfls-option.wfls-option-textarea');
					var option = optionElement.data('textOption');
					var value = $(e).val();

					var originalValue = optionElement.data('originalTextValue');
					if (originalValue == value) {
						delete WFLS.pendingChanges[option];
					}
					else {
						WFLS.pendingChanges[option] = value;
					}

					WFLS.updatePendingChanges();
				}, 4);
			});

			$(window).on('wflsOptionsReset', function() {
				$('input[name="wfls-ip-source"]').each(function() {
					var optionElement = $(this).closest('.wfls-option.wfls-option-ip-source');
					var option = optionElement.data('option');
					var originalValue = optionElement.data('originalValue');

					$(this).prop('checked', originalValue == $(this).attr('value'));
				});

				$('#wfls-ip-source-trusted-proxies textarea').each(function() {
					var optionElement = $(this).closest('.wfls-option.wfls-option-textarea');
					var originalValue = optionElement.data('originalTextAreaValue');
					$(this).val(originalValue);
				});

				updateIPPreview();
			});

			$('#wfls-ip-source-trusted-proxies-show').each(function() {
				$(this).on('keydown', function(e) {
					if (e.keyCode == 32) {
						e.preventDefault();
						e.stopPropagation();

						$(this).trigger('click');
					}
				});

				$(this).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var isActive = $('#wfls-ip-source-trusted-proxies').hasClass('wfls-active');
					if (isActive) {
						$('#wfls-ip-source-trusted-proxies').slideUp({
							always: function() {
								$('#wfls-ip-source-trusted-proxies').removeClass('wfls-active');
							}
						});
					}
					else {
						$(this).parent().slideUp();
						$('#wfls-ip-source-trusted-proxies').slideDown({
							always: function() {
								$('#wfls-ip-source-trusted-proxies').addClass('wfls-active');
							}
						});
					}
				});
			});
		});
	})(jQuery);
</script> 