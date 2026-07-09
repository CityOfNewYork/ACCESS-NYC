<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var \WP_User $user The user being edited. Required.
 * @var bool $inGracePeriod
 * @var bool $lockedOut
 * @var int $requiredAt
 */

$ownAccount = false;
$ownUser = wp_get_current_user();
if ($ownUser->ID == $user->ID) {
	$ownAccount = true;
}
$defaultGracePeriod = \WordfenceLS\Controller_Settings::shared()->get_user_2fa_grace_period();
$hasGracePeriod =  $defaultGracePeriod > 0;
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
	<div class="wfls-block-header wfls-block-header-border-bottom">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title">
				<strong><?php echo $gracePeriod ? esc_html__('Grace Period', 'wordfence') : esc_html__('Locked Out', 'wordfence') ?></strong>
			</div>
		</div>
	</div>
	<div class="wfls-block-content">
		<?php if ($gracePeriod): ?>
			<p><?php
				$requiredDateFormatted = \WordfenceLS\Controller_Time::format_local_time('F j, Y g:i A', $requiredAt);
				echo $ownAccount ?
					sprintf(wp_kses(/* translators: Date */ __('Two-factor authentication will be required for your account beginning <strong>%s</strong>', 'wordfence'), array('strong'=>array())), $requiredDateFormatted) :
					sprintf(wp_kses(/* translators: 1. Username; 2. Date */ __('Two-factor authentication will be required for user <strong>%1$s</strong> beginning <strong>%2$s</strong>.', 'wordfence'), array('strong'=>array())), esc_html($user->user_login), $requiredDateFormatted)
			?></p>
			<?php if (\WordfenceLS\Controller_Users::shared()->has_revokable_grace_period($user)): ?>
			<?php echo \WordfenceLS\Model_View::create(
				'common/revoke-grace-period',
				array(
					'user' => $user
				))->render() ?>
			<?php endif ?>
		<?php else: ?>
			<p>
				<?php echo $ownAccount ?
				esc_html__('Two-factor authentication is required for your account, but has not been configured.', 'wordfence') :
				esc_html__('Two-factor authentication is required for this account, but has not been configured.', 'wordfence') ?>
			</p>
			<?php echo \WordfenceLS\Model_View::create(
				'common/reset-grace-period',
				array(
					'user' => $user,
					'gracePeriod' => $gracePeriod,
					'defaultGracePeriod' => $defaultGracePeriod
				))->render() ?>
		<?php endif ?>
	</div>
</div>