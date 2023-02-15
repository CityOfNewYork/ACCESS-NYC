<?php

if( !defined( 'ABSPATH' ) ) exit();

$active_app = $this->get_option( 'active_app' );
$active_app = ($active_app === 'custom' && $this->app) ? 'custom' : 'local';

$retries_chart_title = '';
$retries_chart_desc = '';
$retries_chart_color = '';
$retries_chart_show_actions = false;

$api_stats = false;
$retries_count = 0;
if( $active_app === 'local' ) {

	$retries_stats = $this->get_option( 'retries_stats' );

	if( $retries_stats ) {
		if( array_key_exists( date_i18n( 'Y-m-d' ), $retries_stats ) ) {
			$retries_count = (int) $retries_stats[date_i18n( 'Y-m-d' )];
		}
	}

    if( $retries_count === 0 ) {

		$retries_chart_title = __( 'Hooray! Zero failed login attempts today', 'limit-login-attempts-reloaded' );
		$retries_chart_color = '#66CC66';
    }
    else if ( $retries_count < 100 ) {

		$retries_chart_title = sprintf( _n( '%d failed login attempt ', '%d failed login attempts ', $retries_count, 'limit-login-attempts-reloaded' ), $retries_count );
		$retries_chart_title .= __( 'today', 'limit-login-attempts-reloaded' );
		$retries_chart_desc = __( 'Your site might have been discovered by hackers', 'limit-login-attempts-reloaded' );
		$retries_chart_color = '#FFCC66';
    } else {

		$retries_chart_title = __( 'Warning: More than 100 failed login attempts today', 'limit-login-attempts-reloaded' );
		$retries_chart_desc = __( 'Your site is likely under a brute-force attack', 'limit-login-attempts-reloaded' );
		$retries_chart_color = '#FF6633';
		$retries_chart_show_actions = true;
    }

} else {

	$api_stats = $this->app->stats();

	if( $api_stats && !empty( $api_stats['attempts']['count'] )) {

		$retries_count = (int) end( $api_stats['attempts']['count'] );
    }

	$retries_chart_title = __( 'Failed Login Attempts Today', 'limit-login-attempts-reloaded' );
	$retries_chart_desc = __( 'All failed login attempts have been neutralized in the cloud', 'limit-login-attempts-reloaded' );
	$retries_chart_color = '#66CC66';
}

?>

<div id="llar-dashboard-page">
	<div class="dashboard-header">
		<h1><?php _e( 'Limit Login Attempts Reloaded Dashboard', 'limit-login-attempts-reloaded' ); ?></h1>
	</div>
	<div class="dashboard-section-1 <?php echo esc_attr( $active_app ); ?>">
		<div class="info-box-1">
            <div class="section-title"><?php _e( 'Failed Login Attempts', 'limit-login-attempts-reloaded' ); ?></div>
            <div class="section-content">
                <div class="chart">
                    <canvas id="llar-attack-velocity-chart"></canvas>
                    <span class="llar-retries-count"><?php echo esc_html( $retries_count ); ?></span>
                </div>
                <script type="text/javascript">
					(function(){

						var ctx = document.getElementById('llar-attack-velocity-chart').getContext('2d');
						var llar_retries_chart = new Chart(ctx, {
							type: 'doughnut',
							data: {
								// labels: ['Success', 'Warning', 'Warning', 'Fail'],
								datasets: [{
									data: [1],
									value: <?php echo esc_js( $retries_count ); ?>,
									backgroundColor: ['<?php echo esc_js( $retries_chart_color ); ?>'],
									borderWidth: [0]
								}]
							},
							options: {
								responsive: true,
								cutoutPercentage: 70,
								title: {
									display: false,
									// text: 'Local Attack Velocity'
								},
                                tooltips: {
									enabled: false
                                },
								layout: {
									padding: {
										// bottom: 40
									}
								},
								// needle: {
								// 	// Needle circle radius as the percentage of the chart area width
								// 	radiusPercentage: 2,
								// 	// Needle width as the percentage of the chart area width
								// 	widthPercentage: 3.2,
								// 	// Needle length as the percentage of the interval between inner radius (0%) and outer radius (100%) of the arc
								// 	lengthPercentage: 40,
								// 	// The color of the needle
								// 	color: 'rgba(0, 0, 0, 1)'
								// },
								valueLabel: {
									display: true,
									fontSize: 25,
									color: '#3e76c1',
									backgroundColor: 'rgba(0,0,0,0)',
									bottomMarginPercentage: -6
								},
							}
						});

					})();
                </script>
                <div class="title"><?php echo esc_html( $retries_chart_title ); ?></div>
                <div class="desc"><?php echo $retries_chart_desc; ?></div>
				<?php if( $retries_chart_show_actions ) : ?>
                    <div class="actions">
                        <ol>
                            <li><?php _e( 'Change your password to something more secure.', 'limit-login-attempts-reloaded' ); ?></li>
                            <li><?php _e( 'Make sure WordPress and all your plugins are updated.', 'limit-login-attempts-reloaded' ); ?></li>
                            <li><?php echo sprintf( __( '<a href="%s" target="_blank">Update to Premium</a> Limit Login Attempts Reloaded.', 'limit-login-attempts-reloaded' ), 'https://www.limitloginattempts.com/info.php?from=plugin-dashboard-status' ); ?></li>
                        </ol>
                    </div>
				<?php endif; ?>
            </div>
        </div>
        <div class="info-box-2">
            <div class="section-content">
                <?php
                $chart2_label = '';
                $chart2_labels = array();
				$chart2_datasets = array();

                if( $active_app === 'custom' ) {

                    $stats_dates = array();
                    $stats_values = array();
                    $date_format = trim( get_option( 'date_format' ), ' yY,._:;-/\\' );
                    $date_format = str_replace( 'F', 'M', $date_format );

					$dataset = array(
						'label' => __('Failed Login Attempts', 'limit-login-attempts-reloaded'),
						'data' => [],
						'backgroundColor' => 'rgb(54, 162, 235)',
						'borderColor' => 'rgb(54, 162, 235)',
						'fill' => false,
					);

                    if( $api_stats && !empty( $api_stats['attempts'] ) ) {

                        foreach ($api_stats['attempts']['at'] as $timest) {

                            $stats_dates[] = date( $date_format, $timest );
                        }

                        $chart2_label = __('Requests', 'limit-login-attempts-reloaded');
                        $chart2_labels = $stats_dates;

                        $dataset['data'] = $api_stats['attempts']['count'];
                    }

					$chart2_datasets[] = $dataset;

                } else {

					$date_format = trim( get_option( 'date_format' ), ' yY,._:;-/\\' );
					$date_format = str_replace( 'F', 'M', $date_format );

					$retries_stats = $this->get_option( 'retries_stats' );

					if( is_array( $retries_stats ) && $retries_stats ) {

						$daterange = new DatePeriod(
							new DateTime( key( $retries_stats ) ),
							new DateInterval('P1D'),
							new DateTime()
						);

						$chart2_data = array();
						foreach ($daterange as $date) {

							$chart2_labels[] = $date->format( $date_format );
							$chart2_data[] = (!empty($retries_stats[$date->format("Y-m-d")])) ? $retries_stats[$date->format("Y-m-d")] : 0;
						}
                    } else {

						$chart2_labels[] = (new DateTime())->format( $date_format );
						$chart2_data[] = 0;
                    }


                    $chart2_datasets[] = array(
						'label' => __( 'Failed Login Attempts', 'limit-login-attempts-reloaded' ),
						'data' => $chart2_data,
						'backgroundColor' => 'rgb(54, 162, 235)',
						'borderColor' => 'rgb(54, 162, 235)',
						'fill' => false,
                    );
				}

                ?>

                <div class="llar-chart-wrap">
                    <canvas id="llar-api-requests-chart" style=""></canvas>
                </div>

                <script type="text/javascript">
					(function(){

						var ctx = document.getElementById('llar-api-requests-chart').getContext('2d');
						var llar_stat_chart = new Chart(ctx, {
							type: 'line',
							data: {
								labels: <?php echo json_encode( $chart2_labels ); ?>,
                                datasets: <?php echo json_encode( $chart2_datasets ); ?>
							},
							options: {
								responsive: true,
								maintainAspectRatio: false,
								tooltips: {
									mode: 'index',
									intersect: false,
								},
								hover: {
									mode: 'nearest',
									intersect: true
								},
								scales: {
									xAxes: [{
										display: true,
										scaleLabel: {
											display: false
										}
									}],
									yAxes: [{
										display: true,
										scaleLabel: {
											display: false
										},
										ticks: {
											beginAtZero: true,
											userCallback: function(label, index, labels) {
												if (Math.floor(label) === label) {
													return label;
												}
											},
										}
									}]
								}
							}
						});

					})();
                </script>

            </div>
        </div>
        <?php if( $active_app === 'local' ) : ?>
		<div class="info-box-3">
            <div class="section-content">
                <div class="title"><?php _e( 'Premium Protection Disabled', 'limit-login-attempts-reloaded' ); ?></div>
                <div class="desc"><?php _e( 'As a free user, your local server is absorbing the traffic brought on by brute force attacks, potentially slowing down your website. Upgrade to Premium today to outsource these attacks through our cloud app, and slow down future attacks with advanced throttling.', 'limit-login-attempts-reloaded' ); ?></div>
                <div class="actions">
                    <a href="https://www.limitloginattempts.com/info.php?from=plugin-dashboard-cta" target="_blank" class="button button-primary"><?php _e( 'Upgrade to Premium', 'limit-login-attempts-reloaded' ); ?></a><br>
                </div>
            </div>
        </div>
        <?php endif; ?>
	</div>
	<div class="dashboard-section-2" style="display:none;">
        <div class="info-box-1">
            <div class="section-title"><?php _e( 'Notifications', 'limit-login-attempts-reloaded' ); ?></div>
            <ul class="notifications-list">
                <li><a href="#">12 issues found in most recent scan</a></li>
                <li><a href="#">Updates are available for WordPress (v5.6) and 10 plugins</a></li>
            </ul>
        </div>
        <div class="info-box-2">
            <div class="info-box-icon">
                <span class="dashicons dashicons-rest-api"></span>
            </div>
            <div class="info-box-content">
                <div class="title"><?php _e( 'Multiply Your Protection By Adding More Domains', 'limit-login-attempts-reloaded' ); ?></div>
                <div class="desc"><?php _e( 'When you upgrade to premium, you can synchronize your IP safelist and blacklist between multiple sites. This is a great way to improve your network performance and slow down future attacks.', 'limit-login-attempts-reloaded' ); ?></div>
                <div class="actions">
                    <a href="#"><?php _e( 'Learn More', 'limit-login-attempts-reloaded' ); ?></a>
                    <a href="#"><?php _e( 'Connect This Site', 'limit-login-attempts-reloaded' ); ?></a>
                </div>
            </div>
        </div>
    </div>
	<div class="dashboard-section-3">
        <div class="info-box-1">
            <div class="info-box-icon">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="info-box-content">
                <div class="title"><a href="<?php echo $this->get_options_page_uri('logs-'.$active_app); ?>"><?php _e( 'Tools', 'limit-login-attempts-reloaded' ); ?></a></div>
                <div class="desc"><?php _e( 'View lockouts logs, block or whitelist usernames or IPs, and more.', 'limit-login-attempts-reloaded' ); ?></div>
            </div>
        </div>
        <div class="info-box-1">
            <div class="info-box-icon">
                <span class="dashicons dashicons-sos"></span>
            </div>
            <div class="info-box-content">
                <div class="title"><a href="https://www.limitloginattempts.com/info.php?from=plugin-dashboard-help" target="_blank"><?php _e( 'Help', 'limit-login-attempts-reloaded' ); ?></a></div>
                <div class="desc"><?php _e( 'Find the documentation and help you need.', 'limit-login-attempts-reloaded' ); ?></div>
            </div>
        </div>
        <div class="info-box-1">
            <div class="info-box-icon">
                <span class="dashicons dashicons-admin-generic"></span>
            </div>
            <div class="info-box-content">
                <div class="title"><a href="<?php echo $this->get_options_page_uri('settings'); ?>"><?php _e( 'Global Options', 'limit-login-attempts-reloaded' ); ?></a></div>
                <div class="desc"><?php _e( 'Many options such as notifications, alerts, premium status, and more.', 'limit-login-attempts-reloaded' ); ?></div>
            </div>
        </div>
    </div>
    <?php if( $stats_global = LLAR_App::stats_global() ) : ?>
	<div class="dashboard-section-4">
        <?php
		$stats_global_dates = array();
		$date_format = trim( get_option( 'date_format' ), ' yY,._:;-/\\' );
		$date_format = str_replace( 'F', 'M', $date_format );

		foreach ( $stats_global['attempts']['day']['at'] as $timest ) {

			$stats_global_dates[] = date( $date_format, $timest );
		}
		
		$countries_list = LLA_Helpers::get_countries_list();
        ?>
        <div class="info-box-1">
            <div class="section-title">
                <span><?php _e( 'Failed Login Attempts By Country', 'limit-login-attempts-reloaded' ); ?></span>
                <span class="section-title-info"><?php _e( 'Global Network (Premium Users)', 'limit-login-attempts-reloaded' ); ?></span>
            </div>
            <div class="section-content">
                <table class="lockouts-by-country-table">
                    <tr>
                        <th><?php _e( 'Country', 'limit-login-attempts-reloaded' ); ?></th>
                        <th><?php _e( 'Count', 'limit-login-attempts-reloaded' ); ?></th>
                    </tr>
                    <?php foreach( $stats_global['countries'] as $country_data ) :

                        $country_code = ( array_key_exists( $country_data['code'], $countries_list ) ) ? $country_data['code'] : 'ZZ';
                        $country_name = apply_filters( 'llar_country_name', $countries_list[$country_code], $country_code );
                        ?>
                        <tr>
                            <td>
                                <?php if( $country_code !== 'ZZ' ) : ?>
                                <img class="flag-icon" src="<?php echo LLA_PLUGIN_URL; ?>/assets/img/flags/<?php echo esc_attr( $country_data['code'] ); ?>.png">
                                <?php endif; ?>
                            <?php echo esc_html( $country_name ); ?></td>
                            <td><?php echo esc_html( $country_data['attempts'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <p class="countries-table-info"><?php _e( 'today', 'limit-login-attempts-reloaded' ); ?></p>
                <p class="countries-table-info-right"><?php _e( 'Block by country feature available with <a href="https://www.limitloginattempts.com/info.php?from=plugin-dashboard-country" target="_blank">premium plus plan</a>.', 'limit-login-attempts-reloaded' ) ?></p>
            </div>
        </div>

        <div class="info-box-2">
            <div class="section-title">
				<span><?php _e( 'Total Failed Login Attempts', 'limit-login-attempts-reloaded' ); ?></span>
                <span class="section-title-info"><?php _e( 'Global Network (Premium Users)', 'limit-login-attempts-reloaded' ); ?></span>
            </div>
            <div class="section-content">
                <div class="llar-chart-wrap">
                    <canvas id="llar-total-attacks-blocked-chart" style=""></canvas>
                </div>
                <script type="text/javascript">
                    (function(){

                        var ctx = document.getElementById('llar-total-attacks-blocked-chart').getContext('2d');
                        var llar_total_attacks_blocked_chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode( $stats_global_dates ); ?>,
                                datasets: [{
                                    label: '<?php echo esc_js( __( 'Total Attempts', 'limit-login-attempts-reloaded' ) ); ?>',
                                    data: <?php echo json_encode( $stats_global['attempts']['day']['count'] ); ?>,
                                    backgroundColor: 'rgb(255, 159, 64)',
                                    borderColor: 'rgb(255, 159, 64)',
                                    fill: false
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                tooltips: {
                                    mode: 'index',
                                    intersect: false,
                                },
                                hover: {
                                    mode: 'nearest',
                                    intersect: true
                                },
                                scales: {
                                    xAxes: [{
                                        display: true,
                                        scaleLabel: {
                                            display: false
                                        }
                                    }],
                                    yAxes: [{
                                        display: true,
                                        scaleLabel: {
                                            display: false
                                        },
                                        ticks: {
                                            beginAtZero: true,
                                            userCallback: function(label, index, labels) {
                                                if (Math.floor(label) === label) {
                                                    return label;
                                                }
                                            },
                                        }
                                    }]
                                }
                            }
                        });

                    })();
                </script>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
