<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents an option with a token field for value entry.
 *
 * Expects $tokenOptionName, $tokenValue, and $title to be defined. $helpLink may also be defined.
 *
 * @var string $tokenOptionName The option name.
 * @var array $tokenValue The current value of $tokenOptionName. It will be JSON-encoded as an array of strings.
 * @var string $title The title shown for the option.
 * @var string $helpLink If defined, the link to the corresponding external help page.
 * @var bool $premium If defined, the option will be tagged as premium only and not allow its value to change for free users.
 */

$id = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $tokenOptionName);
?>
<ul id="<?php echo esc_attr($id); ?>" class="wfls-option wfls-option-token<?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' wfls-option-premium'; } ?>" data-token-option="<?php echo esc_attr($tokenOptionName); ?>" data-original-token-value="<?php echo esc_attr(json_encode($tokenValue)); ?>">
	<li class="wfls-option-spacer"></li>
	<li class="wfls-flex-vertical wfls-flex-align-left">
		<div class="wfls-option-title">
		<?php if (isset($subtitle)): ?>
			<ul class="wfls-flex-vertical wfls-flex-align-left">
				<li>
		<?php endif; ?>
					<span id="<?php echo esc_attr($id); ?>-label"><?php echo esc_html($title); ?></span><?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' <a href="https://www.wordfence.com/gnl1optionUpgrade/wordfence-signup/" target="_blank" rel="noopener noreferrer" class="wfls-premium-link">' . esc_html__('Premium Feature', 'wordfence-login-security') . '</a>'; } ?><?php if (isset($helpLink)) { echo ' <a href="' . esc_attr($helpLink) . '"  target="_blank" rel="noopener noreferrer" class="wfls-inline-help"><i class="' . (\WordfenceLS\Controller_WordfenceLS::shared()->should_use_core_font_awesome_styles() ? 'wf-fa wf-fa-question-circle-o' : 'wfls-fa wfls-fa-question-circle-o') . '" aria-hidden="true"></i></a>'; } ?>
		<?php if (isset($subtitle)): ?>
				</li>
				<li class="wfls-option-subtitle"><?php echo esc_html($subtitle); ?></li>
			</ul>
		<?php endif; ?>
		</div>
		<select multiple<?php echo (!(!wfConfig::p() && isset($premium) && $premium) ? '' : ' disabled'); ?> aria-labelledby="<?php echo esc_attr($id); ?>-label">
		<?php foreach ($tokenValue as $o): ?>
			<option value="<?php echo esc_attr($o); ?>" selected><?php echo esc_html($o); ?></option>
		<?php endforeach; ?>
		</select>
		<div class="wfls-option-token-tags"></div>
	</li>
</ul>