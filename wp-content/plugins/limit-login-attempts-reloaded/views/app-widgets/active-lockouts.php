<?php
if( !defined( 'ABSPATH' ) ) exit();
?>

<h3><?php _e( 'Active Lockouts', 'limit-login-attempts-reloaded' ); ?></h3>

<div class="llar-app-lockouts-pagination">
	<a class="llar-prev-page button disabled" href="#">
		<span aria-hidden="true">‹</span>
	</a>
	<a class="llar-next-page button disabled" href="#">
		<span aria-hidden="true">›</span>
	</a>
</div>

<table class="form-table llar-table-app-lockouts">
	<tr>
		<th scope="col"><?php _e( "IP", 'limit-login-attempts-reloaded' ); ?></th>
		<th scope="col"><?php _e( "Login", 'limit-login-attempts-reloaded' ); ?></th>
		<th scope="col"><?php _e( "Count", 'limit-login-attempts-reloaded' ); ?></th>
		<th scope="col"><?php _e( "Expires in (minutes)", 'limit-login-attempts-reloaded' ); ?></th>
	</tr>
</table>

<script type="text/javascript">
	;(function($){

		$(document).ready(function () {

			var $log_table = $('.llar-table-app-lockouts'),
				current_page = 0,
				page_offsets = [''];

			load_lockouts_data();

			$('.llar-app-lockouts-pagination').on('click', '.llar-prev-page:not(.disabled)', function(e){
				e.preventDefault();

				load_lockouts_data(page_offsets[--current_page]);

				toggle_next_btn(true);
			});

			$('.llar-app-lockouts-pagination').on('click', '.llar-next-page:not(.disabled)', function(e){
				e.preventDefault();

				load_lockouts_data(page_offsets[++current_page]);
			});

			function toggle_prev_btn(enable) {
				if(enable) {

					$('.llar-app-lockouts-pagination .llar-prev-page').removeClass('disabled');
				} else {

					$('.llar-app-lockouts-pagination .llar-prev-page').addClass('disabled');
				}
			}
			function toggle_next_btn(enable) {
				if(enable) {

					$('.llar-app-lockouts-pagination .llar-next-page').removeClass('disabled');
				} else {

					$('.llar-app-lockouts-pagination .llar-next-page').addClass('disabled');
				}
			}

			function load_lockouts_data(offset) {

				llar.progressbar.start();

				$.post(ajaxurl, {
					action: 'app_load_lockouts',
					offset: offset,
					sec: '<?php echo wp_create_nonce( "llar-action" ); ?>'
				}, function(response){

					llar.progressbar.stop();

					if(response.success) {

						$log_table.html(response.data.html);

						if(current_page > 0) {
							toggle_prev_btn(true);
						} else {
							toggle_prev_btn(false);

						}

						if(response.data.offset) {
							page_offsets.push(response.data.offset);
							toggle_next_btn(true);
						} else {
							toggle_next_btn(false);
						}

					} else {

						if(response.data.error_notice) {
							$('.limit-login-app-dashboard').find('.llar-app-notice').remove();
							$('.limit-login-app-dashboard').prepend(response.data.error_notice);
						}

					}

				});

			}
		});

	})(jQuery);
</script>