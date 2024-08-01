<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * @var ?array $counts The counts to display or null to hide user counts.
 */
?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width">
	<div class="wfls-block-header wfls-block-header-border-bottom">
		<div class="wfls-block-header-content">
			<div class="wfls-block-title">
				<h3><?php esc_html_e('User Summary', 'wordfence-login-security'); ?></h3>
			</div>
		</div>
		<div class="wfls-block-header-action wfls-block-header-action-text wfls-nowrap wfls-padding-add-right-responsive">
			<a href="users.php"><?php esc_html_e('Manage Users', 'wordfence-login-security'); ?></a>
		</div>
	</div>
	<?php if (is_array($counts)) : ?>
	<div class="wfls-block-content wfls-padding-no-left wfls-padding-no-right">
		<table class="wfls-table wfls-table-striped wfls-table-header-separators wfls-table-expanded wfls-no-bottom">
			<thead>
			<tr>
				<th><?php esc_html_e('Role', 'wordfence-login-security'); ?></th>
				<th class="wfls-center"><?php esc_html_e('Total Users', 'wordfence-login-security'); ?></th>
				<th class="wfls-center"><?php esc_html_e('2FA Active', 'wordfence-login-security'); ?></th>
				<th class="wfls-center"><?php esc_html_e('2FA Inactive', 'wordfence-login-security'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$roles = new WP_Roles();
			$roleNames = $roles->get_names();
			$roleNames['super-admin'] = __('Super Administrator', 'wordfence-login-security');
			$roleNames[\WordfenceLS\Controller_Users::TRUNCATED_ROLE_KEY] = __('Custom Capabilities / Multiple Roles', 'wordfence-login-security');
			foreach ($counts['avail_roles'] as $roleTag => $count):
				$activeCount = (isset($counts['active_avail_roles'][$roleTag]) ? $counts['active_avail_roles'][$roleTag] : 0);
				$inactiveCount = $count - $activeCount;
				if ($activeCount === 0 && $inactiveCount === 0)
					continue;
				$roleName = $roleNames[$roleTag];
				$requiredAt = \WordfenceLS\Controller_Settings::shared()->get_required_2fa_role_activation_time($roleTag);
				$inactive = $inactiveCount > 0 && $requiredAt !== false;
				$viewUsersBaseUrl = 'admin.php?' . http_build_query(array('page' => 'WFLS', 'role'=> $roleTag));
			?>
				<tr>
					<td><?php echo \WordfenceLS\Text\Model_HTML::esc_html(translate_user_role($roleName)); ?></td>
					<td class="wfls-center"><?php echo number_format($count); ?></td>
					<td class="wfls-center"><?php echo number_format($activeCount); ?></td>
					<td class="wfls-center">
						<?php if ($inactive): ?><a href="<?php echo esc_attr(is_multisite() ? network_admin_url($viewUsersBaseUrl) : admin_url($viewUsersBaseUrl)); ?>"><?php endif ?>
						<?php echo number_format($inactiveCount); ?>
						<?php if ($inactive): ?> (<?php esc_html_e('View users', 'wordfence-login-security') ?>)</a><?php endif ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<th><?php esc_html_e('Total', 'wordfence-login-security'); ?></th>
				<th class="wfls-center"><?php echo number_format($counts['total_users']); ?></th>
				<th class="wfls-center"><?php echo number_format($counts['active_total_users']); ?></th>
				<th class="wfls-center"><?php echo number_format($counts['total_users'] - $counts['active_total_users']); ?></th>
			</tr>
			<?php if (is_multisite()): ?>
			<tr>
				<td colspan="4" class="wfls-text-small"><?php esc_html_e('* User counts currently only reflect the main site on multisite installations.', 'wordfence-login-security'); ?></td>
			</tr>
			<?php endif; ?>
			</tfoot>
		</table>
	</div>
	<?php else: ?>
	<div class="wfls-block-content wfls-padding-add-bottom">
		<p><?php $counts === null ? esc_html_e('User counts are hidden by default on sites with large numbers of users in order to improve performance.', 'wordfence-login-security') : esc_html_e('User counts are currently disabled as the most recent attempt to count users failed to complete successfully.', 'wordfence-login-security') ?></p>
		<a href="<?php echo esc_attr(add_query_arg('wfls-show-user-counts', 'true') . '#top#settings') ?>" class="wfls-btn wfls-btn-sm wfls-btn-primary"<?php if (\WordfenceLS\Controller_Users::shared()->should_force_user_counts()): ?> onclick="window.location.reload()"<?php endif ?>><?php $counts === null ? esc_html_e('Show User Counts', 'wordfence-login-security') : esc_html_e('Try Again', 'wordfence-login-security') ?></a>
	</div>
	<?php endif ?>
</div>