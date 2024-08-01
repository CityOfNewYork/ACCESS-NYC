<?php
if (!defined('WORDFENCE_LS_VERSION')) { exit; }
/**
 * Presents a text field option.
 *
 * Expects $textOptionName, $textValue, and $title to be defined. $placeholder and $helpLink may also be defined.
 *
 * @var string $textOptionName The option name for the text field.
 * @var string $textValue The current value of $textOptionName.
 * @var string $title The title shown for the option.
 * @var string $placeholder If defined, the placeholder for the text field.
 * @var string $helpLink If defined, the link to the corresponding external help page.
 * @var bool $premium If defined, the option will be tagged as premium only and not allow its value to change for free users.
 */

if (!isset($placeholder)) {
	$placeholder = '';
}
$id = 'wfls-option-' . preg_replace('/[^a-z0-9]/i', '-', $textOptionName);
?>
<ul id="<?php echo esc_attr($id); ?>" class="wfls-option wfls-option-text<?php if (!wfConfig::p() && isset($premium) && $premium) { echo ' wfls-option-premium'; } ?>" data-text-option="<?php echo esc_attr($textOptionName); ?>" data-original-text-value="<?php echo esc_attr($textValue); ?>">
	<li class="wfls-option-spacer"></li>
	<li class="wfls-option-content">
		<ul>
			<li class="wfls-option-title">
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
			</li>
			<li class="wfls-option-text">
				<input type="text" value="<?php echo esc_attr($textValue); ?>" placeholder="<?php echo esc_attr($placeholder); ?>"<?php echo (!(!wfConfig::p() && isset($premium) && $premium) ? '' : ' disabled'); ?> aria-labelledby="<?php echo esc_attr($id); ?>-label">
			</li>
		</ul>
	</li>
</ul>