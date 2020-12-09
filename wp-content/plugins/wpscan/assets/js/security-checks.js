jQuery(document).ready(function($) {
	
    // Tooltips
    $("strong[title]").tooltip({
        position: {
            my: "left top",
            at: "right+5 top-5",
            collision: "none"
        }
    });
    
    // Actions
    $('.security-check-actions button').on('click', function() {

        let check = $(this).data('check-id');
        let action_id = $(this).data('action');
        let should_confirm = $(this).data('confirm');
        
        if ( should_confirm && ! confirm('Are you sure?') ) {
            return;
        }

        $(this).siblings('.spinner').css('visibility', 'visible');

        $.ajax({
            url: wpscan.ajaxurl,
            method: 'POST',
            data: {
				action: 'wpscan_check_action',
				action_id: action_id,
				check,
				_ajax_nonce: wpscan.ajax_nonce
            },
            success: function(res) {
                console.log(res);
                if (res.success) {
                    location.reload()
                } else {
                    alert('Something went wrong, please reload the page.')
                }
			}
		});      
    });

});