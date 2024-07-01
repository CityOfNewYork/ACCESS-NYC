module.exports = function (app) {
	var sortKey = null;
	var sortDirection = 'asc';
	var Collection = app.collections.base.extend({
		model: app.models.item,
		totalChecked: 0,
		allChecked: false,
		syncEnabled: false,
		processing: false,
		sortKey: sortKey,
		sortDirection: sortDirection,

		initialize: function (models, options) {
			this.listenTo(this, 'checkAll', this.toggleChecked);
			this.listenTo(this, 'checkSome', this.toggleCheckedIf);
			this.listenTo(this, 'change:checked', this.checkChecked);
			this.listenTo(this, 'sortByColumn', this.sortByColumn);

			this.totalChecked = this.checked().length;

			if (options && options.reinit) {
				this.reinit(models);
			}
		},

		reinit: function (models) {
			this.totalChecked = this.checked(models).length;
			this.syncEnabled = this.totalChecked > 0;
			this.allChecked = this.totalChecked >= models.length;
			this.sortKey = sortKey;
			this.sortDirection = sortDirection;
			this.sort();
		},

		checkChecked: function (model) {
			if (model.changed.checked) {
				this.totalChecked++;
			} else {
				if (this.totalChecked === this.length) {
					this.allChecked = false;
				}
				this.totalChecked--;
			}

			this.checkAllStatus();
		},

		checkAllStatus: function (checked) {
			var syncWasEnabled = this.syncEnabled;
			this.syncEnabled = this.totalChecked > 0;

			if (syncWasEnabled !== this.syncEnabled) {
				this.trigger('enabledChange', this.syncEnabled);
			}

			if (this.totalChecked < this.length) {
				this.trigger('notAllChecked', false);
			}
		},

		toggleCheckedIf: function (checked) {
			this.processing = true;

			this.stopListening(this, 'change:checked', this.checkChecked);
			this.each(function (model) {
				model.set('checked', Boolean('function' === typeof checked ? checked(model) : checked));
			});
			this.listenTo(this, 'change:checked', this.checkChecked);

			this.totalChecked = this.checked().length;
			this.allChecked = this.totalChecked >= this.length;
			this.checkAllStatus();

			this.processing = false;

			this.trigger('render');
		},

		toggleChecked: function (checked) {
			this.allChecked = checked;
			this.toggleCheckedIf(checked);
		},

		checked: function (models) {
			models = models || this;
			return models.filter(function (model) {
				return model.get('checked');
			});
		},

		comparator: function (a, b) {
			if (!this.sortKey) {
				return;
			}

			var dataA = a.get(this.sortKey);
			var dataB = b.get(this.sortKey);

			if ('updated_at' === this.sortKey) {
				dataA = dataA.date || dataA;
				dataB = dataB.date || dataB;
			}

			if ('status' === this.sortKey) {
				dataA = dataA.name || dataA;
				dataB = dataB.name || dataB;
			}

			if ('asc' === this.sortDirection) {
				if (dataA > dataB) {
					return -1;
				}
				if (dataB > dataA) {
					return 1;
				}
				return 0;

			} else {

				if (dataA < dataB) {
					return -1;
				}
				if (dataB < dataA) {
					return 1;
				}

				return 0;
			}
		},

		sortByColumn: function (column, direction) {
			this.sortKey = sortKey = column;
			this.sortDirection = sortDirection = direction;
			this.sort();
		}

	});

	return require('./../collections/search-extension.js')(Collection);

};
