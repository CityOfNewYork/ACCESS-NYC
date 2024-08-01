<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents a text area option.
 *
 * Expects $textOptionName, $textValue, and $title to be defined. $helpLink, $premium, and $noSpacer may also be defined.
 *
 * @var string $textOptionName The option name for the text field. Required.
 * @var string $textValue The current value of $textOptionName. Required.
 * @var string|\WordfenceLS\Text\Model_HTML $title The title shown for the option. Required.
 * @var string|\WordfenceLS\Text\Model_HTML $subtitle The title shown for the option. Optional.
 * @var string $subtitlePosition The position for the subtitle: 'value' for below the value, 'title' for below the title. Optional.
 * @var string $helpLink If defined, the link to the corresponding external help page. Optional.
 * @var bool $noSpacer If defined and truthy, the spacer will be omitted. Optional.
 */

if (!isset($subtitlePosition)) { //May be 'title' to appear below the title or 'value' to appear below the field
	$subtitlePosition = 'title';
}

$id = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $textOptionName);
?>
<ul id="<?php echo esc_attr($id); ?>" class="wfls-option wfls-option-textarea" data-text-option="<?php echo esc_attr($textOptionName); ?>" data-original-text-value="<?php echo esc_attr($textValue); ?>">
	<?php if (!isset($noSpacer) || !$noSpacer): ?>
	<li class="wfls-option-spacer"></li>
	<?php endif; ?>
	<li class="wfls-option-content wfls-no-right">
		<ul>
			<li class="wfls-option-title<?php if (isset($alignTitle)) { echo $alignTitle == 'top' ? ' wfls-option-title-top' : ($alignTitle == 'bottom' ? 'wfls-option-title-bottom' : ''); } ?>">
				<?php if (isset($subtitleHTML) && $subtitlePosition == 'title'): ?>
				<ul class="wfls-flex-vertical wfls-flex-align-left">
					<li>
				<?php endif; ?>
						<span id="<?php echo esc_attr($id); ?>-label"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title); ?></span><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?>
				<?php if (isset($subtitle) && $subtitlePosition == 'title'): ?>
					</li>
					<li class="wfls-option-subtitle"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($subtitle); ?></li>
				</ul>
				<?php endif; ?>
			</li>
			<li class="wfls-option-textarea">
				<?php if (isset($subtitle) && $subtitlePosition == 'value'): ?>
				<ul class="wfls-flex-vertical wfls-flex-align-left wfls-flex-full-width">
					<li>
				<?php endif; ?>
				<textarea aria-labelledby="<?php echo esc_attr($id); ?>-label"><?php echo esc_html($textValue); ?></textarea>
				<?php if (isset($subtitle) && $subtitlePosition == 'value'): ?>
					</li>
					<li class="wfls-option-subtitle"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($subtitle); ?></li>
				</ul>
				<?php endif; ?>
			</li>
		</ul>
	</li>
</ul>