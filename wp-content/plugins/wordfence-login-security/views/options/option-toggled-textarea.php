<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents an option with a boolean on/off toggle checkbox and text area for detailed value entry.
 *
 * Expects $toggleOptionName, $enabledToggleValue, $disabledToggleValue, $toggleValue, $textAreaOptionName, $textAreaValue, and $title to be defined. $helpLink may also be defined.
 *
 * @var string $toggleOptionName The option name for the toggle portion.
 * @var string $enabledToggleValue The value to save in $toggleOption if the toggle is enabled.
 * @var string $disabledToggleValue The value to save in $toggleOption if the toggle is disabled.
 * @var string $toggleValue The current value of $toggleOptionName.
 * @var string $textAreaOptionName The option name for the text area portion.
 * @var string $textAreaValue The current value of $textAreaOptionName.
 * @var string $title The title shown for the option.
 * @var string $helpLink If defined, the link to the corresponding external help page.
 * @var bool $premium If defined, the option will be tagged as premium only and not allow its value to change for free users.
 */

$toggleID = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $toggleOptionName);
$textAreaID = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $textAreaOptionName);
?>
<ul class="wfls-option wfls-option-toggled-textarea<?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' wfls-option-premium'; } ?>" data-toggle-option="<?php echo esc_attr($toggleOptionName); ?>" data-enabled-toggle-value="<?php echo esc_attr($enabledToggleValue); ?>" data-disabled-toggle-value="<?php echo esc_attr($disabledToggleValue); ?>" data-original-toggle-value="<?php echo esc_attr($toggleValue == $enabledToggleValue ? $enabledToggleValue : $disabledToggleValue); ?>" data-text-area-option="<?php echo esc_attr($textAreaOptionName); ?>" data-original-text-area-value="<?php echo esc_attr($textAreaValue); ?>">
	<li id="<?php echo esc_attr($toggleID); ?>" class="wfls-option-checkbox<?php echo ($toggleValue == $enabledToggleValue ? ' wfls-checked' : ''); ?>" role="checkbox" aria-checked="<?php echo ($toggleValue == $enabledToggleValue ? 'true' : 'false'); ?>" tabindex="0"><i class="wfls-ion-ios-checkmark-empty" aria-hidden="true" aria-labelledby="<?php echo esc_attr($toggleID); ?>-label"></i></li>
	<li class="wfls-option-title"><span id="<?php echo esc_attr($toggleID); ?>-label"><?php echo esc_html($title); ?></span><?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' <a href="https://www.wordfence.com/gnl1optionUpgrade/wordfence-signup/" target="_blank" rel="noopener noreferrer" class="wfls-premium-link">' . esc_html__('Premium Feature', 'wordfence-login-security') . '</a>'; } ?><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?></li>
	<li id="<?php echo esc_attr($textAreaID); ?>" class="wfls-option-textarea">
		<select<?php echo ($toggleValue == $enabledToggleValue && !(!wfConfig::p() && isset($premium) && $premium) ? '' : ' disabled'); ?> aria-labelledby="<?php echo esc_attr($toggleID); ?>-label">
			<textarea<?php echo (!(!wfConfig::p() && isset($premium) && $premium) ? '' : ' disabled'); ?>><?php echo esc_html($textAreaValue); ?></textarea>
		</select>
	</li>
</ul>