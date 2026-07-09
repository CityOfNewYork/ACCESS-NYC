<?php
if (!defined('WORDFENCE_VERSION')) exit;
/**
 * Displays a message that application passwords are disabled
 * @var bool $includeLink Whether or not to display a link to the options page to enable application passwords
 */
$isAdmin = isset($isAdmin) && $isAdmin;
?>
<h2><?php esc_html_e('Application Passwords', 'wordfence'); ?></h2>
<table class="form-table" role="presentation">
	<tr>
		<th><?php esc_html_e('Disabled', 'wordfence') ?></th>
		<td>
			<p>
				<?php esc_html_e('Application passwords have been disabled by Wordfence.', 'wordfence') ?>
				<?php if (!$isAdmin): ?>
					<?php esc_html_e('The site admin can change this option.', 'wordfence') ?>
				<?php endif ?>
			</p>
			<?php if ($isAdmin): ?>
			<?php
				$optionsUrl = 'admin.php?page=WordfenceWAF&subpage=waf_options#wf-option-loginSec-disableApplicationPasswords-label';
			?>
			<p><a href="<?php echo esc_attr(is_multisite() ? network_admin_url($optionsUrl) : admin_url($optionsUrl)) ?>" class="button"><?php esc_html_e('Edit Wordfence Options', 'wordfence') ?></a></p>
			<?php endif ?>
		</td>
	</tr>
</table>