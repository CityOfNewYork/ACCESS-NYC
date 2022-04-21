/*jshint devel:true */
/*global jQuery, ajaxurl, tmEditorStrings */

var WPML_TM = WPML_TM || {};

WPML_TM.translationMemory = function ( languages ) {
	"use strict";

	var self = this;

	var init = function ( languages ) {
		self.strings = {};
		self.languages = languages;
	};

	var hasTranslation = function ( field ) {
		return '' === field.field_data || '' !== field.field_data_translated || 0 !== field.field_finished;
	};

	var updateViewWithTranslation = function ( data ) {
		if ( data.original in self.strings ) {
			for ( var i = 0; i < self.strings[ data.original ].length; i++ ) {
				var view = self.strings[ data.original ][ i ];
				if ( view.getTranslation() === '' ) {
					view.setTranslation( data.translation );
				}
			}
		}
	};

	self.addField = function ( field, view ) {
		if ( !hasTranslation( field ) ) {

			var string = field.field_data;
			if ( !(string in self.strings) ) {
				self.strings[ string ] = [];
			}
			self.strings[ string ].push( view );
		}
	};

	self.fetch = function () {
		var strings = [];

		for ( var key in self.strings ) {
			if ( self.strings.hasOwnProperty( key ) ) {
				strings.push( {string: key, source: self.languages['source'], target: self.languages['target']} );
			}
		}
		if ( strings.length > 0 ) {
			jQuery.ajax(
				{
					type: "POST",
					url: ajaxurl,
					dataType: 'json',
					data: {
						data: JSON.stringify({ batch: true, strings: strings }),
						action: 'wpml_action',
						nonce: tmEditorStrings.translationMemoryNonce,
						endpoint: tmEditorStrings.translationMemoryEndpoint
					},
					success: function ( response ) {
						if ( response.success ) {
							var data = response.data;
							for ( var i = 0; i < data.length; i++ ) {
								if ( data[i].length > 0 ) {
									updateViewWithTranslation( data[i][0] );
								}
							}
						}
					}
				}
			);
		}
	};

	init( languages );
};
