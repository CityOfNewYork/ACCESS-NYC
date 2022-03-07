<?php
if( !defined( 'ABSPATH' ) ) exit();
?>
<?php
$app_config = $this->get_custom_app_config();
?>
<div class="llar-table-header">
    <h3><?php _e( 'Event Log', 'limit-login-attempts-reloaded' ); ?></h3>
	<?php if( !empty( $app_config['key'] ) ): ?>
        <span class="right-link"><a href="https://my.limitloginattempts.com/logs?key=<?php echo esc_attr( $app_config['key'] ); ?>" target="_blank"><?php _e( 'Full Logs', 'limit-login-attempts-reloaded' ); ?></a>
        <i class="llar-tooltip" data-text="<?php esc_attr_e( 'All attempts blocked by access rules are hidden by default. You can see the full log at this link.' ); ?>">
            <span class="dashicons dashicons-editor-help"></span>
        </i>
    </span>
	<?php endif; ?>
</div>

<div class="llar-table-scroll-wrap llar-app-log-infinity-scroll">
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
                $infinity_box = $('.llar-app-log-infinity-scroll'),
                loading_data = false,
				page_offset = '',
                page_limit = 10,
                total_loaded = 0;

            $infinity_box.on('scroll', function (){
                if (!loading_data && $infinity_box.get(0).scrollTop + $infinity_box.get(0).clientHeight >= $infinity_box.get(0).scrollHeight - 1) {
                    load_log_data();
                }
            });

			load_log_data();

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

					if(response.success) {

                        if(method === 'lockout/delete') {
                            $('.llar-table-app-lockouts').trigger('llar:refresh');
                        }
					}

				});
			});

			function load_log_data() {

			    if(page_offset === false) {
			        return;
                }

				llar.progressbar.start();
				loading_data = true;

				$.post(ajaxurl, {
					action: 'app_load_log',
					offset: page_offset,
                    limit: page_limit,
					sec: '<?php echo wp_create_nonce( "llar-action" ); ?>'
				}, function(response){

					llar.progressbar.stop();

					if(response.success) {

						$log_table.append(response.data.html);

                        total_loaded += response.data.total_items;

                        if(response.data.offset) {
                            page_offset = response.data.offset;

                            if(response.data.total_items < page_limit && total_loaded < page_limit) {
                                console.log('extra load');
                                load_log_data();
                            }

						} else {
						    page_offset = false;
                        }

                        loading_data = false;
					}

				});

			}
		});

	})(jQuery);
</script>