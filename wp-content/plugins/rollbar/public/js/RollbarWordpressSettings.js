// RollbarWordpressSettings.js
(function() {
    jQuery(function() {
        
        var clearNotices = function() {
                jQuery(".rollbar_wp_test_logging_notice").remove();
            },
            successNotice = function(message) {
                
                jQuery(
                    '<div class="notice updated rollbar_wp_test_logging_notice is-dismissible">'+
                    message +
                    '</div>'
                )
                .hide()
                .insertAfter("#rollbar_wp_test_logging")
                .show(400);
                
            },
            failNotice = function(message) {
                
                jQuery(
                    '<div class="notice error rollbar_wp_test_logging_notice is-dismissible">'+
                    message +
                    '</div>'
                )
                .hide()
                .insertAfter("#rollbar_wp_test_logging")
                .show(400);
                
            },
            phpSuccessNotice = function() {
                successNotice(
                    'Test message sent to Rollbar using PHP. Please, check your Rollbar '+
                    'dashboard to see if you received it. Save your changes and '+
                    'you\'re ready to go.'
                );
            },
            phpFailNotice = function() {
                failNotice(
                    'There was a problem accessing Rollbar service using provided credentials '+
                    'for PHP logging. Check your server side token.'
                )
            },
            jsSuccessNotice = function() {
                successNotice(
                    'Test message sent to Rollbar using JS. Please, check your Rollbar '+
                    'dashboard to see if you received it. Save your changes and '+
                    'you\'re ready to go.'
                );
            },
            jsFailNotice = function() {
                failNotice(
                    'There was a problem accessing Rollbar service using provided credentials '+
                    'for JS logging. Check your client side token.'
                )
            },
            logThroughPhp = function(server_side_access_token, environment, logging_level) {
                jQuery.post(
                    "/index.php?rest_route=/rollbar/v1/test-php-logging",
                    {
                        "server_side_access_token": server_side_access_token,
                        "environment": environment,
                        "logging_level": logging_level
                    },
                    function(response) {
                        
                        phpSuccessNotice();
                        
                    }
                ).fail(function(response) {
                    
                    phpFailNotice();
                    
                });    
            },
            logThroughJs = function(client_side_access_token, environment, logging_level) {
                
                var _rollbarConfig = {
                        accessToken: client_side_access_token,
                        captureUncaught: true,
                        captureUnhandledRejections: true,
                        payload: {
                            environment: environment
                        }
                    }
                    sendRollbarRequest = function() {
                        
                    };
                
                if (window.Rollbar == undefined) {
                    
                    jQuery.ajax({
                        url: RollbarWordpress.plugin_url + "vendor/rollbar/rollbar/data/rollbar.snippet.js",
                        success: function(data){
                            eval(data);
                        },
                        dataType: "text",
                        async: false
                        
                    }).fail(function() {
                        jsFailNotice();
                    });
                        
                }
                
                Rollbar.configure(_rollbarConfig);
                            
                Rollbar.info(
                    "Test message from Rollbar Wordpress plugin using JS: "+
                    "integration with Wordpress successful",
                    function(error, data) {
                        if (error) {
                            jsFailNotice();
                        } else {
                            jsSuccessNotice();
                        }
                    }
                );
                
            };
            
        
        jQuery("#rollbar_wp_test_logging").click(function() {
            
            var server_side_access_token = jQuery("#rollbar_wp_server_side_access_token").val(),
                client_side_access_token = jQuery("#rollbar_wp_client_side_access_token").val(),
                environment = jQuery("#rollbar_wp_environment").val(),
                logging_level = jQuery("#rollbar_wp_logging_level").val(),
                php_logging_enabled = jQuery('#rollbar_wp_php_logging_enabled').prop('checked'),
                js_logging_enabled = jQuery('#rollbar_wp_js_logging_enabled').prop('checked');
                
            clearNotices();
            
            if (php_logging_enabled) {
                logThroughPhp(server_side_access_token, environment, logging_level);
            } else {
                failNotice("Skipped testing PHP logging since it is disabled.");
            }
            
            if (js_logging_enabled) {
                logThroughJs(client_side_access_token, environment, logging_level);
            } else {
                failNotice("Skipped testing JS logging since it is disabled.");
            }
            
        })
        
    })
})();