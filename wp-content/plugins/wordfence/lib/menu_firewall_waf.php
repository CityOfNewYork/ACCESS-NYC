<?php
if (!defined('WORDFENCE_VERSION')) { exit; }
$waf = wfWAF::getInstance();
$d = new wfDashboard(); unset($d->countriesNetwork);
$firewall = new wfFirewall();
$config = $waf->getStorageEngine();
$wafConfigURL = network_admin_url('admin.php?page=WordfenceWAF&subpage=waf_options#configureAutoPrepend');
$wafRemoveURL = network_admin_url('admin.php?page=WordfenceWAF&subpage=waf_options#removeAutoPrepend');
/** @var array $wafData */
?>

<div class="wordfence-vue-wrapper" data-base-component="FirewallHeader"></div>
<div class="wf-row">
	<div class="wf-col-xs-12">
		<div class="wf-block wf-active">
			<div class="wf-block-content">
				<ul class="wf-block-list">
					<li>
						<ul class="wf-block-list wf-block-list-horizontal wf-waf-navigation">
							<li>
								<?php
								echo wfView::create('common/block-navigation-option', array(
									'id' => 'waf-option-rate-limiting',
									'img' => 'ratelimiting.svg',
									'title' => __('Rate Limiting', 'wordfence'),
									'subtitle' => __('Block crawlers that are using too many resources or stealing content', 'wordfence'),
									'link' => network_admin_url('admin.php?page=WordfenceWAF&subpage=waf_options#waf-options-ratelimiting'),
								))->render();
								?>
							</li>
							<li>
								<?php
								echo wfView::create('common/block-navigation-option', array(
									'id' => 'waf-option-blocking',
									'img' => 'blocking.svg',
									'title' => __('Blocking', 'wordfence'),
									'subtitle' => __('Block traffic by country, IP, IP range, user agent, referrer, or hostname', 'wordfence'),
									'link' => '#top#blocking',
								))->render();
								?>
							</li>
						</ul>
					</li>
					<li>
						<ul class="wf-block-list wf-block-list-horizontal wf-waf-navigation">
							<li>
								<?php
								echo wfView::create('common/block-navigation-option', array(
									'id' => 'waf-option-support',
									'img' => 'support.svg',
									'title' => __('Help', 'wordfence'),
									'subtitle' => __('Find the documentation and help you need', 'wordfence'),
									'link' => network_admin_url('admin.php?page=WordfenceSupport'),
								))->render();
								?>
							</li>
							<li>
								<?php
								echo wfView::create('common/block-navigation-option', array(
									'id' => 'waf-option-all-options',
									'img' => 'options.svg',
									'title' => __('All Firewall Options', 'wordfence'),
									'subtitle' => __('Manage global and advanced firewall options', 'wordfence'),
									'link' => network_admin_url('admin.php?page=WordfenceWAF&subpage=waf_options'),
								))->render();
								?>
							</li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<div class="wf-row">
	<div class="wf-col-xs-12 wf-col-lg-6 wf-col-lg-half-padding-right">
		<!-- begin top ips blocked -->
		<div class="wordfence-vue-wrapper" data-base-component="WidgetIPs" data-prop-dashboard-data="<?php echo esc_attr($d->toJson(array('ips24h', 'ips7d', 'ips30d'))); ?>"></div>
		<!-- end top ips blocked -->
		<!-- begin countries blocked -->
		<?php include(dirname(__FILE__) . '/dashboard/widget_countries.php'); ?>
		<!-- end countries blocked -->
	</div> <!-- end content block -->
	<div class="wf-col-xs-12 wf-col-lg-6 wf-col-lg-half-padding-left">
		<!-- begin firewall summary site -->
		<?php include(dirname(__FILE__) . '/dashboard/widget_localattacks.php'); ?>
		<!-- end firewall summary site -->
		<!-- begin total attacks blocked network -->
		<?php include(dirname(__FILE__) . '/dashboard/widget_networkattacks.php'); ?>
		<!-- end total attacks blocked network -->
		<!-- begin recent logins -->
		<div class="wordfence-vue-wrapper" data-base-component="WidgetLogins" data-prop-dashboard-data="<?php echo esc_attr($d->toJson(array('loginsSuccess', 'loginsFail'))); ?>"></div>
		<!-- end recent logins -->
	</div> <!-- end content block -->
</div> <!-- end row -->
<?php if (wfOnboardingController::willShowNewTour(wfOnboardingController::TOUR_FIREWALL)): ?>
<div class="wordfence-vue-wrapper" data-base-component="FirewallNewTour"></div>
<?php endif; ?>
