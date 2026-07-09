<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<?php printf(
/* translators: 1. Blog name/title. 2. Date. */
		__('This email was sent from your website "%1$s" by the Wordfence plugin at %2$s', 'wordfence'), $blogName, $date); ?>

<?php printf(
/* translators: URL to the WordPress admin panel. */
		__('The Wordfence administrative URL for this site is: %s', 'wordfence'), wfUtils::wpAdminURL('admin.php?page=Wordfence')); ?>

<?php echo $alertMsg; ?>
<?php if($IPMsg){ echo "\n$IPMsg\n"; } ?>

<?php if(! $isPaid){ ?>
	<?php esc_html_e('NOTE: You are using the free version of Wordfence. Upgrade today:
 - Receive real-time Firewall and Scan engine rule updates for protection as threats emerge
 - Real-time IP Blocklist blocks the most malicious IPs from accessing your site
 - Country blocking
 - IP reputation monitoring
 - Schedule scans to run more frequently and at optimal times
 - Access to Premium Support
 - Discounts for multi-year and multi-license purchases

Click here to upgrade to Wordfence Premium:
https://www.wordfence.com/zz1/wordfence-signup/', 'wordfence'); ?>
<?php } ?>

--
<?php printf(
/* translators: URL to the WordPress admin panel. */
		__("To change your alert options for Wordfence, visit:\n%s", 'wordfence'), $myOptionsURL); ?>

<?php printf(
/* translators: URL to the WordPress admin panel. */
		__("To see current Wordfence alerts, visit:\n%s", 'wordfence'), $myHomeURL); ?>


