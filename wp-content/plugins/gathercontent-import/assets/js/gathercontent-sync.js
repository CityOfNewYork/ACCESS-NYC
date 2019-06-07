/**
 * GatherContent Plugin - v3.1.13 - 2019-05-22
 * http://www.gathercontent.com
 *
 * Copyright (c) 2019 GatherContent
 * Licensed under the GPLv2 license.
 */

(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

module.exports = Backbone.Collection.extend({
	getById: function getById(id) {
		return this.find(function (model) {
			var modelId = model.get('id');
			return modelId === id || modelId && id && modelId == id;
		});
	}
});

},{}],2:[function(require,module,exports){
'use strict';

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

		initialize: function initialize(models, options) {
			this.listenTo(this, 'checkAll', this.toggleChecked);
			this.listenTo(this, 'checkSome', this.toggleCheckedIf);
			this.listenTo(this, 'change:checked', this.checkChecked);
			this.listenTo(this, 'sortByColumn', this.sortByColumn);

			this.totalChecked = this.checked().length;

			if (options && options.reinit) {
				this.reinit(models);
			}
		},

		reinit: function reinit(models) {
			this.totalChecked = this.checked(models).length;
			this.syncEnabled = this.totalChecked > 0;
			this.allChecked = this.totalChecked >= models.length;
			this.sortKey = sortKey;
			this.sortDirection = sortDirection;
			this.sort();
		},

		checkChecked: function checkChecked(model) {
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

		checkAllStatus: function checkAllStatus(checked) {
			var syncWasEnabled = this.syncEnabled;
			this.syncEnabled = this.totalChecked > 0;

			if (syncWasEnabled !== this.syncEnabled) {
				this.trigger('enabledChange', this.syncEnabled);
			}

			if (this.totalChecked < this.length) {
				this.trigger('notAllChecked', false);
			}
		},

		toggleCheckedIf: function toggleCheckedIf(checked) {
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

		toggleChecked: function toggleChecked(checked) {
			this.allChecked = checked;
			this.toggleCheckedIf(checked);
		},

		checked: function checked(models) {
			models = models || this;
			return models.filter(function (model) {
				return model.get('checked');
			});
		},

		comparator: function comparator(a, b) {
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

		sortByColumn: function sortByColumn(column, direction) {
			this.sortKey = sortKey = column;
			this.sortDirection = sortDirection = direction;
			this.sort();
		}

	});

	return require('./../collections/search-extension.js')(Collection);
};

},{"./../collections/search-extension.js":3}],3:[function(require,module,exports){
'use strict';

module.exports = function (Collection) {

	_.extend(Collection.prototype, {

		//_Cache
		_searchResults: null,

		//@ Search wrapper function
		search: function search(keyword, attributes) {
			var results = this._doSearch(keyword, attributes);

			this.trigger('search', results);

			// For use of returning un-async
			return results;
		},

		//@ Search function
		_doSearch: function _doSearch(keyword, attributes) {
			attributes = attributes && attributes.length ? attributes : false;

			// If collection empty get out
			if (!this.models.length) {
				return [];
			}

			// Filter
			var matcher = this.matcher;
			var results = !keyword ? this.models : this.filter(function (model) {
				attributes = attributes ? attributes : model.searchAttributes || _.keys(model.attributes);
				return _.some(attributes, function (attribute) {
					return matcher(keyword, model.get(attribute));
				});
			});

			this.trigger('searchResults', results);

			// Instantiate new Collection
			var collection = new Collection(results, { reinit: true });

			collection.searching = {
				keyword: keyword,
				attributes: attributes
			};

			collection.getSearchQuery = function () {
				return this.searching;
			};

			// Cache the recently searched metadata
			this._searchResults = collection;

			this.trigger('search', collection);

			// For use of returning un-async
			return collection;
		},

		//@ Default Matcher - may be overwritten
		matcher: function matcher(needle, haystack) {
			if (!needle || !haystack) {
				return;
			}
			needle = needle.toString().toLowerCase();
			haystack = haystack.toString().toLowerCase();
			return haystack.indexOf(needle) >= 0;
		},

		//@ Get recent search value
		getSearchValue: function getSearchValue() {
			return this.getSearchQuery().keyword;
		},

		//@ Get recent search query
		getSearchQuery: function getSearchQuery() {
			return this._searchResults && this._searchResults.getSearchQuery() || {};
		},

		//@ Get recent search results
		getSearchResults: function getSearchResults() {
			return this._searchResults;
		},

		current: function current() {
			return this._searchResults || this;
		}

	});

	return Collection;
};

},{}],4:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	app.models = { base: require('./models/base.js') };
	app.collections = { base: require('./collections/base.js') };
	app.views = { base: require('./views/base.js') };
};

},{"./collections/base.js":1,"./models/base.js":6,"./views/base.js":10}],5:[function(require,module,exports){
'use strict';

module.exports = function (app, defaults) {
	defaults = jQuery.extend({}, {
		action: 'gc_sync_items',
		data: '',
		percent: 0,
		nonce: '',
		id: '',
		stopSync: true,
		flush_cache: false
	}, defaults);

	return app.models.base.extend({
		defaults: defaults,

		initialize: function initialize() {
			this.listenTo(this, 'send', this.send);
		},

		reset: function reset() {
			this.clear().set(this.defaults);
			return this;
		},

		send: function send(formData, cb, percent, failcb) {
			if (percent) {
				this.set('percent', percent);
			}

			jQuery.post(window.ajaxurl, {
				action: this.get('action'),
				percent: this.get('percent'),
				nonce: this.get('nonce'),
				id: this.get('id'),
				data: formData,
				flush_cache: this.get('flush_cache')
			}, (function (response) {
				this.trigger('response', response, formData);

				if (response.success) {
					return cb(response);
				}

				if (failcb) {
					return failcb(response);
				}
			}).bind(this));

			return this;
		}

	});
};

},{}],6:[function(require,module,exports){
"use strict";

module.exports = Backbone.Model.extend({
	sync: function sync() {
		return false;
	}
});

},{}],7:[function(require,module,exports){
'use strict';

module.exports = function (app, gc) {
	return require('./../models/modify-json.js')(app.models.base.extend({
		defaults: {
			id: 0,
			item: 0,
			itemName: 0,
			project_id: 0,
			parent_id: 0,
			template_id: 0,
			custom_state_id: 0,
			position: 0,
			name: '',
			config: '',
			notes: '',
			type: '',
			typeName: '',
			overdue: false,
			archived_by: '',
			archived_at: '',
			created_at: null,
			updated_at: null,
			status: null,
			due_dates: null,
			expanded: false,
			checked: false,
			post_title: false,
			ptLabel: false
		},

		searchAttributes: ['itemName', 'post_title'],

		_get_item: function _get_item(value) {
			return this.get('id');
		},

		_get_typeName: function _get_typeName(value) {
			if (!value) {
				value = Backbone.Model.prototype.get.call(this, 'type');
			}
			return gc._type_names[value] ? gc._type_names[value] : value;
		}
	}));
};

},{"./../models/modify-json.js":8}],8:[function(require,module,exports){
'use strict';

module.exports = function (model) {

	model.prototype._get = function (value, attribute) {
		if (this['_get_' + attribute]) {
			value = this['_get_' + attribute](value);
		}
		return value;
	};

	model.prototype.get = function (attribute) {
		return this._get(Backbone.Model.prototype.get.call(this, attribute), attribute);
	};

	// hijack the toJSON method and overwrite the data that is sent back to the view.
	model.prototype.toJSON = function () {
		return _.mapObject(Backbone.Model.prototype.toJSON.call(this), _.bind(this._get, this));
	};

	return model;
};

},{}],9:[function(require,module,exports){
'use strict';

window.GatherContent = window.GatherContent || {};

(function (window, document, $, gc, undefined) {
	'use strict';

	gc.sync = gc.sync || {};
	var app = gc.sync;

	// Initiate base objects.
	require('./initiate-objects.js')(app);

	app.views.tableBase = require('./views/table-base.js')(app, $, gc);

	/*
  * Item setup
  */

	app.models.item = require('./models/item.js')(app, gc);
	app.collections.items = require('./collections/items.js')(app);
	app.views.item = require('./views/item.js')(app);
	app.views.items = require('./views/items.js')(app, $, gc);

	app.init = function () {
		// Kick it off.
		app.syncView = new app.views.items({
			el: $('.gc-admin-wrap'),
			collection: new app.collections.items(gc._items)
		});

		// Handle error notice dismissals.
		$(document.body).on('click', '#setting-error-gc-import-last-error .notice-dismiss, #setting-error-gc-import-errors .notice-dismiss', function () {
			var lastError = $(this).parents('#setting-error-gc-import-last-error').length > 0;
			$.post(window.ajaxurl, {
				action: 'gc_dismiss_notice',
				lastError: lastError ? 1 : 0,
				mapping: gc.queryargs.mapping
			}, function (response) {
				gc.log('response', response);
			});
		}).on('click', '.gc-notice-dismiss', function () {
			$(this).parents('.notice.is-dismissible').find('.notice-dismiss').trigger('click');
		});
	};

	$(app.init);
})(window, document, jQuery, window.GatherContent);

},{"./collections/items.js":2,"./initiate-objects.js":4,"./models/item.js":7,"./views/item.js":11,"./views/items.js":12,"./views/table-base.js":13}],10:[function(require,module,exports){
'use strict';

module.exports = Backbone.View.extend({
	toggleExpanded: function toggleExpanded(evt) {
		this.model.set('expanded', !this.model.get('expanded'));
	},

	getRenderedModels: function getRenderedModels(View, models) {
		models = models || this.collection;
		var addedElements = document.createDocumentFragment();

		models.each(function (model) {
			var view = new View({ model: model }).render();
			addedElements.appendChild(view.el);
		});

		return addedElements;
	},

	render: function render() {
		this.$el.html(this.template(this.model.toJSON()));
		return this;
	},

	close: function close() {
		this.remove();
		this.unbind();
		if (this.onClose) {
			this.onClose();
		}
	}
});

},{}],11:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return app.views.base.extend({
		template: wp.template('gc-item'),
		tagName: 'tr',
		className: 'gc-item gc-enabled',
		id: function id() {
			return this.model.get('id');
		},

		events: {
			'change .gc-check-column input': 'toggleCheck',
			'click .gc-reveal-items': 'toggleExpanded',
			'click .gc-status-column': 'toggleCheckAndRender'
		},

		initialize: function initialize() {
			this.listenTo(this.model, 'change:checked', this.render);
		},

		toggleCheck: function toggleCheck() {
			this.model.set('checked', !this.model.get('checked'));
		},

		toggleCheckAndRender: function toggleCheckAndRender(evt) {
			this.toggleCheck();
			this.render();
		}
	});
};

},{}],12:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	var thisView;
	var percent = gc.percent;
	app.views.tableSearch = require('./../views/table-search.js')(app, $, gc);
	app.views.tableNav = require('./../views/table-nav.js')(app, $, gc);

	return app.views.tableBase.extend({
		template: wp.template('gc-items-sync'),
		progressTemplate: wp.template('gc-items-sync-progress'),
		modelView: app.views.item,

		events: {
			'click .gc-cancel-sync': 'clickCancelSync',
			'click .gc-field-th.sortable': 'sortRowsByColumn',
			'change .gc-field-th.gc-check-column input': 'checkAll',
			'submit form': 'submit'
		},

		initialize: function initialize() {
			thisView = this;
			app.views.tableBase.prototype.initialize.call(this);

			this.listenTo(this.ajax, 'response', this.ajaxResponse);
			this.listenTo(this.collection, 'enabledChange', this.checkEnableButton);
			this.listenTo(this.collection, 'search', this.initRender);

			this.initRender();
		},

		setupAjax: function setupAjax() {
			var Ajax = require('./../models/ajax.js')(app, {
				checkHits: 0,
				time: 500,
				nonce: gc.el('_wpnonce').value,
				id: gc.el('gc-input-mapping_id').value,
				flush_cache: gc.queryargs.flush_cache ? 1 : 0
			});

			this.ajax = new Ajax({
				percent: percent
			});
		},

		checkEnableButton: function checkEnableButton(syncEnabled) {
			this.buttonStatus(syncEnabled);
		},

		clickCancelSync: function clickCancelSync(evt) {
			evt.preventDefault();
			this.cancelSync();
		},

		submit: function submit(evt) {
			evt.preventDefault();
			this.startSync(this.$('form').serialize());
		},

		startSync: function startSync(formData) {
			this.doSpinner();
			this.ajax.reset().set('stopSync', false);
			this.renderProgress(100 === window.parseInt(percent, 10) ? 0 : percent);
			this.doAjax(formData, percent);
		},

		cancelSync: function cancelSync(url) {
			percent = null;

			this.ajax.reset();
			this.clearTimeout();

			if (url) {
				this.doAjax('cancel', 0, function () {
					window.location.href = url;
				});
			} else {
				this.doAjax('cancel', 0, function () {});
				this.initRender();
			}
		},

		doAjax: function doAjax(formData, completed, cb) {
			cb = cb || this.ajaxSuccess.bind(this);
			this.ajax.send(formData, cb, completed);
		},

		ajaxSuccess: function ajaxSuccess(response) {
			if (this.ajax.get('stopSync')) {
				return;
			}

			percent = response.data.percent || 1;
			var hits = this.checkHits();
			var time = this.ajax.get('time');

			if (hits > 25 && time < 2000) {
				this.clearTimeout();
				this.ajax.set('time', 2000);
			} else if (hits > 50 && time < 5000) {
				this.clearTimeout();
				this.ajax.set('time', 5000);
			}

			this.setTimeout(this.checkProgress.bind(this));

			if (percent > 99) {
				// This is to allow the slight css animation.
				this.renderProgressUpdate(100);

				// This is to render the loading spinner. Wait long enough for css animation to copmlete.
				window.setTimeout(function () {
					thisView.renderProgress(100, true);
				}, 100);

				// Finally, cancel the sync, and redirect.
				this.cancelSync(window.location.href + '&updated=1&flush_cache=1&redirect=1');
			} else {
				this.renderProgressUpdate(percent);
			}
		},

		setTimeout: function setTimeout(callback) {
			this.timeoutID = window.setTimeout(callback, this.ajax.get('time'));
		},

		checkProgress: function checkProgress() {
			this.doAjax('check', percent);
		},

		checkHits: function checkHits() {
			return window.parseInt(this.ajax.get('checkHits'), 10);
		},

		ajaxResponse: function ajaxResponse(response, formData) {
			gc.log('warn', 'hits/interval/response: ' + this.checkHits() + '/' + this.ajax.get('time') + '/', response.success ? response.data : response);

			if ('check' === formData) {
				this.ajax.set('checkHits', this.checkHits() + 1);
			} else if (response.data) {
				this.ajax.set('checkHits', 0);
			}

			if (!response.success) {
				this.renderProgressUpdate(0);
				this.cancelSync();
				if (response.data) {
					if (response.data.url) {
						window.alert(response.data.message);
						window.location.href = response.data.url;
					} else {
						window.alert(response.data);
					}
					return;
				}
			}
		},

		renderProgressUpdate: function renderProgressUpdate(percent) {
			this.$('.gc-progress-bar-partial').css({ width: percent + '%' }).find('span').text(percent + '%');
		},

		renderProgress: function renderProgress(percent, showLoader) {
			this.$el.addClass('gc-sync-progress');
			this.buttonStatus(false);
			this.$('#sync-tabs').html(this.progressTemplate({
				percent: null === percent ? 0 : percent,
				loader: true === showLoader
			}));
		},

		renderRows: function renderRows(html) {
			this.$('#sync-tabs tbody').html(html || this.getRenderedRows());
		},

		sortRender: function sortRender() {
			this.initRender();
		},

		initRender: function initRender() {
			var collection = this.collection.current();
			// If sync is going, show that status.
			if (percent > 0 && percent < 100) {
				this.startSync('check');
			} else {
				this.$('#sync-tabs').html(this.template({
					checked: collection.allChecked,
					sortKey: collection.sortKey,
					sortDirection: collection.sortDirection
				}));
				this.render();
			}
		},

		render: function render() {
			// Not syncing, so remove wrap-class
			this.$el.removeClass('gc-sync-progress');

			return app.views.tableBase.prototype.render.call(this);
		}

	});
};

},{"./../models/ajax.js":5,"./../views/table-nav.js":14,"./../views/table-search.js":15}],13:[function(require,module,exports){
'use strict';

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

		initialize: function initialize() {
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
		setupAjax: function setupAjax() {},

		sortRowsByColumn: function sortRowsByColumn(evt) {
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

		buttonStatus: function buttonStatus(enable) {
			this.$('.button-primary').prop('disabled', !enable);
		},

		allCheckedStatus: function allCheckedStatus() {
			this.$('.gc-field-th.gc-check-column input').prop('checked', this.collection.allChecked);
		},

		checkAll: function checkAll(evt) {
			this.collection.trigger('checkAll', $(evt.target).is(':checked'));
		},

		doSpinner: function doSpinner() {
			var html = this.blankRow('<span class="gc-loader spinner is-active"></span>');
			this.renderRows(html);
		},

		setTimeout: function setTimeout(callback) {
			this.timeoutID = window.setTimeout(callback, this.timeoutTime);
		},

		clearTimeout: function clearTimeout() {
			window.clearTimeout(this.timeoutID);
			this.timeoutID = null;
		},

		getRenderedRows: function getRenderedRows() {
			var rows;

			if (this.collection.current().length) {
				rows = this.getRenderedModels(this.modelView, this.collection.current());
			} else {
				rows = this.blankRow(gc._text.no_items);
			}

			return rows;
		},

		sortRender: function sortRender() {
			this.render();
		},

		blankRow: function blankRow(html) {
			var cols = this.$('thead tr > *').length;
			return '<tr><td colspan="' + cols + '">' + html + '</td></tr>';
		},

		renderRows: function renderRows(html) {
			this.$('tbody').html(html || this.getRenderedRows());
		},

		renderNav: function renderNav() {
			this.$('#gc-tablenav').html(this.tableNavView.render().el);
		},

		render: function render() {
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

},{"./../views/table-nav.js":14,"./../views/table-search.js":15}],14:[function(require,module,exports){
'use strict';

module.exports = function (app, $, gc) {
	return app.views.base.extend({
		template: wp.template('gc-table-nav'),

		render: function render() {
			var collection = this.collection.current();

			this.$el.html(this.template({
				count: collection.length,
				selected: collection.checked ? collection.checked().length : 0
			}));

			return this;
		}
	});
};

},{}],15:[function(require,module,exports){
'use strict';

module.exports = function (app) {
	return Backbone.View.extend({
		el: '#gc-items-search',
		template: wp.template('gc-table-search'),
		events: {
			'keyup #gc-search-input': 'filterCollection',
			'search #gc-search-input': 'filterCollection'
		},

		initialize: function initialize() {
			this.render();
		},

		filterCollection: _.debounce(function (evt) {
			this.collection.search(evt.target.value);
		}, 100),

		render: function render() {
			this.$el.html(this.template());
			return this;
		}

	});
};

},{}]},{},[9]);
