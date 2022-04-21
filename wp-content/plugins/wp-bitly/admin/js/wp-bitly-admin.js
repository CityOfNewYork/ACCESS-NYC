var popup;
var topSpace = window.screenTop + 100;
var leftSpace = window.screenLeft + 100;
var windowFeatures = "menubar=0,location=0,resizable=yes,toolbar=0,scrollbars=yes,status=yes,width=400,height=500,top=" + topSpace + ",left=" + leftSpace;

(function( $ ) {
	'use strict';
        
        function populate_org_options(token){
            $.ajax({
                url:ajaxurl,
                data:{token:token,action:"get_org_options"},
                method: "POST"   
            })
            .done(function( options ) {
                $( "#wpbitly_default_org" ).html( options );
                var curr_org = $( "#wpbitly_default_org" ).val();
                change_group_options(curr_org);
            });
        }
        
        function change_group_options(curr_org){
            $.ajax({
                url:ajaxurl,
                data:{curr_org:curr_org,action:"get_group_options"},
                method: "POST"   
            })
            .done(function( options ) {
                $( "#wpbitly_default_group" ).html( options );
                var curr_group = $( "#wpbitly_default_group" ).val();
                change_domain_options(curr_group);
            });
        }
        
        function change_domain_options(curr_group){
            $.ajax({
                url:ajaxurl,
                data:{curr_group:curr_group,action:"get_domain_options"},
                method: "POST"   
            })
            .done(function( options ) {
                $( "#wpbitly_default_domain" ).html( options );
            });
        }
        $( window ).load(function() {
            $("#wpbitly_default_org").on('change',function(){
                var curr_org = $(this).val();
                change_group_options(curr_org);
            });
            
            $("#wpbitly_default_group").on('change',function(){
                var curr_group = $(this).val();
                change_domain_options(curr_group);
            });
        });
        
        $( document ).ready(function($) {

            $('#disconnect_button').on('click', function(e) {
                e.preventDefault();

                var confirm = window.confirm("Are you sure you want to disconnect your Bitly account?");
                if( confirm ) {
                    bitly_disconnect();
                }

            });        

            function bitly_disconnect() {
                var sendData = {
                    action:'wpbitly_oauth_disconnect',
                };

                $.ajax({
                url: ajaxurl,
                type: "POST",
                data: sendData,
                dataType : "json",
                }).done( function(data) {

                    console.log( data )
                    if( data.status == 'error' ) alert( 'ERROR: '+ data.message );
                    if( data.status == 'disconnected' ) {
                        location.reload();
                    }

                });//end ajax
            }    

            $('#authorization_button').on('click', function(e) {
                e.preventDefault();

                popup = window.open( this.href, 'windowname', windowFeatures );

                window.addEventListener("message", function(event) {

                    // Ignore messages from unexpected origins
                    if(event.origin !== "https://bitly.com") {
                        return;
                    }

                    if( event.data ) {
                        popup.close();
                        var accessCode = event.data.code;
                        console.log( accessCode );
                        get_token( accessCode );
                    }
                });

            });

            function get_token( code ) {
                var sendData = {
                    action:'wpbitly_oauth_get_token',
                    code:code,
                };

                $.ajax({
                url: ajaxurl,
                type: "POST",
                data: sendData,
                dataType : "json",
                }).done( function(data) {

                    console.log( data )
                    if( data.status == 'error' ) alert( 'ERROR: '+ data.message );
                    if( data.status == 'success' ) {
                        $( '#wpbitly_oauth_token' ).val( data.token ).removeClass( 'not_authorized' ).addClass( 'authorized' );
                        $( '#authorization_button' ).remove();
                        $( '#disconnect_button' ).show().css( 'display', 'inline-block' );
                        //also show the meta boxes
                        $( '.wpbitly_default_org_fieldset' ).show();
                        populate_org_options(data.token);
                    }

                });//end ajax
            }

        });

})( jQuery );
