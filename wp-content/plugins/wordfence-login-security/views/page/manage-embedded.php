<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }

$assets = isset($assets) ? $assets : array();
$scriptData = isset($scriptData) ? $scriptData : array();

$enabled = \WordfenceLS\Controller_Users::shared()->has_2fa_active($user);
$requires2fa = \WordfenceLS\Controller_Users::shared()->requires_2fa($user, $inGracePeriod, $requiredAt);
$lockedOut = $requires2fa && !$enabled;

$containerClasses = 'wfls-flex-row ' . ($stacked ? 'wfls-flex-row-wrapped' : 'wfls-flex-row-wrappable wfls-flex-row-equal-heights');
$columnClasses = 'wfls-flex-row wfls-flex-item-xs-100 ' . ($stacked ? '' : 'wfls-flex-row-equal-heights');

?>
<?php if (!empty($scriptData)): ?>
	<script type="text/javascript">
	<?php foreach ($scriptData as $key => $data): ?>
		var <?php echo $key ?> = <?php echo wp_json_encode($data); ?>;
	<?php endforeach ?>
	</script>
<?php endif ?>
<?php foreach ($assets as $asset): ?>
	<?php $asset->renderInlineIfNotEnqueued(); ?>
<?php endforeach ?>
<div id="wfls-management-embedded"<?php if ($stacked): ?> class="stacked" <?php endif ?>>
	<p><?php echo wp_kses(sprintf(__('Two-Factor Authentication, or 2FA, significantly improves login security for your account. Wordfence 2FA works with a number of TOTP-based apps like Google Authenticator, FreeOTP, and Authy. For a full list of tested TOTP-based apps, <a href="%s" target="_blank" rel="noopener noreferrer">click here</a>.', 'wordfence-login-security'), \WordfenceLS\Controller_Support::esc_supportURL(\WordfenceLS\Controller_Support::ITEM_MODULE_LOGIN_SECURITY_2FA)), array('a'=>array('href'=>array(), 'target'=>array(), 'rel'=>array()))); ?></p>
	<div id="wfls-deactivation-controls" class="<?php echo $containerClasses ?>"<?php if (!$enabled) { echo ' style="display: none;"'; } ?>>
		<!-- begin status content -->
		<div class="<?php echo $columnClasses ?>">
			<?php
			echo \WordfenceLS\Model_View::create('manage/deactivate', array(
				'user' => $user,
			))->render();
			?>
		</div>
		<!-- end status content -->
		<!-- begin regenerate codes -->
		<div class="<?php echo $columnClasses ?>">
			<?php
			echo \WordfenceLS\Model_View::create('manage/regenerate', array(
				'user' => $user,
				'remaining' => \WordfenceLS\Controller_Users::shared()->recovery_code_count($user),
			))->render();
			?>
		</div>
		<!-- end regenerate codes -->
	</div>
	<div id="wfls-activation-controls" class="<?php echo $containerClasses ?><?php if (!$stacked): ?> wfls-no-bottom-column-margin<?php endif ?>"<?php if ($enabled) { echo ' style="display: none;"'; } ?>>
		<?php
			$initializationData = new \WordfenceLS\Model_2faInitializationData($user);
		?>
		<!-- begin qr code -->
		<div class="<?php echo $columnClasses ?><?php if (!$stacked): ?> wfls-col-sm-half-padding-right wfls-flex-item-sm-50<?php endif ?>">
			<?php
			echo \WordfenceLS\Model_View::create('manage/code', array(
				'initializationData' => $initializationData
			))->render();
			?>
		</div>
		<!-- end qr code -->
		<!-- begin activation -->
		<div class="<?php echo $columnClasses ?><?php if (!$stacked): ?>  wfls-col-sm-half-padding-left wfls-flex-item-sm-50<?php endif ?>">
			<?php
			echo \WordfenceLS\Model_View::create('manage/activate', array(
				'initializationData' => $initializationData
			))->render();
			?>
		</div>
		<!-- end activation -->
	</div>
	<div id="wfls-grace-period-controls" class="<?php echo $containerClasses ?>"<?php if ($enabled || !($lockedOut || $inGracePeriod)) { echo ' style="display: none;"'; } ?>>
		<div class="<?php echo $columnClasses ?> wfls-add-top">
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
	?>
</div>