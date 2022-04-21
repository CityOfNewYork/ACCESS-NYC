jQuery(function () {
    jQuery('.js-wpml-st-troubleshooting-action').click(function () {
        var self = jQuery(this);
        var nonce = self.data('nonce');
        var reload = self.data('reload');

        self.prop('disabled', true);
        self.after(icl_ajxloaderimg);
        jQuery.ajax({
                        type: "post",
                        url : ajaxurl,
            data : {
                action: self.data('action'),
                nonce: nonce
            },
            success: function() {
                if (reload) {
                    window.location.reload(true);
                } else {
                    alert(self.data('success-message'));
                }
            },
            complete: function() {
                self.prop('disabled', false);
                self.next().fadeOut();
            }
        });
    });
});