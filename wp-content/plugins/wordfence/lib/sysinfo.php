<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<?php if(! wfUtils::isAdmin()){ exit(); } ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"  dir="ltr" lang="en-US">
<head>
<title><?php esc_html_e('Wordfence System Info', 'wordfence') ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel='stylesheet' id='wordfence-main-style-css'  href='<?php echo wfUtils::getBaseURL() . wfUtils::versionedAsset('css/phpinfo.css'); ?>?ver=<?php echo WORDFENCE_VERSION; ?>' type='text/css' media='all' />
<body>
<?php 
ob_start();
if (wfUtils::funcEnabled('phpinfo')) { phpinfo(INFO_ALL); } else { echo '<center><strong>' . esc_html__('Unable to output phpinfo content because it is disabled', 'wordfence') . "</strong></center>\n"; }
$out = ob_get_clean();
$out = str_replace('width="600"','width="900"', $out);
// $out = preg_replace('/<hr.*?PHP Credits.*?<\/h1>/s', '', $out);
$out = preg_replace('/<a [^>]+>/', '', $out);
$out = preg_replace('/<\/a>/', '', $out);
$out = preg_replace('/<title>[^<]*<\/title>/','', $out);
echo $out;
?>
<div class="diffFooter"><?php echo wp_kses(sprintf(/* translators: 1. Start year; 2. End year */ __('&copy;&nbsp;%1$d to %2$d Wordfence &mdash; Visit <a href="https://www.wordfence.com/">Wordfence.com</a> for help, security updates and more.', 'wordfence'), date_i18n('Y', WORDFENCE_EPOCH), date_i18n('Y')), array('a'=>array('href'=>array()))) ?></div>
</body>
</html>