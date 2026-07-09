<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"  dir="ltr" lang="en-US">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel='stylesheet' id='wordfence-main-style-css'  href='<?php echo wfUtils::getBaseURL() . wfUtils::versionedAsset('css/diff.css'); ?>?ver=<?php echo WORDFENCE_VERSION; ?>' type='text/css' media='all' />
</head>
<body>
<h1><?php esc_html_e('Wordfence: Viewing File Differences', 'wordfence') ?></h1>
<p style="width: 800px; font-size: 16px; font-family: Verdana;">
	<?php esc_html_e('The two panels below show a before and after view of a file on your system that has been modified. The left panel shows the original file before modification. The right panel shows your version of the file that has been modified. Use this view to determine if a file has been modified by an attacker or if this is a change that you or another trusted person made. If you are happy with the modifications you see here, then you should choose to ignore this file the next time Wordfence scans your system.', 'wordfence') ?>
</p>
<table border="0" style="margin: 0 0 20px 0;" class="summary">
<tr><td><?php esc_html_e('Filename:', 'wordfence') ?></td><td><?php echo wp_kses($_GET['file'], array()); ?></td></tr>
<tr><td><?php esc_html_e('File type:', 'wordfence') ?></td><td><?php
	$cType = $_GET['cType'];
	if($cType == 'core'){
		esc_html_e('WordPress Core File', 'wordfence') . "</td></tr>";
	} else if($cType == 'theme'){
		echo esc_html__('Theme File', 'wordfence') . "</td></tr><tr><td>" .
			esc_html__('Theme Name:', 'wordfence')
			. "</td><td>" . wp_kses($_GET['cName'], array()) . "</td></tr><tr><td>" .
			esc_html__('Theme Version:', 'wordfence') . "</td><td>" . wp_kses($_GET['cVersion'], array()) . "</td></tr>";
	} else if($cType == 'plugin'){
		echo esc_html__('Plugin File', 'wordfence') . "</td></tr><tr><td>" .
			esc_html__('Plugin Name:', 'wordfence') . "</td><td>" . wp_kses($_GET['cName'], array()) . "</td></tr><tr><td>" .
			esc_html__('Plugin Version:', 'wordfence') . "</td><td>" . wp_kses($_GET['cVersion'], array()) . "</td></tr>";
	} else {
		echo esc_html__('Unknown Type', 'wordfence') . "</td></tr>";
	}
	?>
</table>

<?php 
	if($diffResult){
		echo $diffResult; 
	} else {
		echo "<br />" . esc_html__('There are no differences between the original file and the file in the repository.', 'wordfence');
	}

?>


<div class="diffFooter"><?php echo wp_kses(sprintf(/* translators: 1. Start year; 2. End year */ __('&copy;&nbsp;%1$d to %2$d Wordfence &mdash; Visit <a href="https://www.wordfence.com/">Wordfence.com</a> for help, security updates and more.', 'wordfence'), date_i18n('Y', WORDFENCE_EPOCH), date_i18n('Y')), array('a'=>array('href'=>array()))) ?></div>
</body>
</html>