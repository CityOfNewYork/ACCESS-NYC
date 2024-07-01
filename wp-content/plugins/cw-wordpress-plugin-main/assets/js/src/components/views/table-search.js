module.exports = function (app) {
	return Backbone.View.extend({
		el: '#gc-items-search',
		template: wp.template('gc-table-search'),
		events: {
			'keyup #gc-search-input': 'filterCollection',
			'search #gc-search-input': 'filterCollection',
		},

		initialize: function () {
			this.render();
		},

		filterCollection: _.debounce(function (evt) {
			this.collection.search(evt.target.value);
		}, 100),

		render: function () {
			this.$el.html(this.template());
			return this;
		}

	});
};
