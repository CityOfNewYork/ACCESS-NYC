<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents the fresh install plugin header.
 */

$registrationLink = wfLicense::generateRegistrationLink();
?>
<div id="wf-onboarding-plugin-header">
	<div id="wf-onboarding-plugin-header-header">
		<div id="wf-onboarding-plugin-header-title"><?php esc_html_e('Please Complete Wordfence Installation', 'wordfence'); ?></div>
		<div id="wf-onboarding-plugin-header-accessory"><a href="#" id="wf-onboarding-plugin-header-dismiss" role="button">&times;</a></div>
	</div>
	<div id="wf-onboarding-plugin-header-content">
		<ul>
			<li id="wf-onboarding-plugin-header-stage-content">
				<div id="wf-onboarding-plugin-header-stage-content-1">
					<div class="wf-onboarding-registration-prompt">
						<p><?php esc_html_e('Register with Wordfence to secure your site with the latest threat intelligence.', 'wordfence') ?></p>
						<div class="wf-onboarding-install-new wf-onboarding-install-type">
							<div>
								<a class="wf-btn wf-btn-primary wf-onboarding-register" href="<?php echo esc_attr($registrationLink) ?>" target="_blank"><?php esc_html_e('Get Your Wordfence License', 'wordfence') ?></a>
							</div>
							<div>
								<a class="wf-onboarding-install-type-existing" href="<?php echo esc_attr(network_admin_url('admin.php?page=WordfenceSupport#installExisting')); ?>"><?php esc_html_e('Install an existing license', 'wordfence') ?></a>
							</div>
						</div>
					</div>
				</div>
			</li>
			<li id="wf-onboarding-plugin-header-stage-image"></li>
		</ul>
	</div>
</div>
<script type="application/javascript">
	(function($) {
		$(function() {
			$('#wf-onboarding-plugin-header-dismiss').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				$(window).trigger('wfOnboardingDismiss2');
				$('#wf-onboarding-plugin-header').slideUp(400, function() {
					$('#wf-onboarding-plugin-overlay').remove();
				});

				if ($('#wf-onboarding-plugin-header-stage-content-1').is(':visible')) {
					wordfenceExt.setOption('onboardingAttempt2', '<?php echo esc_attr(wfOnboardingController::ONBOARDING_SKIPPED); ?>');
				}
			});
		});
	})(jQuery);
</script>