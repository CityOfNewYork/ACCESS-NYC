<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
$scanner = wfScanner::shared();
$issues = wfIssues::shared();
$dashboard = new wfDashboard();
?>
<?php if (wfConfig::get('liveActivityPauseEnabled')): ?>
	<div id="wfLiveTrafficOverlayAnchor"></div>
	<div id="wfLiveTrafficDisabledMessage">
		<h2><?php echo wp_kses(__('Status Updates Paused<br /><small>Click inside window to resume</small>', 'wordfence'), array('small'=>array(), 'br'=>array())); ?></h2>
	</div>
<?php endif; ?>
<?php if (!wfOnboardingController::shouldShowAttempt3() && wfConfig::get('touppPromptNeeded')): ?>
	<div id="wf-gdpr-wrapper" class="wordfence-vue-wrapper" data-base-component="GDPRBanner"></div>
<?php endif; ?>
<div id="wordfenceMode_scan"></div>
<div class="wrap wordfence">
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div> 
				<?php
				echo wfView::create('common/section-title', array(
					'title' => __('Scan', 'wordfence'),
					'headerID' => 'wf-section-scan',
					'helpLink' => wfSupportController::supportURL(wfSupportController::ITEM_SCAN),
					'helpLabelHTML' => wp_kses(__('Learn more<span class="wf-hidden-xs"> about the Scanner</span>', 'wordfence'), array('span'=>array('class'=>array()))),
					'showIcon' => true,
				))->render();
				?>
			</div>
			<div class="wordfence-vue-wrapper" data-base-component="ScannerHeader"></div>
		</div>
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wf-block wf-active">
					<div class="wf-block-content">
						<ul class="wf-block-list">
							<li>
								<ul class="wf-block-list wf-block-list-horizontal wf-scan-navigation">
									<li class="wordfence-vue-wrapper" data-base-component="ScanStarter"></li>
									<li>
										<?php
										echo wfView::create('common/block-navigation-option', array(
											'id' => 'wf-scan-option-support',
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
											'id' => 'wf-scan-option-all-options',
											'img' => 'options.svg',
											'title' => __('Scan Options and Scheduling', 'wordfence'),
											'subtitle' => __('Manage scan options including scheduling', 'wordfence'),
											'link' => network_admin_url('admin.php?page=WordfenceScan&subpage=scan_options'),
										))->render();
										?>
									</li>
								</ul>
							</li>
							<li id="wf-scan-progress-bar" class="wordfence-vue-wrapper" data-base-component="ScanProgressStages"></li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="wf-row">
			<div class="wf-col-xs-12 wordfence-vue-wrapper" data-base-component="ScanProgressDetailed"></div>
		</div>
		<div class="wf-row">
			<div class="wf-col-xs-12 wordfence-vue-wrapper" data-base-component="ScanResults"></div>
		</div>
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<?php
				echo wfView::create('scanner/site-cleaning-bottom', array(
				))->render();
				?>
			</div>
		</div>
	</div> <!-- end container -->
	<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
	<div class="wordfence-vue-wrapper" data-base-component="ScannerModals"></div>
	<?php if (wfOnboardingController::willShowNewTour(wfOnboardingController::TOUR_SCAN)): ?>
	<div class="wordfence-vue-wrapper" data-base-component="ScannerNewTour"></div>
	<?php endif; ?>
</div>
