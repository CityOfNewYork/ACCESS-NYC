jQuery(document).ready(function($)
{
	 		$('.emr-installer').on('click', function(e){
	 			e.preventDefault();
	 			var button = $(this);
				var plugin = button.data('plugin');
				var nonce = $('#upsell-nonce').val();

				button.text(emr_upsell.installing);

	 			var enr_eg_opts = {
	 				url: emr_upsell.ajax,
	 				type: 'post',
	 				async: true,
	 				cache: false,
	 				dataType: 'json',
	 				data: {
	 					action: 'emr_plugin_install',
	 					nonce: nonce,
	 					plugin: plugin //'https://downloads.wordpress.org/plugin/envira-gallery-lite.zip',
	 				},
	 				success: function(response) {
	 					$(button).addClass('hidden');

						$('.emr-activate[data-plugin="' + plugin + '"]').removeClass('hidden');

	 				},
	 				error: function(xhr, textStatus, e) {
	 				},
	 			};

	 			$.ajax(enr_eg_opts);
	 		});

		$('.emr-activate').on('click', function(e){
			e.preventDefault();

			var button = $(this);
		  var plugin = button.data('plugin');
			var nonce = $('#upsell-nonce-activate').val();

			var enr_eg_opts = {
				url: emr_upsell.ajax,
				type: 'post',
				async: true,
				cache: false,
				dataType: 'json',
				data: {
					action: 'emr_plugin_activate',
					nonce: nonce,
					plugin: plugin,
				},
				success: function(response) {
					$(button).addClass('hidden')
				  $('.emr-activate-done[data-plugin="' + plugin + '"]').removeClass('hidden');

				},
				error: function(xhr, textStatus, e) {
				},
			};
			$.ajax(enr_eg_opts);
		});

});
