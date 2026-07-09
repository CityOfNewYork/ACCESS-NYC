<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
$scanner = wfScanner::shared();
$scanOptions = $scanner->scanOptions();

$backPage = new wfPage(wfPage::PAGE_SCAN);
if (isset($_GET['source']) && wfPage::isValidPage($_GET['source'])) {
	$backPage = new wfPage($_GET['source']);
}
?>
<script type="application/javascript">
	(function($) {
		$(function() {
			document.title = "<?php esc_attr_e('Scanner Options', 'wordfence'); ?>" + " \u2039 " + WFAD.basePageName;
		});
	})(jQuery);
</script>
<div class="wordfence-vue-wrapper" data-base-component="OptionsLinkBlock"></div>
<div class="wf-options-controls">
	<div class="wf-row">
		<div class="wf-col-xs-12">
			<div class="wordfence-vue-wrapper"
					 data-base-component="SettingsControlBlock"
					 data-prop-section="<?php echo esc_attr(wfConfig::OPTIONS_TYPE_SCANNER); ?>"
					 data-prop-section-title="<?php echo esc_attr(__('Scan', 'wordfence')); ?>"
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
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
			</div>
		</div>
		<div class="wf-row">
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div id="wf-scan-options" class="wf-fixed-tab-content">
					<?php
					echo wfView::create('common/section-title', array(
						'title' => __('Scan Options and Scheduling', 'wordfence'),
						'helpLink' => wfSupportController::supportURL(wfSupportController::ITEM_SCAN),
						'helpLabelHTML' => wp_kses(__('Learn more<span class="wf-hidden-xs"> about Scanning</span>', 'wordfence'), array('span'=>array('classes'=>array()))),
						'showIcon' => true,
					))->render();
					?>
					<div class="wf-row">
						<div class="wordfence-vue-wrapper" data-base-component="ScannerHeader" data-prop-show-scan-status="false" data-prop-show-options-links="false"></div>
					</div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanSchedule" data-prop-state-key="wf-scanner-options-schedule"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanBasic" data-prop-state-key="wf-scanner-options-basic" data-prop-collapseable="false"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanGeneral" data-prop-state-key="wf-scanner-options-general"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanPerformance" data-prop-state-key="wf-scanner-options-performance"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanAdvanced" data-prop-state-key="wf-scanner-options-custom"></div>
				</div> <!-- end wf-scan-options block -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
