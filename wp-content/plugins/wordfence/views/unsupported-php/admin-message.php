<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
/**
 * Presents the unsupported PHP version  modal.
 */

require(dirname(__FILE__) . '/../../lib/wfVersionSupport.php');
/**
 * @var string $wfPHPDeprecatingVersion
 * @var string $wfPHPMinimumVersion
 */
?>
<div style="padding: 10px; border: 2px solid #00709e; background-color: #fff; margin: 20px 20px 10px 0px; color: #00709e">
	<img style="display: block; float: left; margin: 0 10px 0 0" src="<?php echo plugins_url('', WORDFENCE_FCPATH) . '/' ?>images/wordfence-logo.svg" alt="" width="35" height="35">
	<p style="margin: 10px"><?php echo esc_html(sprintf(
		/* translators: 1. PHP version. 2. Wordfence version. 3. Minimum PHP version. */
			__('You are running PHP version %1$s that is not supported by Wordfence %2$s. Wordfence features will not be available until PHP has been upgraded. We recommend using PHP version 8.1, but Wordfence will run on PHP version %3$s at a minimum.', 'wordfence'),
			PHP_VERSION,
			WORDFENCE_VERSION,
		  $wfPHPMinimumVersion
		)) ?></p>
</div>