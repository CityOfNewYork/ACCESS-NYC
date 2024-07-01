module.exports = function (app) {
	return app.views.base.extend({
		template: wp.template('gc-item'),
		tagName: 'tr',
		className: 'gc-item gc-enabled',
		id: function () {
			return this.model.get('id');
		},

		events: {
			'change .gc-check-column input': 'toggleCheck',
			'click .gc-reveal-items': 'toggleExpanded',
			'click .gc-status-column': 'toggleCheckAndRender'
		},

		initialize: function () {
			this.listenTo(this.model, 'change:checked', this.render);
		},

		toggleCheck: function () {
			this.model.set('checked', !this.model.get('checked'));
		},

		toggleCheckAndRender: function (evt) {
			this.toggleCheck();
			this.render();
		}
	});
};
