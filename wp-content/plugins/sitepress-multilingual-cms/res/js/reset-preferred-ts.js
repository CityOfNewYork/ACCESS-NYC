var ResetPreferredTS = function () {
	"use strict";

	var self = this;

	self.init = function () {
		var box = jQuery('#wpml_tm_reset_preferred_translation_service_btn');
		var button = box.find('.button-primary');
		var nonce = box.find('#wpml_tm_reset_preferred_translation_service_nonce').val();
		var spinner = box.find('.spinner');

		button.on('click', function (e) {
			e.preventDefault();

			spinner.addClass('is-active');

			jQuery.ajax({
				type:     "POST",
				url:      ajaxurl,
				data:     {
					'action': 'wpml-tm-reset-preferred-translation-service',
					'nonce':  box.find('#wpml_tm_reset_preferred_translation_service_nonce').val()
				},
				dataType: 'json',
				success:  function (response) {
					if (response.success) {
						document.location.reload(true);
					} else {
						alert(response.data);
					}
				},
				error:    function (jqXHR, status, error) {
                    var parsedResponse = jqXHR.statusText || status || error;
                    alert(parsedResponse);
                },
                            complete: function () {
                                spinner.removeClass('is-active');
                            }
                        });
        });
    };

    jQuery(function () {
		resetPreferredTS.init();
    });
};

var resetPreferredTS = new ResetPreferredTS();
