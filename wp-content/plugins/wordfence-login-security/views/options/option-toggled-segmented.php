<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents a boolean option with a checkbox toggle control.
 *
 * Expects $optionName, $enabledValue, $disabledValue, $value, and $title to be defined. $helpLink may also be defined.
 *
 * @var string $optionName The option name.
 * @var string $enabledValue The value to save in $option if the toggle is enabled.
 * @var string $disabledValue The value to save in $option if the toggle is disabled.
 * @var string $value The current value of $optionName.
 * @var string $title The title shown for the option.
 * @var string $htmlTitle The unescaped title shown for the option.
 * @var string $helpLink If defined, the link to the corresponding external help page.
 * @var bool $premium If defined, the option will be tagged as premium only and not allow its value to change for free users.
 */

$id = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $optionName);
?>
<ul id="<?php echo esc_attr($id); ?>" class="wfls-option wfls-option-toggled-segmented<?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' wfls-option-premium'; } ?>" data-option="<?php echo esc_attr($optionName); ?>" data-enabled-value="<?php echo esc_attr($enabledValue); ?>" data-disabled-value="<?php echo esc_attr($disabledValue); ?>" data-original-value="<?php echo esc_attr($value == $enabledValue ? $enabledValue : $disabledValue); ?>">
	<li class="wfls-option-title"><?php echo (!empty($title)) ? esc_html($title) : ''; echo (!empty($htmlTitle)) ? wp_kses($htmlTitle, 'post') : ''; ?><?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' <a href="https://www.wordfence.com/gnl1optionUpgrade/wordfence-signup/" target="_blank" rel="noopener noreferrer" class="wfls-premium-link">' . esc_html__('Premium Feature', 'wordfence-login-security') . '</a>'; } ?><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?></li>
	<li class="wfls-option-segments">
		<?php
		$onId = sanitize_key('wfls-segment-' . $optionName . '-on');
		$offId = sanitize_key('wfls-segment-' . $optionName . '-off');
		?>
		<input id="<?php echo esc_attr($onId) ?>" type="radio" name="<?php echo esc_attr($optionName) ?>" value="<?php echo esc_attr($enabledValue) ?>"<?php echo ($value == $enabledValue ? ' checked' : ''); ?><?php echo (!(!wfConfig::p() && isset($premium) && $premium) ? '' : ' disabled'); ?>>
		<label class="wfls-segment-first" for="<?php echo esc_attr($onId) ?>">On</label>

		<input id="<?php echo esc_attr($offId) ?>" type="radio" name="<?php echo esc_attr($optionName) ?>" value="<?php echo esc_attr($disabledValue) ?>"<?php echo ($value == $disabledValue ? ' checked' : ''); ?><?php echo (!(!wfConfig::p() && isset($premium) && $premium) ? '' : ' disabled'); ?>>
		<label class="wfls-segment-last" for="<?php echo esc_attr($offId) ?>">Off</label>
	</li>
</ul>