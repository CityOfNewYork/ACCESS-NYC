<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
$waf = wfWAF::getInstance();
$d = new wfDashboard(); unset($d->countriesNetwork);
$firewall = new wfFirewall();
$scanner = wfScanner::shared();
$config = $waf->getStorageEngine();
$wafURL = wfPage::pageURL(wfPage::PAGE_FIREWALL);
$wafConfigURL = network_admin_url('admin.php?page=WordfenceWAF&subpage=waf_options#configureAutoPrepend');
$wafRemoveURL = network_admin_url('admin.php?page=WordfenceWAF&subpage=waf_options#removeAutoPrepend');
/** @var array $wafData */

$backPage = new wfPage(wfPage::PAGE_FIREWALL);
if (isset($_GET['source']) && wfPage::isValidPage($_GET['source'])) {
	$backPage = new wfPage($_GET['source']);
}
?>
<script type="application/javascript">
	(function($) {
		$(function() {
			document.title = "<?php esc_attr_e('All Options', 'wordfence'); ?>" + " \u2039 " + WFAD.basePageName;
		});
	})(jQuery);
</script>
<div class="wordfence-vue-wrapper" data-base-component="OptionsLinkBlock"></div>
<div class="wf-options-controls">
	<div class="wf-row">
		<div class="wf-col-xs-12">
			<div class="wordfence-vue-wrapper"
					 data-base-component="SettingsControlBlock"
					 data-prop-section="<?php echo esc_attr(wfConfig::OPTIONS_TYPE_ALL); ?>"
					 data-prop-accessory-mode="search"
					 data-prop-suppress-logo="true"
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
				<div id="wf-all-options" class="wf-fixed-tab-content">
					<?php
					$stateKeys = array(
						'wf-unified-global-options-license',
						'wf-unified-global-options-view-customization',
						'wf-unified-global-options-general',
						'wf-unified-global-options-dashboard',
						'wf-unified-global-options-alert',
						'wf-unified-global-options-email-summary',
						'wf-unified-waf-options-basic',
						'wf-unified-waf-options-advanced',
						'wf-unified-waf-options-bruteforce',
						'wf-unified-waf-options-ratelimiting',
						'wf-unified-waf-options-whitelisted',
						'wf-unified-blocking-options-country',
						'wf-unified-scanner-options-schedule',
						'wf-unified-scanner-options-basic',
						'wf-unified-scanner-options-general',
						'wf-unified-scanner-options-performance',
						'wf-unified-scanner-options-custom',
						'wf-unified-2fa-options',
						'wf-unified-live-traffic-options',
						'wf-unified-audit-log-options',
					);
					
					echo wfView::create('options/options-title', array(
						'title' => __('All Options', 'wordfence'),
						'stateKeys' => $stateKeys,
						'showIcon' => true,
					))->render();
					?>
					
					<p><?php esc_html_e('These options are also available throughout the plugin pages, in the relevant sections. This page is provided for easier setup for experienced Wordfence users.', 'wordfence'); ?></p>
					
					<?php
					echo wfView::create('common/section-subtitle', array(
						'title' => __('Wordfence Global Options', 'wordfence'),
						'showIcon' => false,
					))->render();
					?>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupLicense" data-prop-state-key="wf-unified-global-options-license"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupViewCustomization" data-prop-state-key="wf-unified-global-options-view-customization"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupGeneral" data-prop-state-key="wf-unified-global-options-general"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupDashboard" data-prop-state-key="wf-unified-global-options-dashboard"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupAlert" data-prop-state-key="wf-unified-global-options-alert"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupEmailSummary" data-prop-state-key="wf-unified-global-options-email-summary"></div>

					<?php
					echo wfView::create('common/section-subtitle', array(
						'title' => __('Firewall Options', 'wordfence'),
						'showIcon' => false,
					))->render();
					?>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupBasicFirewall" data-prop-state-key="wf-unified-waf-options-basic"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupAdvancedFirewall" data-prop-state-key="wf-unified-waf-options-advanced"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupBruteForce" data-prop-state-key="wf-unified-waf-options-bruteforce"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupRateLimiting" data-prop-state-key="wf-unified-waf-options-ratelimiting"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupWhitelisted" data-prop-state-key="wf-unified-waf-options-whitelisted"></div>

					<?php
					echo wfView::create('common/section-subtitle', array(
						'title' => __('Blocking Options', 'wordfence'),
						'showIcon' => false,
					))->render();
					?>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupCountryAdvanced" data-prop-state-key="wf-unified-blocking-options-country"></div>
					
					<?php
					echo wfView::create('common/section-subtitle', array(
						'title' => __('Scan Options', 'wordfence'),
						'showIcon' => false,
					))->render();
					?>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanSchedule" data-prop-state-key="wf-unified-scanner-options-schedule"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanBasic" data-prop-state-key="wf-unified-scanner-options-basic"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanGeneral" data-prop-state-key="wf-unified-scanner-options-general"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanPerformance" data-prop-state-key="wf-unified-scanner-options-performance"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupScanAdvanced" data-prop-state-key="wf-unified-scanner-options-custom"></div>

					<?php
					echo wfView::create('common/section-subtitle', array(
						'title' => __('Tool Options', 'wordfence'),
						'showIcon' => false,
					))->render();
					?>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupLiveTraffic" data-prop-state-key="wf-unified-live-traffic-options" data-prop-hide-show-menu-item="true"></div>
					<div class="wordfence-vue-wrapper" data-base-component="OptionsGroupAuditLog" data-prop-state-key="wf-unified-audit-log-options" data-prop-hide-show-menu-item="true"></div>

					<div class="wf-row">
						<div class="wf-col-xs-12">
							<div class="wf-block wf-always-active" data-persistence-key="">
								<div class="wf-block-header">
									<div class="wf-block-header-content">
										<div class="wf-block-title">
											<strong><?php esc_html_e('Import/Export Options', 'wordfence'); ?></strong>
										</div>
									</div>
								</div>
								<div class="wf-block-content">
									<ul class="wf-block-list">
										<li>
											<ul class="wf-flex-horizontal wf-flex-vertical-xs wf-flex-full-width wf-add-top wf-add-bottom">
												<li><?php esc_html_e('Importing and exporting of options is available on the Tools page', 'wordfence'); ?></li>
												<li class="wf-right wf-left-xs wf-padding-add-top-xs-small">
													<a href="<?php echo esc_url(network_admin_url('admin.php?page=WordfenceTools&subpage=importexport')); ?>" class="wf-btn wf-btn-primary wf-btn-callout-subtle" id="wf-export-options"><?php esc_html_e('Import/Export Options', 'wordfence'); ?></a>
												</li>
											</ul>
											<input type="hidden" id="wf-option-exportOptions">
											<input type="hidden" id="wf-option-importOptions">
										</li>
									</ul>
								</div>
							</div>
						</div>
					</div> <!-- end import options -->
					<?php
					$moduleOptionBlocks = wfModuleController::shared()->optionBlocks;
					foreach ($moduleOptionBlocks as $b) {
						echo $b;
					}
					?>
				</div> <!-- end options block -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="OptionsModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="FirewallModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="FirewallDrawers"></div>