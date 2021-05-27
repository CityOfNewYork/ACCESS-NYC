<?php
if( !defined( 'ABSPATH' ) ) exit();
?>

<div class="llar-app-acl-rules">
	<div class="app-rules-col">
		<h3><?php _e( 'Login Access Rules', 'limit-login-attempts-reloaded' ); ?></h3>
		<table class="form-table llar-app-login-access-rules-table">
			<tr>
				<th scope="col"><?php _e( 'Pattern', 'limit-login-attempts-reloaded' ); ?></th>
				<th scope="col"><?php _e( 'Rule', 'limit-login-attempts-reloaded' ); ?></th>
				<th class="llar-app-acl-action-col" scope="col"><?php _e( 'Action', 'limit-login-attempts-reloaded' ); ?></th>
			</tr>
		</table>
	</div>
	<div class="app-rules-col">
		<h3><?php _e( 'IP Access Rules', 'limit-login-attempts-reloaded' ); ?></h3>
		<table class="form-table llar-app-ip-access-rules-table">
			<tr>
				<th scope="col"><?php _e( 'Pattern', 'limit-login-attempts-reloaded' ); ?></th>
				<th scope="col"><?php _e( 'Rule', 'limit-login-attempts-reloaded' ); ?></th>
				<th class="llar-app-acl-action-col" scope="col"><?php _e( 'Action', 'limit-login-attempts-reloaded' ); ?></th>
			</tr>
		</table>
	</div>

	<script type="text/javascript">
		;(function($){

			$(document).ready(function () {

				var $app_acl_rules = $('.llar-app-acl-rules');

				load_rules_data('login');
				load_rules_data('ip');

				$app_acl_rules
					.on('click', '.llar-app-acl-remove', function(e){
						e.preventDefault();

						if(!confirm('Are you sure?')) {
							return false;
						}

						var $this = $(this),
							pattern = $this.data('pattern');

						if(!pattern) {

							console.log('Wrong pattern');
							return false;
						}

						llar.progressbar.start();

						$.post(ajaxurl, {
							action: 'app_acl_remove_rule',
							pattern: pattern,
							type: $this.data('type'),
							sec: '<?php echo esc_js( wp_create_nonce( "llar-action" ) ); ?>'
						}, function(response){

							llar.progressbar.stop();

							if(response.success) {

								$this.closest('tr').fadeOut(300, function(){
									$this.closest('tr').remove();
								})

							}

						});

					})
					.on('click', '.llar-app-acl-add-rule', function(e){
						e.preventDefault();

						var $this = $(this),
							pattern = $this.closest('tr').find('.llar-app-acl-pattern').val().trim(),
							rule = $this.closest('tr').find('.llar-app-acl-rule').val(),
							type = $this.data('type');

						if(!pattern) {

							alert('Pattern can\'t be empty!');
							return false;
						}

						var row_exist = {};
						$this.closest('table').find('.rule-pattern').each(function(i, el){
							var res = el.innerText.localeCompare(pattern);
							if(res === 0) {
								row_exist = $(el).closest('tr');
							}
						});

						if(row_exist.length) {

							$this.closest('tr').find('.llar-app-acl-pattern').val('');
							row_exist.remove();
						}

						llar.progressbar.start();

						$.post(ajaxurl, {
							action: 'app_acl_add_rule',
							pattern: pattern,
							rule: rule,
							type: type,
							sec: '<?php echo esc_js( wp_create_nonce( "llar-action" ) ); ?>'
						}, function(response){

							llar.progressbar.stop();

							if(response.success) {

								$this.closest('table').find('.empty-row').remove();

								$this.closest('tr').after('<tr class="llar-app-rule-'+rule+'">' +
									'<td class="rule-pattern">'+pattern+'</td>' +
									'<td>'+rule+'</td>' +
									'<td class="llar-app-acl-action-col" scope="col"><button class="button llar-app-acl-remove" data-type="'+type+'" data-pattern="'+pattern+'"><span class="dashicons dashicons-no"></span></button></td>' +
									'</tr>');

							}

						});

					});

			});

			function load_rules_data(type) {

				llar.progressbar.start();

				$.post(ajaxurl, {
					action: 'app_load_acl_rules',
					type: type,
					sec: '<?php echo wp_create_nonce( "llar-action" ); ?>'
				}, function(response){

					llar.progressbar.stop();

					if(response.success) {

						$('.llar-app-'+type+'-access-rules-table').html(response.data.html);
					}
				});
			}

		})(jQuery);
	</script>
</div>
