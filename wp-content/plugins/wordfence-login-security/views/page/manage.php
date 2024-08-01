<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

/**
 * @var \WP_User $user The user being edited. Required.
 * @var bool $canEditUsers Whether or not the viewer of the page can edit other users. Optional, defaults to false.
 */

if (!isset($canEditUsers)) {
	$canEditUsers = false;
}

$ownAccount = false;
$ownUser = wp_get_current_user();
if ($ownUser->ID == $user->ID) {
	$ownAccount = true;
}

$enabled = \WordfenceLS\Controller_Users::shared()->has_2fa_active($user);
$requires2fa = \WordfenceLS\Controller_Users::shared()->requires_2fa($user, $inGracePeriod, $requiredAt);
$lockedOut = $requires2fa && !$enabled;

?>
<p><?php echo wp_kses(sprintf(__('Two-Factor Authentication, or 2FA, significantly improves login security for your website. Wordfence 2FA works with a number of TOTP-based apps like Google Authenticator, FreeOTP, and Authy. For a full list of tested TOTP-based apps, <a href="%s" target="_blank" rel="noopener noreferrer">click here</a>.', 'wordfence-login-security'), \WordfenceLS\Controller_Support::esc_supportURL(\WordfenceLS\Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA)), array('a'=>array('href'=>array(), 'target'=>array(), 'rel'=>array()))); ?></p>
<?php if ($canEditUsers): ?>
<div id="wfls-editing-display" class="wfls-flex-row wfls-flex-row-xs-wrappable wfls-flex-row-equal-heights">
	<div class="wfls-block wfls-always-active wfls-flex-item-full-width wfls-add-bottom">
		<div class="wfls-block-header wfls-block-header-border-bottom">
			<div class="wfls-block-header-content">
				<div class="wfls-block-title">
					<strong><?php echo wp_kses(sprintf(__('Editing User:&nbsp;&nbsp;%s <span class="wfls-text-plain">%s</span>', 'wordfence-login-security'), get_avatar($user->ID, 16, '', $user->user_login), \WordfenceLS\Text\Model_HTML::esc_html($user->user_login) . ($ownAccount ? ' ' . __('(you)', 'wordfence-login-security') : '')), array('span'=>array('class'=>array()))); ?></strong>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif; ?>
<div id="wfls-deactivation-controls" class="wfls-flex-row wfls-flex-row-wrappable wfls-flex-row-equal-heights"<?php if (!$enabled) { echo ' style="display: none;"'; } ?>>
	<!-- begin status content -->
	<div class="wfls-flex-row wfls-flex-row-equal-heights wfls-flex-item-xs-100">
		<?php
		echo \WordfenceLS\Model_View::create('manage/deactivate', array(
			'user' => $user,
		))->render();
		?>
	</div>
	<!-- end status content -->
	<!-- begin regenerate codes -->
	<div class="wfls-flex-row wfls-flex-row-equal-heights wfls-flex-item-xs-100">
		<?php
		echo \WordfenceLS\Model_View::create('manage/regenerate', array(
			'user' => $user,
			'remaining' => \WordfenceLS\Controller_Users::shared()->recovery_code_count($user),
		))->render();
		?>
	</div>
	<!-- end regenerate codes -->
</div>
<div id="wfls-activation-controls" class="wfls-flex-row wfls-flex-row-xs-wrappable wfls-flex-row-equal-heights"<?php if ($enabled) { echo ' style="display: none;"'; } ?>>
	<?php
		$initializationData = new \WordfenceLS\Model_2faInitializationData($user);
	?>
	<!-- begin qr code -->
	<div class="wfls-flex-row wfls-flex-row-equal-heights wfls-col-sm-half-padding-right wfls-flex-item-xs-100 wfls-flex-item-sm-50">
		<?php
		echo \WordfenceLS\Model_View::create('manage/code', array(
			'initializationData' => $initializationData
		))->render();
		?>
	</div>
	<!-- end qr code -->
	<!-- begin activation -->
	<div class="wfls-flex-row wfls-flex-row-equal-heights wfls-col-sm-half-padding-left wfls-flex-item-xs-100 wfls-flex-item-sm-50">
		<?php
		echo \WordfenceLS\Model_View::create('manage/activate', array(
			'initializationData' => $initializationData
		))->render();
		?>
	</div>
	<!-- end activation -->
</div>
<div id="wfls-grace-period-controls" class="wfls-flex-row wfls-flex-row-xs-wrappable wfls-flex-row-equal-heights"<?php if ($enabled || !($lockedOut || $inGracePeriod)) { echo ' style="display: none;"'; } ?>>
	<div class="wfls-flex-row wfls-flex-row-equal-heights wfls-flex-item-xs-100 wfls-add-top">
		<?php
		echo \WordfenceLS\Model_View::create('manage/grace-period', array(
			'user' => $user,
			'lockedOut' => $lockedOut,
			'gracePeriod' => $inGracePeriod,
			'requiredAt' => $requiredAt
		))->render();
		?>
	</div>
</div>
<?php
/**
 * Fires after the main content of the activation page has been output.
 */
do_action('wfls_activation_page_footer');
$time = time();
$correctedTime = \WordfenceLS\Controller_Time::time($time);
$tz = get_option('timezone_string');
if (empty($tz)) {
	$offset = get_option('gmt_offset');
	$tz = 'UTC' . ($offset >= 0 ? '+' . $offset : $offset);
}
?>
<?php if (\WordfenceLS\Controller_Permissions::shared()->can_manage_settings()): ?>
<p><?php esc_html_e('Server Time:', 'wordfence-login-security'); ?> <?php echo date('Y-m-d H:i:s', $time); ?> UTC (<?php echo \WordfenceLS\Controller_Time::format_local_time('Y-m-d H:i:s', $time) . ' ' . $tz; ?>)<br>
	<?php esc_html_e('Browser Time:', 'wordfence-login-security'); ?> <script type="application/javascript">var date = new Date(); document.write(date.toUTCString() + ' (' + date.toString() + ')');</script><br>
<?php
if (\WordfenceLS\Controller_Settings::shared()->is_ntp_enabled()) {
	echo esc_html__('Corrected Time (NTP):', 'wordfence-login-security') . ' ' . date('Y-m-d H:i:s', $correctedTime) . ' UTC (' . \WordfenceLS\Controller_Time::format_local_time('Y-m-d H:i:s', $correctedTime) . ' ' . $tz . ')<br>';
}
else if (WORDFENCE_LS_FROM_CORE && $correctedTime != $time) {
	echo esc_html__('Corrected Time (WF):', 'wordfence-login-security') . ' ' . date('Y-m-d H:i:s', $correctedTime) . ' UTC (' . \WordfenceLS\Controller_Time::format_local_time('Y-m-d H:i:s', $correctedTime) . ' ' . $tz . ')<br>';
}
?>
<?php esc_html_e('Detected IP:', 'wordfence-login-security'); ?> <?php echo \WordfenceLS\Text\Model_HTML::esc_html(\WordfenceLS\Model_Request::current()->ip()); if (\WordfenceLS\Controller_Whitelist::shared()->is_whitelisted(\WordfenceLS\Model_Request::current()->ip())) { echo ' (' . esc_html__('allowlisted', 'wordfence-login-security') . ')'; } ?></p>
<?php endif; ?>