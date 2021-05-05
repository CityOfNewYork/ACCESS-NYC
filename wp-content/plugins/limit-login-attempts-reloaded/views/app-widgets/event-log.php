<?php
if( !defined( 'ABSPATH' ) ) exit();
?>

<h3><?php _e( 'Event Log', 'limit-login-attempts-reloaded' ); ?></h3>

<div class="llar-app-log-pagination">
    <a class="llar-prev-page button disabled" href="#">
        <span aria-hidden="true">‹</span>
    </a>
    <a class="llar-next-page button disabled" href="#">
        <span aria-hidden="true">›</span>
    </a>
</div>

<div class="llar-table-scroll-wrap">
    <table class="form-table llar-table-app-log">
        <tr>
            <th scope="col"><?php _e( "Time", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "IP", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Gateway", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Login", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Rule", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Reason", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Pattern", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Attempts Left", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Lockout Duration", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Actions", 'limit-login-attempts-reloaded' ); ?></th>
        </tr>
    </table>
</div>
<script type="text/javascript">
	;(function($){

		$(document).ready(function () {

			var $log_table = $('.llar-table-app-log'),
				current_page = 0,
				page_offsets = [''];

			load_log_data();

			$('.llar-app-log-pagination').on('click', '.llar-prev-page:not(.disabled)', function(e){
				e.preventDefault();

				load_log_data(page_offsets[--current_page]);

				toggle_next_btn(true);
			});

			$('.llar-app-log-pagination').on('click', '.llar-next-page:not(.disabled)', function(e){
				e.preventDefault();

				load_log_data(page_offsets[++current_page]);
			});

			$log_table.on('click', '.js-app-log-action', function (e) {
				e.preventDefault();

				var $this = $(this),
					method = $this.data('method'),
					params = $this.data('params');

				if(!confirm('Are you sure?')) return;

				llar.progressbar.start();

				$.post(ajaxurl, {
					action: 'app_log_action',
					method: method,
					params: params,
					sec: '<?php echo esc_js( wp_create_nonce( "llar-action" ) ); ?>'
				}, function(response){

					llar.progressbar.stop();

					console.log(response);
					if(response.success) {


					}

				});
			});

			function toggle_prev_btn(enable) {
				if(enable) {

					$('.llar-app-log-pagination .llar-prev-page').removeClass('disabled');
				} else {

					$('.llar-app-log-pagination .llar-prev-page').addClass('disabled');
				}
			}
			function toggle_next_btn(enable) {
				if(enable) {

					$('.llar-app-log-pagination .llar-next-page').removeClass('disabled');
				} else {

					$('.llar-app-log-pagination .llar-next-page').addClass('disabled');
				}
			}

			function load_log_data(offset) {

				llar.progressbar.start();

				$.post(ajaxurl, {
					action: 'app_load_log',
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

					}

				});

			}
		});

	})(jQuery);
</script>