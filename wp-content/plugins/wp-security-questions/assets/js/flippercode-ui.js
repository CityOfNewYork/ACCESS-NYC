/*
* Package : Core
* Author  : Flippercode
* Version : 1.0.0
*/
 
jQuery(document).ready(function($) {

	$('.fc-modal').fcModal({});
	
    $overviewPage = ($('.fcdoc-product-info').length > 0) ? true : false;

    if ($overviewPage) {

        var ajaxUrl = fcajaxurl;
        $('#user-suggestion,#purchase_code').keyup(function() {
            $(this).css('border', 'none');
        });
        
        $('.refundbtn').click(function(e){
            e.preventDefault();
            var update_title = '';
            var update_desc = '';

            update_title = 'Get Refund';
            update_desc = "<p>Have you tried our support yet? We'd recommend that you create a support or email us at support@flippercode.com to resolve your issues. </p> <p><div class='fc-divider'><div class='fc-12'><a class='fc-btn fc-btn-orange fc-btn-large' href='mailto:hello@flippercode.com' >Email Us </a>&nbsp;&nbsp;<a class='fc-btn fc-btn-blue fc-btn-large' href='http://www.flippercode.com/forums' target='_blank'>Support Ticket</a>&nbsp;&nbsp;<a class='fc-btn fc-btn-red fc-btn-large' href='https://codecanyon.net/refund_requests/new' target='_blank'>Fill Refund Form</a></div></div><br></p>";
           
            $('.fc-main #fc_overview_modal').find('.fc-modal-header h4').html(update_title);
            $('.fc-main #fc_overview_modal').find('.fc-modal-body').html(update_desc);
            $('.fc-main #fc_overview_modal').show(); 

        });
        
        $('body').on('click','input[name="wpgmp_update_plugin"]',function(e){
            e.preventDefault();
            var product_id = $('input[name="product_id"]').val();
            var purchase_code = $('input[name="purchase_code"]').val();
            if( purchase_code.length <=0 ) {
                 $('input[name="purchase_code"]').css('border', '1px solid red');
                return false;
            }
            if (purchase_code && product_id && $('input[name="operation"]').val() == "download_plugin" ) {
               jQuery.ajax({
                    type: "POST",
                    url: ajaxUrl,
                    dataType: 'json',
                    data: {
                        action: 'download_plugin',
                        product_id: product_id,
                        purchase_code : purchase_code,
                        nonce : fc_ui_obj.nonce,
                    },
                    beforeSend: function() {
                        $('input[name="wpgmp_update_plugin"]').parent().find('.fc-loader').show();
                    },

                    success: function(data) {

                    var update_title = '';
                    var update_desc = '';
                    console.log(data);
                    if(data.status == 0 || data.purchase_verified === false ) {
                        update_title = 'Update Failed';
                        update_desc = "<div class='fc-msg'>Either your purchase code is invalid or expired. Please try again in few minutes. If problem exists continue, Please contact our support. <br> <input type='button' class='fc-btn fc-success'  value='Support Ticket'/></div>";    
                    } else {
                        update_title = 'Thank You for Downloading...';
                        update_desc = "<div class='fc-msg'>Your purchase code is verified. Your plugin will be downloaded in few seconds.</div>";
                 
                    }
                    $('.fc-main #fc_overview_modal').find('.fc-modal-header h4').html(update_title);
                    $('.fc-main #fc_overview_modal').find('.fc-modal-body').html(update_desc);

                    }

                });
            } 
        });
        
         $('.fc-communication').click(function() {
            var action_btn = $(this);
           
                jQuery.ajax({
                    type: "POST",
                    url: ajaxUrl,
                    data: {
                        nonce: fc_ui_obj.nonce,
                        action: 'fc_communication',
                        operation: 'get_plugin_details',
                        product: $('.fcdoc-product-info').data('current-product-slug'),
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        if( $(action_btn).hasClass('fa-refresh')) {
                            $(action_btn).addClass('fa-spin');
                        }
                       $(action_btn).closest('.fc-tabs-content').find('.fcdoc-loader').show();
                    },

                    success: function(data) {
                     if( $(action_btn).hasClass('fa-spin')) {
                            $(action_btn).removeClass('fa-spin');
                        }    
                    $(action_btn).closest('.fc-tabs-content').find('.fcdoc-loader').hide();

                    if( data.status == 0 ) {
                    $('.fc-main #fc_overview_modal').find('.fc-modal-header h4').html("Error");
                    $('.fc-main #fc_overview_modal').find('.fc-modal-body').html("Something went wrong! Try again in few minutes.");
                    $('.fc-main #fc_overview_modal').show(); 
                    } else if( data.status == 1 ) {
                    $('.fc-main #fc_overview_modal').find('.fc-modal-header h4').html(data.title);
                    $('.fc-main #fc_overview_modal').find('.fc-modal-body').html(data.content);
                    $('.fc-main #fc_overview_modal').show(); 
                    
                    }

                    }

                });

        });

        $('#submit-user-suggestion').click(function() {
            var action_btn = $(this);
            if ($('#user-email').val() == '') {
                $('#user-email').css('border', '1px solid red');
                return false;
            } else if ($('#user-suggestion').val() == '') {
                $('#user-suggestion').css('border', '1px solid red');
                return false;
            } else {

                jQuery.ajax({
                    type: "POST",
                    url: ajaxUrl,
                    data: {
                        nonce: fc_ui_obj.nonce,
                        action: 'fc_communication',
                        operation: 'submit_user_suggestion',
                        product: $('.fcdoc-product-info').data('current-product-slug'),
                        sender: $('#user-email').val(),
                        suggestion: $('#user-suggestion').val(),
                    },
                    dataType: 'json',
                    beforeSend: function() {

                       $(action_btn).closest('form').find('.fcdoc-loader').show();
                       $('#user-suggestion').val('');
                    },

                    success: function(data) {

                    $(action_btn).closest('form').find('.fcdoc-loader').hide();

                    if( data.status == 0 ) {
                    $('.fc-main #fc_overview_modal').find('.fc-modal-header h4').html("Error");
                    $('.fc-main #fc_overview_modal').find('.fc-modal-body').html("Something went wrong! Try again in few minutes.");
                    $('.fc-main #fc_overview_modal').show(); 
                    } else if( data.status == 1 ) {
                        //success
                    $('.fc-main #fc_overview_modal').find('.fc-modal-header h4').html(data.title);
                    $('.fc-main #fc_overview_modal').find('.fc-modal-body').html(data.content);
                    $('.fc-main #fc_overview_modal').show(); 
                    
                    }
      

                    }

                });

            }

        });

        function cmpVersions(a, b) {
            var i, diff;
            var regExStrip0 = /(\.0+)+$/;
            var segmentsA = a.replace(regExStrip0, '').split('.');
            var segmentsB = b.replace(regExStrip0, '').split('.');
            var l = Math.min(segmentsA.length, segmentsB.length);

            for (i = 0; i < l; i++) {
                diff = parseInt(segmentsA[i], 10) - parseInt(segmentsB[i], 10);
                if (diff) {
                    return diff;
                }
            }
            return segmentsA.length - segmentsB.length;
        }

        $('.check_for_updates_btn').click(function() {
            var action_btn = $(this);
            var current_version = $('.fcdoc-product-info').data('product-version');
            jQuery.ajax({
                type: "POST",
                url: ajaxUrl,
                data: {
                    nonce: fc_ui_obj.nonce,
                    action: 'fc_communication',
                    operation: 'compare_version',
                    product: $('.fcdoc-product-info').data('current-product-slug'),
                    current_version: current_version
                },
                dataType: 'json',
                beforeSend: function() {
                    $(action_btn).closest('.action').find('.fcdoc-loader').show();
                },
                success: function(data) {
                    
                    $(action_btn).closest('.action').find('.fcdoc-loader').hide();

                    if( data.status == 0 ) {
                        //error
                    } else if( data.status == 1 ) {
                        //success
                    $('.fc-main #fc_overview_modal').find('.fc-modal-header h4').html(data.title);
                    $('.fc-main #fc_overview_modal').find('.fc-modal-body').html(data.content);
                    $('.fc-main #fc_overview_modal').show(); 
                    
                    }           
                }

            });

        });

    }

});
jQuery(document).ready(function($) {

	$('.fc-field.ext_btn label').on('click', function(){      
		$(this).prev('.fc-file_input').trigger('click');
	});

    var currentDeletedTemplate = '';
    $('.yes-remove-current-template').on("click", function() {

        var product = $(this).data('product');
        var templatetype = $(this).data('templatetype');
        var templateName = $(this).data('templatename');
        var data = {
            action: 'core_backend_ajax_calls',
            product: product,
            templateName: templateName,
            templatetype: templatetype,
            selector: '.default-custom-template',
            operation: 'delete_custom_template'
        }

        currentDeletedTemplate = templateName;
        perform_ajax_events(data);
        $('#remove-current-template').modal('hide');

    });
    
    $('.default-custom-template').on("click", function() {

        $('#remove-current-template').modal('show');
        $('.yes-remove-current-template').data('product', $(this).data('product'));
        $('.yes-remove-current-template').data('templatetype', $(this).data('templatetype'));
        $('.yes-remove-current-template').data('templatename', $(this).data('templatename'));
        return false;

    });

    $("body").on('click', ".repeat_button", function() {
		
        var target = $(this).parent().parent();
        var new_element = $(target).clone();
        var inputs = $(new_element).find("input[type='text']");
        for (var i = 0; i < inputs.length; i++) {
            var element_name = $(inputs[i]).attr("name");
            var patt = new RegExp(/\[([0-9]+)\]/i);
            var res = patt.exec(element_name);
            var new_index = parseInt(res[1]) + 1;
            var name = element_name.replace(/\[([0-9]+)\]/i, "[" + new_index + "]");
            $(inputs[i]).attr("name", name);
        }
        
        var inputs = $(new_element).find("input[type='number']");
        for (var i = 0; i < inputs.length; i++) {
            var element_name = $(inputs[i]).attr("name");
            var patt = new RegExp(/\[([0-9]+)\]/i);
            var res = patt.exec(element_name);
            var new_index = parseInt(res[1]) + 1;
            var name = element_name.replace(/\[([0-9]+)\]/i, "[" + new_index + "]");
            $(inputs[i]).attr("name", name);
        }
        
        $(new_element).find("input[type='text']").val("");
        $(new_element).find("input[type='number']").val("");
        $(target).find(".repeat_button").text("Remove");
        $(target).find(".repeat_button").removeClass("repeat_button").addClass("repeat_remove_button");
        $(target).after($(new_element));
       

    });
    
    $("body").on('click', ".repeat_remove_button", function() {
        
        var target = $(this).parent().parent();
        var temp = $(target).clone();
        $(target).remove();
        var inputs = $(temp).find("input[type='text']");
        $.each(inputs, function(index, element) {
            var current_name = $(this).attr("name");
            var name = current_name.replace(/\[([0-9]+)\]/i, "");
            $.each($("*[name^='" + name + "']"), function(index, element) {
                current_name = $(this).attr('name');
                var name = current_name.replace(/\[([0-9]+)\]/i, "[" + index + "]");
                $(this).attr("name", name);
            });
        });

    });

    window.send_to_editor_default = window.send_to_editor;

	$('.fa-picture-o').click(function() {
        window.send_to_editor = function(html) {

            $('body').append('<div id="temp_image">' + html + '</div>');
            var img = $('#temp_image').find('img');
            imgurl = img.attr('src');
            $('.active_element').css('background-image', 'url(' + imgurl + ')');
            try {
                tb_remove();
            } catch (e) {}
            $('#temp_image').remove();
            window.send_to_editor = window.send_to_editor_default;
        };
        tb_show('', 'media-upload.php?post_ID=0&type=image&TB_iframe=true');
        return false;

    });
    
    var wpp_image_id = '';
    $('.choose_image').click(function() {
        
        var target = "icon_hidden_input";
        wpp_image_id = $(this).parent().parent().attr('id', target);
        currentClickedID = $(this).attr('id');
        window.send_to_editor = window.attach_image;
        tb_show('', 'media-upload.php?post_ID=0&target=' + target + '&type=image&TB_iframe=true');
        return false;

    });

    window.attach_image = function(html) {
		
		htmlobj = $(html);
        $classes = htmlobj.attr('class');
        if(typeof $classes == typeof undefined) {
				
			img = $(html).find("img");
			htmlobj = $(img);
			$classes = htmlobj.attr('class');
			var lastClass = $classes.split(' ').pop();
			$aid = lastClass.replace('wp-image-','');
			
		} else{
			
			var lastClass = $classes.split(' ').pop();
			$aid = lastClass.replace('wp-image-','');
		}
		  
        $('body').append('<div id="temp_image'+currentClickedID+'">' + html + '</div>');
        var img = $('#temp_image'+currentClickedID).find('img');
        imgurl = img.attr('src');
        imgclass = img.attr('class');
        imgid = parseInt(imgclass.replace(/\D/g, ''), 10);
        $(wpp_image_id).find('.remove_image').show();
       
        if($('#'+currentClickedID).prev('img').length > 0) {
			$('#'+currentClickedID).prev('img').show();
			$('#'+currentClickedID).prev('img').attr('src', imgurl);
			$('#'+currentClickedID).prev('img').removeClass('noimage');
		} else {
		   var imgTag = '<img src="'+imgurl+'" alt="" height="100" width="100" class="selected_image">';	
		   var removeLink = '<a style="border:none;text-decoration:underline" href="javascript:void(0);" id="" name="remove_image" class="fc-btn fc-btn-red remove_image remove_image fc-3 fc-offset-1" data-target="'+$('#'+currentClickedID).data('target')+'">Remove Image</a>';
		   $('#'+currentClickedID).parent('div').prepend(imgTag);
		   $('#'+currentClickedID).after(removeLink);	
		}
		
        var img_hidden_field = $('#'+currentClickedID).data('target');
        $('#'+img_hidden_field).val(imgurl);
        $('#'+img_hidden_field+'_attachment_id').val($aid);
        try {
            tb_remove();
        } catch (e) {};
        $('#temp_image'+currentClickedID).remove();
        window.send_to_editor = window.send_to_editor_default;
        
    }
   $(document).on('click', '.remove_image' ,function(){
	   
	    if(confirm('Are you sure you want to remove this image ?')) {
		    img = $(this).parent().find('img');
			$(img).attr('src', '');
			$(this).parent().find('input[name="' + $(this).data('target') + '"]').val('');
			$(this).parent().find('input[name="' + $(this).data('target') + '_attachment_id"]').val('');
			$(this).hide();
			$(img).hide();
			return false;		
		}else{
			return false;
		}
        
    });

    $('.switch_onoff').change(function() {
        var target = $(this).data('target');
        if ($(this).attr('type') == 'radio') {
            $(target).closest('.fc-form-group').hide();
            target += '_' + $(this).val();
        }
        if ($(this).is(":checked")) {
            $(target).closest('.fc-form-group').show();
        } else {
            $(target).closest('.fc-form-group').hide();
            if ($(target).hasClass('switch_onoff')) {
                $(target).attr('checked', false);
                $(target).trigger("change");
            }
        }

    });

    $.each($('.switch_onoff'), function(index, element) {
        if (true == $(this).is(":checked")) {
            $(this).trigger("change");
        }

    });

    $("input[name='wpp_refresh']").trigger('change');

    function ajax_success_handler(data, selector) {

        switch (selector) {

            case '.set-default-template':
                $('.fc_tools').css('display', 'none');
                $('.fc_name').css('display', 'none');
                $('.current_selected').parent().parent().find('.fc_name').css('display', 'block');
                $('.current_selected').closest('.fc_tools').css('display', 'block');
                $('.current-temp-in-use').removeClass('current-temp-in-use');
                $('.current_selected').addClass('current-temp-in-use');
                break;
            case '.default-custom-template':
                $(".default-custom-template[data-templatename=" + currentDeletedTemplate + "]").parent().parent().parent().remove();
                break;
            default:

        }

    }
    
    function perform_ajax_events(data) {

        $inputs = data
        jQuery.ajax({
            type: "POST",
            url: fc_ui_obj.ajax_url,
            dataType: "json",
            data: data,
            beforeSend: function() {

                jQuery(".se-pre-con").fadeIn("slow");
            },
            success: function(data) {
                jQuery(".se-pre-con").fadeOut("slow");
                ajax_success_handler(data, $inputs.selector);

            }

        });

    }

	// Sticky Footer
    if ($('.fc-footer').length > 0) {

        $(window).scroll(function() {

            if ($('.flippercode-ui-height').height() > 800) {

                if ($('.fc-no-sticky').length > 0) {
                    return;
                }

                var scroll = $(window).scrollTop();
                var scrollBottom = $(window).height() - scroll;

                if (scroll >= 0) {
                    $(".fc-footer").addClass("fc-fixed-footer");

                }
                if ($(window).scrollTop() + $(window).height() > ($(document).height() - 30)) {
                    $(".fc-fixed-footer").removeClass("fc-fixed-footer");
                }
            }

        });

    }
    
    $(".set-default-template").click(function(e) {

        $('.current_selected').removeClass('current_selected');
        $(this).addClass('current_selected');
        e.preventDefault();

        var template = $(this).data("templatename");
        var templatetype = $(this).data("templatetype");
        var product = $(this).data("product");

        var data = {
            action: 'core_backend_ajax_calls',
            product: product,
            template: template,
            templatetype: templatetype,
            selector: '.set-default-template',
            operation: 'set_default_template',
        };
        perform_ajax_events(data);

    });

	if (jQuery(".color").length > 0) {
		$('.color').wpColorPicker();
    }
    
    if (jQuery(".fc_select2").length > 0) {
       jQuery(".fc_select2").select2();
    }
    
     $('.fc-main').find('[data-toggle="tab"]').click(function(e){
        e.preventDefault();
        var tab_id = $(this).attr('href');
        $('.fc-tabs-container .fc-tabs-content').hide();
        $(tab_id).show();
        $('.fc-tabs .active').removeClass('active');
        $(this).parent().addClass('active');
    });
    
    if ($('.current-temp-in-use').length) {
        $('.current-temp-in-use').parent().parent().find('.fc_name').css('display', 'block');
        $('.current-temp-in-use').closest('.fc_tools').css('display', 'block');
    }

});
(function( $ ) {
  $.fn.fcModal = function(options) {
	 
	  var fcmodal = $.extend( {
		  onOpen: function() {},
		  register_fc_modal_handing_events: function() {
			  
		  $('.fc-modal').each(function(i, obj) {
			
				var initiator = $(this).data('initiator');
				
				if(typeof initiator != typeof undefined) {
					if($(initiator.length)) {
						
						$(initiator).data('target',$(this).attr('id'));
						var releatedModal = $(this).attr('id');
						$('body').on('click', initiator, function() {
							
							if($('#'+releatedModal).length > 0) {
								fcmodal.onOpen();
								$('#'+releatedModal).css('display','block');
							}
					
						});	
						
						
					}	
				}
				
				
			  });
		 	
			$('body').on('click', '.fc-modal-close', function() {
				var releatedModal = $(this).closest('div.fc-modal');
				$(releatedModal).css('display','none');
			});	
			
			window.onclick = function(event) {
				
				 if($(event.toElement).hasClass('fc-modal'))
				 $('.fc-modal').hide();
				
			}
		  }
	  }, options);
	 
	  return this.each(function() {
	   fcmodal.register_fc_modal_handing_events();
	  });
  };
})( jQuery );
