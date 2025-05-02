module.exports = function (app, table_headings) {
	return app.models.base.extend({
		defaults: {
			id: '',
			label: '',
			hidden: false,
			navClasses: '',
			rows: [],
			table_id: '',
			col_headings: table_headings.default,
		},

		initialize: function () {
			this.rows = new app.collections.tabRows(this.get('rows'), {tab: this});
			this.listenTo(this.rows, 'change', this.triggerRowChange);
		},

		triggerRowChange: function (rowModel) {
			this.trigger('rowChange', rowModel);
		}
	});
};
