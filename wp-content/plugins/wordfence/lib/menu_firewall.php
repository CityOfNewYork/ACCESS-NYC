<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
if (!wfOnboardingController::shouldShowAttempt3() && wfConfig::get('touppPromptNeeded')): ?>
<div id="wf-gdpr-wrapper" class="wordfence-vue-wrapper" data-base-component="GDPRBanner"></div>
<?php endif; ?>
<?php if (isset($storageExceptionMessage)): ?>
	<div class="notice notice-error"><p><?php echo $storageExceptionMessage; ?></p></div>
<?php endif; ?>
<?php
echo wfView::create('common/page-tabbar', array(
				'tabs' => array(
								new wfTab('waf', 'waf', __('Firewall', 'wordfence'), __('Web Application Firewall', 'wordfence')),
								new wfTab('blocking', 'blocking', __('Blocking', 'wordfence'), __('Blocking', 'wordfence')),
				),
))->render();
?>
<div class="wrap wordfence">
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div id="waf" class="wf-tab-content" data-title="Web Application Firewall">
					<?php
					echo wfView::create('common/section-title', array(
						'title' => __('Firewall', 'wordfence'),
						'headerID' => 'wf-section-firewall',
						'helpLink' => wfSupportController::supportURL(wfSupportController::ITEM_FIREWALL_WAF),
						'helpLabelHTML' => wp_kses(__('Learn more<span class="wf-hidden-xs"> about the Firewall</span>', 'wordfence'), array('span'=>array('class'=>array()))),
					))->render();
					require(dirname(__FILE__) . '/menu_firewall_waf.php');
					?>
				</div> <!-- end waf block -->
				<div id="blocking" class="wf-tab-content" data-title="Blocking">
					<?php require(dirname(__FILE__) . '/menu_firewall_blocking.php'); ?>
				</div> <!-- end blocking block -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="FirewallModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="FirewallDrawers"></div>
