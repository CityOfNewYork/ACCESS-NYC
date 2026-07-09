<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Already defined:
 *
 * @var $shouldShowOnboarding
 * @var $email
 * @var $license
 * @var $invalidLink
 * @var $payloadException
 */

$errorMessage = null;
if (!$shouldShowOnboarding) {
	$errorMessage = __('Wordfence is already installed on this site. If you need to replace the current license, you may do so by visiting the "All Options" page of the Wordfence menu.', 'wordfence');
}
elseif ($invalidLink) {
	if ($payloadException instanceof wfWebsiteEphemeralPayloadRateLimitedException) {
		$errorMessage = __('Too many installation requests have been made from your IP address. Please try again later.', 'wordfence');
	}
	elseif ($payloadException instanceof wfWebsiteEphemeralPayloadExpiredException) {
		$errorMessage = __('The link you used to access this page has expired, has already been used, or is otherwise invalid.', 'wordfence');
	}
	else {
		$errorMessage = __('An error occurred while retrieving your license information from the Wordfence servers. Please ensure that your server can reach www.wordfence.com on port 443.', 'wordfence');
	}
}
?>
<div class="wrap wordfence" id="wf-install">
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
				<?php
				echo wfView::create('common/section-title', array(
					'title' => __('Install Wordfence', 'wordfence'),
					'showIcon' => true,
				))->render();
				?>
			</div>
		</div>
		<div class="wf-row wordfence-vue-wrapper" data-base-component="StandaloneInstall" data-prop-error-message="<?php echo esc_attr($errorMessage) ?>" data-prop-should-show-onboarding="<?php echo $shouldShowOnboarding ? 'true' : 'false' ?>" data-prop-initial-state="<?php echo esc_attr(json_encode(array('email' => $email, 'license' => $license))); ?>"></div>
	</div>
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="OnboardingModals" data-prop-allow-onboarding-auto-open="<?php echo (!$email && !$license ? 'true' : 'false') ?>"></div>
