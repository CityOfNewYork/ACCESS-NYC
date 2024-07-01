module.exports = function (app, defaults) {
	defaults = jQuery.extend({}, {
		action: 'cwby_sync_items',
		data: '',
		percent: 0,
		nonce: '',
		id: '',
		stopSync: true,
		flush_cache: false,
	}, defaults);

	return app.models.base.extend({
		defaults: defaults,

		initialize: function () {
			this.listenTo(this, 'send', this.send);
		},

		reset: function () {
			this.clear().set(this.defaults);
			return this;
		},

		send: function (formData, cb, percent, failcb) {
			if (percent) {
				this.set('percent', percent);
			}

			jQuery.post(
				window.ajaxurl,
				{
					action: this.get('action'),
					percent: this.get('percent'),
					nonce: this.get('nonce'),
					id: this.get('id'),
					data: formData,
					flush_cache: this.get('flush_cache')
				},
				function (response) {
					this.trigger('response', response, formData);

					if (response.success) {
						return cb(response);
					}

					if (failcb) {
						return failcb(response);
					}
				}.bind(this)
			);

			return this;
		},

	});
};
