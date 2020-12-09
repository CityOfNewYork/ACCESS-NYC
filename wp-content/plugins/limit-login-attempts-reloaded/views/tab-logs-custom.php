<?php

if( !defined( 'ABSPATH' ) ) exit();

/**
 * @var $this Limit_Login_Attempts
 */
?>

<div class="limit-login-app-dashboard">

    <h3><?php _e( 'Lockouts', 'limit-login-attempts-reloaded' ); ?></h3>

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
</div>