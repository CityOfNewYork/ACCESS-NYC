module.exports = function (app, $, gc) {
	var base = require('./../views/mapping-metabox.js')(app, $, gc);

	var model = new Backbone.Model({
		id: true,
		cancelBtn: true,
		accounts: [],
		projects: [],
		mappings: []
	});

	var View = base.extend({
		close: function () {
			model = this.resetModel();
			base.prototype.close.call(this);
		}
	});

	return function (postIds) {
		model.set('ids', postIds);

		var view = new View({
			model: model
		});

		view.$el.addClass('postbox');

		return view.step();
	};
};
