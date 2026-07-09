<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents the fresh install modal.
 */

$registrationLink = wfLicense::generateRegistrationLink();
?>
<div id="wf-onboarding-fresh-install" class="wf-onboarding-modal">
	<div id="wf-onboarding-fresh-install-1" class="wf-onboarding-modal-content">
		<div class="wf-onboarding-logo"><img src="<?php echo esc_attr(wfUtils::getBaseURL() . 'images/wf-horizontal.svg'); ?>" alt="<?php esc_html_e('Wordfence - Securing your WordPress Website', 'wordfence'); ?>"></div>
		<h3><?php printf(/* translators: Wordfence version. */ esc_html__('You have successfully installed Wordfence %s', 'wordfence'), WORDFENCE_VERSION); ?></h3>
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
</div>
<script type="application/javascript">
	(function($) {
		$(function() {	
			$('#wf-onboarding-fresh-install').on('click', function(e) {
				e.stopPropagation();
			});

			$(window).on('wfOnboardingDismiss', function() {
				if ($('#wf-onboarding-fresh-install-1').is(':visible')) {
					wordfenceExt.setOption('onboardingAttempt1', '<?php echo esc_attr(wfOnboardingController::ONBOARDING_SKIPPED); ?>');
				}
			});
		});
	})(jQuery);
</script>