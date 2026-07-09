<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
$dashboardURL = network_admin_url('admin.php?page=Wordfence');
$firewall = new wfFirewall();
$scanner = wfScanner::shared();
$d = new wfDashboard();
?>
<div class="wf-options-controls">
	<div class="wf-row">
		<div class="wf-col-xs-12">
			<div class="wordfence-vue-wrapper"
					 data-base-component="SettingsControlBlock"
					 data-prop-section="<?php echo esc_attr(wfConfig::OPTIONS_TYPE_GLOBAL); ?>"
					 data-prop-back-link="<?php echo esc_attr($dashboardURL); ?>"
					 data-prop-back-link-label="<?php echo esc_attr(__('Back to Dashboard', 'wordfence')) ?>"
					 data-prop-back-link-label-x-s="<?php echo esc_attr(__('Back', 'wordfence')) ?>"
			></div>
		</div>
	</div>
</div>
<div class="wf-options-controls-spacer"></div>
<?php if (!wfOnboardingController::shouldShowAttempt3() && wfConfig::get('touppPromptNeeded')): ?>
	<div id="wf-gdpr-wrapper" class="wordfence-vue-wrapper" data-base-component="GDPRBanner"></div>
<?php endif; ?>
<div class="wrap wordfence" id="wf-global-options">
	<div class="wordfence-vue-wrapper" data-base-component="OptionsLinkBlock"></div>
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
			</div>
		</div>
		<div class="wf-row">
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div class="wordfence-vue-wrapper" data-base-component="DashboardOptions"></div>
				<!-- end options block -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="OptionsModals"></div>