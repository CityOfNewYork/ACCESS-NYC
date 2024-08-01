<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents a boolean option with a checkbox toggle control.
 * 
 * @var string $optionName The option name. Required.
 * @var string $enabledValue The value to save in $option if the toggle is enabled. Required.
 * @var string $disabledValue The value to save in $option if the toggle is disabled. Required.
 * @var string $value The current value of $optionName. Required.
 * @var string|\WordfenceLS\Text\Model_HTML $title The title shown for the option. Required.
 * @var string|\WordfenceLS\Text\Model_HTML $subtitle The title shown for the option. Optional.
 * @var string $helpLink If defined, the link to the corresponding external help page. Optional.
 * @var bool $disabled If defined and truthy, the option will start out disabled. Optional.
 * @var bool $child If true, this option will be rendered ar a child option. Optional.
 */

$id = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $optionName);
?>
<ul id="<?php echo esc_attr($id); ?>" class="wfls-option wfls-option-toggled<?php if (isset($disabled) && $disabled) { echo ' wfls-disabled'; } if (isset($child) && $child) { echo ' wfls-child-option'; }?>" data-option="<?php echo esc_attr($optionName); ?>" data-enabled-value="<?php echo esc_attr($enabledValue); ?>" data-disabled-value="<?php echo esc_attr($disabledValue); ?>" data-original-value="<?php echo esc_attr($value == $enabledValue ? $enabledValue : $disabledValue); ?>">
	<li class="wfls-option-checkbox<?php echo ($value == $enabledValue ? ' wfls-checked' : ''); ?>" role="checkbox" aria-checked="<?php echo ($value == $enabledValue ? 'true' : 'false'); ?>" tabindex="0" aria-labelledby="<?php echo esc_attr($id); ?>-label"><i class="wfls-ion-ios-checkmark-empty" aria-hidden="true"></i></li>
	<li class="wfls-option-title">
	<?php if (isset($subtitle)): ?>
		<ul class="wfls-flex-vertical wfls-flex-align-left">
			<li>
	<?php endif; ?>
				<span id="<?php echo esc_attr($id); ?>-label"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($title); ?></span><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?>
	<?php if (isset($subtitle)): ?>
			</li>
			<li class="wfls-option-subtitle"><?php echo \WordfenceLS\Text\Model_HTML::esc_html($subtitle); ?></li>
		</ul>
	<?php endif; ?>
	</li>
</ul>