jQuery(document).ready(function($) {


    $('.wpgmp_datepicker').datepicker({
        dateFormat: 'dd-mm-yy'
    });

    $("body").on('change', "select[name='filter_by']", function() {
        var value = $(this).val();
        if (value == 'custom') {
            $('#custom_filter').css('display', 'inline');
        } else {
            $('#custom_filter').css('display', 'none');
        }
    });

    $('.wsq-sample-design').click(function() {
        $('.wsq-sample-design').removeClass('wsq-selected-design');
        $(this).addClass('wsq-selected-design');
        $('input[name="wthp_option_settings[theme_id]"]').val($(this).data('theme'));
    });
    var wpp_image_id = '';
    //intialize add more...
    $("body").on('click', ".repeat_button", function() {
        //find out which container we need to copy.
        var target = $(this).parent().parent();
        var new_element = $(target).clone();
        //incrase index here
        var inputs = $(new_element).find("input[type='text']");
        for (var i = 0; i < inputs.length; i++) {
            var element_name = $(inputs[i]).attr("name");
            var patt = new RegExp(/\[([0-9]+)\]/i);
            var res = patt.exec(element_name);
            var new_index = parseInt(res[1]) + 1;
            var name = element_name.replace(/\[([0-9]+)\]/i, "[" + new_index + "]");
            $(inputs[i]).attr("name", name);
        }
        $(new_element).find("input[type='text']").val("");
        $(target).find(".repeat_button").text("Remove");
        $(target).find(".repeat_button").removeClass("repeat_button").addClass("repeat_remove_button");
        $(target).after($(new_element));

    });
    //Delete add more...
    $("body").on('click', ".repeat_remove_button", function() {
        //find out which container we need to copy.
        var target = $(this).parent().parent();
        var temp = $(target).clone();
        $(target).remove();
        //reindexing
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

    $('span.delete a').click(function() {

        if (confirm(wpp_js_lang.confirm))
            return true;

        return false;

    });

    $("body").on('change', "input[name='rule_match[period]']", function() {
        var value = $(this).val();
        $('.wpgmp_datepicker').parent().parent().hide();
        $('input[name="rule_match[n_days]"]').parent().parent().hide();
        if (value == 'between') {
            $('.wpgmp_datepicker').parent().parent().show();
        } else if (value == 'n_days') {
            $('input[name="rule_match[n_days]"]').parent().parent().show();
        }
    });

    $("div.wpp_form_horizontal .wpp_add_more_fields").click(function() {

        var wpp_length = $(".form-group").length + 1;

        var more_textbox = $('<div class="row form-group">' +
            '<div class="col-md-8"><input  placeholder="Enter name here..." class="form-control" type="text" name="name[]" id="txtbox' + wpp_length + '" /></div>' +
            '<div class="col-md-2"><a href="javascript:void(0);" class="btn btn-danger btn-xs wpp_remove_more_fields">Remove</a>' +
            '</div></div>');

        more_textbox.hide();
        $(".form-group:last").after(more_textbox);
        more_textbox.fadeIn("slow");

        return false;
    });


    $('div.wpp_form_horizontal').on('click', '.wpp_remove_more_fields', function() {

        var remove = confirm("Are you sure you want to delete ? ");
        if (remove == true) {
            var id = $(this).attr('id');
            $(this).parent().parent().css('background-color', '#FF6C6C');
            $(this).parent().parent().css('padding-top', '5px');
            $(this).parent().parent().fadeOut("slow", function() {

                $(this).remove();
                $('.label-numbers').each(function(index) {
                    $(this).text(index + 1);
                });

            });


            jQuery.ajax({
                type: "POST",
                url: wpp_local.urlforajax,
                data: {
                    action: 'wpp_ajax_operation',
                    'id': id
                },
                beforeSend: function() {},
                success: function(data) {}

            });
            return false;

        } else {
            return false;
        }



    });

    window.send_to_editor_default = window.send_to_editor;

    $('.choose_image').click(function() {
        var target = "icon_hidden_input";
        wpp_image_id = $(this).parent().parent().attr('id', target);
        window.send_to_editor = window.attach_image;
        tb_show('', 'media-upload.php?post_ID=0&target=' + target + '&type=image&TB_iframe=true');
        return false;
    });

    window.attach_image = function(html) {
        $('body').append('<div id="temp_image">' + html + '</div>');
        var img = $('#temp_image').find('img');
        imgurl = img.attr('src');
        imgclass = img.attr('class');
        imgid = parseInt(imgclass.replace(/\D/g, ''), 10);
        $(wpp_image_id).find('.remove_image').show();
        $(wpp_image_id).find('img.selected_image').attr('src', imgurl);
        var img_hidden_field = $(wpp_image_id).find('.choose_image').data('target');
        $(wpp_image_id).find('input[name="' + img_hidden_field + '"]').val(imgurl);
        try {
            tb_remove();
        } catch (e) {};
        $('#temp_image').remove();
        window.send_to_editor = window.send_to_editor_default;
    }


    $('.remove_image').click(function() {
        wpp_image_id = $(this).parent().parent();
        $(wpp_image_id).find('.selected_image').attr('src', '');
        $(wpp_image_id).find('input[name="' + $(this).data('target') + '"]').val('');
        $(this).hide();
        return false;
    });

    $('.switch_onoff').change(function() {
        var target = $(this).data('target');
        if ($(this).attr('type') == 'radio') {
            $(target).closest('.form-group').hide();
            target += '_' + $(this).val();
        }
        if ($(this).is(":checked")) {
            $(target).closest('.form-group').show();
        } else {
            $(target).closest('.form-group').hide();
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
    $('.wpgmp-overview .color').wpColorPicker();
});
