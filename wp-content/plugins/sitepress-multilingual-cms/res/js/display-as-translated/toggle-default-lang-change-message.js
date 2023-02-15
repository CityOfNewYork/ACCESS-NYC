jQuery(function () {
    var before_message = jQuery('.wpml-default-lang-before-message');
    var save = jQuery('#icl_save_default_button');
    var gotIt = jQuery('.wpml-default-lang-before-message input');

    jQuery('#icl_change_default_button').click(function () {
        before_message.show();
        save.prop('disabled', true);
    });

    jQuery('#icl_cancel_default_button').click(function () {
		before_message.hide();
	});

	jQuery(gotIt).click(function(){
		save.prop('disabled', !gotIt.is(':checked'));
	})
});