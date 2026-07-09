<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<?php if(! wfUtils::isAdmin()){ exit(); } ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"  dir="ltr" lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel='stylesheet' id='wordfence-main-style-css'  href='<?php echo wfUtils::getBaseURL() . wfUtils::versionedAsset('css/diff.css'); ?>?ver=<?php echo WORDFENCE_VERSION; ?>' type='text/css' media='all' />
<body>
<h1><?php esc_html_e('Wordfence: File Viewer', 'wordfence') ?></h1>
<table border="0" style="margin: 0 0 20px 0;" class="summary">
<tr><td><?php esc_html_e('Filename:', 'wordfence') ?></td><td><?php echo wp_kses($localFile, array()); ?></td></tr>
<tr><td><?php esc_html_e('File Size:', 'wordfence') ?></td><td><?php echo $fileSize; ?></td></tr>
<tr><td><?php esc_html_e('File last modified:', 'wordfence') ?></td><td><?php echo $fileMTime; ?></td></tr>
</table>

<?php 
	if($isEmpty){
		echo "File is empty.";
	} else {
		highlight_string($cont);
	}
?>



<div class="diffFooter"><?php echo wp_kses(sprintf(/* translators: 1. Start year; 2. End year */ __('&copy;&nbsp;%1$d to %2$d Wordfence &mdash; Visit <a href="https://www.wordfence.com/">Wordfence.com</a> for help, security updates and more.', 'wordfence'), date_i18n('Y', WORDFENCE_EPOCH), date_i18n('Y')), array('a'=>array('href'=>array()))); ?></div>
</body>
</html>