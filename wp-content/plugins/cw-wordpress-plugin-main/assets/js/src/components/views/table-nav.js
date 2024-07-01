module.exports = function (app, $, gc) {
	return app.views.base.extend({
		template: wp.template('gc-table-nav'),

		render: function () {
			var collection = this.collection.current();

			this.$el.html(this.template({
				count: collection.length,
				selected: collection.checked ? collection.checked().length : 0,
			}));

			return this;
		},
	});
};
