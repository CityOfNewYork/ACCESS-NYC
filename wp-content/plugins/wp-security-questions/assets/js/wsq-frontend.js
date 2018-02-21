jQuery(document).ready(function($) {

    $('.helpful-block-content a').click(function(event) {
        event.preventDefault();
        var post_id = $(this).data('post');
        var response = $(this).data('response');
        var show_positive_message = false;
        var show_negative_message = false;
        var main = $(this).parent();
        main.find('.wsq-title').remove();
        main.find('.wsq-message').remove();
        main.find('.wsq-submit').remove();

        var title_message = '';
        if (wsq_js_lang.positive_feedback == 'true') {
            show_positive_message = true;
            title_message = wsq_js_lang.wsq_title_yesthank;
        }
        if (wsq_js_lang.negative_feedback == 'true') {
            show_negative_message = true;
            title_message = wsq_js_lang.wsq_title_nothank;
        }

        if (response == '1') {
            title_message = wsq_js_lang.wsq_title_yesthank;
        } else if (response == '0') {
            title_message = wsq_js_lang.wsq_title_nothank;
        }
        if ((show_negative_message == true && response == '0') || (show_positive_message == true && response == '1')) {
            var title_box = $('<div class="wsq-title">' + title_message + '</div>');
            var mess_box = $('<textarea class="wsq-message" rows="3" cols="30"></textarea>');
            var negative_btn = $('<input data-response="' + response + '" data-post="' + post_id + '" type="button" name="wsq-submit" class="wsq-submit" value="' + wsq_js_lang.submit_text + '"/>');
            main.append(title_box);
            main.append(mess_box);
            main.append(negative_btn);
        } else {
            wsq_send_feedback($(this));
        }

    });

    function wsq_send_feedback(obj) {
        var post_id = $(obj).data('post');
        var response = $(obj).data('response');
        var message = $(obj).parent().find('.wsq-message').val();
        var title = $(obj).parent().parent().parent().data('title');
        var ajax_url = wsq_js_lang.ajax_url;
        var data = {
            post_id: post_id,
            response: response,
            message: message,
            title: title,
            action: 'wsq_ajax_call',
            operation: 'wthp_log_feedback',
            nonce: wsq_js_lang.nonce
        };
        var main = $(obj).parent();

        $.ajax({
            url: ajax_url,
            data: data,
            dataType: 'json',
            type: 'POST',
            beforeSend: function() {
                main.append('<div class="wsq-loader"></div>');
            },
            complete: function() {
                //main.find('.wsq_loader').remove();
            },
            success: function(resp) {
                elem = $('<p>').hide();
                if (resp.success == true) {
                    elem.addClass('wsq-success');
                } else if (resp.error == true) {
                    elem.addClass('wsq-error');
                }
                main.parent().html(elem);
                elem.html(resp.message).fadeIn(500);
            },
        });
    }
    $('body').on('click', '.wsq-submit', function(event) {
        event.preventDefault();
        wsq_send_feedback($(this));
    });

});
