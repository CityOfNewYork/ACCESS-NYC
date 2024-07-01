module.exports = function (app, gc) {
	return app.views.base.extend({
		template: wp.template('gc-post-column-row'),
		tagName: 'span',
		className: 'gc-status-column',
		id: function () {
			return 'gc-status-row-' + this.model.get('id');
		},

		initialize: function () {
			this.listenTo(this.model, 'change:status', this.render);
		},

		html: function () {
			return this.template(this.model.toJSON());
		},

		render: function () {
			var $td = gc.$id('post-' + this.model.get('id')).find('.column-gathercontent');
			$td.html(this.html());

			return this;
		}
	});
};
