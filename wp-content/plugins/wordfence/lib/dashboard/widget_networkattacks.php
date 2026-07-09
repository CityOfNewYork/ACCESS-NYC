<?php if (!defined('WORDFENCE_VERSION')) { exit; } ?>
<div class="wf-row">
	<div class="wf-col-xs-12">
		<div class="wf-dashboard-item active">
			<div class="wf-dashboard-item-inner">
				<div class="wf-dashboard-item-content">
					<div class="wf-dashboard-item-title">
						<strong><?php esc_html_e('Total Attacks Blocked:', 'wordfence'); ?> </strong><?php esc_html_e('Wordfence Network', 'wordfence'); ?>
					</div>
					<div class="wf-dashboard-item-action"><div class="wf-dashboard-item-action-disclosure"></div></div>
				</div>
			</div>
			<div class="wf-dashboard-item-extra">
				<ul class="wf-dashboard-item-list">
					<li>
						<?php if ($d->networkBlock24h === null): ?>
							<div class="wf-dashboard-item-list-text"><p><em><?php esc_html_e('Blocked attack counts not available yet.', 'wordfence'); ?></em></p></div>
						<?php else: ?>
							<div class="wf-dashboard-graph-wrapper">
								<div class="wf-dashboard-toggle-btns">
									<ul class="wf-pagination wf-pagination-sm">
										<li class="wf-active"><a href="#" class="wf-dashboard-graph-attacks" data-grouping="24h" role="button"><?php esc_html_e('24 Hours', 'wordfence'); ?></a></li>
										<li><a href="#" class="wf-dashboard-graph-attacks" data-grouping="30d" role="button"><?php esc_html_e('30 Days', 'wordfence'); ?></a></li>
									</ul>
								</div>
								<div class="wf-dashboard-network-blocks"><canvas id="wf-dashboard-network-blocks-24h"></canvas></div>
								<div class="wf-dashboard-network-blocks wf-hidden"><canvas id="wf-dashboard-network-blocks-7d"></canvas></div>
								<div class="wf-dashboard-network-blocks wf-hidden"><canvas id="wf-dashboard-network-blocks-30d"></canvas></div>
							</div>
							<script type="application/javascript">
								<?php
								$totalAttacksString = json_encode(__("Total Attacks", 'wordfence'));
								$styling = <<<STYLING
																		label: $totalAttacksString,
																		fill: false,
																		lineTension: 0.1,
																		backgroundColor: "rgba(75,192,192,0.4)",
																		borderColor: "#16bc9b",
																		borderCapStyle: 'butt',
																		borderDash: [],
																		borderDashOffset: 0.0,
																		borderJoinStyle: 'miter',
																		pointBorderColor: "rgba(75,192,192,1)",
																		pointBackgroundColor: "#fff",
																		pointBorderWidth: 1,
																		pointHoverRadius: 5,
																		pointHoverBackgroundColor: "rgba(75,192,192,1)",
																		pointHoverBorderColor: "rgba(220,220,220,1)",
																		pointHoverBorderWidth: 2,
																		pointRadius: 1,
																		pointHitRadius: 10,
																		spanGaps: false,
STYLING;
								
								?>
								(function($) {
									$(document).ready(function() {
										new Chart($('#wf-dashboard-network-blocks-24h'), {
											type: 'line',
											data: {
												<?php
												$blocks = $d->networkBlock24h;
												$labels = array();
												$values = array();
												
												foreach ($blocks as $b) {
													$values[] = $b['c'];
													$labels[] = "'" . wfUtils::formatLocalTime('g a', $b['t']) . "'";
												}
												?>
												labels: [<?php echo implode(',', $labels); ?>],
												datasets: [
													{
														<?php echo $styling; ?>
														data: [<?php echo implode(',', $values) ?>]
													}
												]
											},
											options: {
												scales: {
													y: {
														beginAtZero: true,
														ticks: {
															callback: function(value, index, values) {
																return value.toLocaleString();
															}
														}
													}
												},
												tooltips: {
													callbacks: {
														label: function(tooltipItem, data) {
															var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || 'Other';
															var label = parseInt(tooltipItem.yLabel).toLocaleString();
															return datasetLabel + ': ' + label;
														}
													}
												}
											}
										});

										new Chart($('#wf-dashboard-network-blocks-7d'), {
											type: 'line',
											data: {
												<?php
												$blocks = $d->networkBlock7d;
												$labels = array();
												$values = array();
												
												foreach ($blocks as $b) {
													$values[] = $b['c'];
													$labels[] = "'" . wfUtils::formatLocalTime('M j', $b['t']) . "'";
												}
												?>
												labels: [<?php echo implode(',', $labels); ?>],
												datasets: [
													{
														<?php echo $styling; ?>
														data: [<?php echo implode(',', $values) ?>]
													}
												]
											},
											options: {
												scales: {
													y: {
														beginAtZero: true,
														ticks: {
															callback: function(value, index, values) {
																return value.toLocaleString();
															}
														}
													}
												},
												tooltips: {
													callbacks: {
														label: function(tooltipItem, data) {
															var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || 'Other';
															var label = parseInt(tooltipItem.yLabel).toLocaleString();
															return datasetLabel + ': ' + label;
														}
													}
												}
											}
										});

										new Chart($('#wf-dashboard-network-blocks-30d'), {
											type: 'line',
											data: {
												<?php
												$blocks = $d->networkBlock30d;
												$labels = array();
												$values = array();
												
												foreach ($blocks as $b) {
													$values[] = $b['c'];
													$labels[] = "'" . wfUtils::formatLocalTime('M j', $b['t']) . "'";
												}
												?>
												labels: [<?php echo implode(',', $labels); ?>],
												datasets: [
													{
														<?php echo $styling; ?>
														data: [<?php echo implode(',', $values) ?>]
													}
												]
											},
											options: {
												scales: {
													y: {
														beginAtZero: true,
														ticks: {
															callback: function(value, index, values) {
																return value.toLocaleString();
															}
														}
													}
												},
												tooltips: {
													callbacks: {
														label: function(tooltipItem, data) {
															var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || 'Other';
															var label = parseInt(tooltipItem.yLabel).toLocaleString();
															return datasetLabel + ': ' + label;
														}
													}
												}
											}
										});
									});
									
									$('.wf-dashboard-graph-attacks').on('click', function(e) {
										e.preventDefault();
										e.stopPropagation();

										$(this).closest('ul').find('li').removeClass('wf-active');
										$(this).closest('li').addClass('wf-active');

										$('.wf-dashboard-network-blocks').addClass('wf-hidden');
										$('#wf-dashboard-network-blocks-' + $(this).data('grouping')).closest('.wf-dashboard-network-blocks').removeClass('wf-hidden');
									});
								})(jQuery);
							</script>
						<?php endif; ?>
					</li>
				</ul>
				<p class="wf-dashboard-last-updated"><?php echo esc_html(sprintf(
					/* translators: Time since. Example: 1 minute, 2 seconds */
						__('Last Updated: %s ago', 'wordfence'), wfUtils::makeTimeAgo(time() - $d->lastGenerated))); ?></p>
			</div>
		</div>
	</div>
</div>