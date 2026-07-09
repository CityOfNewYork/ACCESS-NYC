<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
$backPage = new wfPage(wfPage::PAGE_FIREWALL);
if (isset($_GET['source']) && wfPage::isValidPage($_GET['source'])) {
	$backPage = new wfPage($_GET['source']);
}
?>
<div class="wf-options-controls">
	<div class="wf-row">
		<div class="wf-col-xs-12">
			<div class="wordfence-vue-wrapper"
					 data-base-component="SettingsControlBlock"
					 data-prop-section="<?php echo esc_attr(wfConfig::OPTIONS_TYPE_FIREWALL); ?>"
					 data-prop-section-title="<?php echo esc_attr(__('Firewall', 'wordfence')); ?>"
					 data-prop-back-link="<?php echo esc_attr($backPage->url()); ?>"
					 data-prop-back-link-label="<?php echo esc_attr(sprintf(/* translators: page label */ __('Back to %s', 'wordfence'), $backPage->label())) ?>"
					 data-prop-back-link-label-x-s="<?php echo esc_attr($backPage->label()) ?>"
			></div>
		</div>
	</div>
</div>
<div class="wf-options-controls-spacer"></div>
<?php if (!wfOnboardingController::shouldShowAttempt3() && wfConfig::get('touppPromptNeeded')): ?>
	<div id="wf-gdpr-wrapper" class="wordfence-vue-wrapper" data-base-component="GDPRBanner"></div>
<?php endif; ?>
<div class="wrap wordfence">
	<div class="wordfence-vue-wrapper" data-base-component="OptionsLinkBlock"></div>
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
				<?php if (isset($storageExceptionMessage)): ?>
				<div class="notice notice-error"><p><?php echo $storageExceptionMessage; ?></p></div>
				<?php endif; ?>
			</div>
		</div>
		<div class="wf-row">
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div class="wordfence-vue-wrapper" data-base-component="FirewallOptions"></div>
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="FirewallModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="FirewallDrawers"></div>