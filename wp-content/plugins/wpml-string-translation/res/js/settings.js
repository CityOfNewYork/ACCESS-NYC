/*jshint devel:true */
/*global jQuery */

jQuery(function (){
	// Register String Tracking Dialog when it is enabled.
	var dialogElem = jQuery("#wpml-track-strings-info-dialog");

	dialogElem.dialog({
		autoOpen:		false,
		resizable:		false,
		draggable:		false,
		modal: 			true,
		closeText: 		dialogElem.data('close-btn-label'),
		closeOnEscape: 	true,
		minWidth: 		600,
		classes: {
			'ui-dialog': 'wpml-track-strings-dialog-container'
		},
		buttons: [
			{
				class: 'st-track-strings-ok button button-secondary',
				text: dialogElem.data('ok-btn-label'),
				click: function () {
					jQuery(this).dialog('close');
				}
			}
		]
	});

	/**
	 * Show Strings tracking dialog when "icl-save-form-icl_st_track_strings" is fired.
	 *
	 * @see {wpmlCustomEvent} in /sitepress-multilingual-cms/res/js/scripts.js
 	 */
	jQuery(document).on('icl-save-form-icl_st_track_strings', function (){
		if (jQuery('#track_strings').prop('checked')) {
			jQuery( "#wpml-track-strings-info-dialog" ).dialog( "open" );
		}
	});
});