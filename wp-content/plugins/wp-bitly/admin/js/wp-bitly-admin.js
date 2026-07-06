var popup;
var topSpace = window.screenTop + 100;
var leftSpace = window.screenLeft + 100;
var windowFeatures = "menubar=0,location=0,resizable=yes,toolbar=0,scrollbars=yes,status=yes,width=400,height=500,top=" + topSpace + ",left=" + leftSpace;

(function( $ ) {
	'use strict';
        
        function populate_org_options(){
            $.ajax({
                url:ajaxurl,
                data:{action:"get_org_options",nonce:wpBitlyData.nonce},
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
                data:{curr_org:curr_org,action:"get_group_options",nonce:wpBitlyData.nonce},
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
                data:{curr_group:curr_group,action:"get_domain_options",nonce:wpBitlyData.nonce},
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
                    let nonce = $( this ).data( 'wp_nonce' );
                    bitly_disconnect( nonce );
                }

            });        

            function bitly_disconnect( nonce ) {
                console.log( 'sendData' );
                var sendData = {
                    action:'wpbitly_oauth_disconnect',
                    nonce: nonce
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
                        window.location.reload();
                    }

                });//end ajax
            }    

            $('#authorization_button').on('click', function(e) {
                e.preventDefault();

                popup = window.open( this.href, 'windowname', windowFeatures );

                window.addEventListener("message", function(event) {

                    // Ignore messages from unexpected origins
                    if(event.origin !== "https://bitly.com" && event.origin !== "https://bitly.org" && event.origin !== "https://bitly.net") {
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
                    nonce:wpBitlyData.nonce,
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
                        $( '#authorization_button' ).remove();
                        document.getElementById( 'disconnect_button' ).style.display = 'inline-block';
                        document.getElementById( 'connected_feedback' ).classList.remove( 'hidden' );
                        //also show the meta boxes
                        $( '.wpbitly_default_org_fieldset' ).show();
                        populate_org_options();
                    }

                });//end ajax
            }

        });

})( jQuery );
