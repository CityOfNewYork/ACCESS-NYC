var nfRadio = Backbone.Radio;

function nf_recaptcha_response( response ) {
    nfRadio.channel( 'recaptcha' ).request( 'update:response', response );
}

var nfRecaptcha = Marionette.Object.extend( {
	initialize: function() {
		/*
		 * If we've already rendered our form view, render our recaptcha fields.
		 */		
		if ( 0 != jQuery( '.g-recaptcha' ).length ) {
			this.renderCaptcha();
		}
		/*
		 * We haven't rendered our form view, so hook into the view render radio message, and then render.
		 */
		this.listenTo( nfRadio.channel( 'form' ), 'render:view', this.renderCaptcha );			
	},

	renderCaptcha: function() {
		jQuery( '.g-recaptcha' ).each( function() {
			var opts = {
				theme: jQuery( this ).data( 'theme' ),
				sitekey: jQuery( this ).data( 'sitekey' ),
				callback: nf_recaptcha_response
			};
			
			grecaptcha.render( jQuery( this )[0], opts );
		} );		
	}
	
} );

var nfRenderRecaptcha = function() {
	new nfRecaptcha();
}
