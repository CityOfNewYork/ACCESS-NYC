<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * @var string $subpage
 * @var string $content
 * @var array $wrap
 */
?>
<?php if (!wfOnboardingController::shouldShowAttempt3() && wfConfig::get('touppPromptNeeded')): ?>
	<div id="wf-gdpr-wrapper" class="wordfence-vue-wrapper" data-base-component="GDPRBanner"></div>
<?php endif; ?>
<?php
$tabsArray = array();
if (wfCredentialsController::allowLegacy2FA()) {
	$tabsArray[] = array('twofactor', __('Two-Factor Authentication', 'wordfence'));
}
$tabsArray[] = array('livetraffic', __('Live Traffic', 'wordfence'));
$tabsArray[] = array('auditlog', __('Audit Log', 'wordfence'));
$tabsArray[] = array('whois', __('Whois Lookup', 'wordfence'));
$tabsArray[] = array('importexport', __('Import/Export Options', 'wordfence'));
$tabsArray[] = array('diagnostics', __('Diagnostics', 'wordfence'));

$tabs = array();
foreach ($tabsArray as $tab) {
	list($tabID, $tabLabel) = $tab;
	$tabs[] = new wfTab($tabID,
					network_admin_url('admin.php?page=WordfenceTools&subpage=' . rawurlencode($tabID)),
					$tabLabel, $tabLabel, $subpage === $tabID);
}

echo wfView::create('common/page-fixed-tabbar', array(
				'tabs' => $tabs,
))->render();
?>
<div class="wrap wordfence <?php echo esc_attr(implode(' ', $wrap)); ?>">
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div class="wf-tab-content wf-active">
					<?php echo $content ?>
				</div>
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>
<div class="wordfence-vue-wrapper" data-base-component="CommonModals"></div>
<div class="wordfence-vue-wrapper" data-base-component="OptionsModals"></div>
