jQuery(function () {

    var ajax_success_action = function (response, response_text) {
        if (response.success) {
            response_text.text(icl_ajx_saved);
        } else {
            response_text.text(icl_ajx_error);
            response_text.show();
        }
        setTimeout(function () {
            response_text.fadeOut('slow');
		}, 2500);
	};

    jQuery( '#js-translated_document-options-btn' ).click(function(){

		var document_status = jQuery( 'input[name*="icl_translated_document_status"]:checked' ).val(),
      document_status_sync = jQuery( 'input[name*="icl_translated_document_status_sync"]:checked' ).val(),
			page_url = jQuery( 'input[name*="icl_translated_document_page_url"]:checked' ).val(),
      hide_translated_terms = jQuery('input[name="tm_block_retranslating_terms"]:checked').val(),
			response_text = jQuery( '#icl_ajx_response_tdo' ),
			spinner = '<span id="js-document-options-spinner" style="float: inherit; margin: 0" class="spinner is-active"></span>';

		response_text.html( spinner );
		response_text.show();

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpml_translated_document_options',
				nonce: jQuery( '#wpml-translated-document-options-nonce' ).val(),
				document_status: document_status,
        document_status_sync: document_status_sync,
        tm_block_retranslating_terms: hide_translated_terms,
				page_url: page_url
			},
			success: function ( response ) {
				ajax_success_action( response, response_text );
			}
		});
	});

	jQuery( '#translation-pickup-mode' ).click(function(){
		var pickup_mode = jQuery( 'input[name*="icl_translation_pickup_method"]:checked' ).val(),
			response_text = jQuery( '#icl_ajx_response_tpm' ),
			spinner = '<span id="js-document-options-spinner" style="float: inherit; margin: 0" class="spinner is-active"></span>';

		response_text.html( spinner );
		response_text.show();

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wpml_save_translation_pickup_mode',
				nonce: jQuery( '#wpml_save_translation_pickup_mode' ).val(),
				pickup_mode: pickup_mode
			},
			success: function ( response ) {
				ajax_success_action( response, response_text );
			}
		});
	});

  var radioButtons = document.querySelectorAll('input[name="t_method"]');

  function updateTableClass() {
    var table = document.querySelector('.t_method__table');
    var useForOldTranslationsCheckbox = document.querySelector('.old-translations .wpml-checkbox');
    useForOldTranslationsCheckbox.setAttribute('style', 'display: none;');

    radioButtons.forEach(function(radio) {
      var radioLabel = radio.nextElementSibling;
      if (radio.checked) {
        radioLabel.textContent = icl_enabled_text;
        if (radio.value === "ATE") {
          table.classList.add('t_method__ate-editor');
          table.classList.remove('t_method__classic-editor');
          useForOldTranslationsCheckbox.setAttribute('style', 'display: inline-block;');
        } else if (radio.value === "1") {
          table.classList.add('t_method__classic-editor');
          table.classList.remove('t_method__ate-editor');
        }
      } else {
        radioLabel.textContent = icl_choose_text;
      }
    });
  }

  updateTableClass();

  radioButtons.forEach(function(radio) {
    radio.addEventListener('change', updateTableClass);
  });
});
