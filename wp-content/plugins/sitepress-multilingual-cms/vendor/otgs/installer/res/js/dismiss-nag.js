var otgs_wp_installer_dismiss_nag = {

	init: function () {
		jQuery('.installer-dismiss-nag').click(otgs_wp_installer_dismiss_nag.dismiss_nag);
	},

	dismiss_nag: function () {
		var element = jQuery(this);
		var data = {
			action: 'installer_dismiss_nag',
			repository: element.data('repository'),
			noticeType: element.data('noticeType'),
			noticePluginSlug: element.data('noticePluginSlug') !== 'undefined' ? element.data('noticePluginSlug') : null,
		};

		jQuery.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: data,
			success:
				function () {
					element.closest('.otgs-is-dismissible').remove();
				}
		});

		return false;
	}
};

jQuery(document).ready(otgs_wp_installer_dismiss_nag.init);
