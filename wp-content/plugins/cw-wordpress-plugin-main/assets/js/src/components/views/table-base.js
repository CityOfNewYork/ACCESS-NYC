module.exports = function (app, $, gc) {
	app.views.tableSearch = require('./../views/table-search.js')(app, $, gc);
	app.views.tableNav = require('./../views/table-nav.js')(app, $, gc);

	return app.views.base.extend({
		timeoutID: null,
		ajax: null,
		tableNavView: null,
		searchView: null,
		modelView: null, // Need to override.
		timeoutTime: 1000,

		events: {
			'click .gc-field-th.sortable': 'sortRowsByColumn',
			'change .gc-field-th.gc-check-column input': 'checkAll'
		},

		initialize: function () {
			this.setupAjax();

			this.listenTo(this.collection, 'render', this.render);
			this.listenTo(this.collection, 'notAllChecked', this.allCheckedStatus);
			this.listenTo(this.collection, 'change:checked', this.renderNav);
			this.listenTo(this, 'render', this.render);

			this.tableNavView = new app.views.tableNav({
				collection: this.collection
			});

			this.searchView = new app.views.tableSearch({
				collection: this.collection
			});
		},

		// Need to override.
		setupAjax: function () {
		},

		sortRowsByColumn: function (evt) {
			evt.preventDefault();
			var collection = this.collection.current();

			var $this = $(evt.currentTarget);
			var column = $this.find('a').data('id');
			var direction = false;

			if ($this.hasClass('asc')) {
				direction = 'desc';
			}

			if ($this.hasClass('desc')) {
				direction = 'asc';
			}

			if (!direction) {
				direction = collection.sortDirection;
			}

			if ('asc' === direction) {
				$this.addClass('desc').removeClass('asc');
			} else {
				$this.addClass('asc').removeClass('desc');
			}

			collection.trigger('sortByColumn', column, direction);
			this.sortRender();
		},

		buttonStatus: function (enable) {
			this.$('.button-primary').prop('disabled', !enable);
		},

		allCheckedStatus: function () {
			this.$('.gc-field-th.gc-check-column input').prop('checked', this.collection.allChecked);
		},

		checkAll: function (evt) {
			this.collection.trigger('checkAll', $(evt.target).is(':checked'));
		},

		doSpinner: function () {
			var html = this.blankRow('<span class="gc-loader spinner is-active"></span>');
			this.renderRows(html);
		},

		setTimeout: function (callback) {
			this.timeoutID = window.setTimeout(callback, this.timeoutTime);
		},

		clearTimeout: function () {
			window.clearTimeout(this.timeoutID);
			this.timeoutID = null;
		},

		getRenderedRows: function () {
			var rows;

			if (this.collection.current().length) {
				rows = this.getRenderedModels(this.modelView, this.collection.current());
			} else {
				rows = this.blankRow(gc._text.no_items);
			}

			return rows;
		},

		sortRender: function () {
			this.render();
		},

		blankRow: function (html) {
			var cols = this.$('thead tr > *').length;
			return '<tr><td colspan="' + cols + '">' + html + '</td></tr>';
		},

		renderRows: function (html) {
			this.$('tbody').html(html || this.getRenderedRows());
		},

		renderNav: function () {
			this.$('#gc-tablenav').html(this.tableNavView.render().el);
		},

		render: function () {
			var collection = this.collection.current();

			// Re-render and replace table rows.
			this.renderRows();

			// Re-render table nav
			this.renderNav();

			// Make sync button enabled/disabled
			this.buttonStatus(collection.syncEnabled);

			// Make check-all inputs checked/unchecked
			this.allCheckedStatus(collection.allChecked);

			return this;
		}

	});
};
