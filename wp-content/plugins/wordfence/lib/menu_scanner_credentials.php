<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
$scanner = wfScanner::shared();
$scanURL = network_admin_url('admin.php?page=WordfenceScan');

$action = wfUtils::array_get($_GET, 'action');
if (!is_string($action) || !in_array($action, array('restoreFile', 'deleteFile'))) { $action = ''; }
$filesystemCredentialsAdminURL = network_admin_url('admin.php?' . http_build_query(array(
		'page'               => 'WordfenceScan',
		'subpage'       	 => 'scan_credentials',
		'action' 			 => $action,
		'issueID'            => (int) wfUtils::array_get($_GET, 'issueID', 0),
		'nonce'              => wp_create_nonce('wp-ajax'),
	)));

switch ($action) {
	case 'restoreFile':
		$callback = array('wordfence', 'fsActionRestoreFileCallback');
		break;
	case 'deleteFile':
		$callback = array('wordfence', 'fsActionDeleteFileCallback');
		break;
}
?>
<div class="wf-options-controls">
	<div class="wf-row">
		<div class="wf-col-xs-12">
			<div class="wordfence-vue-wrapper"
					 data-base-component="SettingsControlBlock"
					 data-prop-back-link="<?php echo esc_attr($scanURL); ?>"
					 data-prop-back-link-label="<?php echo esc_attr(__('Back to Scan', 'wordfence')); ?>"
					 data-prop-suppress-controls="true"
			></div>
		</div>
	</div>
</div>
<div class="wf-options-controls-spacer"></div>
<div class="wrap wordfence">
	<div class="wf-container-fluid">
		<div class="wf-row">
			<div class="wf-col-xs-12">
				<div class="wp-header-end"></div>
			</div>
		</div>
		<div class="wf-row">
			<div class="<?php echo wfStyle::contentClasses(); ?>">
				<div id="wf-scan-permissions-prompt" class="wf-fixed-tab-content">
					<?php
					echo wfView::create('common/section-title', array(
						'title' => __('File System Credentials Required', 'wordfence'),
					))->render();
					?>
					<div class="wf-row">
						<div class="wf-col-xs-12">
							<div class="wf-block wf-active">
								<div class="wf-block-content wf-padding-add-top wf-padding-add-bottom">
									<?php
									if (isset($_GET['nonce']) && wp_verify_nonce($_GET['nonce'], 'wp-ajax')) {
										if (wordfence::requestFilesystemCredentials($filesystemCredentialsAdminURL, wfUtils::getHomePath(), true, true)) {
											call_user_func_array($callback, isset($callbackArgs) && is_array($callbackArgs) ? $callbackArgs : array());
										}
										//else - outputs credentials form
									}
									else {
										echo '<p>' . wp_kses(sprintf(
											/* translators: URL to the WordPress admin panel. */
												__('Security token has expired. Click <a href="%s">here</a> to return to the scan page.', 'wordfence'), esc_url($scanURL)), array('a'=>array('href'=>array()))) . '</p>';
									}
									?>
								</div>
							</div>
						</div>
					</div> <!-- end permissions -->
				</div> <!-- end wf-scan-permissions-prompt block -->
			</div> <!-- end content block -->
		</div> <!-- end row -->
	</div> <!-- end container -->
</div>