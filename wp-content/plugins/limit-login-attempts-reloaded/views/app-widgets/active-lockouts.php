<?php
if( !defined( 'ABSPATH' ) ) exit();
?>

<h3><?php _e( 'Active Lockouts', 'limit-login-attempts-reloaded' ); ?></h3>

<div class="llar-table-scroll-wrap llar-app-lockouts-infinity-scroll">
    <table class="form-table llar-table-app-lockouts">
        <tr>
            <th scope="col"><?php _e( "IP", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Login", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Count", 'limit-login-attempts-reloaded' ); ?></th>
            <th scope="col"><?php _e( "Expires in (minutes)", 'limit-login-attempts-reloaded' ); ?></th>
        </tr>
    </table>
</div>

<script type="text/javascript">
	;(function($){

		$(document).ready(function () {

			var $log_table = $('.llar-table-app-lockouts'),
                $log_table_empty = $log_table.html();
                $infinity_box = $('.llar-app-lockouts-infinity-scroll'),
                loading_data = false,
                page_offset = '',
                page_limit = 10;

            $infinity_box.on('scroll', function (){
                if (!loading_data && $infinity_box.get(0).scrollTop + $infinity_box.get(0).clientHeight >= $infinity_box.get(0).scrollHeight - 1) {
                    load_lockouts_data();
                }
            });

            $log_table.on('llar:refresh', function () {
                page_offset = '';
                $log_table.html($log_table_empty);
                load_lockouts_data();
            });

			load_lockouts_data();

			function load_lockouts_data() {

                if(page_offset === false) {
                    return;
                }

                loading_data = true;

				llar.progressbar.start();

				$.post(ajaxurl, {
					action: 'app_load_lockouts',
                    offset: page_offset,
                    limit: page_limit,
					sec: '<?php echo wp_create_nonce( "llar-action" ); ?>'
				}, function(response){

					llar.progressbar.stop();

					if(response.success) {

                        $log_table.append(response.data.html);

                        if(response.data.offset) {
                            page_offset = response.data.offset;
                        } else {
                            page_offset = false;
                        }
					} else {

						if(response.data.error_notice) {
							$('.limit-login-app-dashboard').find('.llar-app-notice').remove();
							$('.limit-login-app-dashboard').prepend(response.data.error_notice);
						}
					}

                    loading_data = false;
				});

			}
		});

	})(jQuery);
</script>