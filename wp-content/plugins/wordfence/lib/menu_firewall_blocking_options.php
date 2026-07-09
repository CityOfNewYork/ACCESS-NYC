<?php
if (!defined('WORDFENCE_VERSION')) { exit; }

$backPage = new wfPage(wfPage::PAGE_BLOCKING);
if (isset($_GET['source']) && wfPage::isValidPage($_GET['source'])) {
	$backPage = new wfPage($_GET['source']);
}
?>
<script type="application/javascript">
	(function($) {
		$(function() {
			document.title = "<?php esc_attr_e('Blocking Options', 'wordfence'); ?>" + " \u2039 " + WFAD.basePageName;
		});
	})(jQuery);
</script>
<div class="wordfence-vue-wrapper" data-base-component="OptionsLinkBlock"></div>
<div class="wf-options-controls">
	<div class="wf-row">
		<div class="wf-col-xs-12">
			<div class="wordfence-vue-wrapper"
					 data-base-component="SettingsControlBlock"
					 data-prop-section="<?php echo esc_attr(wfConfig::OPTIONS_TYPE_BLOCKING); ?>"
					 data-prop-section-title="<?php echo esc_attr(__('Blocking', 'wordfence')); ?>"
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
		<?php
		if (function_exists('network_admin_url') && is_multisite()) {
			$firewallURL = network_admin_url('admin.php?page=WordfenceWAF#top#waf');
			$blockingURL = network_admin_url('admin.php?page=WordfenceWAF#top#blocking');
		}
		else {
			$firewallURL = admin_url('admin.php?page=WordfenceWAF#top#waf');
			$blockingURL = admin_url('admin.php?page=WordfenceWAF#top#blocking');
		}
		?>
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
			</div>
		</div>
		<div class="wf-row">
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div id="waf-options" class="wf-fixed-tab-content">
					<?php
					echo wfView::create('common/section-title', array(
						'title' => __('Blocking Options', 'wordfence'),
						'helpLink' => wfSupportController::supportURL(wfSupportController::ITEM_FIREWALL_BLOCKING),
						'helpLabelHTML' => wp_kses(__('Learn more<span class="wf-hidden-xs"> about Blocking</span>', 'wordfence'), array('span'=>array('class'=>array()))),
						'showIcon' => true,
					))->render();
					?>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupGeneralBlocking" data-prop-state-key="blocking-options-general" data-prop-collapseable="false"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupCountryAdvanced" data-prop-state-key="blocking-options-country" data-prop-collapseable="false"></div>
				</div> <!-- end blocking options block -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
