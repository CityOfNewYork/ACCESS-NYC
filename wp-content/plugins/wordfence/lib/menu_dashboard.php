<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
$firewall = new wfFirewall();
$scanner = wfScanner::shared();
$d = new wfDashboard();
?>
<?php if (!wfOnboardingController::shouldShowAttempt3() && wfConfig::get('touppPromptNeeded')): ?>
<div id="wf-gdpr-wrapper" class="wordfence-vue-wrapper" data-base-component="GDPRBanner"></div>
<?php endif; ?>
<div class="wrap wordfence" id="wf-dashboard">
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
				<?php
				echo wfView::create('common/section-title', array(
					'title' => __('Wordfence Dashboard', 'wordfence'),
					'helpLink' => wfSupportController::supportURL(wfSupportController::ITEM_DASHBOARD),
					'helpLabelHTML' => wp_kses(__('Learn more<span class="wf-hidden-xs"> about the Dashboard</span>', 'wordfence'), array('span'=>array('class'=>array()))),
					'showIcon' => true,
				))->render();
				?>
			</div>
		</div>
		<div class="wordfence-vue-wrapper" data-base-component="DashboardHeader" data-prop-attribution-tag="dashboard" data-prop-firewall-options-link="<?php echo esc_attr(wfPage::pageURL(wfPage::PAGE_FIREWALL_OPTIONS, wfPage::PAGE_DASHBOARD)); ?>" data-prop-scan-options-link="<?php echo esc_attr(wfPage::pageURL(wfPage::PAGE_SCAN_OPTIONS, wfPage::PAGE_DASHBOARD)); ?>"></div>
		<!-- begin notifications -->
		<div class="wf-flex-row wf-flex-row-full-height wf-flex-row-vertical-xs wordfence-vue-wrapper" data-base-component="WidgetNotifications" data-prop-dashboard-data="<?php echo esc_attr($d->toJson(array('notifications', 'wordfenceCentralConnected', 'wordfenceCentralConnectEmail', 'wordfenceCentralConnectTime', 'wordfenceCentralDisconnected', 'wordfenceCentralDisconnectEmail', 'wordfenceCentralDisconnectTime'))); ?>"></div>
		<!-- end notifications -->
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wf-block wf-active wf-add-bottom">
					<div class="wf-block-content">
						<ul class="wf-block-list">
							<li>
								<ul class="wf-block-list wf-block-list-horizontal wf-dashboard-navigation">
									<li>
										<?php
										echo wfView::create('common/block-navigation-option', array(
											'id' => 'wf-dashboard-option-tools',
											'img' => 'tools.svg',
											'title' => __('Tools', 'wordfence'),
											'subtitle' => __('Live Traffic, Whois Lookup, Import/Export, and Diagnostics', 'wordfence'),
											'link' => network_admin_url('admin.php?page=WordfenceTools'),
										))->render();
										?>
									</li>
									<li>
										<?php
										echo wfView::create('common/block-navigation-option', array(
											'id' => 'wf-dashboard-option-support',
											'img' => 'support.svg',
											'title' => __('Help', 'wordfence'),
											'subtitle' => __('Find the documentation and help you need', 'wordfence'),
											'link' => network_admin_url('admin.php?page=WordfenceSupport'),
										))->render();
										?>
									</li>
									<li>
										<?php
										echo wfView::create('common/block-navigation-option', array(
											'id' => 'wf-dashboard-option-options',
											'img' => 'options.svg',
											'title' => __('Global Options', 'wordfence'),
											'subtitle' => __('Manage global options for Wordfence such as alerts, premium status, and more', 'wordfence'),
											'link' => network_admin_url('admin.php?page=Wordfence&subpage=global_options'),
										))->render();
										?>
									</li>
								</ul>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="wf-row">
			<div class="wf-col-xs-12 wf-col-lg-6 wf-col-lg-half-padding-right">
				<!-- begin firewall summary site -->
				<?php include(dirname(__FILE__) . '/dashboard/widget_localattacks.php'); ?>
				<!-- end firewall summary site -->
			</div> <!-- end content block -->
			<div class="wf-col-xs-12 wf-col-lg-6 wf-col-lg-half-padding-left">
				<!-- begin total attacks blocked network -->
				<?php include(dirname(__FILE__) . '/dashboard/widget_networkattacks.php'); ?>
				<!-- end total attacks blocked network -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="DashboardModals"></div>

<?php if (wfOnboardingController::willShowNewTour(wfOnboardingController::TOUR_DASHBOARD)): ?>
<div class="wordfence-vue-wrapper" data-base-component="DashboardNewTour"></div>
<?php endif; ?>
