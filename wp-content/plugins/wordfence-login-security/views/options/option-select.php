<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents an option with a popup menu for detailed value selection.
 *
 * Expects $selectOptionName, $selectOptions, $selectValue, and $title to be defined. $helpLink may also be defined.
 *
 * @var string $selectOptionName The option name for the select portion.
 * @var array $selectOptions An array of the possible values for $selectOptionName. The array is of the format array(array('value' => <the internal value>, 'label' => <a display label>), ...)
 * @var string $selectValue The current value of $selectOptionName.
 * @var string $title The title shown for the option.
 * @var string $helpLink If defined, the link to the corresponding external help page.
 * @var bool $premium If defined, the option will be tagged as premium only and not allow its value to change for free users.
 */

$id = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $selectOptionName);
?>
<ul id="<?php echo esc_attr($id); ?>" class="wfls-option wfls-option-select<?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' wfls-option-premium'; } ?>" data-select-option="<?php echo esc_attr($selectOptionName); ?>" data-original-select-value="<?php echo esc_attr($selectValue); ?>">
	<li class="wfls-option-spacer"></li>
	<li class="wfls-option-content">
		<ul>
			<li class="wfls-option-title"><span id="<?php echo esc_attr($id); ?>-label"><?php echo esc_html($title); ?></span><?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' <a href="https://www.wordfence.com/gnl1optionUpgrade/wordfence-signup/" target="_blank" rel="noopener noreferrer" class="wfls-premium-link">' . esc_html__('Premium Feature', 'wordfence-login-security') . '</a>'; } ?><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?></li>
			<li class="wfls-option-select wfls-padding-add-top-xs-small">
				<select<?php echo (!(!wfConfig::p() && isset($premium) && $premium) ? '' : ' disabled'); ?> aria-labelledby="<?php echo esc_attr($id); ?>-label">
					<?php foreach ($selectOptions as $o): ?>
						<option class="wfls-option-select-option" value="<?php echo esc_attr($o['value']); ?>"<?php if ($o['value'] == $selectValue) { echo ' selected'; } ?>><?php echo esc_html($o['label']); ?></option>
					<?php endforeach; ?>
				</select>
			</li>
		</ul>
	</li>
</ul>