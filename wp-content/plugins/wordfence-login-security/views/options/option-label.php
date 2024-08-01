<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents an option-styled text value.
 *
 * Expects $title (or $titleHTML) to be defined. $helpLink may also be defined.
 *
 * @var string $title The title shown for the option.
 * @var string $titleHTML The raw HTML title shown for the option. This supersedes $title.
 * @var string $helpLink If defined, the link to the corresponding external help page.
 */

if (!isset($titleHTML)) {
	$titleHTML = esc_html($title);
}
?>
<ul class="wfls-option wfls-option-label">
	<?php if (!isset($noSpacer) || !$noSpacer): ?>
		<li class="wfls-option-spacer"></li>
	<?php endif; ?>
	<li class="wfls-option-content">
		<ul>
			<li class="wfls-option-title">
				<?php if (isset($subtitle)): ?>
				<ul class="wfls-flex-vertical wfls-flex-align-left">
					<li>
						<?php endif; ?>
						<?php echo $titleHTML; ?><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?>
						<?php if (isset($subtitle)): ?>
					</li>
					<li class="wfls-option-subtitle"><?php echo esc_html($subtitle); ?></li>
				</ul>
			<?php endif; ?>
			</li>
		</ul>
	</li>
</ul>