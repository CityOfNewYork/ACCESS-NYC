<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
?>
<?php if (is_multisite()): ?>
	<p><em>(<?php esc_html_e('This page only shows users and roles on the main site of this network', 'wordfence-login-security') ?>)</em></p>
<?php endif ?>
<div class="wfls-block wfls-always-active wfls-flex-item-full-width wfls-add-bottom">
	<?php if ($requiredAt === false): ?>
	<div class="wfls-block-content">
		<p><?php echo esc_html(sprintf(__('2FA is not required for the %s role', 'wordfence-login-security'), $roleTitle)) ?></p>
	</div>
	<?php elseif (empty($users)): ?>
	<div class="wfls-block-content">
		<p>
		<?php if ($page == 1): ?>
			<?php echo esc_html(sprintf(__('No users found in the %s state for the %s role', 'wordfence-login-security'), $stateTitle, $roleTitle)) ?>
		<?php else: ?>
			<?php echo esc_html(sprintf(__('Page %d is out of range', 'wordfence-login-security'), $page)) ?>
		<?php endif ?>
		</p>
	</div>
	<?php else: ?>
	<table class="wfls-table wfls-table-striped wfls-table-header-separators wfls-table-expanded wfls-no-bottom">
		<tr>
			<th>User</th>
			<th>Required Date</th>
		</tr>
		<?php foreach ($users as $user): ?>
			<tr>
				<th scope="row"><a href="<?php echo esc_attr(get_edit_user_link($user->user_id)) ?>#wfls-user-settings"><?php echo esc_html($user->user_login) ?></a></td>
				<td>
					<?php if ($user->required_at): ?>
					<?php echo esc_html(\WordfenceLS\Controller_Time::format_local_time('F j, Y g:i A', $user->required_at)) ?>
					<?php else: ?>
					<?php esc_html_e('N/A', 'wordfence-login-security'); ?>
					<?php endif ?>
				</td>
			</tr>
		<?php endforeach ?>
		<?php if ($page != 1 || !$lastPage): ?>
		<tr>
			<td colspan="2" class="wfls-center">
				<?php if ($page > 1): ?>
					<a href="<?php echo esc_attr(add_query_arg($pageKey, $page-1) . "#$stateKey") ?>"><span class="dashicons dashicons-arrow-left-alt2"></span></a>
				<?php endif ?>
				<strong class="wfls-page-indicator"><?php esc_html_e('Page ', 'wordfence-login-security') ?><?php echo (int) $page ?></strong>
				<?php if (!$lastPage): ?>
					<a href="<?php echo esc_attr(add_query_arg($pageKey, $page+1) . "#$stateKey") ?>"><span class="dashicons dashicons-arrow-right-alt2"></span></a>
				<?php endif ?>
			</td>
		</tr>
		<?php endif ?>
	</table>
	<?php endif ?>
</div>